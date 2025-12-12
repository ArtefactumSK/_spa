<?php
/** spa-meta-boxes.php
 * SPA Meta Boxes - Admin formuláre pre CPT
 * @package Samuel Piasecký ACADEMY
 * @version 3.1.0 - Úprava: Dynamický rozvrh programu + Miesta + Ceny
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
    add_meta_box('spa_group_pricing', '💰 Cenník programu', 'spa_group_pricing_meta_box', 'spa_group', 'normal', 'high');
    
    // REGISTRÁCIE
    add_meta_box('spa_registration_details', '📋 Detaily registrácie', 'spa_registration_meta_box', 'spa_registration', 'normal', 'high');
    
    // MIESTA (spa_place) - NOVÉ
    add_meta_box('spa_place_details', '📍 Detaily miesta', 'spa_place_meta_box', 'spa_place', 'normal', 'high');
    add_meta_box('spa_place_schedule', '📅 Rozvrh miesta', 'spa_place_schedule_meta_box', 'spa_place', 'normal', 'default');
    
    // UDALOSTI (spa_event) - NOVÉ
    add_meta_box('spa_event_details', '📅 Detaily udalosti', 'spa_event_meta_box', 'spa_event', 'normal', 'high');
    
    // DOCHÁDZKA (spa_attendance) - NOVÉ
    add_meta_box('spa_attendance_details', '✅ Záznam dochádzky', 'spa_attendance_meta_box', 'spa_attendance', 'normal', 'high');
}

/* ============================================================
   META BOX: DETAILY PROGRAMU (spa_group)
   UPRAVENÝ: Bez "Popis programu" (použuje post_content)
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
    $svg_dir = content_url() . '/uploads/spa-icons/';
    $svg_files = [];
    if (is_dir(WP_CONTENT_DIR . '/uploads/spa-icons/')) {
        $files = scandir(WP_CONTENT_DIR . '/uploads/spa-icons/');
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
    </style>
    
    <div class="spa-section">
        <h4>🤸 Základné informácie</h4>
        
        <div class="spa-meta-row">
            <label for="spa_place_id">Miesto tréningovej jednotky:</label>
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
        
        <div class="spa-meta-row">
            <label>Ikona programu:</label>
            <div class="spa-field" style="display: flex; align-items: center; gap: 15px;">
                <?php if (empty($svg_files)) : ?>
                    <p style="color: #d63638; margin: 0;">
                        Žiadne ikony v adresári /uploads/spa-icons/
                    </p>
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
                    <div class="spa-icon-preview" id="spa_icon_preview" style="width: 60px; height: 60px; border: 1px solid #ddd; border-radius: 4px; padding: 8px; background: #f9f9f9; display: flex; align-items: center; justify-content: center;">
                        <?php if ($icon && file_exists(WP_CONTENT_DIR . '/uploads/spa-icons/' . $icon)) : ?>
                            <?php echo file_get_contents(WP_CONTENT_DIR . '/uploads/spa-icons/' . $icon); ?>
                        <?php else : ?>
                            <span style="color:#999; font-size:12px;">--</span>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
            <p class="spa-help">Vyberte ikonu z dostupných SVG súborov v /uploads/spa-icons/</p>
        </div>
    </div>
    
    <div class="spa-section">
        <h4>👨‍🏫 Tréneri</h4>
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
        <h4>📝 Poznámka: Popis programu</h4>
        <p class="spa-help">Popis programu upravujte v hlavnom editore obsahu (post_content) webu.</p>
    </div>
    <?php
}

function spa_group_schedule_meta_box($post) {
    wp_nonce_field('spa_save_group_schedule', 'spa_group_schedule_nonce');
    
    $schedule_json = get_post_meta($post->ID, 'spa_schedule', true);
    $schedule = $schedule_json ? json_decode($schedule_json, true) : [];
    
    $days = [
        'monday' => 'Pondelok',
        'tuesday' => 'Utorok',
        'wednesday' => 'Streda',
        'thursday' => 'Štvrtok',
        'friday' => 'Piatok',
        'saturday' => 'Sobota',
        'sunday' => 'Nedeľa'
    ];
    
    $day_abbrev = [
        'monday' => 'Po',
        'tuesday' => 'Ut',
        'wednesday' => 'St',
        'thursday' => 'Št',
        'friday' => 'Pi',
        'saturday' => 'So',
        'sunday' => 'Ne'
    ];
    
    ?>
    <style>
    .spa-schedule-box { background: #f9f9f9; padding: 20px; border: 1px solid #ddd; border-radius: 4px; }
    .spa-schedule-item { background: #fff; padding: 15px; border: 1px solid #ddd; margin-bottom: 15px; border-radius: 4px; display: flex; align-items: center; gap: 15px; }
    .spa-schedule-item .day-select { min-width: 120px; }
    .spa-schedule-item .time-input { width: 80px; }
    .spa-schedule-item .remove-btn { background: #dc3545; color: white; border: none; padding: 6px 12px; border-radius: 4px; cursor: pointer; }
    .spa-schedule-item .remove-btn:hover { background: #c82333; }
    .spa-add-btn { background: #0066FF; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; font-weight: 600; }
    .spa-add-btn:hover { background: #0052cc; }
    .spa-help { color: #666; font-size: 12px; margin-top: 10px; }
    </style>
    
    <div class="spa-schedule-box">
        <h4>📅 Tréningy - Dni a časy</h4>
        <p style="color: #666; margin-bottom: 15px;">Pridajte všetky dni a časy, kedy sa tento program koná.</p>
        
        <div id="spa-schedule-container">
            <?php if (!empty($schedule)) : ?>
                <?php foreach ($schedule as $index => $item) : ?>
                    <div class="spa-schedule-item">
                        <select name="spa_schedule[<?php echo $index; ?>][day]" class="day-select">
                            <option value="">-- Vyber deň --</option>
                            <?php foreach ($days as $key => $label) : ?>
                                <option value="<?php echo $key; ?>" <?php selected($item['day'] ?? '', $key); ?>>
                                    <?php echo $label; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        
                        <span>od</span>
                        <input type="time" name="spa_schedule[<?php echo $index; ?>][from]" value="<?php echo esc_attr($item['from'] ?? ''); ?>" class="time-input">
                        
                        <span>do</span>
                        <input type="time" name="spa_schedule[<?php echo $index; ?>][to]" value="<?php echo esc_attr($item['to'] ?? ''); ?>" class="time-input">
                        
                        <button type="button" class="remove-btn" onclick="this.parentElement.remove();">Odstrániť</button>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        
        <button type="button" class="spa-add-btn" onclick="spa_add_schedule_row();">+ Pridať ďalší termín</button>
        
        <p class="spa-help">Príklad: Utorok 10:00-11:00, Štvrtok 10:00-11:00 = 2x týždenne tréningy</p>
    </div>
    
    <script>
    var scheduleIndex = <?php echo !empty($schedule) ? max(array_keys($schedule)) + 1 : 0; ?>;
    
    function spa_add_schedule_row() {
        var days = <?php echo json_encode($days); ?>;
        var html = '<div class="spa-schedule-item">' +
            '<select name="spa_schedule[' + scheduleIndex + '][day]" class="day-select">' +
            '<option value="">-- Vyber deň --</option>';
        
        for (var key in days) {
            html += '<option value="' + key + '">' + days[key] + '</option>';
        }
        
        html += '</select>' +
            '<span>od</span>' +
            '<input type="time" name="spa_schedule[' + scheduleIndex + '][from]" class="time-input">' +
            '<span>do</span>' +
            '<input type="time" name="spa_schedule[' + scheduleIndex + '][to]" class="time-input">' +
            '<button type="button" class="remove-btn" onclick="this.parentElement.remove();">Odstrániť</button>' +
            '</div>';
        
        document.getElementById('spa-schedule-container').insertAdjacentHTML('beforeend', html);
        scheduleIndex++;
    }
    </script>
    <?php
}

/* ============================================================
   META BOX: ROZVRH PROGRAMU (spa_group) - NOVÝ
   Dynamické pridávanie viacerých termínov (dni + časy)
   ============================================================ */

