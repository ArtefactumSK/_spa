<?php
/** spa-meta-boxes.php
 * SPA Meta Boxes - Admin formuláre pre CPT
 * @package Samuel Piasecký ACADEMY
 * @version 3.1.0 - OPRAVA: Pridaný meta box pre programy (spa_group)
 */

if (!defined('ABSPATH')) {
    exit;
}

/* ============================================================
   PRIDANIE VŠETKÝCH META BOXOV
   ============================================================ */
add_action('add_meta_boxes', 'spa_add_all_meta_boxes');
function spa_add_all_meta_boxes() {
    
    // PROGRAMY (spa_group)
    add_meta_box('spa_group_details', '🤸 Detaily programu', 'spa_group_meta_box', 'spa_group', 'normal', 'high');
    add_meta_box('spa_group_schedule', '📅 Rozvrh programu', 'spa_group_schedule_meta_box', 'spa_group', 'normal', 'high');
    add_meta_box('spa_group_pricing', '💳 Cenník programu', 'spa_group_pricing_meta_box', 'spa_group', 'normal', 'high');
    
    // REGISTRÁCIE
    add_meta_box('spa_registration_details', '📋 Detaily registrácie', 'spa_registration_meta_box', 'spa_registration', 'normal', 'high');
    
    // MIESTA (spa_place)
    add_meta_box('spa_place_details', '📍 Detaily miesta', 'spa_place_meta_box', 'spa_place', 'normal', 'high');
    add_meta_box('spa_place_schedule', '📅 Rozvrh miesta', 'spa_place_schedule_meta_box', 'spa_place', 'normal', 'default');
    
    // UDALOSTI (spa_event)
    add_meta_box('spa_event_details', '📅 Detaily udalosti', 'spa_event_meta_box', 'spa_event', 'normal', 'high');
    
    // DOCHÁDZKA (spa_attendance)
    add_meta_box('spa_attendance_details', '✅ Záznam dochádzky', 'spa_attendance_meta_box', 'spa_attendance', 'normal', 'high');
}

/* ============================================================
   META BOX: DETAILY PROGRAMU (spa_group) - NOVÝ
   ============================================================ */
