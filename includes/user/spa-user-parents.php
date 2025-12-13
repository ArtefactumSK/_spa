<?php
/**
 * SPA User Parents - Funkcie pre rodiča
 * 
 * @package Samuel Piasecký ACADEMY
 * @subpackage User/Parents
 * @version 1.0.0
 * 
 * PARENT MODULES: spa-user-fields.php
 * CHILD MODULES: registration/spa-registration-form.php
 * 
 * FUNCTIONS DEFINED:
 * - spa_get_or_create_parent()
 * - spa_parent_exists()
 * - spa_get_parent_children()
 */

if (!defined('ABSPATH')) {
    exit;
}

/* ==================================================
   PARENT: Get or Create
   ================================================== */

function spa_get_or_create_parent($email, $first_name, $last_name, $phone = '', $address_street = '', $address_psc = '', $address_city = '', $vs = '', $pin = '') {
    
    // 1. Hľadaj existujúceho rodiča
    $parent = get_user_by('email', $email);
    
    if ($parent) {
        // Update existujúceho
        update_user_meta($parent->ID, SPA_META_PHONE, sanitize_text_field($phone));
        update_user_meta($parent->ID, SPA_META_ADDRESS_STREET, sanitize_text_field($address_street));
        update_user_meta($parent->ID, SPA_META_ADDRESS_CITY, sanitize_text_field($address_city));
        update_user_meta($parent->ID, SPA_META_ADDRESS_PSC, sanitize_text_field($address_psc));
        if ($vs) update_user_meta($parent->ID, SPA_META_VS, sanitize_text_field($vs));
        if ($pin) update_user_meta($parent->ID, SPA_META_PIN, sanitize_text_field($pin));
        
        spa_log('Parent updated: ' . $email, ['parent_id' => $parent->ID]);
        return $parent->ID;
    }
    
    // 2. Vytvor nového rodiča
    $username = spa_generate_username($first_name, $last_name);
    $password = wp_generate_password(12, true);
    
    $parent_id = wp_create_user($username, $password, $email);
    
    if (is_wp_error($parent_id)) {
        spa_log('Error creating parent: ' . $parent_id->get_error_message());
        return false;
    }
    
    // 3. Prirad rolu + meta
    $parent = new WP_User($parent_id);
    $parent->set_role('spa_parent');
    
    wp_update_user([
        'ID' => $parent_id,
        'first_name' => sanitize_text_field($first_name),
        'last_name' => sanitize_text_field($last_name),
        'display_name' => $first_name . ' ' . $last_name
    ]);
    
    update_user_meta($parent_id, SPA_META_PHONE, sanitize_text_field($phone));
    update_user_meta($parent_id, SPA_META_ADDRESS_STREET, sanitize_text_field($address_street));
    update_user_meta($parent_id, SPA_META_ADDRESS_CITY, sanitize_text_field($address_city));
    update_user_meta($parent_id, SPA_META_ADDRESS_PSC, sanitize_text_field($address_psc));
    if ($vs) update_user_meta($parent_id, SPA_META_VS, sanitize_text_field($vs));
    if ($pin) update_user_meta($parent_id, SPA_META_PIN, sanitize_text_field($pin));
    
    spa_send_welcome_email($email, $username, $password, $first_name);
    spa_log('Parent created: ' . $email, ['parent_id' => $parent_id]);
    
    return $parent_id;
}

/* ==================================================
   PARENT: Check existence
   ================================================== */

function spa_parent_exists($email) {
    $user = get_user_by('email', $email);
    return $user && in_array('spa_parent', $user->roles ?? []);
}

/* ==================================================
   PARENT: Get all children
   ================================================== */

function spa_get_parent_children($parent_id) {
    global $wpdb;
    
    $children = $wpdb->get_results($wpdb->prepare(
        "SELECT u.ID, u.display_name, um.meta_value as birthdate
         FROM {$wpdb->users} u
         JOIN {$wpdb->usermeta} um ON u.ID = um.user_id
         WHERE um.meta_key = %s AND um.meta_value = %s",
        SPA_META_PARENT_ID,
        $parent_id
    ));
    
    return $children ?: [];
}

/* ==================================================
   HELPER: Generate unique username
   ================================================== */

function spa_generate_username($first_name, $last_name) {
    // Remove diacritics
    $chars = [
        'á'=>'a', 'ä'=>'a', 'č'=>'c', 'ď'=>'d', 'é'=>'e', 'ě'=>'e',
        'í'=>'i', 'ľ'=>'l', 'ĺ'=>'l', 'ň'=>'n', 'ó'=>'o', 'ô'=>'o',
        'ŕ'=>'r', 'ř'=>'r', 'š'=>'s', 'ť'=>'t', 'ú'=>'u', 'ů'=>'u',
        'ý'=>'y', 'ž'=>'z',
        'Á'=>'A', 'Ä'=>'A', 'Č'=>'C', 'Ď'=>'D', 'É'=>'E', 'Ě'=>'E',
        'Í'=>'I', 'Ľ'=>'L', 'Ĺ'=>'L', 'Ň'=>'N', 'Ó'=>'O', 'Ô'=>'O',
        'Ŕ'=>'R', 'Ř'=>'R', 'Š'=>'S', 'Ť'=>'T', 'Ú'=>'U', 'Ů'=>'U',
        'Ý'=>'Y', 'Ž'=>'Z'
    ];
    
    $first = strtolower(preg_replace('/[^a-z0-9]/i', '', strtr($first_name, $chars)));
    $last = strtolower(preg_replace('/[^a-z0-9]/i', '', strtr($last_name, $chars)));
    
    $base = $first . '.' . $last;
    if (strlen($base) > 50) {
        $base = substr($base, 0, 50);
    }
    
    $username = $base;
    $counter = 1;
    while (username_exists($username)) {
        $username = $base . $counter;
        $counter++;
    }
    
    return $username;
}