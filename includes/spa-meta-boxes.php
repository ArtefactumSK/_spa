<?php
/**
 * CSV Import Handler v2 - OPRAVENÉ podľa CSV štruktúry
 * 
 * CSV ŠTRUKTÚRA (podľa indexov):
 * [0] Meno dieťaťa
 * [1] Priezvisko dieťaťa
 * [2] Email dieťaťa (nepoužíva sa - vygeneruje sa)
 * [3] Tel dieťaťa (nepoužíva sa)
 * [4] DOB dieťaťa
 * [5] RC dieťaťa
 * [6-19] Ignorované (VS, adresy, atd)
 * [20] Stav
 * [21] Email rodiča ← KRITICKÉ
 * [22] Meno rodiča
 * [23] Priezvisko rodiča
 * [24] Tel rodiča
 * [25] Ulica rodiča
 * [26] PSČ rodiča
 * [27] Mesto rodiča
 * [28+] Ignorované
 * 
 * @package Samuel Piasecký ACADEMY
 * @subpackage Import
 * @version 2.2.0 - CSV extraction fixed
 */

error_log('LOADED: spa-import-csv-v2.php');

if (!defined('ABSPATH')) {
    exit;
}

// LOAD SHARED HELPERS
require_once SPA_INCLUDES . 'import/spa-import-helpers.php';
require_once SPA_INCLUDES . 'import/spa-import-user-parent.php';
require_once SPA_INCLUDES . 'import/spa-import-user-child.php';
require_once SPA_INCLUDES . 'import/spa-import-registration.php';

/**
 * EXTRAKT ÚDAJOV Z CSV RIADKU
 * 
 * Podľa presnej CSV štruktúry s rozlíšením:
 * - Dieťa: indexy 0-5
 * - Rodič: indexy 21-27
 */
