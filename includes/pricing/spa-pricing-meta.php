<?php
/**
 * SPA Pricing Meta - Cenovací systém pre programy
 * 
 * @package Samuel Piasecký ACADEMY
 * @subpackage Pricing
 * @version 1.0.0
 * 
 * FEATURES:
 * - Sezónne ceny (oktober-december, január-marec, apríl-jún, júl-september)
 * - Ceny podľa frekvencie (1x/tyzdenne, 2x/tyzdenne, 3x/tyzdenne)
 * - Príplatok za extern.priestor
 * - Prepočet ceny pri udalostiach
 * 
 * DATABASE:
 * - wp_postmeta (post_type = spa_group)
 *   - spa_pricing_config (JSON)
 * 
 * HOOKS:
 * - add_meta_boxes (registrácia meta boxu)
 * - save_post_spa_group (uloženie)
 */

if (!defined('ABSPATH')) {
    exit;
}

/* ==================================================
   META BOX: Registrácia a renderovanie
   ================================================== */

add_action('add_meta_boxes', 'spa_pricing_add_meta_box');

function spa_pricing_add_meta_box() {
    add_meta_box(
        'spa_pricing_config',
        '💳 Cenovací systém',
        'spa_pricing_meta_box_render',
        'spa_group',
        'normal',
        'high'
    );
}

/* ==================================================
   RENDER: Meta box UI
   ================================================== */

