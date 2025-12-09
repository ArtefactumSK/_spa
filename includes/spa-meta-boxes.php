<?php
/**
 * SPA Meta Boxes - Admin formuláre
 * 
 * @package Samuel Piasecký ACADEMY
 * @version 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/* ==========================
   META BOX: Detaily programu (spa_group)
   ========================== */

add_action('add_meta_boxes', 'spa_add_meta_boxes');

function spa_add_meta_boxes() {
    
    // Meta box pre spa_group
    add_meta_box(
        'spa_group_details',
        'Detaily programu (ikona, cena, tréner, rozvrh)',
        'spa_group_meta_html',
        'spa_group',
        'normal',
        'high'
    );
    
    // Meta box pre spa_hall_block
    add_meta_box(
        'spa_hall_block_details',
        'Detaily obsadenia haly',
        'spa_hall_block_meta_html',
        'spa_hall_block',
        'normal',
        'high'
    );
}

/* ==========================
   FUNKCIA: Meta box pre spa_group
   ========================== */

function spa_group_meta_html($post) {
    wp_nonce_field('spa_group_meta_nonce', 'spa_group_meta_nonce_field');
    
    // Načítaj uložené dáta
    $icon = get_post_meta($post->ID, 'spa_icon', true);
    $price = get_post_meta($post->ID, 'spa_price', true);
    $trainer_id = get_post_meta($post->ID, 'spa_trainer_id', true);
    $schedule_json = get_post_meta($post->ID, 'spa_schedule', true);
    $schedule = $schedule_json ? json_decode($schedule_json, true) : [];
    
    // SVG ikony - zoznam dostupných súborov
    $svg_dir = WP_CONTENT_DIR . '/uploads/spa-icons/';
    $svg_files = [];
    
    if (is_dir($svg_dir)) {
        $files = scandir($svg_dir);
        foreach ($files as $file) {
            if (pathinfo($file, PATHINFO_EXTENSION) === 'svg') {
                $svg_files[] = $file;
            }
        }
    }
    
    ?>
    <div class="spa-meta-box-wrapper">
        
        <!-- NÁVOD -->
        <div class="spa-notice spa-notice-info">
            <p><strong>💡 Návod:</strong><br>Ako nadpis uveď názov nového programu. Ak za názvom programu uvedieš lomítko "<strong>/</strong>", údaje za lomítkom sú vyhradené pre zobrazenie dňa a času tréningu.<br>Následne vyber ikonu pre tento program a vyplň všetky údaje o programe. </p>
        </div>
        
        <table class="form-table">
            
            <!-- SVG IKONA -->
            <tr>
                <th><label><strong>Ikona programu:</strong></label></th>
                <td>
                    <?php if (empty($svg_files)) : ?>
                        <p style="color: #d63638;">
                            ⚠️ Žiadne SVG ikony nenájdené v <code>/wp-content/uploads/spa-icons/</code>
                        </p>
                        <p><small>Nahraj SVG súbory do tohto priečinka cez FTP alebo cPanel File Manager.</small></p>
                        <input type="hidden" name="spa_icon" value="">
                    <?php else : ?>
                        <select name="spa_icon" id="spa_icon_select" style="width: 300px; padding: 8px;">
                            <option value="">— Bez ikony —</option>
                            <?php foreach ($svg_files as $file) : 
                                $selected = ($icon === $file) ? ' selected' : '';
                                $name = pathinfo($file, PATHINFO_FILENAME);
                            ?>
                                <option value="<?php echo esc_attr($file); ?>"<?php echo $selected; ?>>
                                    <?php echo esc_html($name); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        
                        <button type="button" class="button" id="spa_icon_preview_btn" style="margin-left: 8px;">
                            Náhľad
                        </button>
                        <span style="font-size:80%;display:block;">Ak potrebuješ upraviť/ pridať ikony - obráť sa na autora <strong><a href="mailto:support@artefactum.sk">Artefactum</a></strong>.</span>
                        <div id="spa_icon_preview" style="margin-top: 12px; padding: 12px; background: white; border: 1px solid #ddd; border-radius: 4px; min-height: 60px; display: flex; align-items: center; justify-content: center;">
                            <?php if ($icon && file_exists($svg_dir . $icon)) : ?>
                                <?php echo file_get_contents($svg_dir . $icon); ?>
                            <?php else : ?>
                                <span style="color: #999;">Vyber ikonu pre náhľad</span>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </td>
            </tr>
            
            <!-- CENA -->
            <tr>
                <th><label><strong>Cena programu (€):</strong></label></th>
                <td>
                    <input type="number" 
                           step="0.01" 
                           name="spa_price" 
                           value="<?php echo esc_attr($price); ?>" 
                           style="width: 150px; padding: 8px;"
                           placeholder="50.00">
                    <p class="description">Mesačný poplatok za tento program</p>
                </td>
            </tr>
            
            <!-- TRÉNER -->
            <tr>
                <th><label><strong>Tréner programu:</strong></label></th>
                <td>
                    <?php
                    $trainers = get_users(['role' => 'spa_trainer']);
                    
                    if (empty($trainers)) : ?>
                        <p style="color: #d63638;">
                            ⚠️ Žiadni tréneri v systéme. Najprv vytvor používateľa s rolou "Tréner (SPA)".
                        </p>
                    <?php else : ?>
                        <select name="spa_trainer_id" style="width: 300px; padding: 8px;">
                            <option value="">— Vyber trénera —</option>
                            <?php foreach ($trainers as $trainer) : 
                                $sel = ($trainer_id == $trainer->ID) ? ' selected' : '';
                            ?>
                                <option value="<?php echo $trainer->ID; ?>"<?php echo $sel; ?>>
                                    <?php echo esc_html($trainer->display_name); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    <?php endif; ?>
                </td>
            </tr>
            
            <!-- POPIS PROGRAMU  -->
            <tr>
                <th><label><strong>Popis tréningu:</strong></label></th>
                <td>
                    <?php
                    $description = get_post_meta($post->ID, 'spa_description', true);
                    
                    wp_editor($description, 'spa_description', [
                        'textarea_name' => 'spa_description',
                        'textarea_rows' => 5,
                        'media_buttons' => false,
                        'teeny' => true,
                        'quicktags' => false
                    ]);
                    ?>
                    <p class="description">Krátky popis programu (zobrazí sa na frontende pod názvom)</p>
                </td>
            </tr>
            
        </table>
        
        <hr style="margin: 24px 0;">
        
        <!-- ROZVRH -->
        <h3>📅 Rozvrh tréningu</h3>
        <p class="description">Pridaj dni a časy kedy prebieha tento program.</p>
        
        <div id="spa-schedule-repeater" style="margin-top: 16px;">
            <?php 
            if (!empty($schedule) && is_array($schedule)) {
                foreach ($schedule as $i => $row) {
                    echo spa_schedule_row_html($i, $row['day'] ?? '', $row['time'] ?? '');
                }
            } else {
                echo spa_schedule_row_html(0, '', '');
            }
            ?>
        </div>
        
        <p style="margin-top: 16px;">
            <button type="button" class="button" id="spa-add-schedule-row">
                + Pridať deň
            </button>
            <button type="button" class="button" id="spa-remove-last-row">
                − Odstrániť posledný
            </button>
        </p>
        
    </div>
    
    <!-- JavaScript pre preview + repeater -->
    <script>
    (function($){
        // SVG Preview
        $('#spa_icon_preview_btn').on('click', function(){
            var filename = $('#spa_icon_select').val();
            var preview = $('#spa_icon_preview');
            
            preview.html('<span style="color:#999;">Načítavam...</span>');
            
            if (!filename) {
                preview.html('<span style="color:#999;">Vyber ikonu</span>');
                return;
            }
            
            $.ajax({
                url: ajaxurl,
                data: {
                    action: 'spa_preview_svg',
                    file: filename
                },
                success: function(response) {
                    preview.html(response);
                },
                error: function() {
                    preview.html('<span style="color:#d63638;">Chyba načítania</span>');
                }
            });
        });
        
        // Repeater - Pridať deň
        $('#spa-add-schedule-row').on('click', function(){
            var repeater = $('#spa-schedule-repeater');
            var index = repeater.children().length;
            var template = '<?php echo addslashes(str_replace(["\n", "\r"], '', spa_schedule_row_html_js())); ?>';
            template = template.replace(/__INDEX__/g, index);
            repeater.append(template);
        });
        
        // Repeater - Odstrániť riadok
        $('#spa-remove-last-row').on('click', function(){
            var repeater = $('#spa-schedule-repeater');
            if (repeater.children().length > 1) {
                repeater.children().last().remove();
            }
        });
    })(jQuery);
    </script>
    
    <!-- CSS -->
    <style>
    .spa-meta-box-wrapper {
        background: #f9f9fb;
        padding: 20px;
        border-radius: 9px;
    }
    .spa-notice {
        padding: 1px 16px;
        border-left: 4px solid #f60;
        background: #e3f2fd;
        margin-bottom: 20px;
        border-radius: 9px;
    }
    .spa-sched-row {
        border: 1px solid #e6e6e6;
        padding: 12px;
        margin-bottom: 8px;
        border-radius: 9px;
        background: white;
        display: flex;
        gap: 12px;
        align-items: center;
    }
    .spa-sched-row select {
        width: 160px;
        padding: 6px;
    }
    .spa-sched-row input[type="time"] {
        width: 120px;
        padding: 6px;
    }
    #spa_icon_preview svg {
        max-width: 80px;
        max-height: 80px;
        fill: currentColor;
    }
    </style>
    <?php
}

