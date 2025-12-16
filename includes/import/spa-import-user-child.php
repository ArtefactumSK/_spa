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
 * NÁJSŤ ALEBO VYTVORIŤ DIEŤA AKO WP_USER
 * 
 * Parametre:
 *   @param string $first_name - Meno dieťaťa (povinný)
 *   @param string $last_name - Priezvisko dieťaťa (povinný)
 *   @param string $birthdate - Dátum narodenia (Y-m-d alebo D.M.YYYY)
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
 *   6. Ulož všetky povinné user_meta polia
 */
function spa_import_get_or_create_child($first_name, $last_name, $birthdate = '', $parent_user_id = 0, $birth_number = '') {
    error_log('IMPORT CHILD: function entered - first=' . $first_name . ', last=' . $last_name . ', parent_id=' . $parent_user_id);
    
    if (empty($first_name) || empty($last_name)) {
        error_log('SPA IMPORT ERROR CHILD: Name is empty - first=' . $first_name . ', last=' . $last_name);
        return false;
    }
    
    if (empty($parent_user_id)) {
        error_log('SPA IMPORT ERROR CHILD: Parent user ID is required');
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
                error_log('SPA IMPORT CHILD: Found existing child user ID ' . $child->ID);
                
                if (!empty($birthdate) && empty(get_user_meta($child->ID, 'date_of_birth', true))) {
                    update_user_meta($child->ID, 'date_of_birth', sanitize_text_field($birthdate));
                }
                if (!empty($birth_number) && empty(get_user_meta($child->ID, 'rodne_cislo', true))) {
                    $birth_num_clean = preg_replace('/[^0-9]/', '', $birth_number);
                    update_user_meta($child->ID, 'rodne_cislo', $birth_num_clean);
                }
                
                return intval($child->ID);
            }
        }
    }
    
    // 2. VYTVOR NOVÉHO
    $year = !empty($birthdate) ? date('Y', strtotime($birthdate)) : date('Y');
    $username_base = sanitize_user(strtolower($first_name . '-' . $last_name . '-' . $year));
    
    $username = $username_base;
    $counter = 1;
    while (username_exists($username)) {
        $username = $username_base . '-' . $counter;
        $counter++;
    }
    
    // 3. EMAIL: meno.priezvisko@piaseckyacademy.sk
    $email_base = sanitize_user(strtolower($first_name . '.' . $last_name), true);
    $child_email = $email_base . '@piaseckyacademy.sk';
    
    $counter = 1;
    $email_original = $child_email;
    $max_attempts = 100;
    $attempt = 0;
    
    while (email_exists($child_email) && $attempt < $max_attempts) {
        $counter++;
        $child_email = str_replace('@piaseckyacademy.sk', '-' . $counter . '@piaseckyacademy.sk', $email_original);
        $attempt++;
    }
    
    if ($attempt >= $max_attempts) {
        error_log('SPA IMPORT ERROR CHILD: Cannot generate unique email after ' . $max_attempts . ' attempts');
        return false;
    }
    
    $password = wp_generate_password(32);
    
    error_log('IMPORT CHILD: Before wp_create_user() - username=' . $username . ', email=' . $child_email);
    
    $child_user_id = wp_create_user($username, $password, $child_email);
    
    error_log('IMPORT CHILD: After wp_create_user() - result=' . (is_wp_error($child_user_id) ? 'WP_ERROR' : 'user_id=' . $child_user_id));
    
    if (is_wp_error($child_user_id)) {
        error_log('SPA IMPORT ERROR CHILD: wp_create_user failed - ' . $child_user_id->get_error_message());
        return false;
    }
    
    if (!is_numeric($child_user_id) || intval($child_user_id) <= 0) {
        error_log('SPA IMPORT ERROR CHILD: Invalid user_id returned - ' . var_export($child_user_id, true));
        return false;
    }
    
    // 4. NASTAV ROLE
    error_log('IMPORT CHILD: Setting role spa_child for user ID ' . $child_user_id);
    
    $user = new WP_User(intval($child_user_id));
    if (!isset($user->ID) || $user->ID === 0) {
        error_log('SPA IMPORT ERROR CHILD: Cannot create WP_User object for ID ' . $child_user_id);
        return false;
    }
    
    $user->set_role('spa_child');
    error_log('IMPORT CHILD: Role set successfully');
    
    // 5. NASTAV MENO A DISPLAY NAME
    wp_update_user([
        'ID' => $child_user_id,
        'first_name' => sanitize_text_field($first_name),
        'last_name' => sanitize_text_field($last_name),
        'display_name' => trim(sanitize_text_field($first_name) . ' ' . sanitize_text_field($last_name))
    ]);
    error_log('IMPORT CHILD: Display name updated');
    
    // 6. POVINNÉ USER META - PROFIL
    update_user_meta($child_user_id, 'parent_user_id', intval($parent_user_id));
    error_log('IMPORT CHILD: parent_user_id saved - ' . $parent_user_id);
    
    if (!empty($birthdate)) {
        update_user_meta($child_user_id, 'date_of_birth', sanitize_text_field($birthdate));
        error_log('IMPORT CHILD: date_of_birth saved - ' . $birthdate);
    }
    
    if (!empty($birth_number)) {
        $birth_num_clean = preg_replace('/[^0-9]/', '', $birth_number);
        update_user_meta($child_user_id, 'rodne_cislo', $birth_num_clean);
        error_log('IMPORT CHILD: rodne_cislo saved - ' . $birth_num_clean);
    }
    
    update_user_meta($child_user_id, 'health_notes', '');
    error_log('IMPORT CHILD: health_notes initialized (empty)');
    
    // 7. PIN
    $pin = spa_import_generate_pin();
    update_user_meta($child_user_id, 'spa_pin', spa_import_hash_pin($pin));
    update_user_meta($child_user_id, 'spa_pin_plain', $pin);
    error_log('IMPORT CHILD: PIN generated and saved - ' . $pin);
    
    // 8. VARIABILNÝ SYMBOL
    $vs = spa_import_generate_variabilny_symbol();
    update_user_meta($child_user_id, 'variabilny_symbol', $vs);
    error_log('IMPORT CHILD: VS generated and saved - ' . $vs);
    
    error_log('SPA IMPORT CHILD: SUCCESS - Created user ID ' . $child_user_id . ' | name=' . $first_name . ' ' . $last_name . ' | parent_id=' . $parent_user_id . ' | email=' . $child_email . ' | PIN=' . $pin . ' | VS=' . $vs);
    
    return intval($child_user_id);
}