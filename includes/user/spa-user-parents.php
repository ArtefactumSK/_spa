<?php
/**
 * SPA User: Parents - Správa rodičovských účtov
 * 
 * @package Samuel Piasecký ACADEMY
 * @subpackage User Management
 * @version 1.0.0
 * 
 * PARENT MODULES:
 * - spa-user-fields.php
 * - spa-helpers.php
 * - spa-core/spa-roles.php
 * 
 * CHILD MODULES:
 * - spa-user-children.php
 * - registration/spa-registration-form.php
 * - registration/spa-registration-notifications.php
 * - import/spa-import-children.php
 * 
 * FUNCTIONS DEFINED:
 * - spa_get_or_create_parent()
 * - spa_get_or_create_parent_import()
 * - spa_send_welcome_email()
 * - spa_generate_username_from_name()
 * - spa_remove_diacritics()
 * 
 * DATABASE TABLES:
 * - wp_users (INSERT/UPDATE)
 * - wp_usermeta (INSERT/UPDATE)
 * - wp_posts (aktuálne NIE)
 * 
 * USER ROLE ASSIGNED:
 * - spa_parent
 * 
 * USER META FIELDS:
 * - first_name, last_name
 * - phone, address_street, address_psc, address_city
 * 
 * HOOKS USED: žiadne (slúži ako helper modul)
 */

if (!defined('ABSPATH')) {
    exit;
}

/* =============================================
   VYTVOR/NÁJDI RODIČA - REGISTRAČNÝ FORMULÁR
   ============================================= */

