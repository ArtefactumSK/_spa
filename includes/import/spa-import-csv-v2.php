<?php
/**
 * CSV Import Handler v2 - ENHANCED
 * - Podpora ZIP archívov s viacerými CSV
 * - Import ceny z CSV
 * - Metadata pre export tracking
 * 
 * OPRAVA: 
 * - Bez ACF (update_field)
 * - Správne mapovanie CSV stĺpcov (case-insensitive)
 * - Bez dummy "Import registrácia ..." postov
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Normalizácia textu pre porovnanie
 */
function spa_normalize_text_for_comparison($text) {
    $text = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text);
    return trim(strtolower($text));
}

/**
 * Rozbalenie ZIP archívu
 */
function spa_extract_zip_archive($zip_path) {
    $zip = new ZipArchive();
    $extract_path = wp_upload_dir()['basedir'] . '/spa-temp-import-' . time();
    
    if ($zip->open($zip_path) !== true) {
        return ['success' => false, 'error' => 'Nepodarilo sa otvoriť ZIP'];
    }
    
    wp_mkdir_p($extract_path);
    $zip->extractTo($extract_path);
    $zip->close();
    
    $csv_files = [];
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($extract_path)
    );
    
    foreach ($iterator as $file) {
        if ($file->isFile() && $file->getExtension() === 'csv') {
            $relative_path = str_replace($extract_path . '/', '', $file->getPathname());
            $city_folder = dirname($relative_path);
            
            if ($city_folder === '.') {
                $city_folder = '';
            }
            
            $csv_files[] = [
                'path' => $file->getPathname(),
                'filename' => $file->getFilename(),
                'city' => $city_folder
            ];
        }
    }
    
    return [
        'success' => true,
        'files' => $csv_files,
        'extract_path' => $extract_path
    ];
}

/**
 * Vyčistenie dočasných súborov
 */
function spa_cleanup_temp_files($path) {
    if (!file_exists($path)) {
        return;
    }
    
    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );
    
    foreach ($files as $fileinfo) {
        $todo = ($fileinfo->isDir() ? 'rmdir' : 'unlink');
        $todo($fileinfo->getRealPath());
    }
    
    rmdir($path);
}

/**
 * Vyhľadať skupinu podľa názvu (post_title)
 */
function spa_find_group_by_name($group_name) {
    $normalized_search = spa_normalize_text_for_comparison($group_name);
    
    $query = new WP_Query([
        'post_type' => 'spa_group',
        'post_status' => 'publish',
        'posts_per_page' => -1,
        'fields' => 'ids'
    ]);

    if (!$query->have_posts()) {
        return false;
    }

    foreach ($query->posts as $group_id) {
        $group_title = get_the_title($group_id);
        $normalized_title = spa_normalize_text_for_comparison($group_title);
        
        if ($normalized_title === $normalized_search) {
            return $group_id;
        }
    }

    foreach ($query->posts as $group_id) {
        $group_title = get_the_title($group_id);
        $normalized_title = spa_normalize_text_for_comparison($group_title);
        
        if (strpos($normalized_title, $normalized_search) !== false) {
            return $group_id;
        }
    }

    return false;
}

/**
 * Nájsť alebo vytvoriť dieťa z CSV údajov
 * 
 * Hľadá PRVÉ neprázdne meno a priezvisko
 */
