<?php
/**
 * SPA Meta Boxes - Admin formulare pre CPT
 * @package Samuel Piasecky ACADEMY
 * @version 3.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/* ==========================
   REGISTRACIA - MENU REDIRECT
   ========================== */

add_action('admin_menu', 'spa_change_add_registration_link', 999);
function spa_change_add_registration_link() {
    global $submenu;
    if (isset($submenu['edit.php?post_type=spa_registration'])) {
        foreach ($submenu['edit.php?post_type=spa_registration'] as $key => $item) {
            if (strpos($item[2], 'post-new.php') !== false) {
                $submenu['edit.php?post_type=spa_registration'][$key][2] = home_url('/registracia/');
            }
        }
    }
}

add_action('admin_footer', 'spa_add_new_registration_js');
function spa_add_new_registration_js() {
    $url = esc_url(home_url('/registracia/'));
    ?>
    <script type="text/javascript">
    (function() {
        var targetUrl = '<?php echo $url; ?>';
        var addBtn = document.querySelector('.page-title-action[href*="post-new.php?post_type=spa_registration"]');
        if (addBtn) {
            addBtn.setAttribute('href', targetUrl);
            addBtn.setAttribute('target', '_blank');
        }
        var menuLinks = document.querySelectorAll('#adminmenu a[href*="post-new.php?post_type=spa_registration"]');
        menuLinks.forEach(function(link) {
            link.setAttribute('href', targetUrl);
            link.setAttribute('target', '_blank');
        });
    })();
    </script>
    <?php
}

add_action('admin_init', 'spa_redirect_new_registration');
function spa_redirect_new_registration() {
    global $pagenow;
    if ($pagenow === 'post-new.php' && isset($_GET['post_type']) && $_GET['post_type'] === 'spa_registration') {
        wp_redirect(home_url('/registracia/'));
        exit;
    }
}

/* ==========================
   SKRY TITLE EDITOR pre registracie
   ========================== */

add_action('admin_head-post.php', 'spa_hide_title_editor');
add_action('admin_head-post-new.php', 'spa_hide_title_editor');
function spa_hide_title_editor() {
    global $typenow;
    if ($typenow === 'spa_registration') {
        echo '<style>#titlediv { display: none !important; }</style>';
    }
}

/* ==========================
   ADD META BOXES
   ========================== */

add_action('add_meta_boxes', 'spa_add_all_meta_boxes');
function spa_add_all_meta_boxes() {
    
    // REGISTRACIE
    add_meta_box(
        'spa_registration_details',
        'Detaily registracie',
        'spa_registration_details_callback',
        'spa_registration',
        'normal',
        'high'
    );
    
    // SKUPINY TRENINGOV - KOMPLETNY META BOX
    add_meta_box(
        'spa_group_details',
        'Detaily programu',
        'spa_group_details_callback',
        'spa_group',
        'normal',
        'high'
    );
}

/* ==========================
   SKUPINY TRENINGOV - META BOX
   SVG ikona, cena, kapacita, rozvrh, treneri
   ========================== */

