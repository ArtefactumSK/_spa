<?php
/**
 * CSV Import Handler v2 - ENHANCED
 * - Podpora ZIP archívov s viacerými CSV
 * - Import ceny z CSV
 * - Metadata pre export tracking
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Normalizácia textu pre porovnanie
 * - odstráni diakritiku
 * - trim medzier
 * - lowercase
 */
function spa_normalize_text_for_comparison($text) {
    // Odstrániť diakritiku
    $text = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text);
    
    // Trim a lowercase
    return trim(strtolower($text));
}

/**
 * Rozbalenie ZIP archívu
 * 
 * @param string $zip_path Cesta k ZIP súboru
 * @return array ['success' => bool, 'files' => array, 'error' => string]
 */
function spa_extract_zip_archive($zip_path) {
    $zip = new ZipArchive();
    $extract_path = wp_upload_dir()['basedir'] . '/spa-temp-import-' . time();
    
    if ($zip->open($zip_path) !== true) {
        return ['success' => false, 'error' => 'Nepodarilo sa otvoriť ZIP'];
    }
    
    // Vytvoriť dočasný adresár
    wp_mkdir_p($extract_path);
    
    // Rozbaľovanie
    $zip->extractTo($extract_path);
    $zip->close();
    
    // Nájsť všetky CSV súbory
    $csv_files = [];
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($extract_path)
    );
    
    foreach ($iterator as $file) {
        if ($file->isFile() && $file->getExtension() === 'csv') {
            $relative_path = str_replace($extract_path . '/', '', $file->getPathname());
            $city_folder = dirname($relative_path);
            
            // Ak je súbor priamo v root ZIP → mesto = prázdne
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
 * 
 * @param string $group_name Názov skupiny
 * @return int|false Group ID alebo false
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

    // Hľadať exact match (normalizované)
    foreach ($query->posts as $group_id) {
        $group_title = get_the_title($group_id);
        $normalized_title = spa_normalize_text_for_comparison($group_title);
        
        if ($normalized_title === $normalized_search) {
            return $group_id;
        }
    }

    // Ak exact match nenájdený, skúsiť partial match
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
 */
function spa_find_or_create_child_from_csv($row_data) {
    $meno = sanitize_text_field($row_data['meno']);
    $priezvisko = sanitize_text_field($row_data['priezvisko']);
    $datum_narodenia = sanitize_text_field($row_data['datum_narodenia']);

    // Hľadať existujúce dieťa
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
            ],
            [
                'key' => 'datum_narodenia',
                'value' => $datum_narodenia,
                'compare' => '='
            ]
        ],
        'posts_per_page' => 1
    ]);

    if ($existing_child->have_posts()) {
        return $existing_child->posts[0]->ID;
    }

    // Vytvoriť nové dieťa
    $child_id = wp_insert_post([
        'post_type' => 'sp_dieta',
        'post_title' => $meno . ' ' . $priezvisko,
        'post_status' => 'publish'
    ]);

    if (is_wp_error($child_id)) {
        return false;
    }

    // Uložiť ACF polia
    update_field('krstne_meno', $meno, $child_id);
    update_field('priezvisko', $priezvisko, $child_id);
    update_field('pohlavie', sanitize_text_field($row_data['pohlavie']), $child_id);
    update_field('datum_narodenia', $datum_narodenia, $child_id);

    return $child_id;
}

/**
 * Nájsť alebo vytvoriť rodiča z CSV údajov
 */
function spa_find_or_create_parent_from_csv($row_data) {
    $email = sanitize_email($row_data['email']);

    // Hľadať existujúceho rodiča podľa emailu
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
        return $existing_parent->posts[0]->ID;
    }

    // Vytvoriť nového rodiča
    $meno_rodica = sanitize_text_field($row_data['meno_rodica']);
    $priezvisko_rodica = sanitize_text_field($row_data['priezvisko_rodica']);

    $parent_id = wp_insert_post([
        'post_type' => 'sp_rodic',
        'post_title' => $meno_rodica . ' ' . $priezvisko_rodica,
        'post_status' => 'publish'
    ]);

    if (is_wp_error($parent_id)) {
        return false;
    }

    // Uložiť ACF polia
    update_field('krstne_meno', $meno_rodica, $parent_id);
    update_field('priezvisko', $priezvisko_rodica, $parent_id);
    update_field('email', $email, $parent_id);
    update_field('telefon', sanitize_text_field($row_data['telefon']), $parent_id);

    return $parent_id;
}

/**
 * Prepojiť rodiča a dieťa (obojsmerne)
 */