function spa_find_or_create_child_from_csv($row_data) {
    $row = $row_data['_raw_row'] ?? [];
    
    if (empty($row)) {
        error_log('ERROR: No raw row data available for child');
        return false;
    }
    
    // Hľadaj PRVÉ neprázdne meno (väčšinou index 0)
    $meno = '';
    for ($i = 0; $i < count($row) && empty($meno); $i++) {
        $value = trim($row[$i] ?? '');
        if (!empty($value) && !filter_var($value, FILTER_VALIDATE_EMAIL) && strlen($value) < 50) {
            $meno = sanitize_text_field($value);
            error_log('DEBUG: Found child name at index ' . $i . ': ' . $meno);
        }
    }
    
    // Hľadaj PRVÉ neprázdne priezvisko (väčšinou index 1)
    $priezvisko = '';
    for ($i = 1; $i < count($row) && empty($priezvisko); $i++) {
        $value = trim($row[$i] ?? '');
        if (!empty($value) && !filter_var($value, FILTER_VALIDATE_EMAIL) && strlen($value) < 50 && $value !== $meno) {
            $priezvisko = sanitize_text_field($value);
            error_log('DEBUG: Found child surname at index ' . $i . ': ' . $priezvisko);
        }
    }
    
    // Hľadaj dátum narodenia
    $datum_narodenia = '';
    for ($i = 0; $i < count($row); $i++) {
        $value = trim($row[$i] ?? '');
        if (!empty($value) && preg_match('/^\d{1,2}\.\d{1,2}\.\d{4}$/', $value)) {
            $datum_narodenia = $value;
            error_log('DEBUG: Found child birthdate at index ' . $i . ': ' . $datum_narodenia);
            break;
        }
    }

    if (empty($meno) || empty($priezvisko)) {
        error_log('ERROR: Missing meno or priezvisko for child. meno=' . $meno . ', priezvisko=' . $priezvisko);
        return false;
    }

    error_log('DEBUG: Looking for child - meno=' . $meno . ', priezvisko=' . $priezvisko . ', datum=' . $datum_narodenia);

    $existing_child = new WP_Query([
        'post_type' => 'sp_dieta',
        'post_status' => 'publish',
        'meta_query' => [
            'relation' => 'AND',
            [
                'key' => 'krstne_meno',
                'value' => $meno,
                'compare' => '='
            ],
            [
                'key' => 'priezvisko',
                'value' => $priezvisko,
                'compare' => '='
            ]
        ],
        'posts_per_page' => 1
    ]);

    if ($existing_child->have_posts()) {
        error_log('DEBUG: Found existing child ID ' . $existing_child->posts[0]->ID);
        return $existing_child->posts[0]->ID;
    }

    $child_id = wp_insert_post([
        'post_type' => 'sp_dieta',
        'post_title' => $meno . ' ' . $priezvisko,
        'post_status' => 'publish'
    ]);

    if (is_wp_error($child_id)) {
        error_log('ERROR: Failed to create child: ' . $child_id->get_error_message());
        return false;
    }

    update_post_meta($child_id, 'krstne_meno', $meno);
    update_post_meta($child_id, 'priezvisko', $priezvisko);
    update_post_meta($child_id, 'pohlavie', '');
    if (!empty($datum_narodenia)) {
        update_post_meta($child_id, 'datum_narodenia', $datum_narodenia);
    }

    error_log('DEBUG: Created child ID ' . $child_id . ' - ' . $meno . ' ' . $priezvisko);

    return $child_id;
}

/**
 * Nájsť alebo vytvoriť rodiča z CSV údajov
 * 
 * CSV má email na pozícii 30, meno a priezvisko na 31-32
 * Funkcia skúša všetky pozície kde môže byť email
 */