function spa_get_or_create_parent($email, $first_name, $last_name, $phone, $address_street = '', $address_psc = '', $address_city = '') {
    
    $user = get_user_by('email', $email);
    
    if ($user) {
        error_log('SPA: Found existing parent - ' . $email);
        
        update_user_meta($user->ID, 'phone', sanitize_text_field($phone));
        
        if (!empty($first_name)) {
            update_user_meta($user->ID, 'first_name', sanitize_text_field($first_name));
        }
        if (!empty($last_name)) {
            update_user_meta($user->ID, 'last_name', sanitize_text_field($last_name));
        }
        
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
    
    // Generuj username - bez diakritiky
    $first_clean = spa_remove_diacritics($first_name);
    $last_clean = spa_remove_diacritics($last_name);
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
    
    if (!empty($address_street)) {
        update_user_meta($user_id, 'address_street', sanitize_text_field($address_street));
    }
    if (!empty($address_psc)) {
        update_user_meta($user_id, 'address_psc', sanitize_text_field($address_psc));
    }
    if (!empty($address_city)) {
        update_user_meta($user_id, 'address_city', sanitize_text_field($address_city));
    }
    
    // Email
    spa_send_welcome_email($email, $username, $password, $first_name);
    
    error_log('SPA: Created new parent - ' . $email);
    
    return $user_id;
}

/* =============================================
   VYTVOR/NÁJDI RODIČA - IMPORT VERZIA
   ============================================= */

function spa_get_or_create_parent_import($email, $first_name, $last_name, $phone, $address_street = '', $address_psc = '', $address_city = '') {
    
    error_log('=== SPA CREATE PARENT (IMPORT) ===');
    error_log("Email: $email");
    error_log("First: $first_name | Last: $last_name");
    
    if (empty($email) || empty($first_name) || empty($last_name)) {
        error_log('SPA ERROR: Parent data is empty!');
        return false;
    }
    
    $existing = get_user_by('email', $email);
    
    if ($existing) {
        error_log('SPA: Found existing parent ID ' . $existing->ID);
        
        wp_update_user([
            'ID' => $existing->ID,
            'first_name' => sanitize_text_field($first_name),
            'last_name' => sanitize_text_field($last_name),
            'display_name' => trim($first_name . ' ' . $last_name),
            'nickname' => spa_generate_username_from_name($first_name, $last_name)
        ]);
        
        update_user_meta($existing->ID, 'phone', sanitize_text_field($phone));
        
        if (!empty($address_street)) {
            update_user_meta($existing->ID, 'address_street', sanitize_text_field($address_street));
        }
        if (!empty($address_psc)) {
            update_user_meta($existing->ID, 'address_psc', sanitize_text_field($address_psc));
        }
        if (!empty($address_city)) {
            update_user_meta($existing->ID, 'address_city', sanitize_text_field($address_city));
        }
        
        return $existing->ID;
    }
    
    $username = spa_generate_username_from_name($first_name, $last_name);
    $password = wp_generate_password(12, true);
    
    error_log("SPA: Creating parent with username: $username");
    
    $user_id = wp_insert_user([
        'user_login'   => $username,
        'user_email'   => sanitize_email($email),
        'user_pass'    => $password,
        'first_name'   => sanitize_text_field($first_name),
        'last_name'    => sanitize_text_field($last_name),
        'display_name' => trim($first_name . ' ' . $last_name),
        'nickname'     => $username,
        'role'         => 'spa_parent'
    ]);
    
    if (is_wp_error($user_id)) {
        error_log('SPA ERROR: ' . $user_id->get_error_message());
        return false;
    }
    
    update_user_meta($user_id, 'phone', sanitize_text_field($phone));
    
    if (!empty($address_street)) {
        update_user_meta($user_id, 'address_street', sanitize_text_field($address_street));
    }
    if (!empty($address_psc)) {
        update_user_meta($user_id, 'address_psc', sanitize_text_field($address_psc));
    }
    if (!empty($address_city)) {
        update_user_meta($user_id, 'address_city', sanitize_text_field($address_city));
    }
    
    error_log("SPA: Created parent ID $user_id");
    
    spa_send_welcome_email($email, $username, $password, $first_name);
    
    return $user_id;
}

/* =============================================
   SEND WELCOME EMAIL
   ============================================= */

function spa_send_welcome_email($email, $username, $password, $first_name = '') {
    
    if (empty($email)) {
        return false;
    }
    
    $site_name = get_bloginfo('name');
    $login_url = wp_login_url();
    
    $subject = "Vítajte na " . $site_name;
    
    $message = "Ahoj" . (!empty($first_name) ? " " . $first_name : "") . ",\n\n";
    $message .= "Bol/a si úspešne zaregistrovaný/á na " . $site_name . ".\n\n";
    $message .= "Prihlasovací údaje:\n";
    $message .= "Používateľské meno: " . $username . "\n";
    $message .= "Heslo: " . $password . "\n\n";
    $message .= "Prihlás sa tu: " . $login_url . "\n\n";
    $message .= "Ak si neuvedoril heslo, zmeň si ho po prihlásení.\n\n";
    $message .= "S pozdravom,\n" . $site_name;
    
    return wp_mail($email, $subject, $message);
}

/* =============================================
   HELPER: GENERATE USERNAME
   ============================================= */

function spa_generate_username_from_name($firstname, $lastname) {
    
    $firstname = spa_remove_diacritics($firstname);
    $lastname = spa_remove_diacritics($lastname);
    
    $firstname = strtolower(preg_replace('/[^a-z0-9]/i', '', $firstname));
    $lastname = strtolower(preg_replace('/[^a-z0-9]/i', '', $lastname));
    
    $base_username = $firstname . '.' . $lastname;
    
    if (strlen($base_username) > 50) {
        $base_username = substr($base_username, 0, 50);
    }
    
    $username = $base_username;
    $counter = 1;
    
    while (username_exists($username)) {
        $username = $base_username . $counter;
        $counter++;
    }
    
    return $username;
}

/* =============================================
   HELPER: REMOVE DIACRITICS
   ============================================= */

function spa_remove_diacritics($string) {
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
    
    return strtr($string, $chars);
}