/* ==========================
   HELPER: Rozvrh riadok (PHP)
   ========================== */

function spa_schedule_row_html($index, $day, $time) {
    $days = spa_get_days_array();
    
    $html = '<div class="spa-sched-row">';
    $html .= '<label><strong>Deň</strong><br>';
    $html .= '<select name="spa_schedule[' . $index . '][day]">';
    $html .= '<option value="">— vyber —</option>';
    
    foreach ($days as $value => $label) {
        $sel = ($value === $day) ? ' selected' : '';
        $html .= '<option value="' . esc_attr($value) . '"' . $sel . '>' . esc_html($label) . '</option>';
    }
    
    $html .= '</select></label>';
    $html .= '<label><strong>Čas</strong><br>';
    $html .= '<input type="time" name="spa_schedule[' . $index . '][time]" value="' . esc_attr($time) . '">';
    $html .= '</label>';
    $html .= '</div>';
    
    return $html;
}

/* ==========================
   HELPER: Rozvrh riadok (JS template)
   ========================== */

function spa_schedule_row_html_js() {
    $days = spa_get_days_array();
    
    $html = '<div class="spa-sched-row">';
    $html .= '<label><strong>Deň</strong><br>';
    $html .= '<select name="spa_schedule[__INDEX__][day]">';
    $html .= '<option value="">— vyber —</option>';
    
    foreach ($days as $value => $label) {
        $html .= '<option value="' . esc_attr($value) . '">' . esc_html($label) . '</option>';
    }
    
    $html .= '</select></label>';
    $html .= '<label><strong>Čas</strong><br>';
    $html .= '<input type="time" name="spa_schedule[__INDEX__][time]" value="">';
    $html .= '</label>';
    $html .= '</div>';
    
    return $html;
}