function spa_find_or_create_parent_from_csv($row_data) {
    // Ziskaj pôvodné pole
    $row = $row_data['_raw_row'] ?? [];
    
    if (empty($row)) {
        error_log('ERROR: No raw row data available');
        return false;
    }
    
    // Hľadaj email - skúšaj všetky pozície
    $email = '';
    $email_index = -1;
    
    for ($i = 0; $i < count($row); $i++) {
        $value = $row[$i] ?? '';
        if (!empty($value) && filter_var($value, FILTER_VALIDATE_EMAIL)) {
            $email = sanitize_email($value);
            $email_index = $i;
            error_log('DEBUG: Found email at index ' . $i . ': ' . $email);
            break;
        }
    }
    
    if (empty($email)) {
        error_log('WARNING: No valid email found for parent. Row has ' . count($row) . ' columns');
        error_log('WARNING: Row values: ' . implode(' | ', array_slice($row, 0, 35)));
        return false;
    }

    $existing_parent = new WP_Query([
        'post_type' => 'sp_rodic',
        'post_status' => 'publish',
        'meta_query' => [
            [
                'key' => 'email',
                'value' => $email,
                'compare' => '='
            ]
        ],
        'posts_per_page' => 1
    ]);

    if ($existing_parent->have_posts()) {
        error_log('DEBUG: Found existing parent with email: ' . $email);
        return $existing_parent->posts[0]->ID;
    }

    // Hľadaj meno a priezvisko - pokusy všetky bunky OK email
    $meno_rodica = '';
    $priezvisko_rodica = '';
    $telefon = '';
    
    // Skúšaj všetky bunky pre meno
    for ($i = $email_index + 1; $i < count($row) && empty($meno_rodica); $i++) {
        $value = trim($row[$i] ?? '');
        if (!empty($value) && !filter_var($value, FILTER_VALIDATE_EMAIL) && strlen($value) < 50) {
            $meno_rodica = sanitize_text_field($value);
            error_log('DEBUG: Found parent name at index ' . $i . ': ' . $meno_rodica);
        }
    }
    
    // Skúšaj bunky za menom pre priezvisko
    for ($i = $email_index + 2; $i < count($row) && empty($priezvisko_rodica); $i++) {
        $value = trim($row[$i] ?? '');
        if (!empty($value) && !filter_var($value, FILTER_VALIDATE_EMAIL) && strlen($value) < 50) {
            $priezvisko_rodica = sanitize_text_field($value);
            error_log('DEBUG: Found parent surname at index ' . $i . ': ' . $priezvisko_rodica);
        }
    }
    
    // Ak chýbajú mená, vyrob z emailu
    if (empty($meno_rodica) || empty($priezvisko_rodica)) {
        list($meno_rodica, $domain) = explode('@', $email);
        if (empty($priezvisko_rodica)) {
            $priezvisko_rodica = 'Import';
        }
        error_log('DEBUG: Generated parent name from email: ' . $meno_rodica . ' ' . $priezvisko_rodica);
    }

    error_log('DEBUG: Creating parent - email=' . $email . ', meno=' . $meno_rodica . ', priezvisko=' . $priezvisko_rodica);

    $parent_id = wp_insert_post([
        'post_type' => 'sp_rodic',
        'post_title' => $meno_rodica . ' ' . $priezvisko_rodica,
        'post_status' => 'publish'
    ]);

    if (is_wp_error($parent_id)) {
        error_log('ERROR: Failed to create parent: ' . $parent_id->get_error_message());
        return false;
    }

    update_post_meta($parent_id, 'krstne_meno', $meno_rodica);
    update_post_meta($parent_id, 'priezvisko', $priezvisko_rodica);
    update_post_meta($parent_id, 'email', $email);
    if (!empty($telefon)) {
        update_post_meta($parent_id, 'telefon', $telefon);
    }

    error_log('DEBUG: Created parent ID ' . $parent_id . ' with email ' . $email);

    return $parent_id;
}

/**
 * Prepojiť rodiča a dieťa (obojsmerne)
 */
function spa_link_parent_child($parent_id, $child_id) {
    // Ulož ako postmeta (bez ACF)
    $parent_children = get_post_meta($parent_id, 'deti', true);
    if (!is_array($parent_children)) {
        $parent_children = [];
    }
    if (!in_array($child_id, $parent_children)) {
        $parent_children[] = $child_id;
        update_post_meta($parent_id, 'deti', $parent_children);
    }

    $child_parents = get_post_meta($child_id, 'rodicia', true);
    if (!is_array($child_parents)) {
        $child_parents = [];
    }
    if (!in_array($parent_id, $child_parents)) {
        $child_parents[] = $parent_id;
        update_post_meta($child_id, 'rodicia', $child_parents);
    }
}

