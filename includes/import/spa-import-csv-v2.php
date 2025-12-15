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
 * Nájsť spa_group podľa presného termínu
 * @param int $program_id ID programu (spa_group)
 * @param int $city_id ID mesta (spa_place)
 * @param string $day Deň (mo, tu, we, ...)
 * @param string $time Čas (HH:MM)
 * @return int|false Group ID alebo false
 */


function spa_find_group_by_schedule($program_id, $city_id, $day, $time) {
    
    // Program ID = priamo spa_group ID (nie meta pole)
    $group = get_post($program_id);
    
    if (!$group || $group->post_type !== 'spa_group' || $group->post_status !== 'publish') {
        return false;
    }
    
    // Kontrola mesta
    $group_place = get_post_meta($program_id, 'spa_group_place', true);
    if (intval($group_place) !== intval($city_id)) {
        return false;
    }
    
    // Kontrola rozvrhu
    $schedule_days = get_post_meta($program_id, 'spa_schedule_days', true);
    $schedule_times = get_post_meta($program_id, 'spa_schedule_times', true);
    
    if (!is_array($schedule_days) || !is_array($schedule_times)) {
        return false;
    }
    
    // Deň musí byť v rozvrhu
    if (!in_array($day, $schedule_days)) {
        return false;
    }
    
    // Kontrola času (±5 minút tolerancia)
    if (!isset($schedule_times[$day]['from'])) {
        return false;
    }
    
    $schedule_time = $schedule_times[$day]['from'];
    
    if (spa_times_match($schedule_time, $time)) {
        return $program_id; // Vrátiť priamo program ID
    }
    
    return false;
}

/**
 * Porovnanie časov s toleranciou ±5 minút
 */
function spa_times_match($time1, $time2) {
    $timestamp1 = strtotime('1970-01-01 ' . $time1);
    $timestamp2 = strtotime('1970-01-01 ' . $time2);
    
    $diff_minutes = abs($timestamp1 - $timestamp2) / 60;
    
    return $diff_minutes <= 5;
}

/**
 * Spracovanie CSV importu - HLAVNÝ ENTRY POINT
 * Podporuje CSV aj ZIP archívy
 */