function spa_group_details_callback($post) {
    wp_nonce_field('spa_save_group', 'spa_group_nonce');
    
    // Nacitaj ulozene data
    $icon = get_post_meta($post->ID, 'spa_icon', true);
    $price = get_post_meta($post->ID, 'spa_price', true);
    $capacity = get_post_meta($post->ID, 'spa_capacity', true);
    $schedule_json = get_post_meta($post->ID, 'spa_schedule', true);
    $schedule = $schedule_json ? json_decode($schedule_json, true) : [];
    $trainer_ids = get_post_meta($post->ID, 'spa_trainer_ids', true);
    if (!is_array($trainer_ids)) {
        $trainer_ids = $trainer_ids ? array($trainer_ids) : array();
    }
    
    // SVG ikony z adresara
    $svg_dir = WP_CONTENT_DIR . '/uploads/spa-icons/';
    $svg_files = array();
    if (is_dir($svg_dir)) {
        $files = scandir($svg_dir);
        foreach ($files as $file) {
            if (pathinfo($file, PATHINFO_EXTENSION) === 'svg') {
                $svg_files[] = $file;
            }
        }
    }
    
    // Treneri
    $trainers = get_users(array('role' => 'spa_trainer', 'orderby' => 'display_name'));
    
    // Dni tyzdna
    $days = array(
        'monday' => 'Pondelok',
        'tuesday' => 'Utorok',
        'wednesday' => 'Streda',
        'thursday' => 'Stvrtok',
        'friday' => 'Piatok',
        'saturday' => 'Sobota',
        'sunday' => 'Nedela'
    );
    
    ?>
    <style>
    .spa-meta-section { margin-bottom: 25px; padding: 15px; background: #f9f9f9; border: 1px solid #ddd; border-radius: 4px; }
    .spa-meta-section h4 { margin: 0 0 15px 0; padding-bottom: 10px; border-bottom: 1px solid #ddd; color: #23282d; }
    .spa-meta-row { display: flex; align-items: flex-start; margin-bottom: 12px; }
    .spa-meta-row label { width: 140px; font-weight: 600; padding-top: 6px; }
    .spa-meta-row .spa-field { flex: 1; }
    .spa-icon-preview { width: 80px; height: 80px; border: 1px solid #ddd; border-radius: 4px; display: flex; align-items: center; justify-content: center; background: #fff; margin-left: 10px; }
    .spa-icon-preview svg { width: 69px; height: 69px; }
    .spa-schedule-row { display: flex; gap: 10px; margin-bottom: 8px; align-items: center; }
    .spa-schedule-row select, .spa-schedule-row input { padding: 6px 10px; }
    .spa-trainer-checkboxes label { display: block; margin-bottom: 5px; font-weight: normal; }
    .spa-trainer-checkboxes input { margin-right: 8px; }
    .spa-help { color: #666; font-size: 12px; margin-top: 4px; }
    </style>
    
    <!-- SVG IKONA -->
    <div>        
        <div class="spa-meta-row">
            <label>🤸🏻‍♂️ Ikona programu:</label>
            <div class="spa-field" style="display: flex; align-items: center;">
                <?php if (empty($svg_files)) : ?>
                    <p style="color: #d63638; margin: 0;">
                        Žiadne ikony v adresári ikon
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
                    <div class="spa-icon-preview" id="spa_icon_preview">
                        <?php if ($icon && file_exists($svg_dir . $icon)) : ?>
                            <?php echo file_get_contents($svg_dir . $icon); ?>
                        <?php else : ?>
                            <span style="color:#999;">--</span>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- CENA A KAPACITA -->
    <div class="spa-meta-section">
        <h4>🎲 Cena programu a kapacita</h4>
        <div class="spa-meta-row">
            <label for="spa_price">💳 Cena (EUR):</label>
            <div class="spa-field">
                <input type="text" name="spa_price" id="spa_price" value="<?php echo esc_attr($price); ?>" style="width: 100px;">
                <span class="spa-help">napr. 30 alebo 45,50</span>
            </div>
        </div>
        <div class="spa-meta-row">
            <label for="spa_capacity">𐀪𐀪 Kapacita:</label>
            <div class="spa-field">
                <input type="number" name="spa_capacity" id="spa_capacity" value="<?php echo esc_attr($capacity); ?>" style="width: 100px;" min="1">
                <span class="spa-help">max. počet účastníkov tréningu</span>
            </div>
        </div>    
    
    <!-- ROZVRH -->
        <br><br>
        <h4>📅 Rozvrh tréningov programu</h4>
        <div id="spa-schedule-repeater">
            <?php 
            if (!empty($schedule) && is_array($schedule)) {
                foreach ($schedule as $i => $row) {
                    spa_schedule_row_html($i, $row, $days);
                }
            } else {
                spa_schedule_row_html(0, array(), $days);
            }
            ?>
        </div>
        <p style="margin-top: 10px;">
            <button type="button" class="button" id="spa-add-schedule">+ Pridať termín</button>
            <button type="button" class="button" id="spa-remove-schedule">- Odstrániť posledný</button>
        </p>
        
    <!-- TRENERI -->
        <br><br>
        <h4>👟 Pridelení tréneri</h4>
        <div class="spa-trainer-checkboxes">
            <?php if (empty($trainers)) : ?>
                <p style="color: #666;">Ziadni treneri nie su registrovani.</p>
            <?php else : ?>
                <?php foreach ($trainers as $trainer) : 
                    $name = trim($trainer->first_name . ' ' . $trainer->last_name);
                    if (empty($name)) $name = $trainer->display_name;
                    $checked = in_array($trainer->ID, $trainer_ids) ? 'checked' : '';
                ?>
                    <label>
                        <input type="checkbox" name="spa_trainer_ids[]" value="<?php echo $trainer->ID; ?>" <?php echo $checked; ?>>
                        <?php echo esc_html($name); ?>
                    </label>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        <p class="spa-help">Mozes vybrat viacerych trenerov pre jeden program.</p>
    </div>
    
    <!-- JAVASCRIPT -->
    <script>
    (function() {
        // SVG PREVIEW
        var iconSelect = document.getElementById('spa_icon_select');
        var iconPreview = document.getElementById('spa_icon_preview');
        
        if (iconSelect && iconPreview) {
            iconSelect.addEventListener('change', function() {
                var file = this.value;
                if (!file) {
                    iconPreview.innerHTML = '<span style="color:#999;">--</span>';
                    return;
                }
                fetch('<?php echo admin_url("admin-ajax.php"); ?>?action=spa_preview_svg&file=' + encodeURIComponent(file))
                    .then(function(r) { return r.text(); })
                    .then(function(svg) { iconPreview.innerHTML = svg; })
                    .catch(function() { iconPreview.innerHTML = '<span style="color:#d63638;">Chyba</span>'; });
            });
        }
        
        // SCHEDULE REPEATER
        var repeater = document.getElementById('spa-schedule-repeater');
        var addBtn = document.getElementById('spa-add-schedule');
        var remBtn = document.getElementById('spa-remove-schedule');
        
        if (addBtn) {
            addBtn.addEventListener('click', function() {
                var index = repeater.querySelectorAll('.spa-schedule-row').length;
                var row = document.createElement('div');
                row.className = 'spa-schedule-row';
                row.innerHTML = `
                    <select name="spa_schedule[${index}][day]">
                        <option value="">-- Den --</option>
                        <option value="monday">Pondelok</option>
                        <option value="tuesday">Utorok</option>
                        <option value="wednesday">Streda</option>
                        <option value="thursday">Stvrtok</option>
                        <option value="friday">Piatok</option>
                        <option value="saturday">Sobota</option>
                        <option value="sunday">Nedela</option>
                    </select>
                    <input type="time" name="spa_schedule[${index}][time]" style="width: 120px;">
                `;
                repeater.appendChild(row);
            });
        }
        
        if (remBtn) {
            remBtn.addEventListener('click', function() {
                var rows = repeater.querySelectorAll('.spa-schedule-row');
                if (rows.length > 1) {
                    rows[rows.length - 1].remove();
                }
            });
        }
    })();
    </script>
    <?php
}

// Helper: Vykresli riadok rozvrhu
function spa_schedule_row_html($index, $row, $days) {
    $day = isset($row['day']) ? $row['day'] : '';
    $time = isset($row['time']) ? $row['time'] : '';
    ?>
    <div class="spa-schedule-row">
        <select name="spa_schedule[<?php echo $index; ?>][day]">
            <option value="">-- Den --</option>
            <?php foreach ($days as $key => $label) : ?>
                <option value="<?php echo $key; ?>" <?php selected($day, $key); ?>><?php echo $label; ?></option>
            <?php endforeach; ?>
        </select>
        <input type="time" name="spa_schedule[<?php echo $index; ?>][time]" value="<?php echo esc_attr($time); ?>" style="width: 120px;">
    </div>
    <?php
}

/* ==========================
   SKUPINY - SAVE
   ========================== */

add_action('save_post_spa_group', 'spa_save_group_meta', 10, 2);
function spa_save_group_meta($post_id, $post) {
    
    if (!isset($_POST['spa_group_nonce']) || !wp_verify_nonce($_POST['spa_group_nonce'], 'spa_save_group')) {
        return;
    }
    
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    
    if (!current_user_can('edit_post', $post_id)) {
        return;
    }
    
    // Ikona
    if (isset($_POST['spa_icon'])) {
        update_post_meta($post_id, 'spa_icon', sanitize_text_field($_POST['spa_icon']));
    }
    
    // Cena
    if (isset($_POST['spa_price'])) {
        $price = str_replace(',', '.', $_POST['spa_price']);
        update_post_meta($post_id, 'spa_price', floatval($price));
    }
    
    // Kapacita
    if (isset($_POST['spa_capacity'])) {
        update_post_meta($post_id, 'spa_capacity', intval($_POST['spa_capacity']));
    }
    
    // Rozvrh (JSON)
    if (isset($_POST['spa_schedule']) && is_array($_POST['spa_schedule'])) {
        $schedule = array();
        foreach ($_POST['spa_schedule'] as $row) {
            $day = sanitize_text_field($row['day']);
            $time = sanitize_text_field($row['time']);
            if (!empty($day) || !empty($time)) {
                $schedule[] = array('day' => $day, 'time' => $time);
            }
        }
        update_post_meta($post_id, 'spa_schedule', wp_json_encode($schedule));
    }
    
    // Treneri (array)
    if (isset($_POST['spa_trainer_ids'])) {
        $trainer_ids = array_map('intval', $_POST['spa_trainer_ids']);
        update_post_meta($post_id, 'spa_trainer_ids', $trainer_ids);
    } else {
        delete_post_meta($post_id, 'spa_trainer_ids');
    }
}

/* ==========================
   AJAX: SVG Preview
   ========================== */

add_action('wp_ajax_spa_preview_svg', 'spa_ajax_preview_svg');
function spa_ajax_preview_svg() {
    $file = isset($_GET['file']) ? sanitize_file_name($_GET['file']) : '';
    $svg_path = WP_CONTENT_DIR . '/uploads/spa-icons/' . $file;
    
    if ($file && file_exists($svg_path) && pathinfo($file, PATHINFO_EXTENSION) === 'svg') {
        echo file_get_contents($svg_path);
    } else {
        echo '<span style="color:#d63638;">Nenajdene</span>';
    }
    wp_die();
}

/* ==========================
   REGISTRACIA - META BOX
   ========================== */

function spa_registration_details_callback($post) {
    wp_nonce_field('spa_save_registration', 'spa_registration_nonce');
    
    $client_id = get_post_meta($post->ID, 'client_user_id', true);
    $program_id = get_post_meta($post->ID, 'program_id', true);
    $parent_id = get_post_meta($post->ID, 'parent_user_id', true);
    $status = get_post_meta($post->ID, 'status', true);
    
    $client = $client_id ? get_userdata($client_id) : null;
    $program = $program_id ? get_post($program_id) : null;
    $parent = $parent_id ? get_userdata($parent_id) : null;
    
    $vs = $client_id ? get_user_meta($client_id, 'variabilny_symbol', true) : '';
    $pin = $client_id ? get_user_meta($client_id, 'spa_pin_plain', true) : '';
    $phone = $parent_id ? get_user_meta($parent_id, 'phone', true) : '';
    
    $all_programs = get_posts(array('post_type' => 'spa_group', 'posts_per_page' => -1, 'orderby' => 'title', 'order' => 'ASC'));
    
    $statuses = array(
        'pending' => '⏳ Čaká na schválenie',
        'approved' => '✅ Schválené',
        'active' => '🟢 Aktívny',
        'cancelled' => '❌ Zrušené',
        'completed' => '✔ Zaregistrované'
    );
    
    $client_name = $client ? trim($client->first_name . ' ' . $client->last_name) : '';
    if ($client && empty($client_name)) $client_name = $client->display_name;
    
    $parent_name = $parent ? trim($parent->first_name . ' ' . $parent->last_name) : '';
    if ($parent && empty($parent_name)) $parent_name = $parent->display_name;
    
    $place_str = '';
    if ($program_id) {
        $places = get_the_terms($program_id, 'spa_place');
        if ($places && !is_wp_error($places)) {
            $names = array();
            foreach ($places as $place) { $names[] = $place->name; }
            $place_str = implode(', ', $names);
        }
    }
    
    ?>
    <style>
    .spa-reg-table { width: 100%; max-width: 600px; border-collapse: collapse; }
    .spa-reg-table th { padding: 8px; width: 100px; background: #f9f9f9; border: 1px solid #ddd; font-weight: 400;}
    .spa-reg-table td { padding: 8px; border: 1px solid #ddd; }
    .spa-edit-box { margin-top: 20px; padding: 15px; background: #fff8e5; border: 1px solid #f0ad4e; border-radius: 4px; max-width: 550px; }
    </style>
    
    <table class="spa-reg-table">
        <tr><th style="text-align:right">👶 Dieťa / Klient</th><td><strong><?php echo $client_name ?: '--'; ?></strong></td></tr>
        <tr><th style="text-align:right">#️ VS</th><td><?php echo $vs ?: '--'; ?></td></tr>
        <tr><th style="text-align:right">#️ PIN</th><td><?php echo $pin ?: '--'; ?></td></tr>
        <tr><th style="text-align:right">📍 Miesto</th><td><?php echo $place_str ?: '--'; ?></td></tr>
        <tr><th style="text-align:right">👨‍👩‍👧 Rodič</th><td><?php echo $parent_name ?: '--'; ?></td></tr>
        <tr><th style="text-align:right">📧 E-mail</th><td><?php echo $parent ? $parent->user_email : '--'; ?></td></tr>
        <tr><th style="text-align:right">🕻 Telefón</th><td><?php echo $phone ?: '--'; ?></td></tr>
    </table>
    
    <p style="margin-top: 15px;">
        <?php if ($client_id) : ?><a href="<?php echo get_edit_user_link($client_id); ?>" class="button" target="_blank">Upraviť profil dieťata/klienta</a><?php endif; ?>
        <?php if ($parent_id) : ?><a href="<?php echo get_edit_user_link($parent_id); ?>" class="button" target="_blank">Upraviť profil rodiča</a><?php endif; ?>
    </p>
    
    <div class="spa-edit-box">
        <p><strong>Úprava tréningového programu</strong></p>
        <p>
            <label>Program:</label><br>
            <select name="spa_program_id" style="width: 100%; max-width: 400px;">
                <?php foreach ($all_programs as $prog) : ?>
                    <option value="<?php echo $prog->ID; ?>" <?php selected($program_id, $prog->ID); ?>><?php echo esc_html($prog->post_title); ?></option>
                <?php endforeach; ?>
            </select>
        </p>
        <p>
            <label>Status:</label><br>
            <select name="spa_status" style="width: 200px;">
                <?php foreach ($statuses as $key => $label) : ?>
                    <option value="<?php echo $key; ?>" <?php selected($status, $key); ?>><?php echo $label; ?></option>
                <?php endforeach; ?>
            </select>
        </p>
    </div>
    <?php
}

/* ==========================
   REGISTRACIA - SAVE
   ========================== */

add_action('save_post_spa_registration', 'spa_save_registration_meta', 10, 2);
function spa_save_registration_meta($post_id, $post) {
    
    if (!isset($_POST['spa_registration_nonce']) || !wp_verify_nonce($_POST['spa_registration_nonce'], 'spa_save_registration')) {
        return;
    }
    
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    
    if (!current_user_can('edit_post', $post_id)) {
        return;
    }
    
    $title_changed = false;
    
    if (isset($_POST['spa_program_id'])) {
        $new_program_id = intval($_POST['spa_program_id']);
        $old_program_id = get_post_meta($post_id, 'program_id', true);
        if ($new_program_id != $old_program_id) {
            update_post_meta($post_id, 'program_id', $new_program_id);
            $title_changed = true;
        }
    }
    
    if (isset($_POST['spa_status'])) {
        update_post_meta($post_id, 'status', sanitize_text_field($_POST['spa_status']));
    }
    
    if ($title_changed) {
        $client_id = get_post_meta($post_id, 'client_user_id', true);
        $program_id = intval($_POST['spa_program_id']);
        $client = get_userdata($client_id);
        $program = get_post($program_id);
        
        if ($client && $program) {
            $client_name = trim($client->first_name . ' ' . $client->last_name);
            if (empty($client_name)) $client_name = $client->display_name;
            $new_title = $client_name . ' - ' . $program->post_title;
            
            remove_action('save_post_spa_registration', 'spa_save_registration_meta', 10);
            wp_update_post(array('ID' => $post_id, 'post_title' => $new_title));
            add_action('save_post_spa_registration', 'spa_save_registration_meta', 10, 2);
        }
    }
}