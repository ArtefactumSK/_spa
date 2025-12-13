<?php
/**
 * SPA Pricing Migration - Konverzia starých cien na sezónne (OPRAVENÁ)
 * 
 * @package Samuel Piasecký ACADEMY
 * @subpackage Pricing
 * @version 2.0.0 - OPRAVA: Správne mapovanie cien podľa periods
 */

if (!defined('ABSPATH')) {
    exit;
}

/* ==================================================
   ADMIN INIT: Spusti migráciu (raz)
   ================================================== */

add_action('admin_init', 'spa_pricing_migration_check');

function spa_pricing_migration_check() {
    $migration_done = get_option('spa_pricing_migration_v2_done', false);
    
    if ($migration_done) {
        return;
    }
    
    spa_migrate_pricing_to_seasons();
    update_option('spa_pricing_migration_v2_done', true);
}

/* ==================================================
   MIGRÁCIA: Konverzia starých cien
   ================================================== */

function spa_migrate_pricing_to_seasons() {
    $programs = get_posts([
        'post_type' => 'spa_group',
        'posts_per_page' => -1,
        'fields' => 'ids'
    ]);
    
    foreach ($programs as $program_id) {
        spa_migrate_single_program_pricing($program_id);
    }
}

/* ==================================================
   HELPER: Migrácia pre jeden program
   ================================================== */

function spa_migrate_single_program_pricing($program_id) {
    // Zisti či už má sezónne ceny
    $existing_seasons = get_post_meta($program_id, 'spa_pricing_seasons', true);
    
    if (is_array($existing_seasons) && !empty($existing_seasons)) {
        // Zisti či sú to iba defaulty (všetky 0)
        $has_data = false;
        foreach ($existing_seasons as $season => $freqs) {
            foreach ($freqs as $freq => $price) {
                if (floatval($price) > 0) {
                    $has_data = true;
                    break 2;
                }
            }
        }
        
        if ($has_data) {
            return; // Už má platné sezónne ceny, nemigruj
        }
    }
    
    $pricing_seasons = [
        'sep_dec' => ['1x' => 0, '2x' => 0, '3x' => 0],
        'jan_mar' => ['1x' => 0, '2x' => 0, '3x' => 0],
        'apr_jun' => ['1x' => 0, '2x' => 0, '3x' => 0],
        'jul_aug' => ['1x' => 0, '2x' => 0, '3x' => 0]
    ];
    
    // ==================================================
    // STRATÉGIA MIGRÁCIE:
    // 1. Ak existuje spa_price_periods → mapuj podľa periode
    // 2. Ak existuje iba 1x_weekly a 2x_weekly → všetky sezóny dostanú rovnaké ceny
    // ==================================================
    
    // 1. STARÉ POLIA: spa_price_periods (má sezóny)
    $periods_json = get_post_meta($program_id, 'spa_price_periods', true);
    
    if ($periods_json) {
        $periods = is_string($periods_json) ? json_decode($periods_json, true) : $periods_json;
        
        if (is_array($periods) && !empty($periods)) {
            // Máme explicitne sezóny → mapuj ich
            foreach ($periods as $period) {
                $name = strtolower($period['name'] ?? '');
                $price = floatval($period['price'] ?? 0);
                
                if ($price <= 0) continue;
                
                // Zisti sezónu z názvu
                if (spa_period_contains_months($name, [9, 10, 11, 12])) {
                    $pricing_seasons['sep_dec']['1x'] = $price;
                    $pricing_seasons['sep_dec']['2x'] = round($price * 1.3, 2);
                    $pricing_seasons['sep_dec']['3x'] = round($price * 1.65, 2);
                } elseif (spa_period_contains_months($name, [1, 2, 3])) {
                    $pricing_seasons['jan_mar']['1x'] = $price;
                    $pricing_seasons['jan_mar']['2x'] = round($price * 1.3, 2);
                    $pricing_seasons['jan_mar']['3x'] = round($price * 1.65, 2);
                } elseif (spa_period_contains_months($name, [4, 5, 6])) {
                    $pricing_seasons['apr_jun']['1x'] = $price;
                    $pricing_seasons['apr_jun']['2x'] = round($price * 1.3, 2);
                    $pricing_seasons['apr_jun']['3x'] = round($price * 1.65, 2);
                } elseif (spa_period_contains_months($name, [7, 8])) {
                    $pricing_seasons['jul_aug']['1x'] = $price;
                    $pricing_seasons['jul_aug']['2x'] = round($price * 1.3, 2);
                    $pricing_seasons['jul_aug']['3x'] = round($price * 1.65, 2);
                }
            }
        }
    } else {
        // 2. STARÉ POLIA: iba 1x_weekly a 2x_weekly (bez sezón) 
        // → všetky sezóny dostanú rovnaké ceny
        $price_1x = floatval(get_post_meta($program_id, 'spa_price_1x_weekly', true) ?? 0);
        $price_2x = floatval(get_post_meta($program_id, 'spa_price_2x_weekly', true) ?? 0);
        
        if ($price_1x > 0 || $price_2x > 0) {
            // Majú staré ceny bez sezón → naplň všetky sezóny rovnakými cenami
            foreach ($pricing_seasons as $season_key => &$freqs) {
                $freqs['1x'] = $price_1x;
                $freqs['2x'] = $price_2x > 0 ? $price_2x : round($price_1x * 1.3, 2);
                $freqs['3x'] = round($price_1x * 1.65, 2);
            }
        }
    }
    
    // Uložiť migrované ceny (len ak sú nenulové)
    $has_prices = false;
    foreach ($pricing_seasons as $season => $freqs) {
        foreach ($freqs as $freq => $price) {
            if ($price > 0) {
                $has_prices = true;
                break 2;
            }
        }
    }
    
    if ($has_prices) {
        update_post_meta($program_id, 'spa_pricing_seasons', $pricing_seasons);
    }
}