function spa_pricing_meta_box_render($post) {
    wp_nonce_field('spa_save_pricing', 'spa_pricing_nonce');
    
    // Načítaj aktuálnu konfiguráciu
    $pricing_config = get_post_meta($post->ID, 'spa_pricing_config', true);
    if (!is_array($pricing_config)) {
        $pricing_config = spa_get_default_pricing_config();
    }
    
    $seasons = $pricing_config['seasons'] ?? [];
    $frequencies = $pricing_config['frequencies'] ?? [];
    $external_surcharge = $pricing_config['external_surcharge'] ?? 0;
    
    ?>
    <style>
        .spa-pricing-container { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif; }
        .spa-pricing-section { background: #f9f9f9; padding: 20px; border: 1px solid #ddd; border-radius: 4px; margin-bottom: 20px; }
        .spa-pricing-section h3 { margin: 0 0 15px 0; font-size: 14px; font-weight: 600; }
        .spa-pricing-table { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
        .spa-pricing-table th { background: #f0f0f0; padding: 10px; text-align: left; font-weight: 600; border: 1px solid #ddd; font-size: 13px; }
        .spa-pricing-table td { padding: 10px; border: 1px solid #ddd; }
        .spa-pricing-table input { width: 100%; max-width: 120px; padding: 6px; border: 1px solid #ddd; border-radius: 3px; }
        .spa-price-row { display: flex; gap: 15px; margin-bottom: 10px; align-items: center; }
        .spa-price-field { flex: 1; min-width: 200px; }
        .spa-price-field label { display: block; font-weight: 600; margin-bottom: 5px; font-size: 13px; }
        .spa-price-field input { width: 100%; max-width: 200px; }
        .spa-help-text { color: #666; font-size: 12px; margin-top: 5px; }
        .spa-info-box { background: #eff6ff; border-left: 4px solid #0072CE; padding: 12px; margin-bottom: 15px; border-radius: 4px; }
        .spa-info-box p { margin: 0; font-size: 13px; }
    </style>
    
    <div class="spa-pricing-container">
        
        <div class="spa-info-box">
            <p><strong>💡 Ako to funguje:</strong> Vyberte cenu pre každú kombináciu sezóny a frekvencie. Systém automaticky prepočíta cenu podľa počtu reálnych tréningov.</p>
        </div>
        
        <!-- SEZÓNY -->
        <div class="spa-pricing-section">
            <h3>📅 Sezónne ceny (€/týždeň)</h3>
            <table class="spa-pricing-table">
                <thead>
                    <tr>
                        <th>Sezóna</th>
                        <th>Mesiacový rozsah</th>
                        <th>1x týždenne</th>
                        <th>2x týždenne</th>
                        <th>3x týždenne</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $season_labels = [
                        'oct_dec' => '🍂 Október - December',
                        'jan_mar' => '❄️ Január - Marec',
                        'apr_jun' => '🌱 Apríl - Jún',
                        'jul_sep' => '☀️ Júl - September'
                    ];
                    
                    foreach ($season_labels as $season_key => $season_label) :
                        $season_data = $seasons[$season_key] ?? [];
                        ?>
                        <tr>
                            <td><strong><?php echo $season_label; ?></strong></td>
                            <td style="font-size: 12px; color: #666;">
                                <?php echo spa_get_season_months($season_key); ?>
                            </td>
                            <td>
                                <input type="number" 
                                    name="spa_pricing[seasons][<?php echo $season_key; ?>][freq_1]" 
                                    value="<?php echo esc_attr($season_data['freq_1'] ?? ''); ?>" 
                                    step="0.01" min="0" placeholder="napr. 32.00">
                            </td>
                            <td>
                                <input type="number" 
                                    name="spa_pricing[seasons][<?php echo $season_key; ?>][freq_2]" 
                                    value="<?php echo esc_attr($season_data['freq_2'] ?? ''); ?>" 
                                    step="0.01" min="0" placeholder="napr. 50.00">
                            </td>
                            <td>
                                <input type="number" 
                                    name="spa_pricing[seasons][<?php echo $season_key; ?>][freq_3]" 
                                    value="<?php echo esc_attr($season_data['freq_3'] ?? ''); ?>" 
                                    step="0.01" min="0" placeholder="napr. 66.00">
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <p class="spa-help-text">💡 Príklad: Ak je 1x/týždeň = 32€, ale v mesiacoch sú iba 3 tréningy namiesto 4, cena sa prepočíta na 24€.</p>
        </div>
        
        <!-- PRÍPLATOK ZA EXTERNÝ PRIESTOR -->
        <div class="spa-pricing-section">
            <h3>🏢 Príplatok za externý priestor</h3>
            <div class="spa-price-field">
                <label for="spa_external_surcharge">Príplatok (€/týždeň):</label>
                <input type="number" 
                    id="spa_external_surcharge"
                    name="spa_pricing[external_surcharge]" 
                    value="<?php echo esc_attr($external_surcharge); ?>" 
                    step="0.01" min="0" placeholder="napr. 5.00">
                <p class="spa-help-text">Príplatok sa pripočíta, ak je program v externom priestore (nad cenu sezóny)</p>
            </div>
        </div>
        
    </div>
    <?php
}

/* ==================================================
   SAVE: Uloženie cien
   ================================================== */

add_action('save_post_spa_group', 'spa_pricing_save_meta', 10, 2);

function spa_pricing_save_meta($post_id, $post) {
    // Bezpečnostná kontrola
    if (!isset($_POST['spa_pricing_nonce']) || !wp_verify_nonce($_POST['spa_pricing_nonce'], 'spa_save_pricing')) {
        return;
    }
    
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    
    if (!current_user_can('edit_post', $post_id)) {
        return;
    }
    
    // Spracovanie cien
    if (isset($_POST['spa_pricing'])) {
        $pricing_data = spa_sanitize_pricing($_POST['spa_pricing']);
        update_post_meta($post_id, 'spa_pricing_config', $pricing_data);
    }
}

/* ==================================================
   HELPER: Sanitácia cien
   ================================================== */

function spa_sanitize_pricing($data) {
    $seasons = [];
    
    if (isset($data['seasons']) && is_array($data['seasons'])) {
        foreach ($data['seasons'] as $season_key => $season_data) {
            $seasons[$season_key] = [
                'freq_1' => floatval($season_data['freq_1'] ?? 0),
                'freq_2' => floatval($season_data['freq_2'] ?? 0),
                'freq_3' => floatval($season_data['freq_3'] ?? 0),
            ];
        }
    }
    
    return [
        'seasons' => $seasons,
        'external_surcharge' => floatval($data['external_surcharge'] ?? 0),
        'updated' => current_time('mysql')
    ];
}

/* ==================================================
   HELPER: Výchozí konfigurácia
   ================================================== */

function spa_get_default_pricing_config() {
    return [
        'seasons' => [
            'oct_dec' => ['freq_1' => 0, 'freq_2' => 0, 'freq_3' => 0],
            'jan_mar' => ['freq_1' => 0, 'freq_2' => 0, 'freq_3' => 0],
            'apr_jun' => ['freq_1' => 0, 'freq_2' => 0, 'freq_3' => 0],
            'jul_sep' => ['freq_1' => 0, 'freq_2' => 0, 'freq_3' => 0],
        ],
        'external_surcharge' => 0,
        'updated' => current_time('mysql')
    ];
}

/* ==================================================
   HELPER: Mesiace podľa sezóny
   ================================================== */

function spa_get_season_months($season_key) {
    $months = [
        'oct_dec' => '10-12',
        'jan_mar' => '01-03',
        'apr_jun' => '04-06',
        'jul_sep' => '07-09'
    ];
    return $months[$season_key] ?? '';
}

/* ==================================================
   HELPER: Sezóna podľa dátumu
   ================================================== */

function spa_get_season_for_date($date_string) {
    $month = intval(date('m', strtotime($date_string)));
    
    if ($month >= 10 || $month <= 12) return 'oct_dec';
    if ($month >= 1 && $month <= 3) return 'jan_mar';
    if ($month >= 4 && $month <= 6) return 'apr_jun';
    if ($month >= 7 && $month <= 9) return 'jul_sep';
}

/* ==================================================
   PUBLIC API: Získaj cenu programu
   ================================================== */

function spa_get_program_price($program_id, $start_date, $frequency = 1, $is_external = false) {
    $program = get_post($program_id);
    if (!$program || $program->post_type !== 'spa_group') {
        return 0;
    }
    
    $pricing_config = get_post_meta($program_id, 'spa_pricing_config', true);
    if (!is_array($pricing_config)) {
        return 0;
    }
    
    // Zisti sezónu
    $season = spa_get_season_for_date($start_date);
    $season_prices = $pricing_config['seasons'][$season] ?? [];
    
    // Vyber cenu podľa frekvencie
    $freq_key = 'freq_' . $frequency;
    $price = floatval($season_prices[$freq_key] ?? 0);
    
    // Príplatok za externý priestor
    if ($is_external) {
        $price += floatval($pricing_config['external_surcharge'] ?? 0);
    }
    
    return $price;
}