/**
 * Validácia formátu dátumu DD.MM.YYYY
 */
function spa_validate_date_format($date_string) {
    if (empty($date_string)) {
        return false;
    }

    $date = DateTime::createFromFormat('d.m.Y', $date_string);
    return $date && $date->format('d.m.Y') === $date_string;
}

/**
 * Uložiť import log
 */
function spa_save_import_log($stats) {
    $log_dir = wp_upload_dir()['basedir'] . '/spa-import-logs';
    
    if (!file_exists($log_dir)) {
        wp_mkdir_p($log_dir);
    }

    $log_file = $log_dir . '/import-' . date('Y-m-d-His') . '.log';
    
    $log_content = sprintf(
        "=== SPA CSV IMPORT LOG ===\n" .
        "Dátum: %s\n" .
        "Spracovaných súborov: %d\n" .
        "Úspešných registrácií: %d\n" .
        "Chýb: %d\n" .
        "Preskočených: %d\n\n" .
        "=== DETAILY ===\n%s\n",
        current_time('mysql'),
        $stats['processed_files'] ?? 1,
        $stats['success'],
        $stats['errors'],
        $stats['skipped'],
        implode("\n", $stats['error_log'])
    );

    file_put_contents($log_file, $log_content);
}

/**
 * Spracovanie CSV importu - HLAVNÝ ENTRY POINT
 */
function spa_process_csv_import() {
    error_log('SPA IMPORT TRIGGERED');
    error_log('POST data: ' . print_r($_POST, true));
    error_log('FILES data: ' . print_r($_FILES, true));
    error_log('User can manage_options: ' . (current_user_can('manage_options') ? 'YES' : 'NO'));

    if (!current_user_can('manage_options')) {
        wp_die('Nemáte oprávnenie na túto akciu.');
    }

    error_log('Nonce in POST: ' . ($_POST['spa_csv_import_nonce'] ?? 'MISSING'));

    $target_group_id = isset($_POST['import_group_id']) ? intval($_POST['import_group_id']) : 0;
    error_log('Target group ID: ' . $target_group_id);

    if (!$target_group_id || get_post_type($target_group_id) !== 'spa_group' || get_post_status($target_group_id) !== 'publish') {
        error_log('ERROR: Group validation failed!');
        wp_redirect(add_query_arg([
            'page' => 'spa-registrations-import',
            'error' => 'group_not_selected_or_invalid'
        ], admin_url('edit.php?post_type=spa_registration')));
        exit;
    }

    error_log('=== Group validation OK, continuing ===');

    if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
        error_log('ERROR: File upload failed');
        wp_redirect(add_query_arg([
            'page' => 'spa-registrations-import',
            'error' => 'upload_failed'
        ], admin_url('edit.php?post_type=spa_registration')));
        exit;
    }

    error_log('=== File OK, processing ===');

    $uploaded_file = $_FILES['csv_file']['tmp_name'];
    $uploaded_filename = $_FILES['csv_file']['name'];
    $file_extension = strtolower(pathinfo($uploaded_filename, PATHINFO_EXTENSION));
    
    $files_to_process = [];
    $zip_name = '';
    $temp_path = '';
    
    if ($file_extension === 'zip') {
        $zip_result = spa_extract_zip_archive($uploaded_file);
        
        if (!$zip_result['success']) {
            error_log('ERROR: ZIP extraction failed');
            wp_redirect(add_query_arg([
                'page' => 'spa-registrations-import',
                'error' => 'zip_extraction_failed'
            ], admin_url('edit.php?post_type=spa_registration')));
            exit;
        }
        
        $files_to_process = $zip_result['files'];
        $temp_path = $zip_result['extract_path'];
        $zip_name = pathinfo($uploaded_filename, PATHINFO_FILENAME);
        
    } elseif ($file_extension === 'csv') {
        $files_to_process[] = [
            'path' => $uploaded_file,
            'filename' => $uploaded_filename,
            'city' => ''
        ];
    } else {
        error_log('ERROR: Invalid file type');
        wp_redirect(add_query_arg([
            'page' => 'spa-registrations-import',
            'error' => 'invalid_file_type'
        ], admin_url('edit.php?post_type=spa_registration')));
        exit;
    }
    
    $total_stats = [
        'success' => 0,
        'errors' => 0,
        'skipped' => 0,
        'error_log' => [],
        'processed_files' => 0
    ];
    
    foreach ($files_to_process as $file_info) {
        $file_stats = spa_process_single_csv(
            $file_info['path'],
            $file_info['filename'],
            $file_info['city'],
            $zip_name,
            $target_group_id
        );
        
        $total_stats['success'] += $file_stats['success'];
        $total_stats['errors'] += $file_stats['errors'];
        $total_stats['skipped'] += $file_stats['skipped'];
        $total_stats['error_log'] = array_merge($total_stats['error_log'], $file_stats['error_log']);
        $total_stats['processed_files']++;
        
        error_log('AFTER PROCESSING FILE: success=' . $total_stats['success'] . ' errors=' . $total_stats['errors'] . ' skipped=' . $total_stats['skipped']);
    }
   
    if (!empty($temp_path)) {
        spa_cleanup_temp_files($temp_path);
    }
    
    spa_save_import_log($total_stats);

    error_log('FINAL STATS: success=' . $total_stats['success'] . ' errors=' . $total_stats['errors'] . ' skipped=' . $total_stats['skipped'] . ' files=' . $total_stats['processed_files']);
    
    wp_redirect(add_query_arg([
        'post_type' => 'spa_registration',
        'page'      => 'spa-registrations-import',
        'import'    => 'success',
        'imported'  => $total_stats['success'],
        'errors'    => $total_stats['errors'],
        'skipped'   => $total_stats['skipped'],
        'files'     => $total_stats['processed_files']
    ], admin_url('edit.php')));
    exit;
}

