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

// ═══════════════════════════════════════════════════════════════════════════════
// FIX 3: /includes/import/spa-import-user-parent.php
// ═══════════════════════════════════════════════════════════════════════════════
function spa_import_get_or_create_parent($email, $first_name = '', $last_name = '', $phone = '') {
    error_log('[SPA IMPORT] Parent: Entering - email=' . $email);
    
    if (empty($email)) {
        error_log('[SPA IMPORT] Parent ERROR: Email is empty');
        return false;
    }
    
    // 1. SKONTROLUJ EXISTUJÚCEHO
    $existing_user = get_user_by('email', $email);
    
    if ($existing_user) {
        error_log('[SPA IMPORT] Parent found existing ID=' . $existing_user->ID . ' email=' . $email);
        
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
            error_log('[SPA IMPORT] Parent meta: phone updated');
        }
        
        return $existing_user->ID;
    }
    
    // 2. VYTVOR NOVÉHO
    $username = spa_import_generate_username_from_email($email);
    $password = wp_generate_password(16, true);
    
    error_log('[SPA IMPORT] Parent: Creating new - username=' . $username . ' email=' . $email);
    
    $user_id = wp_create_user($username, $password, $email);
    
    if (is_wp_error($user_id)) {
        error_log('[SPA IMPORT] Parent ERROR: wp_create_user failed - ' . $user_id->get_error_message());
        return false;
    }
    
    if (!is_numeric($user_id) || intval($user_id) <= 0) {
        error_log('[SPA IMPORT] Parent ERROR: Invalid user_id');
        return false;
    }
    
    error_log('[SPA IMPORT] Parent created user ID=' . $user_id);
    
    // 3. NASTAV ROLE
    $user = new WP_User(intval($user_id));
    if (!isset($user->ID) || $user->ID === 0) {
        error_log('[SPA IMPORT] Parent ERROR: Cannot load WP_User object');
        return false;
    }
    
    $user->set_role('spa_parent');
    error_log('[SPA IMPORT] Parent role set to spa_parent');
    
    // 4. NASTAV MENO A DISPLAY NAME
    wp_update_user([
        'ID' => $user_id,
        'first_name' => sanitize_text_field($first_name),
        'last_name' => sanitize_text_field($last_name),
        'display_name' => trim(sanitize_text_field($first_name) . ' ' . sanitize_text_field($last_name))
    ]);
    
    // 5. ULOŽ PHONE
    if (!empty($phone)) {
        update_user_meta($user_id, 'phone', sanitize_text_field($phone));
        error_log('[SPA IMPORT] Parent meta: phone=' . sanitize_text_field($phone));
    }
    
    // 6. ULOŽ VS
    $vs = spa_import_generate_variabilny_symbol();
    update_user_meta($user_id, 'variabilny_symbol', $vs);
    error_log('[SPA IMPORT] Parent meta: VS=' . $vs);
    
    error_log('[SPA IMPORT] Parent SUCCESS: ID=' . $user_id . ' | name=' . $first_name . ' ' . $last_name . ' | email=' . $email);
    
    return intval($user_id);
}