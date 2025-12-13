<?php
/**
 * SPA Registration Helpers - Helper funkcie pre registráciu
 * 
 * @package Samuel Piasecký ACADEMY
 * @subpackage Registration
 * @version 1.0.0
 * 
 * PARENT MODULES: 
 * - spa-core/spa-roles.php (role definitions)
 * - spa-user-fields.php (user meta)
 * 
 * CHILD MODULES:
 * - spa-registration-form.php
 * - spa-registration-notifications.php
 * 
 * FUNCTIONS DEFINED:
 * - spa_get_or_create_parent() - Vytvor/nájdi rodiča
 * - spa_create_child_account() - Vytvor dieťa
 * - spa_get_or_create_client() - Vytvor dospelého klienta
 * - spa_create_registration() - Vytvor registráciu (CPT)
 * - spa_get_status_label() - Label pre status
 * 
 * DATABASE TABLES:
 * - wp_users (user accounts)
 * - wp_usermeta (user metadata)
 * - wp_posts (spa_registration CPT)
 * - wp_postmeta (registration metadata)
 */

if (!defined('ABSPATH')) {
    exit;
}


/* ==================================================
   HELPER: Logging - Zaznamenanie chýb a udalostí
   ================================================== */

function spa_log($message, $data = null) {
    $log_msg = '[SPA] ' . date('Y-m-d H:i:s') . ' - ' . $message;
    if (!empty($data)) {
        $log_msg .= ' | Data: ' . print_r($data, true);
    }
    error_log($log_msg);
}

function spa_get_status_label($status) {
    $labels = [
        'pending'   => 'Čaká na schválenie',
        'approved'  => 'Schválené',
        'active'    => 'Aktívne',
        'cancelled' => 'Zrušené',
        'completed' => 'Zaregistrované'
    ];

    return isset($labels[$status]) ? $labels[$status] : ucfirst($status);
}

/* ==================================================
   HELPER: Vytvor/Nájdi rodiča
   ================================================== */

function spa_get_or_create_parent($email, $first_name, $last_name, $phone, $address_street = '', $address_psc = '', $address_city = '') {
    
    $user = get_user_by('email', $email);
    
    if ($user) {
        error_log('SPA: Found existing parent - ' . $email);
        
        // Aktualizuj údaje
        update_user_meta($user->ID, 'phone', sanitize_text_field($phone));
        
        if (!empty($first_name)) {
            update_user_meta($user->ID, 'first_name', sanitize_text_field($first_name));
        }
        if (!empty($last_name)) {
            update_user_meta($user->ID, 'last_name', sanitize_text_field($last_name));
        }
        
        // Aktualizuj adresu
        if (!empty($address_street)) {
            update_user_meta($user->ID, 'address_street', sanitize_text_field($address_street));
        }
        if (!empty($address_psc)) {
            update_user_meta($user->ID, 'address_psc', sanitize_text_field($address_psc));
        }
        if (!empty($address_city)) {
            update_user_meta($user->ID, 'address_city', sanitize_text_field($address_city));
        }
        
        return $user->ID;
    }
    
    // Vytvor nového rodiča - odstráň diakritiku z username
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

    $first_clean = strtr($first_name, $chars);
    $last_clean = strtr($last_name, $chars);
    $first_clean = strtolower(preg_replace('/[^a-z0-9]/i', '', $first_clean));
    $last_clean = strtolower(preg_replace('/[^a-z0-9]/i', '', $last_clean));

    $base_username = $first_clean . '.' . $last_clean;
    if (strlen($base_username) > 50) {
        $base_username = substr($base_username, 0, 50);
    }

    $username = $base_username;
    $counter = 1;
    while (username_exists($username)) {
        $username = $base_username . $counter;
        $counter++;
    }

    $password = wp_generate_password(12, true);
    
    $user_id = wp_create_user($username, $password, $email);
    
    if (is_wp_error($user_id)) {
        error_log('SPA ERROR: Failed to create parent - ' . $user_id->get_error_message());
        return false;
    }
    
    $user = new WP_User($user_id);
    $user->set_role('spa_parent');
    
    // Meta data
    update_user_meta($user_id, 'first_name', sanitize_text_field($first_name));
    update_user_meta($user_id, 'last_name', sanitize_text_field($last_name));
    update_user_meta($user_id, 'phone', sanitize_text_field($phone));
    
    // Adresa
    if (!empty($address_street)) {
        update_user_meta($user_id, 'address_street', sanitize_text_field($address_street));
    }
    if (!empty($address_psc)) {
        update_user_meta($user_id, 'address_psc', sanitize_text_field($address_psc));
    }
    if (!empty($address_city)) {
        update_user_meta($user_id, 'address_city', sanitize_text_field($address_city));
    }
    
    // Email s prihlasovacími údajmi
    spa_send_welcome_email($email, $username, $password, $first_name);
    
    error_log('SPA: Created new parent - ' . $email);
    
    return $user_id;
}

/* ==================================================
   HELPER: Vytvor dieťa
   ================================================== */