function spa_extract_csv_row_data($row) {
    
    $data = [
        // DIEŤA
        'child_first_name' => '',
        'child_last_name' => '',
        'child_birthdate' => '',
        'child_birth_number' => '',
        
        // RODIČ
        'parent_email' => '',
        'parent_first_name' => '',
        'parent_last_name' => '',
        'parent_phone' => '',
        'parent_street' => '',
        'parent_psc' => '',
        'parent_city' => ''
    ];
    
    // DIEŤA - indexy 0-5
    if (isset($row[0])) {
        $data['child_first_name'] = sanitize_text_field(trim($row[0]));
    }
    if (isset($row[1])) {
        $data['child_last_name'] = sanitize_text_field(trim($row[1]));
    }
    if (isset($row[4])) {
        $data['child_birthdate'] = sanitize_text_field(trim($row[4]));
    }
    if (isset($row[5])) {
        $birth_num_raw = trim($row[5]);
        $birth_num_clean = preg_replace('/[^0-9]/', '', $birth_num_raw);
        if (!empty($birth_num_clean)) {
            $data['child_birth_number'] = $birth_num_clean;
        }
    }
    
    // RODIČ - indexy 21-27
    if (isset($row[21])) {
        $parent_email = trim($row[21]);
        if (!empty($parent_email) && filter_var($parent_email, FILTER_VALIDATE_EMAIL)) {
            $data['parent_email'] = sanitize_email($parent_email);
        }
    }
    if (isset($row[22])) {
        $data['parent_first_name'] = sanitize_text_field(trim($row[22]));
    }
    if (isset($row[23])) {
        $data['parent_last_name'] = sanitize_text_field(trim($row[23]));
    }
    if (isset($row[24])) {
        $parent_phone = trim($row[24]);
        if (!empty($parent_phone)) {
            $data['parent_phone'] = sanitize_text_field($parent_phone);
        }
    }
    if (isset($row[25])) {
        $data['parent_street'] = sanitize_text_field(trim($row[25]));
    }
    if (isset($row[26])) {
        $data['parent_psc'] = sanitize_text_field(trim($row[26]));
    }
    if (isset($row[27])) {
        $data['parent_city'] = sanitize_text_field(trim($row[27]));
    }
    
    error_log('CSV_EXTRACT: child=' . $data['child_first_name'] . ' ' . $data['child_last_name'] . ' | parent_email=' . $data['parent_email']);
    
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
    
    error_log('CSV_PROCESS: FUNCTION ENTERED - file_path=' . $file_path . ', filename=' . $filename . ', target_group_id=' . $target_group_id);
    
    error_log('CSV_PROCESS: About to fopen - file_path=' . $file_path);
    $handle = fopen($file_path, 'r');
    error_log('CSV_PROCESS: After fopen - handle=' . (is_resource($handle) ? 'RESOURCE' : var_export($handle, true)));
    
    if ($handle === false) {
        error_log('ERROR CSV_PROCESS: fopen() returned false - file_path=' . $file_path);
        $stats['errors']++;
        $stats['error_log'][] = 'ERROR: Chyba pri otvorení súboru: ' . $filename;
        return $stats;
    }
    
    error_log('CSV_PROCESS: fopen() SUCCESS, reading header');
    
    // Preskočí header
    $header = fgetcsv($handle, 0, ';', '"');
    error_log('CSV_PROCESS: After fgetcsv header - header=' . (is_array($header) ? 'ARRAY[' . count($header) . ']' : var_export($header, true)));
    
    if (!$header) {
        error_log('ERROR CSV_PROCESS: fgetcsv() returned false or empty - cannot read header');
        $stats['errors']++;
        $stats['error_log'][] = 'ERROR: Súbor je prázdny';
        fclose($handle);
        return $stats;
    }
    
    error_log('CSV_PROCESS: Header parsed successfully, header fields: ' . json_encode($header));
    
    $row_number = 1;
    
    while (($row = fgetcsv($handle, 0, ';', '"')) !== false) {
        $row_number++;
        
        // Preskoč prázdne riadky
        if (empty(array_filter($row))) {
            $stats['skipped']++;
            error_log('CSV_LOOP: Skipping empty row ' . $row_number);
            continue;
        }
        
        error_log('=== CSV_LOOP: Processing row ' . $row_number . ' ===');
        
        // Extrakt údajov
        $data = spa_extract_csv_row_data($row);
        
        // VALIDÁCIA
        if (empty($data['parent_email'])) {
            $stats['errors']++;
            $stats['error_log'][] = 'Riadok ' . $row_number . ': Nenájdený email rodiča';
            error_log('CSV_LOOP: Row ' . $row_number . ' - Missing parent email');
            continue;
        }
        
        if (empty($data['child_first_name']) || empty($data['child_last_name'])) {
            $stats['errors']++;
            $stats['error_log'][] = 'Riadok ' . $row_number . ': Nenájdené meno/priezvisko dieťaťa';
            error_log('CSV_LOOP: Row ' . $row_number . ' - Missing child name');
            continue;
        }
        
        // === 1. VYTVORENIE RODIČOVHO WP_USER ===
        error_log('CSV_LOOP: Row ' . $row_number . ' - Calling spa_import_get_or_create_parent()');
        
        $parent_user_id = spa_import_get_or_create_parent(
            $data['parent_email'],
            $data['parent_first_name'],
            $data['parent_last_name'],
            $data['parent_phone']
        );
        
        if (!$parent_user_id) {
            $stats['errors']++;
            $stats['error_log'][] = 'Riadok ' . $row_number . ': Chyba vytvorenia rodiča - email: ' . $data['parent_email'];
            error_log('CSV_LOOP: Row ' . $row_number . ' - Parent creation FAILED, result=' . var_export($parent_user_id, true) . ', email=' . $data['parent_email']);
            continue;
        }
        
        // Ulož adresu rodiča do user_meta
        if (!empty($data['parent_street']) || !empty($data['parent_psc']) || !empty($data['parent_city'])) {
            $parent_address = trim($data['parent_street'] . ' ' . $data['parent_psc'] . ' ' . $data['parent_city']);
            update_user_meta($parent_user_id, 'address', $parent_address);
            error_log('CSV_LOOP: Row ' . $row_number . ' - Parent address saved: ' . $parent_address);
        }
        
        error_log('CSV_LOOP: Row ' . $row_number . ' - Parent created successfully with ID ' . $parent_user_id);
        
        // === 2. VYTVORENIE DETSKÉHO WP_USER ===
        error_log('CSV_LOOP: Row ' . $row_number . ' - Calling spa_import_get_or_create_child()');
        
        $child_user_id = spa_import_get_or_create_child(
            $data['child_first_name'],
            $data['child_last_name'],
            $data['child_birthdate'],
            $parent_user_id,
            $data['child_birth_number']
        );
        
        if (!$child_user_id) {
            $stats['errors']++;
            $stats['error_log'][] = 'Riadok ' . $row_number . ': Chyba vytvorenia dieťaťa - ' . $data['child_first_name'] . ' ' . $data['child_last_name'];
            error_log('CSV_LOOP: Row ' . $row_number . ' - Child creation FAILED, result=' . var_export($child_user_id, true) . ', parent_id=' . $parent_user_id);
            continue;
        }
        
        error_log('CSV_LOOP: Row ' . $row_number . ' - Child created successfully with ID ' . $child_user_id);
        
        // === 3. VYTVORENIE REGISTRÁCIE ===
        error_log('CSV_LOOP: Row ' . $row_number . ' - Calling spa_import_create_registration()');
        
        $registration_id = spa_import_create_registration(
            $child_user_id,
            $parent_user_id,
            $target_group_id,
            $training_day,
            $training_time,
            $filename
        );
        
        if (!$registration_id) {
            $stats['errors']++;
            $stats['error_log'][] = 'Riadok ' . $row_number . ': Chyba vytvorenia registrácie';
            error_log('CSV_LOOP: Row ' . $row_number . ' - Registration creation FAILED, result=' . var_export($registration_id, true));
            continue;
        }
        
        error_log('CSV_LOOP: Row ' . $row_number . ' - Registration created successfully with ID ' . $registration_id . ' | parent_id=' . $parent_user_id . ' | child_id=' . $child_user_id);
        
        $stats['success']++;
    }
    
    fclose($handle);
    
    error_log('CSV_PROCESS: Complete - success=' . $stats['success'] . ', errors=' . $stats['errors'] . ', skipped=' . $stats['skipped']);
    
    return $stats;
}

