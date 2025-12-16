<?php
/**
 * CSV Import Handler v2 - ORCHESTRÁTOR
 * 
 * ROLE:
 * - Načíta CSV súbor
 * - Extrahuje údaje z riadkov
 * - VOLÁ import helpery:
 *   * spa_import_get_or_create_parent() - z spa-import-user-parent.php
 *   * spa_import_get_or_create_child() - z spa-import-user-child.php
 *   * spa_import_create_registration() - z spa-import-registration.php
 * - Spracúva chyby
 * - Vracia štatistiku
 * 
 * @package Samuel Piasecký ACADEMY
 * @subpackage Import
 * @version 2.1.0 - FIXED: Volá externe helpery
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
 * 
 * PRIAME VOLANIA HELPEROVÝCH FUNKCIÍ:
 * 1. spa_import_get_or_create_parent() - vytvorí rodiča
 * 2. spa_import_get_or_create_child() - vytvorí dieťa
 * 3. spa_import_create_registration() - vytvorí registráciu
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
        error_log('ERROR CSV: Cannot open file: ' . $filename);
        return $stats;
    }
    
    // Preskočí header
    $header = fgetcsv($handle, 0, ';', '"');
    if (!$header) {
        $stats['errors']++;
        $stats['error_log'][] = 'ERROR: Súbor je prázdny';
        error_log('ERROR CSV: File is empty: ' . $filename);
        fclose($handle);
        return $stats;
    }
    
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
            $data['parent_first_name'] ?: 'Rodič',
            $data['parent_last_name'] ?: 'Import',
            $data['parent_phone']
        );
        
        if (!$parent_user_id) {
            $stats['errors']++;
            $stats['error_log'][] = 'Riadok ' . $row_number . ': Chyba vytvorenia rodiča - email: ' . $data['parent_email'];
            error_log('CSV_LOOP: Row ' . $row_number . ' - Parent creation FAILED, result=' . var_export($parent_user_id, true) . ', email=' . $data['parent_email']);
            continue;
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