<?php
/**
 * SPA Import - Parent User Helper
 * Vytvára / nájde rodiča ako WP_USER (role: spa_parent)
 * 
 * OPRAVY:
 * - Explicitný logging pred/po wp_create_user()
 * - Kontrola WP_Error
 * - Povinné uloženie všetkých meta polí
 * 
 * @package Samuel Piasecký ACADEMY
 * @subpackage Import
 * @version 1.1.0 - FIXED
 */
error_log('LOADED: spa-import-user-parent.php');

if (!defined('ABSPATH')) {
    exit;
}

/**
 * NÁJSŤ ALEBO VYTVORIŤ RODIČA AKO WP_USER
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
    error_log('IMPORT PARENT: function entered - email=' . $email);
    
    if (empty($email)) {
        error_log('SPA IMPORT ERROR PARENT: Email is empty');
        return false;
    }
    
    // 1. SKONTROLUJ EXISTUJÚCEHO
    $existing_user = get_user_by('email', $email);
    
    if ($existing_user) {
        error_log('SPA IMPORT PARENT: Found existing parent user ID ' . $existing_user->ID . ' for email ' . $email);
        
        // Aktualizuj meta
        if (!empty($first_name)) {
            wp_update_user([
                'ID' => $existing_user->ID,
                'first_name' => sanitize_text_field($first_name)
            ]);
        }
        if (!empty($last_name)) {
            wp_update_user([
                'ID' => $existing_user->ID,
                'last_name' => sanitize_text_field($last_name)
            ]);
        }
        if (!empty($phone)) {
            update_user_meta($existing_user->ID, 'phone', sanitize_text_field($phone));
        }
        
        return $existing_user->ID;
    }
    
    // 2. VYTVOR NOVÉHO
    $username = spa_import_generate_username_from_email($email);
    $password = wp_generate_password(16, true);
    
    error_log('IMPORT PARENT: Before wp_create_user() - username=' . $username . ', email=' . $email);
    
    $user_id = wp_create_user($username, $password, $email);
    
    error_log('IMPORT PARENT: After wp_create_user() - result=' . (is_wp_error($user_id) ? 'WP_ERROR' : 'user_id=' . $user_id));
    
    if (is_wp_error($user_id)) {
        error_log('SPA IMPORT ERROR PARENT: wp_create_user failed - ' . $user_id->get_error_message());
        return false;
    }
    
    if (!is_numeric($user_id) || intval($user_id) <= 0) {
        error_log('SPA IMPORT ERROR PARENT: Invalid user_id returned - ' . var_export($user_id, true));
        return false;
    }
    
    // 3. NASTAV ROLE
    error_log('IMPORT PARENT: Setting role spa_parent for user ID ' . $user_id);
    
    $user = new WP_User(intval($user_id));
    if (!isset($user->ID) || $user->ID === 0) {
        error_log('SPA IMPORT ERROR PARENT: Cannot create WP_User object for ID ' . $user_id);
        return false;
    }
    
    $user->set_role('spa_parent');
    error_log('IMPORT PARENT: Role set successfully');
    
    // 4. NASTAV MENO A DISPLAY NAME
    wp_update_user([
        'ID' => $user_id,
        'first_name' => sanitize_text_field($first_name),
        'last_name' => sanitize_text_field($last_name),
        'display_name' => trim(sanitize_text_field($first_name) . ' ' . sanitize_text_field($last_name))
    ]);
    error_log('IMPORT PARENT: Display name updated');
    
    // 5. ULOŽ PHONE META
    if (!empty($phone)) {
        update_user_meta($user_id, 'phone', sanitize_text_field($phone));
        error_log('IMPORT PARENT: Phone saved - ' . sanitize_text_field($phone));
    }
    
    // 6. ULOŽ VARIABILNÝ SYMBOL
    $vs = spa_import_generate_variabilny_symbol();
    update_user_meta($user_id, 'variabilny_symbol', $vs);
    error_log('IMPORT PARENT: VS generated and saved - ' . $vs);
    
    // 7. WELCOME EMAIL (ak existuje funkcia)
    if (function_exists('spa_import_send_parent_welcome_email')) {
        spa_import_send_parent_welcome_email($email, $username, $password, $first_name);
        error_log('IMPORT PARENT: Welcome email sent');
    }
    
    error_log('SPA IMPORT PARENT: SUCCESS - Created user ID ' . $user_id . ' | email=' . $email . ' | name=' . $first_name . ' ' . $last_name . ' | vs=' . $vs);
    
    return intval($user_id);
}