/* ==================================================
   HELPER: Zisti či period obsahuje mesiace
   ================================================== */

function spa_period_contains_months($period_name, $months) {
    $period_lower = strtolower($period_name);
    
    // Mesiace v SK a EN
    $month_names = [
        'september' => 9, 'september' => 9,
        'oktober' => 10, 'october' => 10, 'november' => 11, 'december' => 12,
        'januar' => 1, 'january' => 1, 'februar' => 2, 'february' => 2, 'marec' => 3, 'march' => 3,
        'april' => 4, 'apríl' => 4, 'maj' => 5, 'may' => 5, 'jun' => 6, 'june' => 6,
        'júl' => 7, 'july' => 7, 'august' => 8
    ];
    
    foreach ($month_names as $name => $month_num) {
        if (in_array($month_num, $months) && strpos($period_lower, $name) !== false) {
            return true;
        }
    }
    
    return false;
}

/* ==================================================
   ADMIN PAGE: Manuálna migrácia (Debug)
   ================================================== */

add_action('admin_menu', 'spa_pricing_migration_menu');

function spa_pricing_migration_menu() {
    add_submenu_page(
        'edit.php?post_type=spa_group',
        'Migrácia cien',
        '🔄 Migrácia cien',
        'manage_options',
        'spa-pricing-migration',
        'spa_pricing_migration_page'
    );
}

function spa_pricing_migration_page() {
    ?>
    <div class="wrap">
        <h1>🔄 Migrácia cien (staré → sezónne)</h1>
        
        <?php
        if (isset($_POST['spa_run_migration']) && wp_verify_nonce($_POST['_wpnonce'], 'spa_migration_action')) {
            delete_option('spa_pricing_migration_v2_done');
            spa_pricing_migration_check();
            
            echo '<div class="notice notice-success"><p>✅ Migrácia dokončená!</p></div>';
        }
        ?>
        
        <div class="card" style="max-width: 600px; margin-top: 20px;">
            <h2 style="margin-top: 0;">Konverzia starých cien na sezónne</h2>
            
            <p>
                Táto migrácia konvertuje staré meta polia na nový formát:
            </p>
            
            <ul style="list-style: disc; margin-left: 20px; color: #666;">
                <li><code>spa_price_periods</code> → mapovanie na sezóny (smart)</li>
                <li><code>spa_price_1x_weekly</code> → všetky sezóny (fallback)</li>
                <li><code>spa_price_2x_weekly</code> → odhad na ostatné frekvencie</li>
            </ul>
            
            <p style="color: #d63638; font-weight: 600;">
                ⚠️ Migrácia sa spustí automaticky pri prvom načítaní. 
                Kliknite nižšie len ak chcete spustiť ručne znova.
            </p>
            
            <form method="post">
                <?php wp_nonce_field('spa_migration_action'); ?>
                <button type="submit" name="spa_run_migration" class="button button-primary" style="padding: 10px 20px;">
                    🔄 Spustiť migráciu
                </button>
            </form>
        </div>
        
        <div class="card" style="max-width: 600px; margin-top: 20px;">
            <h3>Status</h3>
            
            <?php
            $migration_done = get_option('spa_pricing_migration_v2_done', false);
            $programs = get_posts(['post_type' => 'spa_group', 'posts_per_page' => -1, 'fields' => 'ids']);
            $migrated = 0;
            
            foreach ($programs as $pid) {
                $seasons = get_post_meta($pid, 'spa_pricing_seasons', true);
                if (is_array($seasons)) {
                    foreach ($seasons as $season => $freqs) {
                        foreach ($freqs as $freq => $price) {
                            if (floatval($price) > 0) {
                                $migrated++;
                                break 2;
                            }
                        }
                    }
                }
            }
            
            echo '<p>';
            if ($migration_done) {
                echo '✅ <strong>Migrácia (v2) bola vykonaná</strong><br>';
            } else {
                echo '❌ <strong>Migrácia sa ešte nespustila</strong><br>';
            }
            
            printf(
                'Programy so sezónnymi cenami: <strong>%d / %d</strong>',
                $migrated,
                count($programs)
            );
            
            echo '</p>';
            ?>
        </div>
    </div>
    <?php
}