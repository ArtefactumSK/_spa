<?php
/** spa-meta-boxes.php
 * SPA Meta Boxes - Admin formuláre pre CPT (EMERGENCY - bez pricing)
 * @package Samuel Piasecký ACADEMY
 * @version 3.2.0 - OPRAVA: Removed broken pricing meta box
 */

if (!defined('ABSPATH')) {
    exit;
}

/* ============================================================
   PRIDANIE META BOXOV
   ============================================================ */
add_action('add_meta_boxes', 'spa_add_all_meta_boxes');
function spa_add_all_meta_boxes() {
    
    // PROGRAMY (spa_group)
    add_meta_box('spa_group_details', '🤸 Detaily programu', 'spa_group_meta_box', 'spa_group', 'normal', 'high');
    add_meta_box('spa_group_schedule', '📅 Rozvrh programu', 'spa_group_schedule_meta_box', 'spa_group', 'normal', 'high');
    
    // PRICING META BOX BOL ODSTRÁNENÝ - BUDE OPRAVENÝ NESKÔR
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
   META BOX: DETAILY PROGRAMU (spa_group)
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
            <p class="spa-help">Odporúčaný vek účastníkov (napr. 5-7 rokov)</p>
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

/* ============================================================
   META BOX: ROZVRH PROGRAMU
   ============================================================ */

function spa_group_schedule_meta_box($post) {
    wp_nonce_field('spa_save_group_schedule', 'spa_group_schedule_nonce');
    
    $schedule_json = get_post_meta($post->ID, 'spa_schedule', true);
    $schedule = $schedule_json ? json_decode($schedule_json, true) : [];
    
    if (!is_array($schedule)) {
        $schedule = [];
    }
    
    $days = [
        'monday' => '🟦 Pondelok',
        'tuesday' => '🟩 Utorok',
        'wednesday' => '🟪 Streda',
        'thursday' => '🟨 Štvrtok',
        'friday' => '🟧 Piatok',
        'saturday' => '🟥 Sobota',
        'sunday' => '⚪ Nedeľa'
    ];
    
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
                    
                    echo '<select name="spa_schedule[' . $index . '][day]" required>';
                    echo '<option value="">-- Vyber deň --</option>';
                    foreach ($days as $day_key => $day_label) {
                        $selected = ($day === $day_key) ? 'selected' : '';
                        echo '<option value="' . esc_attr($day_key) . '" ' . $selected . '>' . esc_html($day_label) . '</option>';
                    }
                    echo '</select>';
                    
                    echo '<div><label style="font-size:12px;color:#666;">od</label>';
                    echo '<select name="spa_schedule[' . $index . '][from]" required>';
                    echo '<option value="">--:--</option>';
                    foreach ($times as $time_val => $time_label) {
                        $selected = ($from === $time_val) ? 'selected' : '';
                        echo '<option value="' . esc_attr($time_val) . '" ' . $selected . '>' . esc_html($time_label) . '</option>';
                    }
                    echo '</select></div>';
                    
                    echo '<div><label style="font-size:12px;color:#666;">do</label>';
                    echo '<select name="spa_schedule[' . $index . '][to]" required>';
                    echo '<option value="">--:--</option>';
                    foreach ($times as $time_val => $time_label) {
                        $selected = ($to === $time_val) ? 'selected' : '';
                        echo '<option value="' . esc_attr($time_val) . '" ' . $selected . '>' . esc_html($time_label) . '</option>';
                    }
                    echo '</select></div>';
                    
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
    
    <script>
    var spa_schedule_counter = <?php echo !empty($schedule) ? max(array_keys($schedule)) + 1 : 0; ?>;
    var spa_times_json = <?php echo json_encode($times); ?>;
    var spa_days_json = <?php echo json_encode($days); ?>;
    
    function spa_add_schedule_row() {
        var container = document.getElementById('spa-schedule-container');
        
        var dayOptions = '<option value="">-- Vyber deň --</option>';
        Object.entries(spa_days_json).forEach(([key, label]) => {
            dayOptions += '<option value="' + key + '">' + label + '</option>';
        });
        
        var timeOptions = '<option value="">--:--</option>';
        Object.entries(spa_times_json).forEach(([val, label]) => {
            timeOptions += '<option value="' + val + '">' + label + '</option>';
        });
        
        var newRow = document.createElement('div');
        newRow.className = 'spa-schedule-item';
        newRow.innerHTML = '<select name="spa_schedule[' + spa_schedule_counter + '][day]" required>' + dayOptions + '</select>' +
                          '<div><label style="font-size:12px;color:#666;">od</label><select name="spa_schedule[' + spa_schedule_counter + '][from]" required>' + timeOptions + '</select></div>' +
                          '<div><label style="font-size:12px;color:#666;">do</label><select name="spa_schedule[' + spa_schedule_counter + '][to]" required>' + timeOptions + '</select></div>' +
                          '<button type="button" class="remove-btn" onclick="this.parentElement.remove();">Odstrániť</button>';
        
        container.appendChild(newRow);
        spa_schedule_counter++;
    }
    </script>
    
    <?php
}

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
    
    if (isset($_POST['spa_schedule']) && is_array($_POST['spa_schedule'])) {
        $schedule = [];
        
        foreach ($_POST['spa_schedule'] as $index => $item) {
            if (empty($item['day']) || empty($item['from']) || empty($item['to'])) {
                continue;
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
   META BOX: CENNÍK PROGRAMU (JEDNODUCHÝ FORMÁT)
   ============================================================ */

function spa_group_pricing_meta_box($post) {
    wp_nonce_field('spa_save_group_pricing', 'spa_group_pricing_nonce');
    
    $pricing_seasons = get_post_meta($post->ID, 'spa_pricing_seasons', true);
    if (!is_array($pricing_seasons)) {
        $pricing_seasons = array(
            'sep_dec' => array('1x' => 0, '2x' => 0, '3x' => 0),
            'jan_mar' => array('1x' => 0, '2x' => 0, '3x' => 0),
            'apr_jun' => array('1x' => 0, '2x' => 0, '3x' => 0),
            'jul_aug' => array('1x' => 0, '2x' => 0, '3x' => 0)
        );
    }
    
    echo '<table style="width:100%; border-collapse:collapse; margin-bottom:20px;">';
    echo '<tr style="background:#f0f0f0;">';
    echo '<th style="padding:12px; border:1px solid #ddd; text-align:left; font-weight:600;">Sezóna</th>';
    echo '<th style="padding:12px; border:1px solid #ddd; text-align:center; font-weight:600;">1x týždenne (€)</th>';
    echo '<th style="padding:12px; border:1px solid #ddd; text-align:center; font-weight:600;">2x týždenne (€)</th>';
    echo '<th style="padding:12px; border:1px solid #ddd; text-align:center; font-weight:600;">3x týždenne (€)</th>';
    echo '</tr>';
    
    $seasons = array(
        'sep_dec' => '🍂 September - December (09-12)',
        'jan_mar' => '❄️ Január - Marec (01-03)',
        'apr_jun' => '🌱 Apríl - Jún (04-06)',
        'jul_aug' => '☀️ Júl - August (07-08)'
    );
    
    foreach ($seasons as $key => $label) {
        $data = isset($pricing_seasons[$key]) ? $pricing_seasons[$key] : array();
        echo '<tr>';
        echo '<td style="padding:12px; border:1px solid #ddd;"><strong>' . esc_html($label) . '</strong></td>';
        echo '<td style="padding:12px; border:1px solid #ddd; text-align:center;"><input type="number" name="spa_pricing_seasons[' . esc_attr($key) . '][1x]" value="' . esc_attr(isset($data['1x']) ? $data['1x'] : 0) . '" step="0.01" min="0" style="width:120px; padding:8px; border:1px solid #ddd; border-radius:4px;"></td>';
        echo '<td style="padding:12px; border:1px solid #ddd; text-align:center;"><input type="number" name="spa_pricing_seasons[' . esc_attr($key) . '][2x]" value="' . esc_attr(isset($data['2x']) ? $data['2x'] : 0) . '" step="0.01" min="0" style="width:120px; padding:8px; border:1px solid #ddd; border-radius:4px;"></td>';
        echo '<td style="padding:12px; border:1px solid #ddd; text-align:center;"><input type="number" name="spa_pricing_seasons[' . esc_attr($key) . '][3x]" value="' . esc_attr(isset($data['3x']) ? $data['3x'] : 0) . '" step="0.01" min="0" style="width:120px; padding:8px; border:1px solid #ddd; border-radius:4px;"></td>';
        echo '</tr>';
    }
    
    echo '</table>';
    echo '<p style="color:#666; font-size:12px;">💡 Nastav ceny (€/týždeň) pre každú sezónu a frekvenciu. Príklad: September-December, 1x = 60€; Január-Marec, 1x = 66€</p>';
}


/*SAVE HANDLER*/

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
    
    if (isset($_POST['spa_pricing_seasons']) && is_array($_POST['spa_pricing_seasons'])) {
        $pricing_seasons = array();
        
        foreach ($_POST['spa_pricing_seasons'] as $season => $frequencies) {
            $season = sanitize_key($season);
            $pricing_seasons[$season] = array();
            
            if (is_array($frequencies)) {
                foreach ($frequencies as $freq => $price) {
                    $freq = sanitize_key($freq);
                    $pricing_seasons[$season][$freq] = floatval($price);
                }
            }
        }
        
        update_post_meta($post_id, 'spa_pricing_seasons', $pricing_seasons);
    }
}
/* ============================================================
   META BOX: DETAILY REGISTRÁCIE (spa_registration)
   ============================================================ */

function spa_registration_meta_box($post) {
    wp_nonce_field('spa_save_registration', 'spa_registration_nonce');
    
    // Ziskaj meta údaje
    $parent_user_id = get_post_meta($post->ID, 'parent_user_id', true);
    $client_user_id = get_post_meta($post->ID, 'client_user_id', true);
    $spa_group_id = get_post_meta($post->ID, 'spa_group_id', true);
    $registration_day = get_post_meta($post->ID, 'registration_day', true);
    $registration_time = get_post_meta($post->ID, 'registration_time', true);
    $status = get_post_meta($post->ID, 'status', true);
    $variable_symbol = get_post_meta($post->ID, 'variable_symbol', true);
    $pin = get_post_meta($post->ID, 'pin', true);
    
    // Načítaj dostupné programy
    $programs = get_posts([
        'post_type' => 'spa_group',
        'posts_per_page' => -1,
        'orderby' => 'title',
        'order' => 'ASC'
    ]);
    
    // Načítaj dostupných rodičov
    $parents = get_users(['role' => 'spa_parent', 'orderby' => 'display_name']);
    
    // Načítaj dostupné deti
    $children = get_users(['role' => 'spa_child', 'orderby' => 'display_name']);
    
    // Mapy dní a statusov
    $days_map = [
        'mo' => 'Pondelok',
        'tu' => 'Utorok',
        'we' => 'Streda',
        'th' => 'Štvrtok',
        'fr' => 'Piatok',
        'sa' => 'Sobota',
        'su' => 'Nedeľa'
    ];
    
    $statuses = [
        'pending' => 'Čaká na schválenie',
        'awaiting_payment' => 'Čaká na platbu',
        'partially_paid' => 'Čiastočne zaplatené',
        'approved' => 'Schválené',
        'active' => 'Aktívny',
        'blocked' => 'Blokované',
        'cancelled' => 'Zrušené',
        'completed' => 'Zaregistrované'
    ];
    
    ?>
    <style>
    .spa-meta-row { display: flex; margin-bottom: 15px; align-items: flex-start; }
    .spa-meta-row label { width: 150px; font-weight: 600; padding-top: 8px; }
    .spa-meta-row .spa-field { flex: 1; }
    .spa-meta-row input[type="text"], .spa-meta-row input[type="time"], .spa-meta-row select { width: 100%; max-width: 400px; padding: 8px; }
    .spa-help { color: #666; font-size: 12px; margin-top: 4px; }
    .spa-info { background: #e7f3ff; padding: 10px; border-left: 3px solid #0073aa; margin-bottom: 15px; }
    .spa-info strong { display: block; margin-bottom: 5px; }
    </style>
    
    <div class="spa-info">
        <strong>👶 Dieťa/Klient:</strong>
        <?php 
        if ($client_user_id) {
            $child = get_userdata($client_user_id);
            if ($child) {
                echo esc_html($child->first_name . ' ' . $child->last_name . ' (' . $child->user_email . ')');
            } else {
                echo '<span style="color:red;">Neznáme ID: ' . intval($client_user_id) . '</span>';
            }
        } else {
            echo '<span style="color:red;">Nie je nastavené</span>';
        }
        ?>
    </div>
    
    <div class="spa-meta-row">
        <label for="spa_parent_id">👨‍👩‍👧 Rodič:</label>
        <div class="spa-field">
            <select name="spa_parent_id" id="spa_parent_id">
                <option value="">-- Vyberte rodiča --</option>
                <?php foreach ($parents as $parent) : ?>
                    <option value="<?php echo $parent->ID; ?>" <?php selected($parent_user_id, $parent->ID); ?>>
                        <?php echo esc_html($parent->first_name . ' ' . $parent->last_name . ' (' . $parent->user_email . ')'); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <p class="spa-help">Vyberte alebo zmeňte rodiča</p>
        </div>
    </div>
    
    <div class="spa-meta-row">
        <label for="spa_group_id">🏋️ Program:</label>
        <div class="spa-field">
            <select name="spa_group_id" id="spa_group_id">
                <option value="">-- Vyberte program --</option>
                <?php foreach ($programs as $program) : ?>
                    <option value="<?php echo $program->ID; ?>" <?php selected($spa_group_id, $program->ID); ?>>
                        <?php echo esc_html($program->post_title); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <p class="spa-help">Zmeňte tréningový program</p>
        </div>
    </div>
    
    <div class="spa-meta-row">
        <label for="spa_day">⏰ Deň v týždni:</label>
        <div class="spa-field">
            <select name="spa_day" id="spa_day">
                <option value="">-- Vyberte deň --</option>
                <?php foreach ($days_map as $key => $label) : ?>
                    <option value="<?php echo $key; ?>" <?php selected($registration_day, $key); ?>>
                        <?php echo esc_html($label); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <p class="spa-help">Zmeňte deň tréningu</p>
        </div>
    </div>
    
    <div class="spa-meta-row">
        <label for="spa_time">🕐 Čas:</label>
        <div class="spa-field">
            <input type="time" name="spa_time" id="spa_time" value="<?php echo esc_attr($registration_time); ?>">
            <p class="spa-help">Zmeňte čas začiatku tréningu</p>
        </div>
    </div>
    
    <div class="spa-meta-row">
        <label for="spa_vs">🔢 Variabilný symbol:</label>
        <div class="spa-field">
            <input type="text" name="spa_vs" id="spa_vs" value="<?php echo esc_attr($variable_symbol); ?>" placeholder="napr. 0123">
            <p class="spa-help">Bankovný variabilný symbol pre platby</p>
        </div>
    </div>
    
    <div class="spa-meta-row">
        <label for="spa_status">📊 Status:</label>
        <div class="spa-field">
            <select name="spa_status" id="spa_status">
                <option value="">-- Vyberte status --</option>
                <?php foreach ($statuses as $key => $label) : ?>
                    <option value="<?php echo $key; ?>" <?php selected($status, $key); ?>>
                        <?php echo esc_html($label); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <p class="spa-help">Aktuálny stav registrácie</p>
        </div>
    </div>
    
    <?php
}

/* ============================================================
   SAVE META BOX: REGISTRÁCIA
   ============================================================ */

add_action('save_post_spa_registration', 'spa_save_registration_meta', 11, 2);

function spa_save_registration_meta($post_id, $post) {
    if (!isset($_POST['spa_registration_nonce'])) {
        return;
    }
    
    if (!wp_verify_nonce($_POST['spa_registration_nonce'], 'spa_save_registration')) {
        return;
    }
    
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    
    if (!current_user_can('edit_post', $post_id)) {
        return;
    }
    
    // Ulož meta
    if (isset($_POST['spa_parent_id'])) {
        update_post_meta($post_id, 'parent_user_id', intval($_POST['spa_parent_id']));
    }
    
    if (isset($_POST['spa_group_id'])) {
        update_post_meta($post_id, 'spa_group_id', intval($_POST['spa_group_id']));
    }
    
    if (isset($_POST['spa_day'])) {
        update_post_meta($post_id, 'registration_day', sanitize_text_field($_POST['spa_day']));
    }
    
    if (isset($_POST['spa_time'])) {
        update_post_meta($post_id, 'registration_time', sanitize_text_field($_POST['spa_time']));
    }
    
    if (isset($_POST['spa_vs'])) {
        update_post_meta($post_id, 'variable_symbol', sanitize_text_field($_POST['spa_vs']));
    }
    
    if (isset($_POST['spa_status'])) {
        update_post_meta($post_id, 'status', sanitize_text_field($_POST['spa_status']));
    }
}