/**
 * Spracovanie jedného CSV súboru
 */
function spa_process_single_csv($file_path, $filename, $city = '', $zip_name = '', $target_group_id = 0) {
    
    $import_stats = [
        'success' => 0,
        'errors' => 0,
        'skipped' => 0,
        'error_log' => []
    ];
    
    $handle = fopen($file_path, 'r');
    
    if ($handle === false) {
        $import_stats['errors']++;
        $import_stats['error_log'][] = 'Chyba otvorenia súboru: ' . $filename;
        error_log('ERROR: Cannot open CSV file: ' . $filename);
        return $import_stats;
    }
    
    $first_line = fgets($handle);
    
    if ($first_line === false) {
        $import_stats['errors']++;
        $import_stats['error_log'][] = 'Súbor je prázdny alebo nečitateľný: ' . $filename;
        error_log('ERROR: Cannot read first line from CSV file: ' . $filename);
        fclose($handle);
        return $import_stats;
    }
    
    if (substr($first_line, 0, 3) === "\xEF\xBB\xBF") {
        $first_line = substr($first_line, 3);
    }
    
    $header = str_getcsv($first_line, ';', '"', '\\');
    $header = array_map('trim', $header);
    
    // Normalizuj header keys na lowercase (pre case-insensitive access)
    $header_normalized = array_map('strtolower', $header);
    
    error_log('CSV HEADERS: ' . implode(' | ', $header));
    
    $row_number = 1;
    
    while (($row = fgetcsv($handle, 0, ';', '"', '\\')) !== false) {
        $row_number++;
        
        if (empty(array_filter($row))) {
            $import_stats['skipped']++;
            continue;
        }
        
        error_log('ROW ' . $row_number . ': ' . implode(' | ', $row));
        
        // Mapovanie CSV stĺpcov na dáta s normalizovanými kľúčmi
        // Ulož aj pôvodný $row a indexy pre prístup podľa pozície
        $row_data = [
            '_raw_row' => $row,  // Pôvodné pole hodnôt
            '_header' => $header,  // Pôvodné header
            '_header_normalized' => $header_normalized  // Normalizované headery
        ];
        
        foreach ($header_normalized as $index => $col_name_normalized) {
            $col_name_original = $header[$index];
            $value = $row[$index] ?? '';
            $row_data[$col_name_normalized] = $value;
            $row_data[$col_name_original] = $value; // Ulož obidva formáty
        }
        
        error_log('ROW_DATA - Email value at raw[2]: ' . ($row[2] ?? 'EMPTY') . ', raw[30]: ' . ($row[30] ?? 'EMPTY'));
        
        // === VYTVORENIE REÁLNEJ REGISTRÁCIE ===
        
        // 1. Nájsť/vytvoriť rodiča
        $parent_id = spa_find_or_create_parent_from_csv($row_data);
        if (!$parent_id) {
            $import_stats['errors']++;
            $import_stats['error_log'][] = 'Chyba vytvorenia rodiča na riadku ' . $row_number;
            error_log('ERROR: Failed to create parent for row ' . $row_number);
            continue;
        }
        
        // 2. Nájsť/vytvoriť dieťa
        $child_id = spa_find_or_create_child_from_csv($row_data);
        if (!$child_id) {
            $import_stats['errors']++;
            $import_stats['error_log'][] = 'Chyba vytvorenia dieťaťa na riadku ' . $row_number;
            error_log('ERROR: Failed to create child for row ' . $row_number);
            continue;
        }
        
        // 3. Prepojiť rodiča a dieťa
        spa_link_parent_child($parent_id, $child_id);
        
        // 4. Vytvor REÁLNU registráciu s client_user_id a parent_user_id
        $registration_title = get_the_title($child_id) . ' - ' . get_the_title($target_group_id);
        
        $registration_id = wp_insert_post([
            'post_type' => 'spa_registration',
            'post_title' => $registration_title,
            'post_status' => 'publish'
        ], true);
        
        if (is_wp_error($registration_id)) {
            $error_msg = $registration_id->get_error_message();
            $import_stats['errors']++;
            $import_stats['error_log'][] = 'Chyba vytvorenia registrácie na riadku ' . $row_number . ': ' . $error_msg;
            error_log('ERROR: Failed to create registration for row ' . $row_number . ' - ' . $error_msg);
            continue;
        }
        
        if (!$registration_id) {
            $import_stats['errors']++;
            $import_stats['error_log'][] = 'Neznáma chyba pri vytváraní registrácie na riadku ' . $row_number;
            error_log('ERROR: Failed to create registration for row ' . $row_number . ' - Unknown error');
            continue;
        }
        
        // 5. Uložiť POVINNÉ meta polia
        update_post_meta($registration_id, 'client_user_id', $child_id);
        update_post_meta($registration_id, 'parent_user_id', $parent_id);
        update_post_meta($registration_id, 'spa_group_id', $target_group_id);
        update_post_meta($registration_id, 'import_source', 'csv');
        update_post_meta($registration_id, 'import_filename', $filename);
        update_post_meta($registration_id, 'import_timestamp', current_time('mysql'));
        
        // 6. Ulož voliteľné meta
        if (!empty($row_data['predvolená suma'])) {
            $price = floatval(str_replace(',', '.', $row_data['predvolená suma']));
            update_post_meta($registration_id, 'registration_price', $price);
        }
        
        if (!empty($row_data['variabilný symbol'])) {
            update_post_meta($registration_id, 'variable_symbol', sanitize_text_field($row_data['variabilný symbol']));
        }
        
        error_log('CREATED: Registration ID ' . $registration_id . ' for row ' . $row_number . ' (parent=' . $parent_id . ', child=' . $child_id . ')');
        
        $import_stats['success']++;
    }
    
    fclose($handle);
    
    return $import_stats;
}

add_action('admin_post_spa_import_csv', 'spa_process_csv_import');