function spa_create_child_account($first_name, $last_name, $birthdate, $parent_id, $health_notes = '', $rodne_cislo = '') {
    
    // Virtuálny účet bez prihlásenia
    $username = 'child_' . $parent_id . '_' . uniqid();
    $email = $username . '@piaseckyacademy.local';
    $password = wp_generate_password(32);
    
    $user_id = wp_create_user($username, $password, $email);
    
    if (is_wp_error($user_id)) {
        error_log('SPA ERROR: Failed to create child - ' . $user_id->get_error_message());
        return false;
    }
    
    $user = new WP_User($user_id);
    $user->set_role('spa_child');
    
    // Základné meta data
    update_user_meta($user_id, 'first_name', sanitize_text_field($first_name));
    update_user_meta($user_id, 'last_name', sanitize_text_field($last_name));
    update_user_meta($user_id, 'birthdate', sanitize_text_field($birthdate));
    update_user_meta($user_id, 'parent_id', intval($parent_id));
    
    // Display name
    wp_update_user([
        'ID' => $user_id,
        'display_name' => $first_name . ' ' . $last_name
    ]);
    
    if ($health_notes) {
        update_user_meta($user_id, 'health_notes', sanitize_textarea_field($health_notes));
    }
    
    // Rodné číslo (ulož bez lomky)
    if ($rodne_cislo) {
        $rc_clean = preg_replace('/[^0-9]/', '', $rodne_cislo);
        update_user_meta($user_id, 'rodne_cislo', $rc_clean);
    }
    
    // Automatické pridelenie variabilného symbolu
    do_action('spa_after_child_created', $user_id);
    
    error_log('SPA: Created child - ' . $first_name . ' ' . $last_name . ' (ID: ' . $user_id . ')');
    
    return $user_id;
}

/* ==================================================
   HELPER: Vytvor/Nájdi dospelého klienta
   ================================================== */

function spa_get_or_create_client($email, $first_name, $last_name, $phone, $birthdate) {
    
    $user = get_user_by('email', $email);
    
    if ($user) {
        update_user_meta($user->ID, 'phone', sanitize_text_field($phone));
        update_user_meta($user->ID, 'birthdate', sanitize_text_field($birthdate));
        return $user->ID;
    }
    
    $username = sanitize_user(strtolower($first_name . '.' . $last_name));
    $password = wp_generate_password(12, true);
    
    $base_username = $username;
    $counter = 1;
    while (username_exists($username)) {
        $username = $base_username . $counter;
        $counter++;
    }
    
    $user_id = wp_create_user($username, $password, $email);
    
    if (is_wp_error($user_id)) {
        return false;
    }
    
    $user = new WP_User($user_id);
    $user->set_role('spa_client');
    
    update_user_meta($user_id, 'first_name', sanitize_text_field($first_name));
    update_user_meta($user_id, 'last_name', sanitize_text_field($last_name));
    update_user_meta($user_id, 'phone', sanitize_text_field($phone));
    update_user_meta($user_id, 'birthdate', sanitize_text_field($birthdate));
    
    spa_send_welcome_email($email, $username, $password, $first_name);
    
    return $user_id;
}

/* ==================================================
   HELPER: Vytvor registráciu (CPT)
   ================================================== */

function spa_create_registration($client_user_id, $program_id, $parent_id = null, $gf_entry_id = null) {
    
    $program = get_post($program_id);
    $user = get_userdata($client_user_id);
    
    if (!$program || !$user) {
        return false;
    }
    
    $title = $user->first_name . ' ' . $user->last_name . ' - ' . $program->post_title;
    
    $registration_id = wp_insert_post([
        'post_type' => 'spa_registration',
        'post_title' => $title,
        'post_status' => 'pending',
        'post_author' => 1
    ]);
    
    if (!$registration_id || is_wp_error($registration_id)) {
        return false;
    }
    
    // Meta data
    update_post_meta($registration_id, 'client_user_id', intval($client_user_id));
    update_post_meta($registration_id, 'program_id', intval($program_id));
    update_post_meta($registration_id, 'registration_date', current_time('Y-m-d H:i:s'));
    update_post_meta($registration_id, 'status', 'pending');
    
    if ($parent_id) {
        update_post_meta($registration_id, 'parent_user_id', intval($parent_id));
    }
    
    if ($gf_entry_id) {
        update_post_meta($registration_id, 'gf_entry_id', intval($gf_entry_id));
    }
    
    // Cena programu
    $price = get_post_meta($program_id, 'spa_price', true);
    if ($price) {
        update_post_meta($registration_id, 'registration_price', floatval($price));
    }
    
    return $registration_id;
}

* ==================================================
   HELPER: Logging - Zaznamenanie chýb a udalostí
   ================================================== */

function spa_log($message, $data = null) {
    
    $log_msg = '[SPA] ' . date('Y-m-d H:i:s') . ' - ' . $message;
    
    if (!empty($data)) {
        $log_msg .= ' | Data: ' . print_r($data, true);
    }
    
    error_log($log_msg);
}