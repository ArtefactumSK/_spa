<?php
/**
 * SPA User Children - Funkcie pre deti
 * 
 * @package Samuel Piasecký ACADEMY
 * @subpackage User/Children
 * @version 1.0.0
 * 
 * PARENT MODULES: spa-user-fields.php
 * CHILD MODULES: registration/spa-registration-form.php
 * 
 * FUNCTIONS DEFINED:
 * - spa_create_child_account()
 * - spa_child_exists()
 * - spa_get_child_parent()
 */

if (!defined('ABSPATH')) {
    exit;
}

/* ==================================================
   CHILD: Create account
   ================================================== */

function spa_create_child_account($first_name, $last_name, $birthdate, $parent_id, $health_notes = '', $rodne_cislo = '') {
    
    $username = 'child_' . $parent_id . '_' . uniqid();
    $email = $username . '@piaseckyacademy.local';
    $password = wp_generate_password(32);
    
    $child_id = wp_create_user($username, $password, $email);
    
    if (is_wp_error($child_id)) {
        spa_log('Error creating child: ' . $child_id->get_error_message());
        return false;
    }
    
    // Role + meta
    $child = new WP_User($child_id);
    $child->set_role('spa_child');
    
    wp_update_user([
        'ID' => $child_id,
        'first_name' => sanitize_text_field($first_name),
        'last_name' => sanitize_text_field($last_name),
        'display_name' => $first_name . ' ' . $last_name
    ]);
    
    update_user_meta($child_id, SPA_META_BIRTHDATE, sanitize_text_field($birthdate));
    update_user_meta($child_id, SPA_META_PARENT_ID, intval($parent_id));
    if ($health_notes) {
        update_user_meta($child_id, SPA_META_HEALTH_NOTES, sanitize_textarea_field($health_notes));
    }
    if ($rodne_cislo) {
        update_user_meta($child_id, SPA_META_RODNE_CISLO, sanitize_text_field($rodne_cislo));
    }
    
    spa_log('Child created: ' . $first_name . ' ' . $last_name, ['child_id' => $child_id, 'parent_id' => $parent_id]);
    
    return $child_id;
}

/* ==================================================
   CHILD: Check existence
   ================================================== */

function spa_child_exists($first_name, $last_name, $parent_id) {
    global $wpdb;
    
    $child = $wpdb->get_row($wpdb->prepare(
        "SELECT u.ID FROM {$wpdb->users} u
         WHERE u.first_name = %s AND u.last_name = %s
         AND u.ID IN (
            SELECT user_id FROM {$wpdb->usermeta}
            WHERE meta_key = %s AND meta_value = %s
         )",
        $first_name, $last_name, SPA_META_PARENT_ID, $parent_id
    ));
    
    return $child ? $child->ID : false;
}

/* ==================================================
   CHILD: Get parent
   ================================================== */

function spa_get_child_parent($child_id) {
    $parent_id = get_user_meta($child_id, SPA_META_PARENT_ID, true);
    return $parent_id ? get_user_by('id', $parent_id) : null;
}

/* ==================================================
   CHILD: Get all registrations
   ================================================== */

function spa_get_child_registrations($child_id) {
    global $wpdb;
    
    $registrations = $wpdb->get_results($wpdb->prepare(
        "SELECT p.ID, p.post_title, pm.meta_value as program_id, pm2.meta_value as status
         FROM {$wpdb->posts} p
         JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
         JOIN {$wpdb->postmeta} pm2 ON p.ID = pm2.post_id
         WHERE p.post_type = %s
         AND pm.meta_key = %s AND pm.meta_value = %s
         AND pm2.meta_key = %s",
        'spa_registration', 'spa_child_id', $child_id, 'spa_status'
    ));
    
    return $registrations ?: [];
}