/* ============================================================
   META BOX: CENNÍK PROGRAMU (spa_group)
   Cena za 1x a 2x týždenne
   ============================================================ */
function spa_group_pricing_meta_box($post) {
    wp_nonce_field('spa_save_group_pricing', 'spa_group_pricing_nonce');
    
    $price_1x = get_post_meta($post->ID, 'spa_price_1x_weekly', true);
    $price_2x = get_post_meta($post->ID, 'spa_price_2x_weekly', true);
    $price_monthly = get_post_meta($post->ID, 'spa_price_monthly', true);
    $price_semester = get_post_meta($post->ID, 'spa_price_semester', true);
    $external_surcharge = get_post_meta($post->ID, 'spa_external_surcharge', true);
    
    ?>
    <style>
    .spa-pricing-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px; }
    .spa-price-box { background: #fff; border: 2px solid #ddd; padding: 15px; border-radius: 8px; }
    .spa-price-box h5 { margin: 0 0 10px 0; color: #333; }
    .spa-price-box input { width: 100px; padding: 8px; font-size: 16px; font-weight: bold; }
    .spa-price-box .currency { font-size: 16px; margin-left: 5px; }
    .spa-help { color: #666; font-size: 12px; margin-top: 5px; }
    </style>
    
    <div class="spa-pricing-grid">
        <div class="spa-price-box">
            <h5>💰 Cena za 1x týždenne</h5>
            <input type="number" name="spa_price_1x_weekly" value="<?php echo esc_attr($price_1x); ?>" step="0.01" min="0">
            <span class="currency">€</span>
            <p class="spa-help">Mesačná cena pri jednom tréningu týždenne</p>
        </div>
        
        <div class="spa-price-box">
            <h5>💰 Cena za 2x týždenne</h5>
            <input type="number" name="spa_price_2x_weekly" value="<?php echo esc_attr($price_2x); ?>" step="0.01" min="0">
            <span class="currency">€</span>
            <p class="spa-help">Mesačná cena pri dvoch tréningoch týždenne (zvýhodnená)</p>
        </div>
        
        <div class="spa-price-box">
            <h5>📅 Cena mesačne (paušál)</h5>
            <input type="number" name="spa_price_monthly" value="<?php echo esc_attr($price_monthly); ?>" step="0.01" min="0">
            <span class="currency">€</span>
            <p class="spa-help">Voliteľné - fixná mesačná cena</p>
        </div>
        
        <div class="spa-price-box">
            <h5>🎓 Cena za semester</h5>
            <input type="number" name="spa_price_semester" value="<?php echo esc_attr($price_semester); ?>" step="0.01" min="0">
            <span class="currency">€</span>
            <p class="spa-help">Voliteľné - cena za celý školský polrok</p>
        </div>
    </div>
    
    <div style="margin-top: 20px; padding: 15px; background: #fff3cd; border: 1px solid #ffc107; border-radius: 4px;">
        <h5 style="margin: 0 0 10px 0;">🏫 Príplatok za externé priestory</h5>
        <input type="number" name="spa_external_surcharge" value="<?php echo esc_attr($external_surcharge); ?>" step="0.01" min="0" style="width: 80px;">
        <span class="currency">€</span>
        <p class="spa-help" style="margin-top: 5px;">Príplatok k cene ak sa tréning koná v externých priestoroch (prenájom)</p>
    </div>
    <?php
}


/* ============================================================
   AJAX: Dynamické načítanie ikony (náhľad)
   Doplniť na koniec spa-meta-boxes.php
   ============================================================ */

add_action('wp_ajax_spa_load_icon', 'spa_ajax_load_icon');
function spa_ajax_load_icon() {
    if (!isset($_POST['icon'])) {
        wp_die('Chyba: Ikona nie je zadaná');
    }
    
    $icon_file = sanitize_file_name($_POST['icon']);
    $icon_path = WP_CONTENT_DIR . '/uploads/spa-icons/' . $icon_file;
    
    // Bezpečnostná kontrola - len .svg súbory z tego adresára
    if (!file_exists($icon_path) || pathinfo($icon_path, PATHINFO_EXTENSION) !== 'svg') {
        wp_die('Chyba: Súbor neexistuje alebo nie je SVG');
    }
    
    // Načítaj SVG obsah a vyrenderuj
    $svg_content = file_get_contents($icon_path);
    echo wp_kses_post($svg_content);
    wp_die();
}