/* ==========================
   AJAX: SVG Preview
   ========================== */

add_action('wp_ajax_spa_preview_svg', 'spa_preview_svg_ajax');

function spa_preview_svg_ajax() {
    $file = sanitize_file_name($_GET['file'] ?? '');
    $svg_dir = WP_CONTENT_DIR . '/uploads/spa-icons/';
    $path = $svg_dir . $file;
    
    if (!$file || !file_exists($path) || pathinfo($path, PATHINFO_EXTENSION) !== 'svg') {
        echo '<span style="color:#d63638;">Neplatný súbor</span>';
        wp_die();
    }
    
    echo file_get_contents($path);
    wp_die();
}

/* ==========================
   SAVE: spa_group meta
   ========================== */

add_action('save_post_spa_group', 'spa_save_group_meta', 10, 1);

function spa_save_group_meta($post_id) {
    
    // Nonce check
    if (!isset($_POST['spa_group_meta_nonce_field']) || 
        !wp_verify_nonce($_POST['spa_group_meta_nonce_field'], 'spa_group_meta_nonce')) {
        return;
    }
    
    // Autosave check
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    
    // Permissions check
    if (!current_user_can('edit_post', $post_id)) {
        return;
    }
    
    // SVG ICON
    if (isset($_POST['spa_icon'])) {
        update_post_meta($post_id, 'spa_icon', sanitize_file_name($_POST['spa_icon']));
    } else {
        delete_post_meta($post_id, 'spa_icon');
    }
    
    // PRICE
    if (isset($_POST['spa_price'])) {
        $price = sanitize_text_field($_POST['spa_price']);
        update_post_meta($post_id, 'spa_price', floatval(str_replace(',', '.', $price)));
    } else {
        delete_post_meta($post_id, 'spa_price');
    }
    
    // TRAINER
    if (isset($_POST['spa_trainer_id'])) {
        update_post_meta($post_id, 'spa_trainer_id', intval($_POST['spa_trainer_id']));
    } else {
        delete_post_meta($post_id, 'spa_trainer_id');
    }
    
    // DESCRIPTION
    if (isset($_POST['spa_description'])) {
        update_post_meta($post_id, 'spa_description', wp_kses_post($_POST['spa_description']));
    } else {
        delete_post_meta($post_id, 'spa_description');
    }
    
    // SCHEDULE
    $schedule_input = $_POST['spa_schedule'] ?? [];
    $schedule = [];
    
    if (is_array($schedule_input)) {
        foreach ($schedule_input as $row) {
            $day = sanitize_text_field($row['day'] ?? '');
            $time = sanitize_text_field($row['time'] ?? '');
            
            if ($day === '' && $time === '') {
                continue;
            }
            
            $schedule[] = [
                'day' => $day,
                'time' => $time
            ];
        }
    }
    
    update_post_meta($post_id, 'spa_schedule', wp_json_encode($schedule, JSON_UNESCAPED_UNICODE));
}

