<?php
/**
 * CSV Import Handler v2 - FINÁLNA VERZIA
 * 
 * @package Samuel Piasecký ACADEMY
 * @subpackage Import
 * @version 2.3.0 - FIXED
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
 */
function spa_extract_csv_row_data($row) {
    $data = [
        'child_first_name' => '',
        'child_last_name' => '',
        'child_birthdate' => '',
        'child_birth_number' => '',
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
    
    // RODIČ - indexy 30-36
    if (isset($row[30])) {
        $parent_email = trim($row[30]);
        if (!empty($parent_email) && filter_var($parent_email, FILTER_VALIDATE_EMAIL)) {
            $data['parent_email'] = sanitize_email($parent_email);
        }
    }
    if (isset($row[31])) {
        $data['parent_first_name'] = sanitize_text_field(trim($row[31]));
    }
    if (isset($row[32])) {
        $data['parent_last_name'] = sanitize_text_field(trim($row[32]));
    }
    if (isset($row[33])) {
        $parent_phone = trim($row[33]);
        if (!empty($parent_phone)) {
            $data['parent_phone'] = sanitize_text_field($parent_phone);
        }
    }
    if (isset($row[34])) {
        $data['parent_street'] = sanitize_text_field(trim($row[34]));
    }
    if (isset($row[35])) {
        $data['parent_psc'] = sanitize_text_field(trim($row[35]));
    }
    if (isset($row[36])) {
        $data['parent_city'] = sanitize_text_field(trim($row[36]));
    }
    
    return $data;
}

/**
 * SPRACOVANIE JEDNÉHO CSV SÚBORU
 */
function spa_process_single_csv($file_path, $filename, $target_group_id = 0, $training_day = '', $training_time = '') {
    
    error_log('[SPA IMPORT] CSV_PROCESS: Starting - file=' . $filename . ', group=' . $target_group_id);
    
    $stats = [
        'success' => 0,
        'errors' => 0,
        'skipped' => 0,
        'error_log' => []
    ];
    
    $handle = fopen($file_path, 'r');
    
    if ($handle === false) {
        error_log('[SPA IMPORT] ERROR: Cannot open file');
        $stats['errors']++;
        return $stats;
    }
    
    // Preskočí header
    $header = fgetcsv($handle, 0, ';', '"');
    
    if (!$header) {
        error_log('[SPA IMPORT] ERROR: Empty CSV');
        $stats['errors']++;
        fclose($handle);
        return $stats;
    }
    
    $row_number = 1;
    
    while (($row = fgetcsv($handle, 0, ';', '"')) !== false) {
        $row_number++;
        
        if (empty(array_filter($row))) {
            $stats['skipped']++;
            continue;
        }
        
        error_log('[SPA IMPORT] CSV_ROW ' . $row_number . ': Processing');
        
        // Extrakt údajov
        $data = spa_extract_csv_row_data($row);
        
        // VALIDÁCIA
        if (empty($data['parent_email'])) {
            $stats['errors']++;
            error_log('[SPA IMPORT] CSV_ROW ' . $row_number . ' ERROR: Missing parent email');
            continue;
        }
        
        if (empty($data['child_first_name']) || empty($data['child_last_name'])) {
            $stats['errors']++;
            error_log('[SPA IMPORT] CSV_ROW ' . $row_number . ' ERROR: Missing child name');
            continue;
        }
        
        // === 1. VYTVORENIE RODIČOVHO WP_USER ===
        error_log('[SPA IMPORT] CSV_ROW ' . $row_number . ': Creating parent');
        
        $parent_user_id = spa_import_get_or_create_parent(
            $data['parent_email'],
            $data['parent_first_name'],
            $data['parent_last_name'],
            $data['parent_phone']
        );
        
        if (!$parent_user_id) {
            $stats['errors']++;
            error_log('[SPA IMPORT] CSV_ROW ' . $row_number . ' ERROR: Parent creation failed');
            continue;
        }
        
        error_log('[SPA IMPORT] CSV_ROW ' . $row_number . ' Parent ID=' . $parent_user_id);
        
        // Ulož adresu rodiča ODDELENE
        if (!empty($data['parent_street'])) {
            update_user_meta($parent_user_id, 'address', $data['parent_street']);
            error_log('[SPA IMPORT] Parent meta: address=' . $data['parent_street']);
        }
        
        if (!empty($data['parent_city'])) {
            update_user_meta($parent_user_id, 'city', $data['parent_city']);
            error_log('[SPA IMPORT] Parent meta: city=' . $data['parent_city']);
        }
        
        if (!empty($data['parent_psc'])) {
            update_user_meta($parent_user_id, 'psc', $data['parent_psc']);
            error_log('[SPA IMPORT] Parent meta: psc=' . $data['parent_psc']);
        }
        
        // === 2. VYTVORENIE DETSKÉHO WP_USER ===
        error_log('[SPA IMPORT] CSV_ROW ' . $row_number . ': Creating child');
        
        $child_user_id = spa_import_get_or_create_child(
            $data['child_first_name'],
            $data['child_last_name'],
            $data['child_birthdate'],
            $parent_user_id,
            $data['child_birth_number']
        );
        
        if (!$child_user_id) {
            $stats['errors']++;
            error_log('[SPA IMPORT] CSV_ROW ' . $row_number . ' ERROR: Child creation failed');
            continue;
        }
        
        error_log('[SPA IMPORT] CSV_ROW ' . $row_number . ' Child ID=' . $child_user_id);
        
        // === 3. VYTVORENIE REGISTRÁCIE ===
        error_log('[SPA IMPORT] CSV_ROW ' . $row_number . ': Creating registration');
        
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
            error_log('[SPA IMPORT] CSV_ROW ' . $row_number . ' ERROR: Registration creation failed');
            continue;
        }
        
        error_log('[SPA IMPORT] CSV_ROW ' . $row_number . ' SUCCESS: parent=' . $parent_user_id . ', child=' . $child_user_id . ', reg=' . $registration_id);
        
        $stats['success']++;
    }
    
    fclose($handle);
    
    error_log('[SPA IMPORT] CSV_PROCESS COMPLETE: success=' . $stats['success'] . ', errors=' . $stats['errors']);
    
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
    
    error_log('[SPA IMPORT] Parameters: group=' . $target_group_id . ', day=' . $training_day . ', time=' . $training_time);
    
    if (!$target_group_id) {
        wp_die('Neplatný program');
    }
    
    $post_type = get_post_type($target_group_id);
    
    if ($post_type !== 'spa_group') {
        wp_die('Neplatný program');
    }
    
    if (!isset($_FILES['csv_file'])) {
        wp_die('Chyba pri nahrávaní súboru');
    }
    
    if ($_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
        wp_die('Chyba pri nahrávaní súboru');
    }
    
    if (empty($_FILES['csv_file']['tmp_name'])) {
        wp_die('Chyba pri nahrávaní súboru');
    }
    
    $file_path = $_FILES['csv_file']['tmp_name'];
    $filename = $_FILES['csv_file']['name'];
    
    if (!file_exists($file_path)) {
        wp_die('Súbor sa nenašiel');
    }
    
    error_log('[SPA IMPORT] File validated: ' . $filename);
    
    $stats = spa_process_single_csv($file_path, $filename, $target_group_id, $training_day, $training_time);
    
    error_log('[SPA IMPORT] COMPLETE: success=' . $stats['success'] . ', errors=' . $stats['errors']);
    
    wp_redirect(add_query_arg([
        'post_type' => 'spa_registration',
        'import_success' => $stats['success'],
        'import_errors' => $stats['errors'],
        'import_skipped' => $stats['skipped']
    ], admin_url('edit.php')));
    
    exit;
}

add_action('admin_post_spa_import_csv', 'spa_process_csv_import');
error_log('[SPA IMPORT] Hook registered: admin_post_spa_import_csv');