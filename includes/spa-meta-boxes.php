<?php
/**
 * SPA Meta Boxes - Admin formulare pre CPT
 * @package Samuel Piasecky ACADEMY
 * @version 2.3.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/* ==========================
   ZMENA LINKU: Pridat registraciu -> nove okno
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

// JavaScript pre VSETKY admin stranky (menu je vsade)
add_action('admin_footer', 'spa_add_new_registration_js');
function spa_add_new_registration_js() {
    $url = esc_url(home_url('/registracia/'));
    ?>
    <script type="text/javascript">
    (function() {
        var targetUrl = '<?php echo $url; ?>';
        
        // 1. Tlacidlo "Pridat novu registraciu" v liste (horizontalne)
        var addBtn = document.querySelector('.page-title-action[href*="post-new.php?post_type=spa_registration"]');
        if (addBtn) {
            addBtn.setAttribute('href', targetUrl);
            addBtn.setAttribute('target', '_blank');
            addBtn.setAttribute('rel', 'noopener');
        }
        
        // 2. Menu vlavo - "Pridat registraciu"
        var menuLinks = document.querySelectorAll('#adminmenu a[href*="post-new.php?post_type=spa_registration"]');
        menuLinks.forEach(function(link) {
            link.setAttribute('href', targetUrl);
            link.setAttribute('target', '_blank');
            link.setAttribute('rel', 'noopener');
        });
        
        // 3. Aj submenu pod Registracie
        var submenuLinks = document.querySelectorAll('#menu-posts-spa_registration a[href*="post-new.php"]');
        submenuLinks.forEach(function(link) {
            link.setAttribute('href', targetUrl);
            link.setAttribute('target', '_blank');
            link.setAttribute('rel', 'noopener');
        });
    })();
    </script>
    <?php
}

add_action('admin_init', 'spa_redirect_new_registration');
function spa_redirect_new_registration() {
    global $pagenow;
    
    if ($pagenow === 'post-new.php' && 
        isset($_GET['post_type']) && 
        $_GET['post_type'] === 'spa_registration') {
        
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
    
    add_meta_box(
        'spa_registration_details',
        'Detaily registrácie',
        'spa_registration_details_callback',
        'spa_registration',
        'normal',
        'high'
    );
    
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
    
    // Vsetky programy pre dropdown
    $all_programs = get_posts(array(
        'post_type' => 'spa_group',
        'posts_per_page' => -1,
        'orderby' => 'title',
        'order' => 'ASC'
    ));
    
    $statuses = array(
        'pending' => '⏳ Čaká na schválenie',
        'approved' => '✅ Schválené',
        'active' => '🟢 Aktívny',
        'cancelled' => '❌ Zrušené',
        'completed' => '🟠 Dokončené'
    );
    
    $client_name = '';
    if ($client) {
        $client_name = trim($client->first_name . ' ' . $client->last_name);
        if (empty($client_name)) $client_name = $client->display_name;
    }
    
    $parent_name = '';
    if ($parent) {
        $parent_name = trim($parent->first_name . ' ' . $parent->last_name);
        if (empty($parent_name)) $parent_name = $parent->display_name;
    }
    
    $place_str = '';
    if ($program_id) {
        $places = get_the_terms($program_id, 'spa_place');
        if ($places && !is_wp_error($places)) {
            $names = array();
            foreach ($places as $place) {
                $names[] = $place->name;
            }
            $place_str = implode(', ', $names);
        }
    }
    
    ?>
    <style>
    .spa-compact-box { max-width: 750px; }
    .spa-compact-table { width: 100%; border-collapse: collapse; font-size: 13px; }
    .spa-compact-table th { 
        text-align: left; 
        padding: 8px 10px; 
        width: 100px; 
        background: #f9f9f9; 
        border: 1px solid #ddd;
        font-weight: 600;
    }
    .spa-compact-table td { 
        padding: 8px 10px; 
        border: 1px solid #ddd;
        background: #fff;
    }
    .spa-mono { font-family: monospace; background: #f5f5f5; padding: 2px 6px; border-radius: 3px; }
    .spa-empty { color: #999; }
    .spa-actions { margin-top: 15px; display: flex; gap: 10px; flex-wrap: wrap; }
    .spa-edit-box { 
        margin-top: 20px; 
        padding: 15px; 
        background: #fff8e5; 
        border: 1px solid #f0ad4e; 
        border-radius: 4px;
    }
    .spa-edit-box h4 { margin: 0 0 12px 0; color: #856404; font-size: 14px; }
    .spa-edit-row { display: flex; align-items: center; gap: 15px; margin-bottom: 10px; }
    .spa-edit-row:last-child { margin-bottom: 0; }
    .spa-edit-row label { width: 80px; font-weight: 600; font-size: 13px; }
    .spa-edit-row select { flex: 1; max-width: 650px; padding: 6px 10px; }
    .spa-edit-row select#spa_status { max-width: 250px;}
    </style>
    
    <div class="spa-compact-box">
        
        <table class="spa-compact-table">
            <tr>
                <th style="text-align:right">👶 Dieťa / Klient</th>
                <td><strong><?php echo $client_name ? esc_html($client_name) : '<span class="spa-empty">--</span>'; ?></strong></td>
            </tr>
            <tr>
                <th style="text-align:right">#️ VS</th>
                <td><?php echo $vs ? '<span class="spa-mono">' . esc_html($vs) . '</span>' : '<span class="spa-empty">--</span>'; ?></td>
            </tr>
            <tr>
                <th style="text-align:right">#️ PIN</th>
                <td><?php echo $pin ? '<span class="spa-mono">' . esc_html($pin) . '</span>' : '<span class="spa-empty">--</span>'; ?></td>
            </tr>
            <tr>
                <th style="text-align:right">📍 Miesto</th>
                <td><?php echo $place_str ? esc_html($place_str) : '<span class="spa-empty">--</span>'; ?></td>
            </tr>
            <tr>
                <th style="text-align:right">👨‍👩‍👧 Rodič</th>
                <td><?php echo $parent_name ? esc_html($parent_name) : '<span class="spa-empty">--</span>'; ?></td>
            </tr>
            <tr>
                <th style="text-align:right">📧 E-mail</th>
                <td><?php echo $parent ? esc_html($parent->user_email) : '<span class="spa-empty">--</span>'; ?></td>
            </tr>
            <tr>
                <th style="text-align:right">🕻 Telefón</th>
                <td><?php echo $phone ? esc_html($phone) : '<span class="spa-empty">--</span>'; ?></td>
            </tr>
        </table>
        
        <div class="spa-actions">
            <?php if ($client_id) : ?>
                <a href="<?php echo get_edit_user_link($client_id); ?>" class="button" target="_blank">Upraviť profil dieťata/klienta</a>
            <?php endif; ?>
            <?php if ($parent_id) : ?>
                <a href="<?php echo get_edit_user_link($parent_id); ?>" class="button" target="_blank">Upraviť profil rodiča</a>
            <?php endif; ?>
        </div>
        
        <!-- EDITOVATELNA CAST -->
        <div class="spa-edit-box">
            <h4>Úprava tréningového programu</h4>
            
            <div class="spa-edit-row">
                <label for="spa_program_id">Program:</label>
                <select name="spa_program_id" id="spa_program_id">
                    <?php foreach ($all_programs as $prog) : 
                        $prog_places = get_the_terms($prog->ID, 'spa_place');
                        $prog_place = '';
                        if ($prog_places && !is_wp_error($prog_places)) {
                            $pnames = array();
                            foreach ($prog_places as $pp) {
                                $pnames[] = $pp->name;
                            }
                            $prog_place = ' [' . implode(', ', $pnames) . ']';
                        }
                    ?>
                        <option value="<?php echo $prog->ID; ?>" <?php selected($program_id, $prog->ID); ?>>
                            <?php echo esc_html($prog->post_title . $prog_place); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="spa-edit-row">
                <label for="spa_status">Status:</label>
                <select name="spa_status" id="spa_status">
                    <?php foreach ($statuses as $key => $label) : ?>
                        <option value="<?php echo $key; ?>" <?php selected($status, $key); ?>>
                            <?php echo esc_html($label); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        
    </div>
    <?php
}

/* ==========================
   REGISTRACIA - SAVE (Program + Status + Title)
   ========================== */

