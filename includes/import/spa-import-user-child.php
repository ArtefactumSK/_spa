<?php
/**
 * SPA Import - Child User Helper
 * Vytvára / nájde dieťa ako WP_USER (role: spa_child)
 * 
 * POUŽÍVANÉ IBA: spa-import-csv-v2.php
 * ŽIADNE: zásahy do spa-registration-helpers.php
 * 
 * @package Samuel Piasecký ACADEMY
 * @subpackage Import
 * @version 1.0.0
 */
error_log('LOADED: spa-import-user-child.php');

if (!defined('ABSPATH')) {
    exit;
}

/**
 * NÁJSŤ ALEBO VYTVORIŤ DIEŤA AKO WP_USER
 * 
 * Parametre:
 *   @param string $first_name - Meno dieťaťa (povinný)
 *   @param string $last_name - Priezvisko dieťaťa (povinný)
 *   @param string $birthdate - Dátum narodenia (Y-m-d)
 *   @param int $parent_user_id - ID rodiča (povinný)
 *   @param string $birth_number - Rodné číslo
 * 
 * Výstup:
 *   @return int|false - User ID dieťaťa alebo false
 * 
 * Logika:
 *   1. Hľadaj dieťa podľa: parent_id + meno + priezvisko
 *   2. Ak nájdeš → vráť ID
 *   3. Ak nie → vytvor nového user s rolou spa_child
 *   4. Email: meno.priezvisko@piaseckyacademy.sk
 *   5. Vygeneruj PIN + VS
 */
function spa_import_get_or_create_child($first_name, $last_name, $birthdate = '', $parent_user_id = 0, $birth_number = '') {
    error_log('IMPORT CHILD: function entered');

    
    if (empty($first_name) || empty($last_name)) {
        error_log('SPA IMPORT ERROR: Child name is empty - first=' . $first_name . ', last=' . $last_name);
        return false;
    }
    
    if (empty($parent_user_id)) {
        error_log('SPA IMPORT ERROR: Parent user ID is required');
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
                error_log('SPA IMPORT: Found existing child user ID ' . $child->ID);
                
                // Aktualizuj meta ak chýbajú
                if (!empty($birthdate) && empty(get_user_meta($child->ID, 'birthdate', true))) {
                    update_user_meta($child->ID, 'birthdate', sanitize_text_field($birthdate));
                }
                if (!empty($birth_number) && empty(get_user_meta($child->ID, 'rodne_cislo', true))) {
                    $birth_num_clean = preg_replace('/[^0-9]/', '', $birth_number);
                    update_user_meta($child->ID, 'rodne_cislo', $birth_num_clean);
                }
                
                return $child->ID;
            }
        }
    }
    
    // 2. VYTVOR NOVÉHO
    $year = !empty($birthdate) ? date('Y', strtotime($birthdate)) : date('Y');
    $username_base = sanitize_user(strtolower($first_name . '-' . $last_name . '-' . $year));
    
    // Zabezpeč unique username
    $username = $username_base;
    $counter = 1;
    while (username_exists($username)) {
        $username = $username_base . '-' . $counter;
        $counter++;
    }
    
    // 3. EMAIL: meno.priezvisko@piaseckyacademy.sk
    $email_base = sanitize_user(strtolower($first_name . '.' . $last_name), true);
    $child_email = $email_base . '@piaseckyacademy.sk';
    
    // Zabezpeč unique email
    $counter = 1;
    $email_original = $child_email;
    while (email_exists($child_email)) {
        $child_email = str_replace('@piaseckyacademy.sk', '-' . $counter . '@piaseckyacademy.sk', $email_original);
        $counter++;
    }
    
    $password = wp_generate_password(32);
    
    $child_user_id = wp_create_user($username, $password, $child_email);
    
    if (is_wp_error($child_user_id)) {
        error_log('SPA IMPORT ERROR: Failed to create child user: ' . $child_user_id->get_error_message());
        return false;
    }
    
    // 4. NASTAV ROLE + MENO
    $user = new WP_User($child_user_id);
    $user->set_role('spa_child');
    
    wp_update_user([
        'ID' => $child_user_id,
        'first_name' => sanitize_text_field($first_name),
        'last_name' => sanitize_text_field($last_name),
        'display_name' => trim($first_name . ' ' . $last_name)
    ]);
    
    // 5. META - POVINNÉ
    update_user_meta($child_user_id, 'parent_user_id', intval($parent_user_id));
    
    if (!empty($birthdate)) {
        update_user_meta($child_user_id, 'birthdate', sanitize_text_field($birthdate));
    }
    
    if (!empty($birth_number)) {
        $birth_num_clean = preg_replace('/[^0-9]/', '', $birth_number);
        update_user_meta($child_user_id, 'rodne_cislo', $birth_num_clean);
    }
    
    // 6. PIN
    $pin = spa_import_generate_pin();
    update_user_meta($child_user_id, 'spa_pin', spa_import_hash_pin($pin));
    update_user_meta($child_user_id, 'spa_pin_plain', $pin);
    
    // 7. VS
    $vs = spa_import_generate_variabilny_symbol();
    update_user_meta($child_user_id, 'variabilny_symbol', $vs);
    
    error_log('SPA IMPORT: Created child user ID ' . $child_user_id . ' - ' . $first_name . ' ' . $last_name . ', parent_id=' . $parent_user_id . ', PIN=' . $pin . ', VS=' . $vs);
    
    return $child_user_id;
}