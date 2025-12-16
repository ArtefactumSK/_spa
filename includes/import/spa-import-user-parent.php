<?php
/**
 * SPA Import - Parent User Helper
 * Vytvára / nájde rodiča ako WP_USER (role: spa_parent)
 * 
 * POUŽÍVANÉ IBA: spa-import-csv-v2.php
 * ŽIADNE: zásahy do spa-registration-helpers.php
 * 
 * @package Samuel Piasecký ACADEMY
 * @subpackage Import
 * @version 1.0.0
 */
error_log('LOADED: spa-import-user-parent.php');

if (!defined('ABSPATH')) {
    exit;
}

/**
 * NÁJSŤ ALEBO VYTVORIŤ RODIČA KAO WP_USER
 * 
 * Parametre:
 *   @param string $email - Email rodiča (povinný, jedinečný identifikátor)
 *   @param string $first_name - Meno rodiča
 *   @param string $last_name - Priezvisko rodiča
 *   @param string $phone - Telefón rodiča
 * 
 * Výstup:
 *   @return int|false - User ID rodiča alebo false
 * 
 * Logika:
 *   1. Skontroluj či user s emailom existuje
 *   2. Ak áno → aktualizuj meta, vráť ID
 *   3. Ak nie → vytvor nového user s rolou spa_parent
 *   4. Vygeneruj a ulož VS
 *   5. Pošli welcome email
 */
function spa_import_get_or_create_parent($email, $first_name = '', $last_name = '', $phone = '') {
    error_log('IMPORT PARENT: function entered');
    
    if (empty($email)) {
        error_log('SPA IMPORT ERROR: Parent email is empty');
        return false;
    }
    
    // 1. SKONTROLUJ EXISTUJÚCEHO
    $existing_user = get_user_by('email', $email);
    
    if ($existing_user) {
        error_log('SPA IMPORT: Found existing parent user ID ' . $existing_user->ID . ' for email ' . $email);
        
        // Aktualizuj meta
        if (!empty($first_name)) {
            wp_update_user(['ID' => $existing_user->ID, 'first_name' => sanitize_text_field($first_name)]);
        }
        if (!empty($last_name)) {
            wp_update_user(['ID' => $existing_user->ID, 'last_name' => sanitize_text_field($last_name)]);
        }
        if (!empty($phone)) {
            update_user_meta($existing_user->ID, 'phone', sanitize_text_field($phone));
        }
        
        return $existing_user->ID;
    }
    
    // 2. VYTVOR NOVÉHO
    $username = spa_import_generate_username_from_email($email);
    
    $password = wp_generate_password(16, true);
    
    $user_id = wp_create_user($username, $password, $email);
    
    if (is_wp_error($user_id)) {
        error_log('SPA IMPORT ERROR: Failed to create parent user: ' . $user_id->get_error_message());
        return false;
    }
    
    // 3. NASTAV ROLE + MENO
    $user = new WP_User($user_id);
    $user->set_role('spa_parent');
    
    wp_update_user([
        'ID' => $user_id,
        'first_name' => sanitize_text_field($first_name),
        'last_name' => sanitize_text_field($last_name),
        'display_name' => trim($first_name . ' ' . $last_name)
    ]);
    
    // 4. META
    if (!empty($phone)) {
        update_user_meta($user_id, 'phone', sanitize_text_field($phone));
    }
    
    // 5. VS
    $vs = spa_import_generate_variabilny_symbol();
    update_user_meta($user_id, 'variabilny_symbol', $vs);
    
    // 6. WELCOME EMAIL
    spa_import_send_parent_welcome_email($email, $username, $password, $first_name);
    
    error_log('SPA IMPORT: Created parent user ID ' . $user_id . ' with email ' . $email);
    error_log('IMPORT PARENT: about to create user with email: ' . $email);
    
    return $user_id;
}