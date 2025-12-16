<?php
/**
 * SPA Registration Meta Box
 * Detaily registrácie s READONLY rodičom + údajmi
 */

if (!defined('ABSPATH')) {
    exit;
}

add_action('add_meta_boxes_spa_registration', 'spa_registration_add_meta_box');

function spa_registration_add_meta_box() {
    add_meta_box(
        'spa_registration_details',
        '📋 Detaily registrácie',
        'spa_registration_meta_box_callback',
        'spa_registration',
        'normal',
        'high'
    );
}

function spa_registration_meta_box_callback($post) {
    wp_nonce_field('spa_save_registration_details', 'spa_registration_nonce');
    
    $client_user_id = get_post_meta($post->ID, 'client_user_id', true);
    $parent_user_id = get_post_meta($post->ID, 'parent_user_id', true);
    $program_id = get_post_meta($post->ID, 'program_id', true);
    $training_day = get_post_meta($post->ID, 'training_day', true);
    $training_time = get_post_meta($post->ID, 'training_time', true);
    $status = get_post_meta($post->ID, 'status', true);
    $vs = '';
    $pin = '';
    
    $days = [
        'monday' => 'Pondelok',
        'tuesday' => 'Utorok',
        'wednesday' => 'Streda',
        'thursday' => 'Štvrtok',
        'friday' => 'Piatok',
        'saturday' => 'Sobota',
        'sunday' => 'Nedeľa'
    ];
    
    // Ziskaj info o dieťati
    $child_user = $client_user_id ? get_userdata($client_user_id) : null;
    if ($child_user) {
        $vs = get_user_meta($client_user_id, 'variabilny_symbol', true);
        $pin = get_user_meta($client_user_id, 'spa_pin_plain', true);
    }
    
    // Ziskaj info o rodičovi
    $parent_user = $parent_user_id ? get_userdata($parent_user_id) : null;
    $parent_phone = $parent_user_id ? get_user_meta($parent_user_id, 'phone', true) : '';
    
    ?>
    <style>
        .spa-registration-field { margin-bottom: 20px; }
        .spa-registration-field label { display: block; font-weight: 600; margin-bottom: 8px; color: #333; }
        .spa-registration-field input, .spa-registration-field select, .spa-registration-field textarea {
            width: 100%; max-width: 500px; padding: 10px; border: 1px solid #ddd; border-radius: 4px;
        }
        .spa-registration-field input:disabled, .spa-registration-field textarea:disabled {
            background: #f5f5f5; color: #666;
        }
        .spa-readonly-box { background: #e8f5e9; padding: 15px; border-left: 4px solid #4caf50; border-radius: 4px; margin-bottom: 20px; }
        .spa-readonly-box h4 { margin: 0 0 10px; font-size: 14px; color: #2e7d32; }
        .spa-readonly-box p { margin: 5px 0; color: #555; font-size: 14px; }
        .spa-help { color: #888; font-size: 13px; margin-top: 5px; display: block; }
    </style>
    
    <!-- DIEŤA - READONLY -->
    <div class="spa-readonly-box">
        <h4>👶 Dieťa/Klient</h4>
        <?php if ($child_user) : ?>
            <p><strong><?php echo esc_html($child_user->display_name); ?></strong></p>
            <p>Email: <code><?php echo esc_html($child_user->user_email); ?></code></p>
            
            <?php 
            $birthdate = get_user_meta($client_user_id, 'birthdate', true);
            if ($birthdate) :
            ?>
                <p>Dátum narodenia: <?php echo esc_html(date_i18n('j.n.Y', strtotime($birthdate))); ?></p>
            <?php endif; ?>
            
            <?php 
            $rodne_cislo = get_user_meta($client_user_id, 'rodne_cislo', true);
            if ($rodne_cislo) :
                $formatted = substr($rodne_cislo, 0, 6) . '/' . substr($rodne_cislo, 6);
            ?>
                <p>Rodné číslo: <code><?php echo esc_html($formatted); ?></code></p>
            <?php endif; ?>
            
            <?php if ($vs) : ?>
                <p>Variabilný symbol: <strong style="font-size: 18px; color: #d32f2f;"><?php echo esc_html($vs); ?></strong></p>
            <?php endif; ?>
            
            <?php if ($pin) : ?>
                <p>PIN na vstup: <strong style="font-size: 18px; color: #1976d2; letter-spacing: 3px;"><?php echo esc_html($pin); ?></strong></p>
            <?php endif; ?>
        <?php else : ?>
            <p style="color: #d32f2f;">⚠️ Dieťa nie je priradené</p>
        <?php endif; ?>
    </div>
    
    <!-- RODIČ - READONLY -->
    <div class="spa-readonly-box">
        <h4>👨‍👩‍👧 Rodič</h4>
        <?php if ($parent_user) : ?>
            <p><strong><?php echo esc_html($parent_user->display_name); ?></strong></p>
            <p>Email: <code><?php echo esc_html($parent_user->user_email); ?></code></p>
            <?php if ($parent_phone) : ?>
                <p>Telefón: <a href="tel:<?php echo esc_attr($parent_phone); ?>"><?php echo esc_html($parent_phone); ?></a></p>
            <?php endif; ?>
        <?php else : ?>
            <p style="color: #d32f2f;">⚠️ Rodič nie je priradený</p>
        <?php endif; ?>
    </div>
    
    <!-- PROGRAM - EDITOVATEĽNÝ -->
    <div class="spa-registration-field">
        <label for="program_id">🤸 Program *</label>
        <select name="program_id" id="program_id" required>
            <option value="">-- Vyber program --</option>
            <?php 
            $programs = get_posts(['post_type' => 'spa_group', 'posts_per_page' => -1, 'orderby' => 'title']);
            foreach ($programs as $prog) :
            ?>
                <option value="<?php echo $prog->ID; ?>" <?php selected($program_id, $prog->ID); ?>>
                    <?php echo esc_html($prog->post_title); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    
    <!-- DEŇTRÉNINGU + ČAS - EDITOVATEĽNÝ -->
    <div class="spa-registration-field">
        <label for="training_day">🗓️ Deň tréningu *</label>
        <select name="training_day" id="training_day">
            <option value="">-- Vyber deň --</option>
            <?php foreach ($days as $key => $label) : ?>
                <option value="<?php echo esc_attr($key); ?>" <?php selected($training_day, $key); ?>>
                    <?php echo esc_html($label); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    
    <div class="spa-registration-field">
        <label for="training_time">⏰ Čas tréningu</label>
        <input type="time" name="training_time" id="training_time" value="<?php echo esc_attr($training_time); ?>">
    </div>
    
    <!-- STATUS - EDITOVATEĽNÝ -->
    <div class="spa-registration-field">
        <label for="status">📌 Status</label>
        <select name="status" id="status">
            <option value="active" <?php selected($status, 'active'); ?>>✅ Aktívna</option>
            <option value="inactive" <?php selected($status, 'inactive'); ?>>⏸️ Neaktívna</option>
            <option value="cancelled" <?php selected($status, 'cancelled'); ?>>❌ Zrušená</option>
        </select>
    </div>
    
    <?php
}

add_action('save_post_spa_registration', 'spa_registration_save_meta', 10, 2);

function spa_registration_save_meta($post_id, $post) {
    
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (!isset($_POST['spa_registration_nonce']) || !wp_verify_nonce($_POST['spa_registration_nonce'], 'spa_save_registration_details')) return;
    if (!current_user_can('edit_post', $post_id)) return;
    
    if (isset($_POST['program_id'])) {
        update_post_meta($post_id, 'program_id', intval($_POST['program_id']));
    }
    
    if (isset($_POST['training_day'])) {
        update_post_meta($post_id, 'training_day', sanitize_text_field($_POST['training_day']));
    }
    
    if (isset($_POST['training_time'])) {
        update_post_meta($post_id, 'training_time', sanitize_text_field($_POST['training_time']));
    }
    
    if (isset($_POST['status'])) {
        update_post_meta($post_id, 'status', sanitize_text_field($_POST['status']));
    }
}