/* ==========================
   META BOX: Obsadenie haly (spa_hall_block)
   ========================== */

function spa_hall_block_meta_html($post) {
    wp_nonce_field('spa_hall_block_nonce', 'spa_hall_block_nonce_field');
    
    $place = get_post_meta($post->ID, 'block_place', true);
    $date = get_post_meta($post->ID, 'block_date', true);
    $time_from = get_post_meta($post->ID, 'block_time_from', true);
    $time_to = get_post_meta($post->ID, 'block_time_to', true);
    $reason = get_post_meta($post->ID, 'block_reason', true);
    $show_on_calendar = get_post_meta($post->ID, 'show_on_calendar', true);
    
    ?>
    <div class="spa-meta-box-wrapper">
        
        <div class="spa-notice spa-notice-info">
            <p><strong>💡 Návod:</strong> Vyplň dátum a čas kedy je hala obsadená. Tréningy v tomto čase sa automaticky zobrazia ako zrušené.</p>
        </div>
        
        <table class="form-table">
            
            <tr>
                <th><label><strong>Miesto (hala):</strong></label></th>
                <td>
                    <select name="block_place" style="width: 300px; padding: 8px;" required>
                        <option value="">— Vyber halu —</option>
                        <option value="malacky" <?php selected($place, 'malacky'); ?>>Hala Malacky</option>
                        <option value="kosice" <?php selected($place, 'kosice'); ?>>Hala Košice</option>
                    </select>
                </td>
            </tr>
            
            <tr>
                <th><label><strong>Dátum:</strong></label></th>
                <td>
                    <input type="date" 
                           name="block_date" 
                           value="<?php echo esc_attr($date); ?>" 
                           style="width: 300px; padding: 8px;"
                           required>
                </td>
            </tr>
            
            <tr>
                <th><label><strong>Čas OD:</strong></label></th>
                <td>
                    <input type="time" 
                           name="block_time_from" 
                           value="<?php echo esc_attr($time_from); ?>" 
                           style="width: 150px; padding: 8px;">
                    <p class="description">Nepovinné - ak nevyplníš, platí pre celý deň</p>
                </td>
            </tr>
            
            <tr>
                <th><label><strong>Čas DO:</strong></label></th>
                <td>
                    <input type="time" 
                           name="block_time_to" 
                           value="<?php echo esc_attr($time_to); ?>" 
                           style="width: 150px; padding: 8px;">
                </td>
            </tr>
            
            <tr>
                <th><label><strong>Dôvod:</strong></label></th>
                <td>
                    <input type="text" 
                           name="block_reason" 
                           value="<?php echo esc_attr($reason); ?>" 
                           placeholder="Napr: Narodeniny, Súkromná akcia..."
                           style="width: 100%; padding: 8px;">
                </td>
            </tr>
            
            <tr>
                <th><label><strong>Zobraziť v kalendári:</strong></label></th>
                <td>
                    <label>
                        <input type="checkbox" 
                               name="show_on_calendar" 
                               value="1"
                               <?php checked($show_on_calendar, '1'); ?>>
                        Áno, zobraziť toto obsadenie verejne v kalendári
                    </label>
                    <p class="description">Ak odškrtneš, obsadenie bude interné (viditeľné len v admin)</p>
                </td>
            </tr>
            
        </table>
        
        <div style="background: #fff3e0; padding: 12px; border-left: 4px solid #ff9800; margin-top: 20px; border-radius: 4px;">
            <strong>⚠️ Upozornenie:</strong> Klienti s tréningom v tomto čase dostanú automaticky notifikáciu o zrušení.
        </div>
        
    </div>
    <?php
}

/* ==========================
   SAVE: spa_hall_block meta
   ========================== */

add_action('save_post_spa_hall_block', 'spa_save_hall_block_meta', 10, 1);

function spa_save_hall_block_meta($post_id) {
    
    if (!isset($_POST['spa_hall_block_nonce_field']) || 
        !wp_verify_nonce($_POST['spa_hall_block_nonce_field'], 'spa_hall_block_nonce')) {
        return;
    }
    
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    
    if (!current_user_can('edit_post', $post_id)) {
        return;
    }
    
    $fields = ['block_place', 'block_date', 'block_time_from', 'block_time_to', 'block_reason'];
    
    foreach ($fields as $field) {
        if (isset($_POST[$field])) {
            update_post_meta($post_id, $field, sanitize_text_field($_POST[$field]));
        }
    }
    
    $show = isset($_POST['show_on_calendar']) ? '1' : '0';
    update_post_meta($post_id, 'show_on_calendar', $show);
}