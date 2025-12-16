<?php
/**
 * SPA Import - Child User Helper
 * Vytvára / nájde dieťa ako WP_USER (role: spa_child)
 * 
 * OPRAVY:
 * - Povinné user_meta polia: date_of_birth, rodne_cislo, health_notes, parent_user_id
 * - Explicitný logging pred/po wp_create_user()
 * - Kontrola WP_Error
 * 
 * @package Samuel Piasecký ACADEMY
 * @subpackage Import
 * @version 1.1.0 - FIXED
 */
error_log('LOADED: spa-import-user-child.php');

if (!defined('ABSPATH')) {
    exit;
}
/**
 * ═══════════════════════════════════════════════════════════════════════════════
 * FIX2: /includes/import/spa-import-user-child.php
 * ═══════════════════════════════════════════════════════════════════════════════
 * 
 * OPRAVY:
 * 1. ❌ username z "alexia-lendvorska-2023" → "alexia.lendvorska"
 * 2. ❌ email z "alexia.lendvorska-2@..." → "alexia.lendvorska@piaseckyacademy.sk"
 * 3. ✅ date_of_birth uloží do user_meta
 */

function spa_import_get_or_create_child($first_name, $last_name, $birthdate = '', $parent_user_id = 0, $birth_number = '') {
    error_log('[SPA IMPORT] Child: Entering - first=' . $first_name . ', last=' . $last_name . ', parent_id=' . $parent_user_id);
    
    if (empty($first_name) || empty($last_name)) {
        error_log('[SPA IMPORT] Child ERROR: Name is empty');
        return false;
    }
    
    if (empty($parent_user_id)) {
        error_log('[SPA IMPORT] Child ERROR: Parent user ID is required');
        return false;
    }
    
    // 1. HĽADAJ EXISTUJÚCEHO
    $existing_children = get_users([
        'meta_key' => 'parent_user_id',
        'meta_value' => intval($parent_user_id),
        'role' => 'spa_child'
    ]);
    
    if (!empty($existing_children)) {
        foreach ($existing_children as $child) {
            $child_fname = get_user_meta($child->ID, 'first_name', true);
            $child_lname = get_user_meta($child->ID, 'last_name', true);
            
            if (strcasecmp($child_fname, $first_name) === 0 && strcasecmp($child_lname, $last_name) === 0) {
                error_log('[SPA IMPORT] Child found existing ID=' . $child->ID);
                
                if (!empty($birthdate) && empty(get_user_meta($child->ID, 'date_of_birth', true))) {
                    update_user_meta($child->ID, 'date_of_birth', sanitize_text_field($birthdate));
                    error_log('[SPA IMPORT] Child meta: date_of_birth updated (was empty)');
                }
                if (!empty($birth_number) && empty(get_user_meta($child->ID, 'rodne_cislo', true))) {
                    $birth_num_clean = preg_replace('/[^0-9]/', '', $birth_number);
                    update_user_meta($child->ID, 'rodne_cislo', $birth_num_clean);
                }
                
                return intval($child->ID);
            }
        }
    }
    
    // 2. VYTVOR NOVÉHO - USERNAME: meno.priezvisko (BEZ ROKU!)
    $username_base = sanitize_user(strtolower($first_name . '.' . $last_name), true);
    
    $username = $username_base;
    $counter = 1;
    while (username_exists($username)) {
        $username = $username_base . '-' . $counter;
        $counter++;
    }
    
    error_log('[SPA IMPORT] Child username generated: ' . $username);
    
    // 3. EMAIL: meno.priezvisko@piaseckyacademy.sk (BEZ SUFFIXU!)
    $email_base = sanitize_user(strtolower($first_name . '.' . $last_name), true);
    $child_email = $email_base . '@piaseckyacademy.sk';
    
    $counter = 1;
    $email_original = $child_email;
    $max_attempts = 100;
    $attempt = 0;
    
    // Hľadaj unikátny email len ak existuje
    while (email_exists($child_email) && $attempt < $max_attempts) {
        $counter++;
        $child_email = str_replace('@piaseckyacademy.sk', '-' . $counter . '@piaseckyacademy.sk', $email_original);
        $attempt++;
    }
    
    if ($attempt >= $max_attempts) {
        error_log('[SPA IMPORT] Child ERROR: Cannot generate unique email');
        return false;
    }
    
    error_log('[SPA IMPORT] Child email generated: ' . $child_email);
    
    $password = wp_generate_password(32);
    
    error_log('[SPA IMPORT] Child: Before wp_create_user() - username=' . $username . ', email=' . $child_email);
    
    $child_user_id = wp_create_user($username, $password, $child_email);
    
    if (is_wp_error($child_user_id)) {
        error_log('[SPA IMPORT] Child ERROR: wp_create_user failed - ' . $child_user_id->get_error_message());
        return false;
    }
    
    if (!is_numeric($child_user_id) || intval($child_user_id) <= 0) {
        error_log('[SPA IMPORT] Child ERROR: Invalid user_id');
        return false;
    }
    
    error_log('[SPA IMPORT] Child created user ID=' . $child_user_id);
    
    // 4. NASTAV ROLE
    $user = new WP_User(intval($child_user_id));
    if (!isset($user->ID) || $user->ID === 0) {
        error_log('[SPA IMPORT] Child ERROR: Cannot load WP_User object');
        return false;
    }
    
    $user->set_role('spa_child');
    error_log('[SPA IMPORT] Child role set to spa_child');
    
    // 5. NASTAV MENO A DISPLAY NAME
    wp_update_user([
        'ID' => $child_user_id,
        'first_name' => sanitize_text_field($first_name),
        'last_name' => sanitize_text_field($last_name),
        'display_name' => trim(sanitize_text_field($first_name) . ' ' . sanitize_text_field($last_name))
    ]);
    
    error_log('[SPA IMPORT] Child display name updated');
    
    // 6. POVINNÉ USER META - PROFIL
    update_user_meta($child_user_id, 'parent_user_id', intval($parent_user_id));
    error_log('[SPA IMPORT] Child meta: parent_user_id=' . $parent_user_id);
    
    // ✅ KRITICKÉ: Ulož date_of_birth
    if (!empty($birthdate)) {
        update_user_meta($child_user_id, 'date_of_birth', sanitize_text_field($birthdate));
        error_log('[SPA IMPORT] Child meta: date_of_birth=' . $birthdate);
    } else {
        error_log('[SPA IMPORT] Child WARNING: birthdate is empty!');
    }
    
    if (!empty($birth_number)) {
        $birth_num_clean = preg_replace('/[^0-9]/', '', $birth_number);
        update_user_meta($child_user_id, 'rodne_cislo', $birth_num_clean);
        error_log('[SPA IMPORT] Child meta: rodne_cislo=' . $birth_num_clean);
    }
    
    update_user_meta($child_user_id, 'health_notes', '');
    error_log('[SPA IMPORT] Child meta: health_notes initialized');
    
    // 7. PIN
    $pin = spa_import_generate_pin();
    update_user_meta($child_user_id, 'spa_pin', spa_import_hash_pin($pin));
    update_user_meta($child_user_id, 'spa_pin_plain', $pin);
    error_log('[SPA IMPORT] Child meta: PIN=' . $pin);
    
    // 8. VARIABILNÝ SYMBOL
    $vs = spa_import_generate_variabilny_symbol();
    update_user_meta($child_user_id, 'variabilny_symbol', $vs);
    error_log('[SPA IMPORT] Child meta: VS=' . $vs);
    
    error_log('[SPA IMPORT] Child SUCCESS: ID=' . $child_user_id . ' | name=' . $first_name . ' ' . $last_name . ' | email=' . $child_email . ' | parent=' . $parent_user_id);
    
    return intval($child_user_id);
}