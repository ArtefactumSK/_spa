<?php
/**
 * SPA Pricing Migration - Konverzia starých cien na sezónne
 * 
 * @package Samuel Piasecký ACADEMY
 * @subpackage Pricing
 * @version 1.0.0
 * 
 * ÚČEL:
 * Automaticky konvertovať staré meta polia:
 * - spa_price_1x_weekly → spa_pricing_seasons[oct_dec][1x]
 * - spa_price_2x_weekly → spa_pricing_seasons[oct_dec][2x]
 * - spa_price_periods → spa_pricing_seasons[jan_mar][...] atď.
 * 
 * Vykonané len raz pri prvom načítaní
 */

if (!defined('ABSPATH')) {
    exit;
}

/* ==================================================
   ADMIN INIT: Spusti migráciu
   ================================================== */

add_action('admin_init', 'spa_pricing_migration_check');

function spa_pricing_migration_check() {
    // Flag na kontrolu či bola migrácia vykonaná
    $migration_done = get_option('spa_pricing_migration_v1_done', false);
    
    if ($migration_done) {
        return; // Migrácia už prebehla
    }
    
    // Vykonaj migráciu
    spa_migrate_pricing_to_seasons();
    
    // Označ ako hotovo
    update_option('spa_pricing_migration_v1_done', true);
}

/* ==================================================
   MIGRÁCIA: Konverzia starých cien
   ================================================== */

function spa_migrate_pricing_to_seasons() {
    // Zisti všetky programy
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
        return; // Už má sezónne ceny, nemigruj
    }
    
    $pricing_seasons = [
        'oct_dec' => ['1x' => 0, '2x' => 0, '3x' => 0],
        'jan_mar' => ['1x' => 0, '2x' => 0, '3x' => 0],
        'apr_jun' => ['1x' => 0, '2x' => 0, '3x' => 0],
        'jul_sep' => ['1x' => 0, '2x' => 0, '3x' => 0]
    ];
    
    // 1. STARÉ POLIA: 1x_weekly, 2x_weekly → october-december
    $price_1x = floatval(get_post_meta($program_id, 'spa_price_1x_weekly', true) ?? 0);
    $price_2x = floatval(get_post_meta($program_id, 'spa_price_2x_weekly', true) ?? 0);
    
    if ($price_1x > 0 || $price_2x > 0) {
        $pricing_seasons['oct_dec']['1x'] = $price_1x;
        $pricing_seasons['oct_dec']['2x'] = $price_2x;
        $pricing_seasons['oct_dec']['3x'] = $price_2x + ($price_2x - $price_1x); // Lineárny odhad
    }
    
    // 2. STARÉ POLIA: spa_price_periods → mapuj na sezóny
    $periods_json = get_post_meta($program_id, 'spa_price_periods', true);
    
    if ($periods_json) {
        $periods = is_string($periods_json) ? json_decode($periods_json, true) : $periods_json;
        
        if (is_array($periods)) {
            foreach ($periods as $period) {
                $name = strtolower($period['name'] ?? '');
                $price = floatval($period['price'] ?? 0);
                
                // Rozpoznaj sezónu z názvu
                if (strpos($name, 'október') !== false || strpos($name, 'oktober') !== false || strpos($name, 'december') !== false) {
                    $pricing_seasons['oct_dec']['1x'] = $price;
                    $pricing_seasons['oct_dec']['2x'] = $price * 1.3; // Odhad 2x
                } elseif (strpos($name, 'január') !== false || strpos($name, 'januar') !== false || strpos($name, 'január') !== false || strpos($name, 'marec') !== false) {
                    $pricing_seasons['jan_mar']['1x'] = $price;
                    $pricing_seasons['jan_mar']['2x'] = $price * 1.3;
                } elseif (strpos($name, 'apríl') !== false || strpos($name, 'april') !== false || strpos($name, 'jún') !== false || strpos($name, 'jun') !== false) {
                    $pricing_seasons['apr_jun']['1x'] = $price;
                    $pricing_seasons['apr_jun']['2x'] = $price * 1.3;
                } elseif (strpos($name, 'júl') !== false || strpos($name, 'jul') !== false || strpos($name, 'september') !== false) {
                    $pricing_seasons['jul_sep']['1x'] = $price;
                    $pricing_seasons['jul_sep']['2x'] = $price * 1.3;
                }
            }
        }
    }
    
    // Uložiť migrované sezónne ceny
    if (!empty($pricing_seasons)) {
        update_post_meta($program_id, 'spa_pricing_seasons', $pricing_seasons);
    }
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
            delete_option('spa_pricing_migration_v1_done');
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
                <li><code>spa_price_1x_weekly</code> → sezónne ceny</li>
                <li><code>spa_price_2x_weekly</code> → sezónne ceny</li>
                <li><code>spa_price_periods</code> → mapovanie na sezóny</li>
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
            $migration_done = get_option('spa_pricing_migration_v1_done', false);
            $programs = get_posts(['post_type' => 'spa_group', 'posts_per_page' => -1, 'fields' => 'ids']);
            $migrated = 0;
            
            foreach ($programs as $pid) {
                $seasons = get_post_meta($pid, 'spa_pricing_seasons', true);
                if (is_array($seasons) && !empty($seasons)) {
                    $migrated++;
                }
            }
            
            echo '<p>';
            if ($migration_done) {
                echo '✅ <strong>Migrácia bola vykonaná</strong><br>';
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