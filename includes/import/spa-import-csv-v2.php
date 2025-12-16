<?php
/**
 * CSV Import Handler v2 - ORCHESTRÁTOR
 * 
 * ROLE:
 * - Načíta CSV súbor
 * - Extrahuje údaje z riadkov
 * - Volá import helpery:
 *   * spa-import-user-parent.php
 *   * spa-import-user-child.php
 *   * spa-import-registration.php
 * - Spracúva chyby
 * - Vracia štatistiku
 * 
 * ŽIADNE: priame vytvorenie userov / registrácií
 * (všetko delegované na helpery)
 * 
 * @package Samuel Piasecký ACADEMY
 * @subpackage Import
 * @version 2.0.0
 */

error_log('SPA IMPORT ENTRY POINT HIT');


if (!defined('ABSPATH')) {
    exit;
}


// LOAD SHARED HELPERS (MUSÍ BYŤ PRVÝ!)
require_once SPA_INCLUDES . 'import/spa-import-helpers.php';

// LOAD IMPORT HELPERY
require_once SPA_INCLUDES . 'import/spa-import-user-parent.php';
require_once SPA_INCLUDES . 'import/spa-import-user-child.php';
require_once SPA_INCLUDES . 'import/spa-import-registration.php';

/**
 * NÁJSŤ ALEBO VYTVORIŤ RODIČA AKO WP_USER
 * Role: spa_parent
 */
function spa_get_or_create_parent_user($parent_email, $parent_first_name = '', $parent_last_name = '', $parent_phone = '') {
    
    if (empty($parent_email)) {
        error_log('ERROR: Parent email is empty');
        return false;
    }
    
    // Skontroluj či user s emailom existuje
    $existing_user = get_user_by('email', $parent_email);
    
    if ($existing_user) {
        error_log('DEBUG: Found existing parent user ID ' . $existing_user->ID . ' for email ' . $parent_email);
        
        // Aktualizuj meta
        if (!empty($parent_first_name)) {
            wp_update_user(['ID' => $existing_user->ID, 'first_name' => $parent_first_name]);
        }
        if (!empty($parent_last_name)) {
            wp_update_user(['ID' => $existing_user->ID, 'last_name' => $parent_last_name]);
        }
        if (!empty($parent_phone)) {
            update_user_meta($existing_user->ID, 'phone', $parent_phone);
        }
        
        return $existing_user->ID;
    }
    
    // Vytvor nového WP user pre rodiča
    $username = sanitize_user(strtolower(str_replace(['@', '.'], ['_', '_'], $parent_email)));
    
    // Zabezpeč unique username
    $base_username = $username;
    $counter = 1;
    while (username_exists($username)) {
        $username = $base_username . $counter;
        $counter++;
    }
    
    $password = wp_generate_password(16, true);
    
    $user_id = wp_create_user($username, $password, $parent_email);
    
    if (is_wp_error($user_id)) {
        error_log('ERROR: Failed to create parent user: ' . $user_id->get_error_message());
        return false;
    }
    
    // Nastav role
    $user = new WP_User($user_id);
    $user->set_role('spa_parent');
    
    // Update first/last name
    wp_update_user([
        'ID' => $user_id,
        'first_name' => sanitize_text_field($parent_first_name),
        'last_name' => sanitize_text_field($parent_last_name),
        'display_name' => trim($parent_first_name . ' ' . $parent_last_name)
    ]);
    
    // Meta
    if (!empty($parent_phone)) {
        update_user_meta($user_id, 'phone', sanitize_text_field($parent_phone));
    }
    
    // VS
    $vs = spa_generate_variabilny_symbol();
    update_user_meta($user_id, 'variabilny_symbol', $vs);
    
    error_log('DEBUG: Created parent user ID ' . $user_id . ' with email ' . $parent_email);
    
    return $user_id;
}

/**
 * NÁJSŤ ALEBO VYTVORIŤ DIEŤA AKO WP_USER
 * Role: spa_child
 * Email: meno.priezvisko@piaseckyacademy.sk
 */