/**
 * HLAVNÝ ENTRY POINT
 */
function spa_process_csv_import() {
    
    error_log('=== SPA CSV IMPORT START ===');
    
    if (!current_user_can('manage_options')) {
        error_log('ERROR IMPORT: User does not have manage_options capability');
        wp_die('Nemáte oprávnenie');
    }
    error_log('IMPORT_CHECK: current_user_can(manage_options) = TRUE');
    
    if (!isset($_POST['spa_csv_import_nonce']) || !wp_verify_nonce($_POST['spa_csv_import_nonce'], 'spa_csv_import')) {
        error_log('ERROR IMPORT: Nonce check failed - nonce_isset=' . (isset($_POST['spa_csv_import_nonce']) ? 'yes' : 'no'));
        wp_die('Security check failed');
    }
    error_log('IMPORT_CHECK: Nonce verification = PASS');
    
    $target_group_id = isset($_POST['import_group_id']) ? intval($_POST['import_group_id']) : 0;
    $training_day = isset($_POST['import_day']) ? sanitize_text_field($_POST['import_day']) : '';
    $training_time = isset($_POST['import_time']) ? sanitize_text_field($_POST['import_time']) : '';
    
    error_log('IMPORT_CHECK: target_group_id=' . $target_group_id . ', training_day=' . $training_day . ', training_time=' . $training_time);
    
    if (!$target_group_id) {
        error_log('ERROR IMPORT: target_group_id is empty or 0');
        wp_die('Neplatný program');
    }
    
    $post_type = get_post_type($target_group_id);
    error_log('IMPORT_CHECK: get_post_type(' . $target_group_id . ') = ' . ($post_type ? $post_type : 'false/null'));
    
    if ($post_type !== 'spa_group') {
        error_log('ERROR IMPORT: Post type is not spa_group, got: ' . var_export($post_type, true));
        wp_die('Neplatný program');
    }
    error_log('IMPORT_CHECK: post_type = spa_group PASS');
    
    if (!isset($_FILES['csv_file'])) {
        error_log('ERROR IMPORT: $_FILES[csv_file] is NOT SET');
        wp_die('Chyba pri nahrávaní súboru');
    }
    error_log('IMPORT_CHECK: $_FILES[csv_file] EXISTS');
    
    if ($_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
        error_log('ERROR IMPORT: File upload error code = ' . $_FILES['csv_file']['error']);
        wp_die('Chyba pri nahrávaní súboru');
    }
    error_log('IMPORT_CHECK: File upload error = UPLOAD_ERR_OK');
    
    if (empty($_FILES['csv_file']['tmp_name'])) {
        error_log('ERROR IMPORT: tmp_name is empty - $_FILES[csv_file][tmp_name]=' . var_export($_FILES['csv_file']['tmp_name'], true));
        wp_die('Chyba pri nahrávaní súboru');
    }
    error_log('IMPORT_CHECK: tmp_name is NOT empty = ' . $_FILES['csv_file']['tmp_name']);
    
    $file_path = $_FILES['csv_file']['tmp_name'];
    $filename = $_FILES['csv_file']['name'];
    
    error_log('IMPORT_CHECK: file_path=' . $file_path . ', filename=' . $filename);
    
    if (!file_exists($file_path)) {
        error_log('ERROR IMPORT: File does not exist at path: ' . $file_path);
        wp_die('Súbor sa nenašiel');
    }
    error_log('IMPORT_CHECK: file_exists(' . $file_path . ') = TRUE');
    
    $stats = spa_process_single_csv($file_path, $filename, $target_group_id, $training_day, $training_time);
    
    error_log('IMPORT_CHECK: spa_process_single_csv() returned - stats=' . json_encode($stats));
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