add_action('save_post_spa_registration', 'spa_save_registration_meta', 10, 2);
function spa_save_registration_meta($post_id, $post) {
    
    if (!isset($_POST['spa_registration_nonce']) || 
        !wp_verify_nonce($_POST['spa_registration_nonce'], 'spa_save_registration')) {
        return;
    }
    
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    
    if (!current_user_can('edit_post', $post_id)) {
        return;
    }
    
    $title_changed = false;
    
    // Uloz program
    if (isset($_POST['spa_program_id'])) {
        $new_program_id = intval($_POST['spa_program_id']);
        $old_program_id = get_post_meta($post_id, 'program_id', true);
        
        if ($new_program_id != $old_program_id) {
            update_post_meta($post_id, 'program_id', $new_program_id);
            $title_changed = true;
        }
    }
    
    // Uloz status
    if (isset($_POST['spa_status'])) {
        update_post_meta($post_id, 'status', sanitize_text_field($_POST['spa_status']));
    }
    
    // Aktualizuj title ak sa zmenil program
    if ($title_changed) {
        $client_id = get_post_meta($post_id, 'client_user_id', true);
        $program_id = intval($_POST['spa_program_id']);
        
        $client = get_userdata($client_id);
        $program = get_post($program_id);
        
        if ($client && $program) {
            $client_name = trim($client->first_name . ' ' . $client->last_name);
            if (empty($client_name)) {
                $client_name = $client->display_name;
            }
            
            $new_title = $client_name . ' - ' . $program->post_title;
            
            remove_action('save_post_spa_registration', 'spa_save_registration_meta', 10);
            wp_update_post(array(
                'ID' => $post_id,
                'post_title' => $new_title
            ));
            add_action('save_post_spa_registration', 'spa_save_registration_meta', 10, 2);
        }
    }
}