function spa_get_or_create_child_user($child_first_name, $child_last_name, $child_birthdate = '', $parent_user_id = 0, $birth_number = '') {
    
    if (empty($child_first_name) || empty($child_last_name)) {
        error_log('ERROR: Child name is empty');
        return false;
    }
    
    // Hľadaj dieťa podľa mena + rodiča
    $existing_children = get_users([
        'meta_key' => 'parent_user_id',
        'meta_value' => intval($parent_user_id),
        'role' => 'spa_child'
    ]);
    
    if (!empty($existing_children)) {
        foreach ($existing_children as $child) {
            $child_fname = get_user_meta($child->ID, 'first_name', true);
            $child_lname = get_user_meta($child->ID, 'last_name', true);
            
            if (strcasecmp($child_fname, $child_first_name) === 0 && 
                strcasecmp($child_lname, $child_last_name) === 0) {
                
                error_log('DEBUG: Found existing child user ID ' . $child->ID);
                
                // Aktualizuj meta ak chýbajú
                if (!empty($child_birthdate) && empty(get_user_meta($child->ID, 'birthdate', true))) {
                    update_user_meta($child->ID, 'birthdate', sanitize_text_field($child_birthdate));
                }
                if (!empty($birth_number) && empty(get_user_meta($child->ID, 'rodne_cislo', true))) {
                    $birth_num_clean = preg_replace('/[^0-9]/', '', $birth_number);
                    update_user_meta($child->ID, 'rodne_cislo', $birth_num_clean);
                }
                
                return $child->ID;
            }
        }
    }
    
    // Vytvor nového WP user pre dieťa
    // Username: meno-priezvisko-rok
    $year = !empty($child_birthdate) ? date('Y', strtotime($child_birthdate)) : date('Y');
    $username_base = sanitize_user(strtolower($child_first_name . '-' . $child_last_name . '-' . $year));
    
    // Zabezpeč unique username
    $username = $username_base;
    $counter = 1;
    while (username_exists($username)) {
        $username = $username_base . '-' . $counter;
        $counter++;
    }
    
    // Email: meno.priezvisko@piaseckyacademy.sk
    $email_base = sanitize_user(strtolower($child_first_name . '.' . $child_last_name), true);
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
        error_log('ERROR: Failed to create child user: ' . $child_user_id->get_error_message());
        return false;
    }
    
    // Nastav role
    $user = new WP_User($child_user_id);
    $user->set_role('spa_child');
    
    // Update first/last name
    wp_update_user([
        'ID' => $child_user_id,
        'first_name' => sanitize_text_field($child_first_name),
        'last_name' => sanitize_text_field($child_last_name),
        'display_name' => trim($child_first_name . ' ' . $child_last_name)
    ]);
    
    // Meta
    update_user_meta($child_user_id, 'parent_user_id', intval($parent_user_id));
    
    if (!empty($child_birthdate)) {
        update_user_meta($child_user_id, 'birthdate', sanitize_text_field($child_birthdate));
    }
    
    if (!empty($birth_number)) {
        $birth_num_clean = preg_replace('/[^0-9]/', '', $birth_number);
        update_user_meta($child_user_id, 'rodne_cislo', $birth_num_clean);
    }
    
    // PIN
    $pin = spa_generate_pin();
    update_user_meta($child_user_id, 'spa_pin', spa_hash_pin($pin));
    update_user_meta($child_user_id, 'spa_pin_plain', $pin);
    
    // VS
    $vs = spa_generate_variabilny_symbol();
    update_user_meta($child_user_id, 'variabilny_symbol', $vs);
    
    error_log('DEBUG: Created child user ID ' . $child_user_id . ' - ' . $child_first_name . ' ' . $child_last_name);
    
    return $child_user_id;
}

/**
 * EXTRAKT ÚDAJOV Z CSV RIADKU
 */