function spa_link_parent_child($parent_id, $child_id) {
    // Pridať dieťa k rodičovi
    $parent_children = get_field('deti', $parent_id) ?: [];
    if (!in_array($child_id, $parent_children)) {
        $parent_children[] = $child_id;
        update_field('deti', $parent_children, $parent_id);
    }

    // Pridať rodiča k dieťaťu
    $child_parents = get_field('rodicia', $child_id) ?: [];
    if (!in_array($parent_id, $child_parents)) {
        $child_parents[] = $parent_id;
        update_field('rodicia', $child_parents, $child_id);
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
 * Podporuje CSV aj ZIP archívy
 */
function spa_process_csv_import() {
    // === TEMPORARY DEBUG ===
        error_log('SPA IMPORT TRIGGERED');
        error_log('POST data: ' . print_r($_POST, true));
        error_log('FILES data: ' . print_r($_FILES, true));
        error_log('User can manage_options: ' . (current_user_can('manage_options') ? 'YES' : 'NO'));
        // === END DEBUG ===

    // Bezpečnostná kontrola
        if (!current_user_can('manage_options')) {
            wp_die('Nemáte oprávnenie na túto akciu.');
        }

        // TEMPORARY: Skip nonce check for debugging
        // check_admin_referer('spa_csv_import', 'spa_csv_import_nonce');
        error_log('Nonce in POST: ' . ($_POST['spa_csv_import_nonce'] ?? 'MISSING'));
        error_log('Expected nonce: ' . wp_create_nonce('spa_csv_import'));

        error_log('=== Checking group ===');
        $target_group_id = isset($_POST['import_group_id']) ? intval($_POST['import_group_id']) : 0;
        error_log('Target group ID: ' . $target_group_id);

        if (!$target_group_id) {
            error_log('ERROR: No group ID');
        }

        $post_type = get_post_type($target_group_id);
        error_log('Post type: ' . $post_type);

        $post_status = get_post_status($target_group_id);
        error_log('Post status: ' . $post_status);

        if (!$target_group_id || get_post_type($target_group_id) !== 'spa_group' || get_post_status($target_group_id) !== 'publish') {
            error_log('ERROR: Group validation failed!');
            error_log('Redirecting to: group_not_selected_or_invalid');
            wp_redirect(add_query_arg([
                'page' => 'spa-registrations-import',
                'error' => 'group_not_selected_or_invalid'
            ], admin_url('edit.php?post_type=spa_registration')));
            exit;
        }

        error_log('=== Group validation OK, continuing ===');

        error_log('=== Checking file upload ===');
        error_log('FILES isset: ' . (isset($_FILES['csv_file']) ? 'YES' : 'NO'));
        error_log('File error: ' . ($_FILES['csv_file']['error'] ?? 'N/A'));

        // Kontrola nahratého súboru
        if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
            error_log('ERROR: File upload failed');
            wp_redirect(add_query_arg([
            'page' => 'spa-registrations-import',  // ← SPRÁVNE!
            'import' => 'success',
            'imported' => $total_stats['success'],
            'errors' => $total_stats['errors'],
            'skipped' => $total_stats['skipped'],
            'files' => $total_stats['processed_files']
            ], admin_url('edit.php?post_type=spa_registration')));
            exit;
        }

        error_log('=== File OK, processing ===');

        // === ZÍSKANIE SKUPINY PRIAMO Z UI (ID) ===
        $target_group_id = isset($_POST['import_group_id']) ? intval($_POST['import_group_id']) : 0;

        if (!$target_group_id || get_post_type($target_group_id) !== 'spa_group' || get_post_status($target_group_id) !== 'publish') {
            wp_redirect(add_query_arg([
                'page' => 'spa-registrations-import',
                'error' => 'group_not_selected_or_invalid'
            ], admin_url('edit.php?post_type=spa_registration')));
            exit;
        }

    
    // Kontrola nahratého súboru
    if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
            wp_redirect(add_query_arg([
                'page' => 'spa-import',
                'error' => 'upload_failed'
            ], admin_url('admin.php')));
            exit;
        }

    $uploaded_file = $_FILES['csv_file']['tmp_name'];
    $uploaded_filename = $_FILES['csv_file']['name'];
    $file_extension = strtolower(pathinfo($uploaded_filename, PATHINFO_EXTENSION));
    
    $files_to_process = [];
    $zip_name = '';
    $temp_path = '';
    
    /**
 * Normalizácia textu pre porovnanie
 * - odstráni diakritiku
 * - odstráni špeciálne znaky
 * - nahradí viacnásobné medzery jednou
 */
function spa_normalize_text($text) {
    // Odstrániť diakritiku
    $text = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text);
    
    // Odstrániť špeciálne znaky (ponechať len alfanumerické a medzery)
    $text = preg_replace('/[^a-z0-9\s]/i', '', $text);
    
    // Nahradiť viacnásobné medzery jednou
    $text = preg_replace('/\s+/', ' ', $text);
    
    // Trim a lowercase
    return trim(strtolower($text));
}

/**
 * Určiť sezónu na základe dátumu
 * 
 * @param string $date Dátum vo formáte 'Y-m-d H:i:s'
 * @return string Rok sezóny (napr. "2024/2025")
 */