/* ==========================
   SKUPINY/PROGRAMY - META BOX
   ========================== */

function spa_group_details_callback($post) {
    wp_nonce_field('spa_save_group', 'spa_group_nonce');
    
    $price = get_post_meta($post->ID, 'spa_price', true);
    $capacity = get_post_meta($post->ID, 'spa_capacity', true);
    $schedule = get_post_meta($post->ID, 'spa_schedule', true);
    $trainer_id = get_post_meta($post->ID, 'spa_trainer_id', true);
    
    $trainers = get_users(array('role' => 'spa_trainer', 'orderby' => 'display_name'));
    
    ?>
    <table class="form-table">
        <tr>
            <th><label for="spa_price">Cena (EUR)</label></th>
            <td><input type="text" name="spa_price" id="spa_price" value="<?php echo esc_attr($price); ?>" class="small-text"></td>
        </tr>
        <tr>
            <th><label for="spa_capacity">Kapacita</label></th>
            <td><input type="number" name="spa_capacity" id="spa_capacity" value="<?php echo esc_attr($capacity); ?>" class="small-text" min="1"></td>
        </tr>
        <tr>
            <th><label for="spa_schedule">Rozvrh</label></th>
            <td><textarea name="spa_schedule" id="spa_schedule" rows="2" class="large-text"><?php echo esc_textarea($schedule); ?></textarea></td>
        </tr>
        <tr>
            <th><label for="spa_trainer_id">Trener</label></th>
            <td>
                <select name="spa_trainer_id" id="spa_trainer_id">
                    <option value="">-- Vyber --</option>
                    <?php foreach ($trainers as $t) : 
                        $n = trim($t->first_name . ' ' . $t->last_name);
                        if (empty($n)) $n = $t->display_name;
                    ?>
                        <option value="<?php echo $t->ID; ?>" <?php selected($trainer_id, $t->ID); ?>><?php echo esc_html($n); ?></option>
                    <?php endforeach; ?>
                </select>
            </td>
        </tr>
    </table>
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
    
    if (isset($_POST['spa_price'])) {
        update_post_meta($post_id, 'spa_price', floatval(str_replace(',', '.', $_POST['spa_price'])));
    }
    if (isset($_POST['spa_capacity'])) {
        update_post_meta($post_id, 'spa_capacity', intval($_POST['spa_capacity']));
    }
    if (isset($_POST['spa_schedule'])) {
        update_post_meta($post_id, 'spa_schedule', sanitize_textarea_field($_POST['spa_schedule']));
    }
    if (isset($_POST['spa_trainer_id'])) {
        update_post_meta($post_id, 'spa_trainer_id', intval($_POST['spa_trainer_id']));
    }
}