function spa_extract_csv_row_data($row) {
    
    $data = [
        'parent_email' => '',
        'parent_first_name' => '',
        'parent_last_name' => '',
        'parent_phone' => '',
        'parent_address_street' => '',
        'parent_address_psc' => '',
        'parent_address_city' => '',
        'child_first_name' => '',
        'child_last_name' => '',
        'child_birthdate' => '',
        'child_birth_number' => ''
    ];
    
    // Hľadaj EMAIL
    for ($i = 0; $i < count($row); $i++) {
        $val = trim($row[$i] ?? '');
        if (!empty($val) && filter_var($val, FILTER_VALIDATE_EMAIL)) {
            $data['parent_email'] = sanitize_email($val);
            
            // Meno rodiča za emailom
            for ($j = $i + 1; $j < count($row) && empty($data['parent_first_name']); $j++) {
                $v = trim($row[$j] ?? '');
                if (!empty($v) && strlen($v) < 50 && !filter_var($v, FILTER_VALIDATE_EMAIL)) {
                    $data['parent_first_name'] = sanitize_text_field($v);
                }
            }
            
            // Priezvisko rodiča za menom
            for ($j = $i + 2; $j < count($row) && empty($data['parent_last_name']); $j++) {
                $v = trim($row[$j] ?? '');
                if (!empty($v) && strlen($v) < 50 && !filter_var($v, FILTER_VALIDATE_EMAIL) && $v !== $data['parent_first_name']) {
                    $data['parent_last_name'] = sanitize_text_field($v);
                }
            }
            
            break;
        }
    }
    
    // Hľadaj MENO DIEŤAŤA (index 0-2)
    for ($i = 0; $i < min(3, count($row)); $i++) {
        $val = trim($row[$i] ?? '');
        if (!empty($val) && strlen($val) < 50 && !filter_var($val, FILTER_VALIDATE_EMAIL)) {
            $data['child_first_name'] = sanitize_text_field($val);
            break;
        }
    }
    
    // Hľadaj PRIEZVISKO DIEŤAŤA (index 1-3)
    for ($i = 1; $i < min(4, count($row)); $i++) {
        $val = trim($row[$i] ?? '');
        if (!empty($val) && strlen($val) < 50 && !filter_var($val, FILTER_VALIDATE_EMAIL) && 
            $val !== $data['child_first_name']) {
            $data['child_last_name'] = sanitize_text_field($val);
            break;
        }
    }
    
    // Hľadaj DÁTUM NARODENIA (D.M.YYYY)
    for ($i = 0; $i < count($row); $i++) {
        $val = trim($row[$i] ?? '');
        if (!empty($val) && preg_match('/^\d{1,2}\.\d{1,2}\.\d{4}$/', $val)) {
            $data['child_birthdate'] = sanitize_text_field($val);
            break;
        }
    }
    
    // Hľadaj RODNÉ ČÍSLO
    for ($i = 0; $i < count($row); $i++) {
        $val = trim($row[$i] ?? '');
        $val_clean = preg_replace('/[^0-9]/', '', $val);
        if (!empty($val_clean) && (strlen($val_clean) === 9 || strlen($val_clean) === 10)) {
            $data['child_birth_number'] = $val;
            break;
        }
    }
    
    return $data;
}

/**
 * SPRACOVANIE JEDNÉHO CSV SÚBORU
 */
function spa_process_single_csv($file_path, $filename, $target_group_id = 0, $training_day = '', $training_time = '') {
    
    $stats = [
        'success' => 0,
        'errors' => 0,
        'skipped' => 0,
        'error_log' => []
    ];
    
    $handle = fopen($file_path, 'r');
    
    if ($handle === false) {
        $stats['errors']++;
        $stats['error_log'][] = 'ERROR: Chyba pri otvorení súboru: ' . $filename;
        return $stats;
    }
    
    // Preskočí header
    $header = fgetcsv($handle, 0, ';', '"');
    if (!$header) {
        $stats['errors']++;
        $stats['error_log'][] = 'ERROR: Súbor je prázdny';
        fclose($handle);
        return $stats;
    }
    
    $row_number = 1;
    
    while (($row = fgetcsv($handle, 0, ';', '"')) !== false) {
        $row_number++;
        
        // Preskoč prázdne riadky
        if (empty(array_filter($row))) {
            $stats['skipped']++;
            continue;
        }
        
        error_log('=== Processing CSV row ' . $row_number . ' ===');
        
        // Extrakt údajov
        $data = spa_extract_csv_row_data($row);
        
        // VALIDÁCIA
        if (empty($data['parent_email'])) {
            $stats['errors']++;
            $stats['error_log'][] = 'Riadok ' . $row_number . ': Nenájdený email rodiča';
            continue;
        }
        
        if (empty($data['child_first_name']) || empty($data['child_last_name'])) {
            $stats['errors']++;
            $stats['error_log'][] = 'Riadok ' . $row_number . ': Nenájdené meno/priezvisko dieťaťa';
            continue;
        }
        
        // === VYTVORENIE RODIČOVHO WP_USER ===
        $parent_user_id = spa_get_or_create_parent_user(
            $data['parent_email'],
            $data['parent_first_name'] ?: 'Rodič',
            $data['parent_last_name'] ?: 'Import',
            $data['parent_phone']
        );
        
        if (!$parent_user_id) {
            $stats['errors']++;
            $stats['error_log'][] = 'Riadok ' . $row_number . ': Chyba vytvorenia rodiča';
            continue;
        }
        
        // === VYTVORENIE DETSKÉHO WP_USER ===
        $child_user_id = spa_get_or_create_child_user(
            $data['child_first_name'],
            $data['child_last_name'],
            $data['child_birthdate'],
            $parent_user_id,
            $data['child_birth_number']
        );
        
        if (!$child_user_id) {
            $stats['errors']++;
            $stats['error_log'][] = 'Riadok ' . $row_number . ': Chyba vytvorenia dieťaťa';
            continue;
        }
        
        // === VYTVORENIE REGISTRÁCIE ===
        $child_name = get_user_meta($child_user_id, 'first_name', true) . ' ' . get_user_meta($child_user_id, 'last_name', true);
        $registration_title = trim($child_name);
        
        $registration_id = wp_insert_post([
            'post_type' => 'spa_registration',
            'post_title' => $registration_title,
            'post_status' => 'publish'
        ], true);
        
        if (is_wp_error($registration_id)) {
            $stats['errors']++;
            $stats['error_log'][] = 'Riadok ' . $row_number . ': Chyba vytvorenia registrácie';
            continue;
        }
        
        // === ULOŽENIE META ===
        update_post_meta($registration_id, 'client_user_id', intval($child_user_id));
        update_post_meta($registration_id, 'parent_user_id', intval($parent_user_id));
        update_post_meta($registration_id, 'program_id', intval($target_group_id));
        update_post_meta($registration_id, 'training_day', sanitize_text_field($training_day));
        update_post_meta($registration_id, 'training_time', sanitize_text_field($training_time));
        update_post_meta($registration_id, 'status', 'active');
        update_post_meta($registration_id, 'registration_date', current_time('mysql'));
        update_post_meta($registration_id, 'import_source', 'csv');
        update_post_meta($registration_id, 'import_filename', $filename);
        update_post_meta($registration_id, 'import_timestamp', current_time('mysql'));
        
        error_log('REGISTRATION CREATED: ID ' . $registration_id . ' | child_user_id=' . $child_user_id . ' | parent_user_id=' . $parent_user_id);
        
        $stats['success']++;
    }
    
    fclose($handle);
    
    return $stats;
}