function spa_process_csv_import() {
    // Bezpečnostná kontrola
    if (!current_user_can('manage_options')) {
        wp_die('Nemáte oprávnenie na túto akciu.');
    }

    check_admin_referer('spa_csv_import', 'spa_csv_import_nonce');

    // === ZÍSKANIE SKUPINY PRIAMO Z UI (ID) ===
$target_group_id = isset($_POST['import_group_id']) ? intval($_POST['import_group_id']) : 0;

if (!$target_group_id || get_post_type($target_group_id) !== 'spa_group' || get_post_status($target_group_id) !== 'publish') {
    wp_redirect(add_query_arg([
        'page'  => 'spa-registrations-import',
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

    // Redirect s výsledkami
    wp_redirect(add_query_arg([
        'page' => 'spa-import',
        'import' => 'success',
        'imported' => $total_stats['success'],
        'errors' => $total_stats['errors'],
        'skipped' => $total_stats['skipped'],
        'files' => $total_stats['processed_files']
    ], admin_url('admin.php')));
    exit;
}
add_action('admin_post_spa_import_csv', 'spa_process_csv_import');

/**
 * Spracovanie jedného CSV súboru
 * 
 * @param string $file_path Cesta k CSV
 * @param string $filename Názov súboru
 * @param string $city Názov mesta/adresára
 * @param string $zip_name Názov ZIP archívu (ak existuje)
 * @return array Štatistiky importu
 */
function spa_process_single_csv($file_path, $filename, $city = '', $zip_name = '', $target_group_id = 0) {
    
    $fallback_group_name = pathinfo($filename, PATHINFO_FILENAME);
    $fallback_group_name = sanitize_text_field($fallback_group_name);
    
    // Inicializácia štatistík
    $import_stats = [
        'success' => 0,
        'errors' => 0,
        'skipped' => 0,
        'error_log' => []
    ];
    
    // Pridať info o súbore do logu
    $import_stats['error_log'][] = sprintf(
        '=== Spracovanie: %s%s ===',
        !empty($city) ? $city . '/' : '',
        $filename
    );

    // Otvorenie CSV súboru
    if (($handle = fopen($file_path, 'r')) === false) {
        $import_stats['errors']++;
        $import_stats['error_log'][] = 'Chyba otvorenia súboru: ' . $filename;
        return $import_stats;
    }

    // Načítanie hlavičky
    $header = fgetcsv($handle, 0, ',');
    
    if ($header === false) {
        fclose($handle);
        $import_stats['errors']++;
        $import_stats['error_log'][] = 'Prázdny súbor: ' . $filename;
        return $import_stats;
    }

    // Normalizácia hlavičky
    $header = array_map('trim', $header);
    $header = array_map('strtolower', $header);

    // Validácia povinných stĺpcov
    $required_columns = [
        'meno',
        'priezvisko',
        'pohlavie',
        'datum_narodenia',
        'meno_rodica',
        'priezvisko_rodica',
        'email',
        'telefon'
    ];

    $missing_columns = array_diff($required_columns, $header);
    
    if (!empty($missing_columns)) {
        fclose($handle);
        $import_stats['errors']++;
        $import_stats['error_log'][] = sprintf(
            'Chýbajúce stĺpce v %s: %s',
            $filename,
            implode(', ', $missing_columns)
        );
        return $import_stats;
    }
    
    // Kontrola prítomnosti voliteľných stĺpcov
    $has_price_column = in_array('predvolena_suma', $header);

    // Spracovanie riadkov
    $row_number = 1;
    
    while (($row = fgetcsv($handle, 0, ',')) !== false) {
        $row_number++;

        // Preskočiť prázdne riadky
        if (empty(array_filter($row))) {
            $import_stats['skipped']++;
            continue;
        }

        // Kombinovať hlavičku s hodnotami
        $row_data = array_combine($header, $row);

        // Validácia dátumov
        if (!spa_validate_date_format($row_data['datum_narodenia'])) {
            $import_stats['errors']++;
            $import_stats['error_log'][] = sprintf(
                '[%s - Riadok %d] Neplatný dátum narodenia: %s',
                $filename,
                $row_number,
                $row_data['datum_narodenia']
            );
            continue;
        }

        // Kontrola roku narodenia
        $birth_year = DateTime::createFromFormat('d.m.Y', $row_data['datum_narodenia'])->format('Y');
        if ($birth_year < 1900 || $birth_year > 2020) {
            $import_stats['errors']++;
            $import_stats['error_log'][] = sprintf(
                '[%s - Riadok %d] Rok narodenia mimo rozsah (1900-2020): %s',
                $filename,
                $row_number,
                $birth_year
            );
            continue;
        }

        // === 1. DIEŤA ===
        $child_id = spa_find_or_create_child_from_csv($row_data);
        
        if (!$child_id) {
            $import_stats['errors']++;
            $import_stats['error_log'][] = sprintf(
                '[%s - Riadok %d] Chyba vytvorenia dieťaťa: %s %s',
                $filename,
                $row_number,
                $row_data['meno'],
                $row_data['priezvisko']
            );
            continue;
        }

        // === 2. RODIČ ===
        $parent_id = spa_find_or_create_parent_from_csv($row_data);
        
        if (!$parent_id) {
            $import_stats['errors']++;
            $import_stats['error_log'][] = sprintf(
                '[%s - Riadok %d] Chyba vytvorenia rodiča: %s',
                $filename,
                $row_number,
                $row_data['email']
            );
            continue;
        }

        // === 3. PREPOJENIE RODIČ ↔ DIEŤA ===
        spa_link_parent_child($parent_id, $child_id);

        // === 4. PRIRADENIE K CIEĽOVEJ SKUPINE ===

        // Skupina je už určená z admin UI
        $group_id = $target_group_id;

        if (!$group_id || get_post_status($group_id) !== 'publish') {
            $import_stats['errors']++;
            $import_stats['error_log'][] = sprintf(
                '[%s - Riadok %d] Cieľová skupina (ID: %d) neexistuje alebo nie je publikovaná',
                $filename,
                $row_number,
                $target_group_id
            );
            continue;
        }

        // === 5. KONTROLA DUPLICITY ===
        
        $existing_registration = new WP_Query([
            'post_type' => 'spa_registration',
            'post_status' => 'publish',
            'meta_query' => [
                'relation' => 'AND',
                [
                    'key' => 'child_id',
                    'value' => $child_id,
                    'compare' => '='
                ],
                [
                    'key' => 'group_id',
                    'value' => $group_id,
                    'compare' => '='
                ],
                [
                    'key' => 'registration_status',
                    'value' => 'active',
                    'compare' => '='
                ]
            ],
            'posts_per_page' => 1
        ]);

        if ($existing_registration->have_posts()) {
            $import_stats['skipped']++;
            $import_stats['error_log'][] = sprintf(
                '[%s - Riadok %d] Registrácia už existuje (cena sa nemenila): %s %s v skupine %s',
                $filename,
                $row_number,
                $row_data['meno'],
                $row_data['priezvisko'],
                $group_search_name
            );
            continue;
        }

        // === 6. ZÍSKAŤ CENU Z CSV ===
        
        $csv_price = null;
        if ($has_price_column && isset($row_data['predvolena_suma']) && !empty($row_data['predvolena_suma'])) {
            $price_string = str_replace(',', '.', $row_data['predvolena_suma']);
            $csv_price = floatval($price_string);
        }

        // === 7. VYTVORENIE REGISTRÁCIE ===
        
        $child_name = get_the_title($child_id);
        $group_name = get_the_title($group_id);

        $registration_id = wp_insert_post([
            'post_type'   => 'spa_registration',
            'post_title'  => sprintf('Registrácia %s - %s', $child_name, $group_name),
            'post_status' => 'publish',
        ]);

        if (is_wp_error($registration_id)) {
            $import_stats['errors']++;
            $import_stats['error_log'][] = sprintf(
                '[%s - Riadok %d] Chyba vytvorenia registrácie: %s',
                $filename,
                $row_number,
                $registration_id->get_error_message()
            );
            continue;
        }

        // === 8. ULOŽENIE META POLÍ ===

        $registration_date = current_time('mysql');

        // Základné prepojenia
        update_post_meta($registration_id, 'child_id', $child_id);
        update_post_meta($registration_id, 'parent_id', $parent_id);
        update_post_meta($registration_id, 'group_id', $group_id);
        update_post_meta($registration_id, 'registration_date', $registration_date);
        update_post_meta($registration_id, 'registration_status', 'active');

        // Sezóna (použiť existujúcu funkciu na výpočet)
        if (function_exists('spa_get_current_season')) {
            $calculated_season = spa_get_current_season($registration_date);
        } else {
            // Fallback ak funkcia neexistuje
            $month = (int)date('n', strtotime($registration_date));
            $year = (int)date('Y', strtotime($registration_date));
            $calculated_season = ($month >= 9) ? $year . '/' . ($year + 1) : ($year - 1) . '/' . $year;
        }
        update_post_meta($registration_id, 'spa_price_season', $calculated_season);

        // Zdroj ceny
        update_post_meta($registration_id, 'spa_price_source', 'csv_import');

        // Import metadata (pre export tracking)
        if (!empty($city)) {
            update_post_meta($registration_id, 'import_city', sanitize_text_field($city));
        }
        update_post_meta($registration_id, 'import_csv_filename', sanitize_file_name($filename));
        if (!empty($zip_name)) {
            update_post_meta($registration_id, 'import_zip_name', sanitize_file_name($zip_name));
        }
        update_post_meta($registration_id, 'import_timestamp', current_time('mysql'));

        // Cena z CSV (len ak bola zadaná)
        if ($csv_price !== null && $csv_price > 0) {
            update_post_meta($registration_id, 'registration_price', $csv_price);
        }

        $import_stats['success']++;
    }

    fclose($handle);
    
    return $import_stats;
}