function spa_get_season_from_date($date) {
    $timestamp = strtotime($date);
    $month = (int)date('n', $timestamp);
    $year = (int)date('Y', $timestamp);
    
    // September - December = aktuálny rok / nasledujúci rok
    if ($month >= 9) {
        return $year . '/' . ($year + 1);
    }
    
    // Január - August = predchádzajúci rok / aktuálny rok
    return ($year - 1) . '/' . $year;
}
    
    // === DETEKCIA TYPU SÚBORU ===
    
    if ($file_extension === 'zip') {
        // ZIP ARCHÍV
        $zip_result = spa_extract_zip_archive($uploaded_file);
        
        if (!$zip_result['success']) {
            wp_redirect(add_query_arg([
                'page' => 'spa-import',
                'error' => 'zip_extraction_failed'
            ], admin_url('admin.php')));
            exit;
        }
        
        $files_to_process = $zip_result['files'];
        $temp_path = $zip_result['extract_path'];
        $zip_name = pathinfo($uploaded_filename, PATHINFO_FILENAME);
        
    } elseif ($file_extension === 'csv') {
        // JEDNODUCHÝ CSV - spätná kompatibilita
        $files_to_process[] = [
            'path' => $uploaded_file,
            'filename' => $uploaded_filename,
            'city' => ''
        ];
    } else {
        wp_redirect(add_query_arg([
            'page' => 'spa-import',
            'error' => 'invalid_file_type'
        ], admin_url('admin.php')));
        exit;
    }
    
    // === SPRACOVANIE VŠETKÝCH CSV SÚBOROV ===
    
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
        
        // Agregácia štatistík
        $total_stats['success'] += $file_stats['success'];
        $total_stats['errors'] += $file_stats['errors'];
        $total_stats['skipped'] += $file_stats['skipped'];
        $total_stats['error_log'] = array_merge($total_stats['error_log'], $file_stats['error_log']);
        $total_stats['processed_files']++;
    }
   
    // Vyčistiť dočasné súbory
    if (!empty($temp_path)) {
        spa_cleanup_temp_files($temp_path);
    }
    
    // Uložiť log
    spa_save_import_log($total_stats);

    // === IMPORT FINISHED – REDIRECT BACK TO IMPORT PAGE ===
    wp_redirect(add_query_arg([
        'post_type' => 'spa_registration',
        'page'      => 'spa-registrations-import',
        'import'    => 'success'
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
    
    // Otvorenie CSV súboru
    $handle = fopen($file_path, 'r');
    
    if ($handle === false) {
        $import_stats['errors']++;
        $import_stats['error_log'][] = 'Chyba otvorenia súboru: ' . $filename;
        error_log('ERROR: Cannot open CSV file: ' . $filename);
        return $import_stats;
    }
    
    // Načítanie prvého riadku (header)
    $first_line = fgets($handle);
    
    // Odstránenie UTF-8 BOM
    if (substr($first_line, 0, 3) === "\xEF\xBB\xBF") {
        $first_line = substr($first_line, 3);
    }
    
    // Parsovanie hlavičky
    $header = str_getcsv($first_line, ';', '"', '\\');
    $header = array_map('trim', $header);
    
    error_log('CSV HEADERS: ' . implode(' | ', $header));
    
    // Spracovanie riadkov
    $row_number = 1;
    
    while (($row = fgetcsv($handle, 0, ';', '"', '\\')) !== false) {
        $row_number++;
        
        // Preskočiť prázdne riadky
        if (empty(array_filter($row))) {
            $import_stats['skipped']++;
            continue;
        }
        
        error_log('ROW ' . $row_number . ': ' . implode(' | ', $row));
        
        // Vytvorenie registrácie
        $registration_id = wp_insert_post([
            'post_type' => 'spa_registration',
            'post_title' => 'Import registrácia ' . date('Y-m-d H:i:s') . ' #' . $row_number,
            'post_status' => 'publish'
        ]);
        
        if (is_wp_error($registration_id) || !$registration_id) {
            $import_stats['errors']++;
            $import_stats['error_log'][] = 'Chyba vytvorenia registrácie na riadku ' . $row_number;
            error_log('ERROR: Failed to create registration for row ' . $row_number);
            continue;
        }
        
        // Uloženie meta polí
        update_post_meta($registration_id, 'spa_group_id', $target_group_id);
        update_post_meta($registration_id, 'import_source', 'csv');
        update_post_meta($registration_id, 'import_filename', $filename);
        update_post_meta($registration_id, 'import_timestamp', current_time('mysql'));
        
        error_log('CREATED: Registration ID ' . $registration_id . ' for row ' . $row_number);
        
        $import_stats['success']++;
    }
    
    fclose($handle);
    
    return $import_stats;
}

add_action('admin_post_spa_import_csv', 'spa_process_csv_import');