/**
 * HLAVNÝ ENTRY POINT
 */
function spa_process_csv_import() {
    
    error_log('=== SPA CSV IMPORT START ===');
    
    if (!current_user_can('manage_options')) {
        wp_die('Nemáte oprávnenie');
    }
    
    if (!isset($_POST['spa_csv_import_nonce']) || !wp_verify_nonce($_POST['spa_csv_import_nonce'], 'spa_csv_import')) {
        wp_die('Security check failed');
    }
    
    $target_group_id = isset($_POST['import_group_id']) ? intval($_POST['import_group_id']) : 0;
    $training_day = isset($_POST['import_day']) ? sanitize_text_field($_POST['import_day']) : '';
    $training_time = isset($_POST['import_time']) ? sanitize_text_field($_POST['import_time']) : '';
    
    if (!$target_group_id || get_post_type($target_group_id) !== 'spa_group') {
        wp_die('Neplatný program');
    }
    
    if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
        wp_die('Chyba pri nahrávaní súboru');
    }
    
    $file_path = $_FILES['csv_file']['tmp_name'];
    $filename = $_FILES['csv_file']['name'];
    
    $stats = spa_process_single_csv($file_path, $filename, $target_group_id, $training_day, $training_time);
    
    error_log('=== SPA CSV IMPORT COMPLETE ===');
    
    wp_redirect(add_query_arg([
        'post_type' => 'spa_registration',
        'import_success' => $stats['success'],
        'import_errors' => $stats['errors'],
        'import_skipped' => $stats['skipped']
    ], admin_url('edit.php')));
    
    exit;
}

add_action('admin_post_spa_import_csv', 'spa_process_csv_import');

/**
 * HELPER FUNKCIE
 */

function spa_generate_variabilny_symbol() {
    global $wpdb;
    $max_vs = $wpdb->get_var("
        SELECT MAX(CAST(meta_value AS UNSIGNED)) 
        FROM {$wpdb->usermeta} 
        WHERE meta_key = 'variabilny_symbol'
        AND meta_value REGEXP '^[0-9]{3,}$'
    ");
    
    $next_vs = $max_vs ? intval($max_vs) + 1 : 100;
    if ($next_vs < 100) $next_vs = 100;
    
    return str_pad($next_vs, 3, '0', STR_PAD_LEFT);
}

function spa_generate_pin() {
    return str_pad(rand(1000, 9999), 4, '0', STR_PAD_LEFT);
}

function spa_hash_pin($pin) {
    return wp_hash_password($pin);
}