function spa_group_meta_box($post) {
    wp_nonce_field('spa_save_group_details', 'spa_group_nonce');
    
    $place_id = get_post_meta($post->ID, 'spa_place_id', true);
    $trainers = get_post_meta($post->ID, 'spa_trainers', true);
    $trainers = is_array($trainers) ? $trainers : (empty($trainers) ? [] : [$trainers]);
    $capacity = get_post_meta($post->ID, 'spa_capacity', true);
    $registration_type = get_post_meta($post->ID, 'spa_registration_type', true);
    $age_from = get_post_meta($post->ID, 'spa_age_from', true);
    $age_to = get_post_meta($post->ID, 'spa_age_to', true);
    $level = get_post_meta($post->ID, 'spa_level', true);
    $icon = get_post_meta($post->ID, 'spa_icon', true);
    
    // Načítaj dostupné SVG ikony
    $svg_files = [];
    $icons_dir = WP_CONTENT_DIR . '/uploads/spa-icons/';
    if (is_dir($icons_dir)) {
        $files = scandir($icons_dir);
        $svg_files = array_filter($files, function($file) {
            return pathinfo($file, PATHINFO_EXTENSION) === 'svg';
        });
        sort($svg_files);
    }
    
    // Získaj všetky miesta
    $places = get_posts([
        'post_type' => 'spa_place',
        'posts_per_page' => -1,
        'orderby' => 'title',
        'order' => 'ASC'
    ]);
    
    // Získaj všetkých trénerov
    $all_trainers = get_users(['role' => 'spa_trainer', 'orderby' => 'display_name']);
    
    ?>
    <style>
    .spa-meta-row { display: flex; margin-bottom: 15px; align-items: flex-start; }
    .spa-meta-row label { width: 150px; font-weight: 600; padding-top: 8px; }
    .spa-meta-row .spa-field { flex: 1; }
    .spa-meta-row input[type="text"], .spa-meta-row select { width: 100%; max-width: 400px; padding: 8px; }
    .spa-help { color: #666; font-size: 12px; margin-top: 4px; }
    .spa-section { background: #f9f9f9; padding: 20px; border: 1px solid #ddd; border-radius: 4px; margin-bottom: 20px; }
    .spa-section h4 { margin: 0 0 15px 0; padding-bottom: 10px; border-bottom: 1px solid #ddd; }
    .spa-trainers-list { max-width: 400px; }
    .spa-trainer-item { padding: 8px; background: #fff; border: 1px solid #ddd; margin-bottom: 8px; border-radius: 4px; }
    .spa-trainer-item label { margin: 0; width: auto; }
    .spa-icon-preview { width: 60px; height: 60px; border: 1px solid #ddd; border-radius: 4px; padding: 8px; background: #f9f9f9; display: flex; align-items: center; justify-content: center; }
    </style>
    
    <div class="spa-section">
        <h4>🤸 Základné informácie</h4>
        
        <div class="spa-meta-row">
            <label for="spa_place_id">Adresa miesta:</label>
            <div class="spa-field">
                <select name="spa_place_id" id="spa_place_id" required>
                    <option value="">-- Vyberte miesto --</option>
                    <?php foreach ($places as $place) : ?>
                        <option value="<?php echo $place->ID; ?>" <?php selected($place_id, $place->ID); ?>>
                            <?php echo esc_html($place->post_title); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <p class="spa-help">Tréningy sa budú konať na tomto mieste</p>
            </div>
        </div>
        
        <div class="spa-meta-row">
            <label for="spa_capacity">Kapacita skupiny:</label>
            <div class="spa-field">
                <input type="number" name="spa_capacity" id="spa_capacity" value="<?php echo esc_attr($capacity); ?>" min="1" max="100" style="max-width: 100px;">
                <p class="spa-help">Maximálny počet detí v jednej skupine</p>
            </div>
        </div>
        
        <div class="spa-meta-row">
            <label for="spa_registration_type">Typ registrácie:</label>
            <div class="spa-field">
                <select name="spa_registration_type" id="spa_registration_type">
                    <option value="new" <?php selected($registration_type, 'new'); ?>>Nová registrácia</option>
                    <option value="existing" <?php selected($registration_type, 'existing'); ?>>Len pre už prihlásených</option>
                    <option value="both" <?php selected($registration_type, 'both'); ?>>Oboje</option>
                </select>
                <p class="spa-help">Kto sa môže registrovať do tohto programu</p>
            </div>
        </div>
        
        <div class="spa-meta-row">
            <label>Vekové rozpätie (rokov):</label>
            <div class="spa-field" style="display: flex; gap: 15px; align-items: center;">
                <div style="flex: 1;">
                    <label style="width: auto; font-weight: 600;">OD:</label>
                    <input type="number" name="spa_age_from" value="<?php echo esc_attr($age_from); ?>" step="0.1" min="0" max="100" placeholder="napr. 3 alebo 3.5" style="max-width: 120px;">
                </div>
                <div style="flex: 1;">
                    <label style="width: auto; font-weight: 600;">DO:</label>
                    <input type="number" name="spa_age_to" value="<?php echo esc_attr($age_to); ?>" step="0.1" min="0" max="100" placeholder="napr. 7 alebo 7.5" style="max-width: 120px;">
                </div>
            </div>
            <p class="spa-help">Odporúčaný vek účastníkov (napr. 5-7 rokov). Lze zadat aj s desatinou (5,5 alebo 5.5)</p>
        </div>
        
        <div class="spa-meta-row">
            <label for="spa_level">Úroveň:</label>
            <div class="spa-field">
                <select name="spa_level" id="spa_level">
                    <option value="">-- Vyberte úroveň --</option>
                    <option value="beginner" <?php selected($level, 'beginner'); ?>>🟢 Začiatočník</option>
                    <option value="intermediate" <?php selected($level, 'intermediate'); ?>>🟡 Mierne pokročilý</option>
                    <option value="advanced" <?php selected($level, 'advanced'); ?>>🟠 Pokročilý</option>
                    <option value="professional" <?php selected($level, 'professional'); ?>>🔴 Profesionál</option>
                </select>
                <p class="spa-help">Úroveň obtiažnosti/skúsenosti</p>
            </div>
        </div>
    </div>
    
    <div class="spa-section">
        <h4>👟 Tréneri</h4>
        <div class="spa-trainers-list">
            <?php foreach ($all_trainers as $trainer) : ?>
                <div class="spa-trainer-item">
                    <label>
                        <input type="checkbox" name="spa_trainers[]" value="<?php echo $trainer->ID; ?>" 
                            <?php echo in_array($trainer->ID, $trainers) ? 'checked' : ''; ?>>
                        <?php echo esc_html($trainer->display_name); ?>
                    </label>
                </div>
            <?php endforeach; ?>
        </div>
        <p class="spa-help" style="margin-top: 10px;">Vyberte trénerov, ktorí vedú tento program</p>
    </div>
    
    <div class="spa-section">
        <h4>💥 Vyberte ikonu programu</h4>
        <div class="spa-meta-row">
            <div class="spa-field" style="display: flex; align-items: center; gap: 15px;">
                <?php if (empty($svg_files)) : ?>
                    <p style="color: #d63638; margin: 0;">Žiadne ikony v /uploads/spa-icons/</p>
                    <input type="hidden" name="spa_icon" value="">
                <?php else : ?>
                    <select name="spa_icon" id="spa_icon_select" style="width: 250px;">
                        <option value="">-- Bez ikony --</option>
                        <?php foreach ($svg_files as $file) : 
                            $name = pathinfo($file, PATHINFO_FILENAME);
                        ?>
                            <option value="<?php echo esc_attr($file); ?>" <?php selected($icon, $file); ?>>
                                <?php echo esc_html($name); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <div class="spa-icon-preview" id="spa_icon_preview">
                        <?php if ($icon && file_exists($icons_dir . $icon)) : ?>
                            <?php echo file_get_contents($icons_dir . $icon); ?>
                        <?php else : ?>
                            <span style="color:#999; font-size:12px;">--</span>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <script>
    (function() {
        var select = document.getElementById('spa_icon_select');
        var preview = document.getElementById('spa_icon_preview');
        
        if (!select || !preview) return;
        
        select.addEventListener('change', function() {
            if (!this.value) {
                preview.innerHTML = '<span style="color:#999; font-size:12px;">--</span>';
                return;
            }
            
            var iconFile = this.value;
            var ajaxUrl = '<?php echo admin_url('admin-ajax.php'); ?>';
            
            fetch(ajaxUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=spa_load_icon&icon=' + encodeURIComponent(iconFile)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success && data.svg) {
                    preview.innerHTML = data.svg;
                } else {
                    preview.innerHTML = '<span style="color:#d63638; font-size:12px;">Chyba</span>';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                preview.innerHTML = '<span style="color:#d63638; font-size:12px;">Chyba</span>';
            });
        });
    })();
    </script>
    <?php
}


/**
 * SEASONAL PRICING META BOX - Sezónne ceny pre programy
 * 
 * Nahradí starý formát (1x_weekly, 2x_weekly)
 * Používa: spa_pricing_seasons meta pole (JSON)
 * 
 * Štruktúra:
 * {
 *   "oct_dec": { "1x": 60.00, "2x": 80.00, "3x": 100.00 },
 *   "jan_mar": { "1x": 66.00, "2x": 88.00, "3x": 110.00 },
 *   "apr_jun": { "1x": 55.00, "2x": 75.00, "3x": 95.00 },
 *   "jul_sep": { "1x": 50.00, "2x": 70.00, "3x": 90.00 }
 * }
 */
/* ============================================================
   META BOX: CENNÍK PROGRAMU (SEZÓNNE CENY - NOVÝ FORMÁT)
   ============================================================ */

function spa_group_pricing_meta_box($post) {
    wp_nonce_field('spa_save_group_pricing', 'spa_group_pricing_nonce');
    
    // NOVÝ FORMÁT: Sezónne ceny
    $pricing_seasons = get_post_meta($post->ID, 'spa_pricing_seasons', true);
    if (!is_array($pricing_seasons)) {
        $pricing_seasons = [
            'oct_dec' => ['1x' => 0, '2x' => 0, '3x' => 0],
            'jan_mar' => ['1x' => 0, '2x' => 0, '3x' => 0],
            'apr_jun' => ['1x' => 0, '2x' => 0, '3x' => 0],
            'jul_sep' => ['1x' => 0, '2x' => 0, '3x' => 0]
        ];
    }
    
    // LEGACY: Staré polia (pre kompatibilitu)
    $price_monthly = get_post_meta($post->ID, 'spa_price_monthly', true);
    $price_semester = get_post_meta($post->ID, 'spa_price_semester', true);
    $price_external = get_post_meta($post->ID, 'spa_price_external_addon', true);
    
    $seasons = [
        'oct_dec' => '🍂 Október - December (10-12)',
        'jan_mar' => '❄️ Január - Marec (01-03)',
        'apr_jun' => '🌱 Apríl - Jún (04-06)',
        'jul_sep' => '☀️ Júl - September (07-09)'
    ];
    
    $frequencies = ['1x' => '1x týždenne', '2x' => '2x týždenne', '3x' => '3x týždenne'];
    
    ?>
    <style>
        .spa-seasonal-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; background: white; }
        .spa-seasonal-table th { background: #f0f0f0; padding: 12px; text-align: left; font-weight: 600; border: 1px solid #ddd; font-size: 13px; }
        .spa-seasonal-table td { padding: 12px; border: 1px solid #ddd; }
        .spa-seasonal-table input { width: 100%; max-width: 120px; padding: 8px; border: 1px solid #ddd; border-radius: 4px; font-size: 13px; }
        .spa-season-label { font-weight: 600; width: 200px; white-space: nowrap; }
        .spa-freq-label { color: #666; font-size: 12px; font-weight: 400; }
        
        .spa-pricing-info { background: #e7f3ff; border-left: 4px solid #0066ff; padding: 12px; margin-bottom: 20px; border-radius: 4px; }
        .spa-pricing-info p { margin: 0; font-size: 13px; color: #333; line-height: 1.5; }
        .spa-pricing-info strong { color: #0066ff; }
        
        .spa-old-pricing { background: #fff3cd; padding: 15px; border: 1px solid #ffc107; border-radius: 4px; margin-top: 20px; }
        .spa-old-pricing h4 { margin: 0 0 10px 0; font-size: 13px; font-weight: 600; }
        .spa-old-price-row { display: flex; gap: 30px; margin-bottom: 10px; flex-wrap: wrap; }
        .spa-old-price-item { flex: 1; min-width: 200px; }
        .spa-old-price-item label { display: block; font-weight: 600; font-size: 12px; margin-bottom: 5px; }
        .spa-old-price-item input { width: 100%; max-width: 150px; padding: 8px; border: 1px solid #ddd; border-radius: 4px; }
    </style>
    
    <div class="spa-pricing-info">
        <p>💡 <strong>Ako to funguje:</strong><br>
        Nastav cenu (€/týždeň) pre každú sezónu a frekvenciu. 
        Napríklad:<br>
        • Oktober-December, 1x týždenne = 60€<br>
        • Január-Marec, 1x týždenne = 66€
        </p>
    </div>
    
    <!-- SEZÓNNE CENY - TABUĽKA -->
    <h3 style="margin: 20px 0 15px 0; font-size: 14px; font-weight: 600;">📅 Sezónne ceny (€/týždeň)</h3>
    
    <table class="spa-seasonal-table">
        <thead>
            <tr>
                <th class="spa-season-label">Sezóna</th>
                <?php foreach ($frequencies as $freq_key => $freq_label) : ?>
                    <th style="text-align: center; width: 140px;">
                        <?php echo esc_html($freq_label); ?>
                        <div class="spa-freq-label">(€/týždeň)</div>
                    </th>
                <?php endforeach; ?>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($seasons as $season_key => $season_label) : 
                $season_data = $pricing_seasons[$season_key] ?? [];
            ?>
                <tr>
                    <td class="spa-season-label"><?php echo esc_html($season_label); ?></td>
                    <?php foreach ($frequencies as $freq_key => $freq_label) : ?>
                        <td style="text-align: center;">
                            <input 
                                type="number" 
                                name="spa_pricing_seasons[<?php echo esc_attr($season_key); ?>][<?php echo esc_attr($freq_key); ?>]" 
                                value="<?php echo esc_attr($season_data[$freq_key] ?? ''); ?>" 
                                step="0.01" 
                                min="0" 
                                placeholder="0.00"
                            >
                        </td>
                    <?php endforeach; ?>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    
    <!-- LEGACY POLIA -->
    <div class="spa-old-pricing">
        <h4>⚠️ Ostatné ceny (Legacy formáty - nepoužívajú sa)</h4>
        
        <div class="spa-old-price-row">
            <div class="spa-old-price-item">
                <label for="spa_price_monthly">Cena mesačne (paušál):</label>
                <input type="number" name="spa_price_monthly" id="spa_price_monthly" value="<?php echo esc_attr($price_monthly); ?>" step="0.01" min="0" placeholder="0.00">
                <p style="font-size: 11px; color: #666; margin-top: 4px;">Fixná mesačná cena (legacy - nepoužíva sa)</p>
            </div>
            
            <div class="spa-old-price-item">
                <label for="spa_price_semester">Cena za semester:</label>
                <input type="number" name="spa_price_semester" id="spa_price_semester" value="<?php echo esc_attr($price_semester); ?>" step="0.01" min="0" placeholder="0.00">
                <p style="font-size: 11px; color: #666; margin-top: 4px;">Cena za školský rok (legacy - nepoužíva sa)</p>
            </div>
            
            <div class="spa-old-price-item">
                <label for="spa_price_external_addon">Príplatok za externý priestor:</label>
                <input type="number" name="spa_price_external_addon" id="spa_price_external_addon" value="<?php echo esc_attr($price_external); ?>" step="0.01" min="0" placeholder="0.00">
                <p style="font-size: 11px; color: #666; margin-top: 4px;">€/týždeň príplatok (nepoužíva sa)</p>
            </div>
        </div>
    </div>
    
    <?php
}

/* ============================================================
   META BOX: ROZVRH PROGRAMU (JEDNODUCHE - BEZ CHYB)
   ============================================================ */

function spa_group_schedule_meta_box($post) {
    wp_nonce_field('spa_save_group_schedule', 'spa_group_schedule_nonce');
    
    // Načítaj rozvrh
    $schedule_json = get_post_meta($post->ID, 'spa_schedule', true);
    $schedule = $schedule_json ? json_decode($schedule_json, true) : [];
    
    if (!is_array($schedule)) {
        $schedule = [];
    }
    
    // Dni v týždni
    $days = [
        'monday' => '🟦 Pondelok',
        'tuesday' => '🟩 Utorok',
        'wednesday' => '🟪 Streda',
        'thursday' => '🟨 Štvrtok',
        'friday' => '🟧 Piatok',
        'saturday' => '🟥 Sobota',
        'sunday' => '⚪ Nedeľa'
    ];
    
    // Generuj časy (00:00 - 23:55, po 5 minútach)
    $times = [];
    for ($h = 0; $h < 24; $h++) {
        for ($m = 0; $m < 60; $m += 5) {
            $time = sprintf("%02d:%02d", $h, $m);
            $times[$time] = $time;
        }
    }
    
    ?>
    <style>
        .spa-schedule-box { background: #f9f9f9; padding: 20px; border: 1px solid #ddd; border-radius: 4px; }
        .spa-schedule-item { 
            background: #fff; 
            padding: 15px; 
            border: 1px solid #ddd; 
            margin-bottom: 12px; 
            border-radius: 4px; 
            display: grid;
            grid-template-columns: 1fr 100px 100px 100px auto;
            gap: 12px;
            align-items: center;
        }
        .spa-schedule-item select { width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; }
        .spa-schedule-item .remove-btn { 
            background: #dc3545; 
            color: white; 
            border: none; 
            padding: 8px 12px; 
            border-radius: 4px; 
            cursor: pointer;
            font-size: 12px;
        }
        .spa-schedule-item .remove-btn:hover { background: #c82333; }
        .spa-add-schedule-btn { 
            background: #0066FF; 
            color: white; 
            padding: 10px 20px; 
            border: none; 
            border-radius: 4px; 
            cursor: pointer; 
            font-weight: 600; 
            margin-top: 12px; 
        }
        .spa-add-schedule-btn:hover { background: #0052cc; }
        .spa-help { color: #666; font-size: 12px; margin-top: 10px; line-height: 1.5; }
        .spa-time-label { font-size: 12px; color: #666; font-weight: 500; }
    </style>
    
    <div class="spa-schedule-box">
        <p style="margin: 0 0 15px 0; color: #666; font-size: 13px;">
            📅 Pridajte všetky dni a časy, kedy sa tento program koná.
        </p>
        
        <div id="spa-schedule-container">
            <?php
            if (!empty($schedule)) {
                foreach ($schedule as $index => $item) {
                    $day = isset($item['day']) ? $item['day'] : '';
                    $from = isset($item['from']) ? $item['from'] : '';
                    $to = isset($item['to']) ? $item['to'] : '';
                    
                    echo '<div class="spa-schedule-item">';
                    
                    // Deň
                    echo '<select name="spa_schedule[' . $index . '][day]" required>';
                    echo '<option value="">-- Vyber deň --</option>';
                    foreach ($days as $day_key => $day_label) {
                        $selected = ($day === $day_key) ? 'selected' : '';
                        echo '<option value="' . esc_attr($day_key) . '" ' . $selected . '>' . esc_html($day_label) . '</option>';
                    }
                    echo '</select>';
                    
                    // OD čas
                    echo '<div><label class="spa-time-label">od</label>';
                    echo '<select name="spa_schedule[' . $index . '][from]" required>';
                    echo '<option value="">--:--</option>';
                    foreach ($times as $time_val => $time_label) {
                        $selected = ($from === $time_val) ? 'selected' : '';
                        echo '<option value="' . esc_attr($time_val) . '" ' . $selected . '>' . esc_html($time_label) . '</option>';
                    }
                    echo '</select></div>';
                    
                    // DO čas
                    echo '<div><label class="spa-time-label">do</label>';
                    echo '<select name="spa_schedule[' . $index . '][to]" required>';
                    echo '<option value="">--:--</option>';
                    foreach ($times as $time_val => $time_label) {
                        $selected = ($to === $time_val) ? 'selected' : '';
                        echo '<option value="' . esc_attr($time_val) . '" ' . $selected . '>' . esc_html($time_label) . '</option>';
                    }
                    echo '</select></div>';
                    
                    // Odstrániť tlačidlo
                    echo '<button type="button" class="remove-btn" onclick="this.parentElement.remove();">Odstrániť</button>';
                    
                    echo '</div>';
                }
            }
            ?>
        </div>
        
        <button type="button" class="spa-add-schedule-btn" onclick="spa_add_schedule_row()">
            + Pridať ďalší termín
        </button>
    </div>
    
    <p class="spa-help">
        💡 <strong>Príklad:</strong> Ak sa program koná v utorok a štvrtok od 17:00 do 18:00, 
        pridaj dve položky (po jednej pre každý deň).
    </p>
    
    <script>
    var spa_schedule_counter = <?php echo !empty($schedule) ? max(array_keys($schedule)) + 1 : 0; ?>;
    var spa_times_json = <?php echo json_encode($times); ?>;
    var spa_days_json = <?php echo json_encode($days); ?>;
    
    function spa_add_schedule_row() {
        var container = document.getElementById('spa-schedule-container');
        
        // Generuj select pre dni
        var dayOptions = '<option value="">-- Vyber deň --</option>';
        Object.entries(spa_days_json).forEach(([key, label]) => {
            dayOptions += '<option value="' + key + '">' + label + '</option>';
        });
        
        // Generuj select pre časy
        var timeOptions = '<option value="">--:--</option>';
        Object.entries(spa_times_json).forEach(([val, label]) => {
            timeOptions += '<option value="' + val + '">' + label + '</option>';
        });
        
        // Vytvor nový riadok
        var newRow = document.createElement('div');
        newRow.className = 'spa-schedule-item';
        newRow.innerHTML = '<select name="spa_schedule[' + spa_schedule_counter + '][day]" required>' + dayOptions + '</select>' +
                          '<div><label class="spa-time-label">od</label><select name="spa_schedule[' + spa_schedule_counter + '][from]" required>' + timeOptions + '</select></div>' +
                          '<div><label class="spa-time-label">do</label><select name="spa_schedule[' + spa_schedule_counter + '][to]" required>' + timeOptions + '</select></div>' +
                          '<button type="button" class="remove-btn" onclick="this.parentElement.remove();">Odstrániť</button>';
        
        container.appendChild(newRow);
        spa_schedule_counter++;
    }
    </script>
    
    <?php
}

/* ============================================================
   SAVE: Uloženie rozvrhu
   ============================================================ */

add_action('save_post_spa_group', 'spa_group_schedule_save', 10, 2);

function spa_group_schedule_save($post_id, $post) {
    if ($post->post_type !== 'spa_group') {
        return;
    }
    
    if (!isset($_POST['spa_group_schedule_nonce']) || !wp_verify_nonce($_POST['spa_group_schedule_nonce'], 'spa_save_group_schedule')) {
        return;
    }
    
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    
    if (!current_user_can('edit_post', $post_id)) {
        return;
    }
    
    // Rozvrh
    if (isset($_POST['spa_schedule']) && is_array($_POST['spa_schedule'])) {
        $schedule = [];
        
        foreach ($_POST['spa_schedule'] as $index => $item) {
            if (empty($item['day']) || empty($item['from']) || empty($item['to'])) {
                continue; // Preskočiť neúplné položky
            }
            
            $schedule[$index] = [
                'day' => sanitize_key($item['day']),
                'from' => sanitize_text_field($item['from']),
                'to' => sanitize_text_field($item['to'])
            ];
        }
        
        update_post_meta($post_id, 'spa_schedule', json_encode($schedule));
    }
}

/* ============================================================
   SAVE: Uloženie cien
   ============================================================ */

add_action('save_post_spa_group', 'spa_group_pricing_save', 10, 2);

function spa_group_pricing_save($post_id, $post) {
    if ($post->post_type !== 'spa_group') {
        return;
    }
    
    if (!isset($_POST['spa_group_pricing_nonce']) || !wp_verify_nonce($_POST['spa_group_pricing_nonce'], 'spa_save_group_pricing')) {
        return;
    }
    
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    
    if (!current_user_can('edit_post', $post_id)) {
        return;
    }
    
    // SEZÓNNE CENY (NOVÝ FORMÁT)
    if (isset($_POST['spa_pricing_seasons']) && is_array($_POST['spa_pricing_seasons'])) {
        $pricing_seasons = [];
        
        foreach ($_POST['spa_pricing_seasons'] as $season => $frequencies) {
            $season = sanitize_key($season);
            $pricing_seasons[$season] = [];
            
            if (is_array($frequencies)) {
                foreach ($frequencies as $freq => $price) {
                    $freq = sanitize_key($freq);
                    $pricing_seasons[$season][$freq] = floatval($price);
                }
            }
        }
        
        update_post_meta($post_id, 'spa_pricing_seasons', $pricing_seasons);
    }
    
    // LEGACY POLIA (KOMPATIBILITA)
    if (isset($_POST['spa_price_monthly'])) {
        update_post_meta($post_id, 'spa_price_monthly', floatval($_POST['spa_price_monthly']));
    }
    
    if (isset($_POST['spa_price_semester'])) {
        update_post_meta($post_id, 'spa_price_semester', floatval($_POST['spa_price_semester']));
    }
    
    if (isset($_POST['spa_price_external_addon'])) {
        update_post_meta($post_id, 'spa_price_external_addon', floatval($_POST['spa_price_external_addon']));
    }
}



/**
 * HELPER: Render riadku rozvrhu v2
 */
function spa_render_schedule_row_v2($index, $item, $days, $times) {
    $day = isset($item['day']) ? $item['day'] : '';
    $from = isset($item['from']) ? $item['from'] : '';
    $to = isset($item['to']) ? $item['to'] : '';
    
    $timeOptions = array_map(function($val) use ($from, $to) {
        return sprintf(
            '<option value="%s" %s>%s</option>',
            esc_attr($val),
            selected($from === $val || $to === $val, true, false),
            esc_html($val)
        );
    }, array_keys($times));
    
    $dayOptions = array_map(function($val, $label) use ($day) {
        return sprintf(
            '<option value="%s" %s>%s</option>',
            esc_attr($val),
            selected($day === $val, true, false),
            esc_html($label)
        );
    }, array_keys($days), array_values($days));
    
    ?>
    <div class="spa-schedule-item">
        <select name="spa_schedule[<?php echo $index; ?>][day]" class="day-select">
            <option value="">-- Vyber deň --</option>
            <?php echo implode('', $dayOptions); ?>
        </select>
        
        <span>od</span>
        <select name="spa_schedule[<?php echo $index; ?>][from]" class="time-select">
            <option value="">-- od --</option>
            <?php foreach ($times as $val => $label) : ?>
                <option value="<?php echo esc_attr($val); ?>" <?php selected($from, $val); ?>>
                    <?php echo esc_html($label); ?>
                </option>
            <?php endforeach; ?>
        </select>
        
        <span>do</span>
        <select name="spa_schedule[<?php echo $index; ?>][to]" class="time-select">
            <option value="">-- do --</option>
            <?php foreach ($times as $val => $label) : ?>
                <option value="<?php echo esc_attr($val); ?>" <?php selected($to, $val); ?>>
                    <?php echo esc_html($label); ?>
                </option>
            <?php endforeach; ?>
        </select>
        
        <button type="button" class="remove-btn" onclick="this.parentElement.remove()">Odstrániť</button>
    </div>
    <?php
}

/* ============================================================
   META BOX: MIESTO (spa_place)
   ============================================================ */
function spa_place_meta_box($post) {
    wp_nonce_field('spa_save_place', 'spa_place_nonce');
    
    $type = get_post_meta($post->ID, 'spa_place_type', true);
    $address = get_post_meta($post->ID, 'spa_place_address', true);
    $city = get_post_meta($post->ID, 'spa_place_city', true);
    $gps_lat = get_post_meta($post->ID, 'spa_place_gps_lat', true);
    $gps_lng = get_post_meta($post->ID, 'spa_place_gps_lng', true);
    $contact = get_post_meta($post->ID, 'spa_place_contact', true);
    $notes = get_post_meta($post->ID, 'spa_place_notes', true);
    
    ?>
    <style>
    .spa-meta-row { display: flex; margin-bottom: 15px; align-items: flex-start; }
    .spa-meta-row label { width: 150px; font-weight: 600; padding-top: 8px; }
    .spa-meta-row .spa-field { flex: 1; }
    .spa-meta-row input[type="text"], .spa-meta-row textarea, .spa-meta-row select { width: 100%; max-width: 400px; padding: 8px; }
    .spa-help { color: #666; font-size: 12px; margin-top: 4px; }
    .spa-section { background: #f9f9f9; padding: 20px; border: 1px solid #ddd; border-radius: 4px; margin-bottom: 20px; }
    .spa-section h4 { margin: 0 0 15px 0; padding-bottom: 10px; border-bottom: 1px solid #ddd; }
    </style>
    
    <div class="spa-section">
        <h4>📍 Základné informácie</h4>
        
        <div class="spa-meta-row">
            <label for="spa_place_type">Typ priestoru:</label>
            <div class="spa-field">
                <select name="spa_place_type" id="spa_place_type">
                    <option value="">-- Vyberte typ --</option>
                    <option value="spa" <?php selected($type, 'spa'); ?>>🏠 Priestory SPA (vlastné)</option>
                    <option value="external" <?php selected($type, 'external'); ?>>🏫 Externé priestory (prenájom)</option>
                </select>
                <p class="spa-help">Externé priestory môžu mať príplatok v cene programu</p>
            </div>
        </div>
        
        <div class="spa-meta-row">
            <label for="spa_place_city">Mesto:</label>
            <div class="spa-field">
                <input type="text" name="spa_place_city" id="spa_place_city" value="<?php echo esc_attr($city); ?>" placeholder="napr. Malacky, Košice">
            </div>
        </div>
        
        <div class="spa-meta-row">
            <label for="spa_place_address">Adresa:</label>
            <div class="spa-field">
                <input type="text" name="spa_place_address" id="spa_place_address" value="<?php echo esc_attr($address); ?>" placeholder="napr. Športová hala Basso, Sasinkova 2">
            </div>
        </div>
        
        <div class="spa-meta-row">
            <label>GPS súradnice:</label>
            <div class="spa-field">
                <input type="text" name="spa_place_gps_lat" value="<?php echo esc_attr($gps_lat); ?>" placeholder="Lat" style="width: 150px; margin-right: 10px;">
                <input type="text" name="spa_place_gps_lng" value="<?php echo esc_attr($gps_lng); ?>" placeholder="Lng" style="width: 150px;">
                <p class="spa-help">Voliteľné - pre zobrazenie na mape</p>
            </div>
        </div>
        
        <div class="spa-meta-row">
            <label for="spa_place_contact">Kontakt:</label>
            <div class="spa-field">
                <input type="text" name="spa_place_contact" id="spa_place_contact" value="<?php echo esc_attr($contact); ?>" placeholder="Telefón alebo email na správcu">
            </div>
        </div>
        
        <div class="spa-meta-row">
            <label for="spa_place_notes">Poznámky:</label>
            <div class="spa-field">
                <textarea name="spa_place_notes" id="spa_place_notes" rows="3" placeholder="Interné poznámky k miestu..."><?php echo esc_textarea($notes); ?></textarea>
            </div>
        </div>
    </div>
    <?php
}

/* ============================================================
   SAVE ACTIONS - Uloženie všetkých meta boxov
   ============================================================ */

// DETAILY PROGRAMU (spa_group)
add_action('save_post_spa_group', 'spa_save_group_details_meta', 10, 2);
function spa_save_group_details_meta($post_id, $post) {
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (!isset($_POST['spa_group_nonce']) || !wp_verify_nonce($_POST['spa_group_nonce'], 'spa_save_group_details')) return;
    if (!current_user_can('edit_post', $post_id)) return;
    
    $fields = ['spa_place_id', 'spa_capacity', 'spa_registration_type', 'spa_level', 'spa_icon'];
    foreach ($fields as $field) {
        if (isset($_POST[$field])) {
            $value = ($field === 'spa_place_id' || $field === 'spa_capacity') 
                ? intval($_POST[$field]) 
                : sanitize_text_field($_POST[$field]);
            update_post_meta($post_id, $field, $value);
        }
    }
    
    // Vekové hodnoty - prijmi čiarku aj bodku
    if (isset($_POST['spa_age_from'])) {
        $age = floatval(str_replace(',', '.', $_POST['spa_age_from']));
        update_post_meta($post_id, 'spa_age_from', $age);
    }
    if (isset($_POST['spa_age_to'])) {
        $age = floatval(str_replace(',', '.', $_POST['spa_age_to']));
        update_post_meta($post_id, 'spa_age_to', $age);
    }
    
    // Tréneri (pole)
    $trainers = isset($_POST['spa_trainers']) && is_array($_POST['spa_trainers']) 
        ? array_map('intval', $_POST['spa_trainers']) 
        : [];
    update_post_meta($post_id, 'spa_trainers', $trainers);
}

// ROZVRH PROGRAMU (spa_group)
add_action('save_post_spa_group', 'spa_save_group_schedule_meta', 11, 2);
function spa_save_group_schedule_meta($post_id, $post) {
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (!isset($_POST['spa_group_schedule_nonce']) || !wp_verify_nonce($_POST['spa_group_schedule_nonce'], 'spa_save_group_schedule')) return;
    if (!current_user_can('edit_post', $post_id)) return;
    
    if (isset($_POST['spa_schedule']) && is_array($_POST['spa_schedule'])) {
        $schedule = [];
        foreach ($_POST['spa_schedule'] as $index => $item) {
            if (!empty($item['day'])) {
                $schedule[$index] = [
                    'day' => sanitize_text_field($item['day']),
                    'from' => sanitize_text_field($item['from'] ?? ''),
                    'to' => sanitize_text_field($item['to'] ?? '')
                ];
            }
        }
        update_post_meta($post_id, 'spa_schedule', wp_json_encode($schedule));
    }
}

// CENNÍK PROGRAMU (spa_group)
add_action('save_post_spa_group', 'spa_save_group_pricing_meta', 12, 2);
function spa_save_group_pricing_meta($post_id, $post) {
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (!isset($_POST['spa_group_pricing_nonce']) || !wp_verify_nonce($_POST['spa_group_pricing_nonce'], 'spa_save_group_pricing')) return;
    if (!current_user_can('edit_post', $post_id)) return;
    
    $price_fields = [
        'spa_price_1x_weekly',
        'spa_price_2x_weekly',
        'spa_price_monthly',
        'spa_price_semester',
        'spa_external_surcharge'
    ];
    
    foreach ($price_fields as $field) {
        if (isset($_POST[$field])) {
            $value = floatval(str_replace(',', '.', $_POST[$field]));
            update_post_meta($post_id, $field, $value);
        }
    }
    
    if (isset($_POST['spa_price_1x_weekly'])) {
        $price = floatval(str_replace(',', '.', $_POST['spa_price_1x_weekly']));
        update_post_meta($post_id, 'spa_price', $price);
    }
}

// MIESTO (spa_place)
add_action('save_post_spa_place', 'spa_save_place_meta', 10, 2);
function spa_save_place_meta($post_id, $post) {
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (!isset($_POST['spa_place_nonce']) || !wp_verify_nonce($_POST['spa_place_nonce'], 'spa_save_place')) return;
    if (!current_user_can('edit_post', $post_id)) return;
    
    $fields = ['spa_place_type', 'spa_place_city', 'spa_place_address', 'spa_place_gps_lat', 'spa_place_gps_lng', 'spa_place_contact', 'spa_place_notes'];
    foreach ($fields as $field) {
        if (isset($_POST[$field])) {
            update_post_meta($post_id, $field, sanitize_text_field($_POST[$field]));
        }
    }
    
    if (isset($_POST['spa_place_schedule']) && is_array($_POST['spa_place_schedule'])) {
        $schedule = [];
        foreach ($_POST['spa_place_schedule'] as $day => $data) {
            if (!empty($data['from']) || !empty($data['to'])) {
                $schedule[$day] = [
                    'from' => sanitize_text_field($data['from']),
                    'to' => sanitize_text_field($data['to']),
                    'capacity' => intval($data['capacity'] ?? 0),
                    'active' => !empty($data['active'])
                ];
            }
        }
        update_post_meta($post_id, 'spa_place_schedule', wp_json_encode($schedule));
    }
}

// UDALOSŤ (spa_event)
add_action('save_post_spa_event', 'spa_save_event_meta', 10, 2);
function spa_save_event_meta($post_id, $post) {
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (!isset($_POST['spa_event_nonce']) || !wp_verify_nonce($_POST['spa_event_nonce'], 'spa_save_event')) return;
    if (!current_user_can('edit_post', $post_id)) return;
    
    $fields = [
        'spa_event_place_id' => 'intval',
        'spa_event_type' => 'sanitize_text_field',
        'spa_event_date_from' => 'sanitize_text_field',
        'spa_event_date_to' => 'sanitize_text_field',
        'spa_event_time_from' => 'sanitize_text_field',
        'spa_event_time_to' => 'sanitize_text_field',
        'spa_event_recurring' => 'sanitize_text_field'
    ];
    
    foreach ($fields as $key => $sanitize) {
        if (isset($_POST[$key])) {
            $value = ($sanitize === 'intval') ? intval($_POST[$key]) : sanitize_text_field($_POST[$key]);
            update_post_meta($post_id, $key, $value);
        }
    }
    
    update_post_meta($post_id, 'spa_event_all_day', isset($_POST['spa_event_all_day']) ? 1 : 0);
}

// DOCHÁDZKA (spa_attendance)
add_action('save_post_spa_attendance', 'spa_save_attendance_meta', 10, 2);
function spa_save_attendance_meta($post_id, $post) {
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (!isset($_POST['spa_attendance_nonce']) || !wp_verify_nonce($_POST['spa_attendance_nonce'], 'spa_save_attendance')) return;
    if (!current_user_can('edit_post', $post_id)) return;
    
    $fields = [
        'spa_att_client_id' => 'intval',
        'spa_att_program_id' => 'intval',
        'spa_att_registration_id' => 'intval',
        'spa_att_date' => 'sanitize_text_field',
        'spa_att_status' => 'sanitize_text_field',
        'spa_att_stars' => 'intval',
        'spa_att_points' => 'intval',
        'spa_att_rating' => 'sanitize_textarea_field',
        'spa_att_note' => 'sanitize_textarea_field'
    ];
    
    foreach ($fields as $key => $sanitize) {
        if (isset($_POST[$key])) {
            if ($sanitize === 'intval') {
                $value = intval($_POST[$key]);
            } elseif ($sanitize === 'sanitize_textarea_field') {
                $value = sanitize_textarea_field($_POST[$key]);
            } else {
                $value = sanitize_text_field($_POST[$key]);
            }
            update_post_meta($post_id, $key, $value);
        }
    }
    
    $client_id = intval($_POST['spa_att_client_id'] ?? 0);
    $date = sanitize_text_field($_POST['spa_att_date'] ?? '');
    
    if ($client_id && $date) {
        $user = get_userdata($client_id);
        if ($user) {
            $name = trim($user->first_name . ' ' . $user->last_name);
            if (empty($name)) $name = $user->display_name;
            $new_title = $name . ' - ' . date_i18n('j.n.Y', strtotime($date));
            
            remove_action('save_post_spa_attendance', 'spa_save_attendance_meta', 10);
            wp_update_post(['ID' => $post_id, 'post_title' => $new_title]);
            add_action('save_post_spa_attendance', 'spa_save_attendance_meta', 10, 2);
        }
    }
}

/* ============================================================
   AJAX: Dynamické načítanie ikony
   ============================================================ */

add_action('wp_ajax_spa_load_icon', 'spa_ajax_load_icon');
add_action('wp_ajax_nopriv_spa_load_icon', 'spa_ajax_load_icon');
function spa_ajax_load_icon() {
    if (!isset($_POST['icon']) || empty($_POST['icon'])) {
        wp_send_json(['success' => false, 'error' => 'Ikona nie je zadaná']);
    }
    
    $icon_file = sanitize_file_name($_POST['icon']);
    $icon_path = WP_CONTENT_DIR . '/uploads/spa-icons/' . $icon_file;
    
    if (!file_exists($icon_path) || pathinfo($icon_path, PATHINFO_EXTENSION) !== 'svg') {
        wp_send_json(['success' => false, 'error' => 'Súbor neexistuje alebo nie je SVG']);
    }
    
    $svg_content = file_get_contents($icon_path);
    if (!$svg_content) {
        echo json_encode(['success' => false, 'error' => 'Nemôžem načítať súbor']);
        wp_die();
    }

    // Odstráň XML deklaráciu ak existuje
    $svg_content = preg_replace('/<\?xml[^?]*\?>/', '', $svg_content);

    echo json_encode(['success' => true, 'svg' => $svg_content]);
    wp_die();
}

