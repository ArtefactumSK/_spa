<?php
/**
 * SPA Import Tool - Import dát z Paysy.app
 * Podporuje: Deti (spa_child + spa_parent) aj Dospelých (spa_client)
 * 
 * @package Piasecky Academy
 * @version 2.1.0 - Fixed username format (meno.priezvisko)
 */

if (!defined('ABSPATH')) {
    exit;
}

/* ==========================
   ADMIN MENU
   ========================== */

add_action('admin_menu', 'spa_import_menu');

function spa_import_menu() {
    add_management_page(
        'SPA Import',
        '📥 SPA Import',
        'manage_options',
        'spa-import',
        'spa_import_page'
    );
}

/* ==========================
   IMPORT PAGE
   ========================== */

function spa_import_page() {
    
    $import_result = null;
    if (isset($_POST['spa_import_submit']) && wp_verify_nonce($_POST['spa_import_nonce'], 'spa_import')) {
        $import_result = spa_process_import();
    }
    
    $programs = get_posts([
        'post_type' => 'spa_group',
        'posts_per_page' => -1,
        'orderby' => 'menu_order title',
        'order' => 'ASC'
    ]);
    
    ?>
    <div class="wrap">
        <h1>📥 SPA Import - Dáta z Paysy.app</h1>
        
        <?php if ($import_result) : ?>
            <div class="notice notice-<?php echo $import_result['status']; ?> is-dismissible">
                <p><strong><?php echo $import_result['message']; ?></strong></p>
                <?php if (!empty($import_result['details'])) : ?>
                    <details style="margin-top: 10px;">
                        <summary style="cursor: pointer; font-weight: 600;">📋 Detaily importu</summary>
                        <pre style="background: #f5f5f5; padding: 15px; max-height: 400px; overflow: auto; margin-top: 10px; font-size: 12px; line-height: 1.6;"><?php echo esc_html($import_result['details']); ?></pre>
                    </details>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        
        <div class="card" style="max-width: 800px; padding: 20px;">
            
            <h2>📋 Príprava CSV súboru</h2>
            <ol>
                <li>Otvor XLS v Exceli</li>
                <li>Ulož ako: <strong>CSV UTF-8 (oddeľovač: bodkočiarka)</strong></li>
                <li>Vyber správny program nižšie</li>
            </ol>
            
            <hr style="margin: 25px 0;">
            
            <h2>🚀 Import</h2>
            
            <form method="post" enctype="multipart/form-data">
                <?php wp_nonce_field('spa_import', 'spa_import_nonce'); ?>
                
                <table class="form-table">
                    <tr>
                        <th><label for="import_file">CSV súbor:</label></th>
                        <td>
                            <input type="file" name="import_file" id="import_file" accept=".csv" required>
                        </td>
                    </tr>
                    
                    <tr>
                        <th><label for="program_id">Program:</label></th>
                        <td>
                            <select name="program_id" id="program_id" required style="width: 100%; max-width: 500px;">
                                <option value="">— Vyber program —</option>
                                <?php foreach ($programs as $program) : 
                                    $places = get_the_terms($program->ID, 'spa_place');
                                    $cats = get_the_terms($program->ID, 'spa_group_category');
                                    $place_str = $places ? implode(', ', wp_list_pluck($places, 'name')) : '';
                                    $cat_str = $cats ? $cats[0]->name : '';
                                ?>
                                    <option value="<?php echo $program->ID; ?>">
                                        <?php echo esc_html($program->post_title); ?>
                                        <?php if ($place_str) echo ' [' . $place_str . ']'; ?>
                                        <?php if ($cat_str) echo ' (' . $cat_str . ')'; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>
                    
                    <tr>
                        <th><label for="delimiter">Oddeľovač:</label></th>
                        <td>
                            <select name="delimiter" id="delimiter">
                                <option value=";">Bodkočiarka (;)</option>
                                <option value=",">Čiarka (,)</option>
                            </select>
                        </td>
                    </tr>
                    
                    <tr>
                        <th>Možnosti:</th>
                        <td>
                            <label><input type="checkbox" name="skip_existing" value="1" checked> Preskočiť existujúcich (podľa emailu + mena)</label><br>
                            <label><input type="checkbox" name="dry_run" value="1"> <strong>🔍 TEST MÓD</strong> (nezapíše dáta)</label><br>
                            <label><input type="checkbox" name="import_vs" value="1" checked> Použiť VS z CSV (ak existuje)</label>
                        </td>
                    </tr>
                </table>
                
                <p class="submit">
                    <input type="submit" name="spa_import_submit" class="button button-primary button-large" value="🚀 Spustiť import">
                </p>
            </form>
        </div>
        
        <div class="card" style="max-width: 800px; padding: 20px; margin-top: 20px;">
            <h2>📊 Aktuálny stav systému</h2>
            <?php
            $parents = count(get_users(['role' => 'spa_parent']));
            $children = count(get_users(['role' => 'spa_child']));
            $clients = count(get_users(['role' => 'spa_client']));
            $regs = wp_count_posts('spa_registration')->publish + wp_count_posts('spa_registration')->pending;
            ?>
            <table class="widefat" style="max-width: 300px;">
                <tr><td>👨‍👩‍👧 Rodičia</td><td><strong><?php echo $parents; ?></strong></td></tr>
                <tr><td>👶 Deti</td><td><strong><?php echo $children; ?></strong></td></tr>
                <tr><td>🏃 Dospelí klienti</td><td><strong><?php echo $clients; ?></strong></td></tr>
                <tr><td>📋 Registrácie</td><td><strong><?php echo $regs; ?></strong></td></tr>
                <tr><td>🤸🏻‍♂️ Programy</td><td><strong><?php echo count($programs); ?></strong></td></tr>
            </table>
        </div>
    </div>
    <?php
}

/* ==========================
   PROCESS IMPORT
   ========================== */

function spa_process_import() {
    
    if (!current_user_can('manage_options')) {
        return ['status' => 'error', 'message' => 'Nedostatočné oprávnenia'];
    }
    
    if (!isset($_FILES['import_file']) || $_FILES['import_file']['error'] !== UPLOAD_ERR_OK) {
        return ['status' => 'error', 'message' => 'Chyba pri nahrávaní súboru'];
    }
    
    $file = $_FILES['import_file']['tmp_name'];
    $program_id = intval($_POST['program_id']);
    $delimiter = $_POST['delimiter'];
    $skip_existing = isset($_POST['skip_existing']);
    $dry_run = isset($_POST['dry_run']);
    $import_vs = isset($_POST['import_vs']);
    
    if (!$program_id) {
        return ['status' => 'error', 'message' => 'Vyber program'];
    }
    
    $handle = fopen($file, 'r');
    if (!$handle) {
        return ['status' => 'error', 'message' => 'Nepodarilo sa otvoriť súbor'];
    }
    
    $headers = fgetcsv($handle, 0, $delimiter);
    if (!$headers) {
        fclose($handle);
        return ['status' => 'error', 'message' => 'Prázdny súbor'];
    }
    
    $headers = array_map('trim', $headers);
    $column_map = spa_detect_columns($headers);
    
    $log = [];
    $log[] = "=== IMPORT ŠTART ===";
    $log[] = "Program ID: $program_id";
    $log[] = "Test mód: " . ($dry_run ? 'ÁNO' : 'NIE');
    $log[] = "Detekované stĺpce: " . json_encode($column_map, JSON_UNESCAPED_UNICODE);
    $log[] = "";
    
    $imported_children = 0;
    $imported_adults = 0;
    $skipped = 0;
    $errors = 0;
    $row_num = 1;
    
    while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
        $row_num++;
        
        if (empty(array_filter($row))) {
            continue;
        }
        
        $data = spa_map_row_data($row, $column_map);
        
        // Validácia
        if (empty($data['member_firstname'])) {
            $log[] = "Riadok $row_num: ⚠️ Chýba meno člena - PRESKOČENÉ";
            $errors++;
            continue;
        }
        
        // Rozhodnutie: Dieťa alebo Dospelý?
        $is_child = !empty($data['parent_email']) || !empty($data['parent_firstname']);
        $type_label = $is_child ? 'DIEŤA' : 'DOSPELÝ';
        
        // Kontrola existujúceho
        $contact_email = $is_child ? $data['parent_email'] : $data['member_email'];
        
        if ($skip_existing && !empty($contact_email)) {
            $existing = spa_find_existing_member($contact_email, $data['member_firstname'], $data['member_lastname'], $is_child);
            if ($existing) {
                $log[] = "Riadok $row_num: ⭐️ [{$type_label}] {$data['member_firstname']} {$data['member_lastname']} už existuje - PRESKOČENÉ";
                $skipped++;
                continue;
            }
        }
        
        // DRY RUN
        if ($dry_run) {
            $vs_info = $data['variabilny_symbol'] ?: '(nový)';
            $username_preview = spa_generate_username_from_name($data['member_firstname'], $data['member_lastname']);
            $log[] = "Riadok $row_num: 🔍 [{$type_label}] {$data['member_firstname']} {$data['member_lastname']} | Username: {$username_preview} | VS: {$vs_info} | Email: {$contact_email}";
            if ($is_child) {
                $imported_children++;
            } else {
                $imported_adults++;
            }
            continue;
        }
        
        // SKUTOČNÝ IMPORT
        if ($is_child) {
            $result = spa_import_child($data, $program_id, $import_vs);
        } else {
            $result = spa_import_adult($data, $program_id, $import_vs);
        }
        
        if ($result['success']) {
            $log[] = "Riadok $row_num: ✅ [{$type_label}] {$data['member_firstname']} {$data['member_lastname']} | Username: {$result['username']} | VS: {$result['vs']} | PIN: " . ($result['pin'] ?? '—');
            if ($is_child) {
                $imported_children++;
            } else {
                $imported_adults++;
            }
        } else {
            $log[] = "Riadok $row_num: ❌ [{$type_label}] Chyba - {$result['error']}";
            $errors++;
        }
    }
    
    fclose($handle);
    
    $log[] = "";
    $log[] = "=== IMPORT KONIEC ===";
    $log[] = "Deti: $imported_children";
    $log[] = "Dospelí: $imported_adults";
    $log[] = "Preskočené: $skipped";
    $log[] = "Chyby: $errors";
    
    $mode = $dry_run ? '[TEST MÓD] ' : '';
    $total = $imported_children + $imported_adults;
    $message = "{$mode}Import dokončený: {$total} importovaných ({$imported_children} detí, {$imported_adults} dospelých), {$skipped} preskočených, {$errors} chýb";
    
    return [
        'status' => $errors > 0 ? 'warning' : 'success',
        'message' => $message,
        'details' => implode("\n", $log)
    ];
}

/* ==========================
   DETECT COLUMNS - Paysy.app štruktúra
   ========================== */


function spa_detect_columns($headers) {
    
    // OPRAVENÉ indexy podľa skutočného CSV
    $map = [
        // DIEŤA / ČLEN
        'member_firstname'   => 0,   // Meno
        'member_lastname'    => 1,   // Priezvisko
        'member_email'       => 2,   // Email (zvyčajne prázdny pre deti)
        'member_phone'       => 3,   // Tel. číslo
        'member_birthdate'   => 4,   // Dátum narodenia
        'rodne_cislo'        => 5,   // Rodné číslo
        'variabilny_symbol'  => 8,   // Variabilný symbol
        
        // RODIČ (stĺpce 30-37)
        'stav'               => 29,  // Stav
        'parent_email'       => 30,  // Email rodiča
        'parent_firstname'   => 31,  // Meno rodiča
        'parent_lastname'    => 32,  // Priezvisko rodiča
        'parent_phone'       => 33,  // Tel. číslo rodiča
        // index 34 = Adresa (prázdne)
        'address_street'     => 35,  // Ulica Číslo
        'address_psc'        => 36,  // PSČ
        'address_city'       => 37,  // Mesto
    ];
    
    return $map;
}

/* ==========================
   MAP ROW DATA - s opravou Excel exponenciálneho formátu
   ========================== */

function spa_map_row_data($row, $column_map) {
    $data = [];
    
    foreach ($column_map as $field => $idx) {
        $value = isset($row[$idx]) ? trim($row[$idx]) : '';
        $data[$field] = $value;
    }
    
    // FIX: Excel exponenciálny formát pre telefónne čísla (napr. 4,21915E+11)
    if (!empty($data['parent_phone'])) {
        $data['parent_phone'] = spa_fix_excel_number($data['parent_phone']);
    }
    if (!empty($data['member_phone'])) {
        $data['member_phone'] = spa_fix_excel_number($data['member_phone']);
    }
    
    // FIX: Odstráň medzery z PSČ (90875 vs 900 68)
    if (!empty($data['address_psc'])) {
        $data['address_psc'] = preg_replace('/\s+/', '', $data['address_psc']);
    }
    
    // DEBUG log
    error_log('=== MAPPED DATA ===');
    error_log('Child: ' . $data['member_firstname'] . ' ' . $data['member_lastname']);
    error_log('Parent: ' . $data['parent_firstname'] . ' ' . $data['parent_lastname']);
    error_log('Parent email: ' . $data['parent_email']);
    error_log('Parent phone: ' . $data['parent_phone']);
    error_log('Address: ' . $data['address_street'] . ', ' . $data['address_psc'] . ' ' . $data['address_city']);
    
    return $data;
}

/* ==========================
   FIX EXCEL NUMBER - oprava exponenciálneho formátu
   Konvertuje "4,21915E+11" na "421915000000" alebo čistí na čísla
   ========================== */

function spa_fix_excel_number($value) {
    
    // Ak obsahuje E+ alebo e+ (exponenciálny formát)
    if (preg_match('/[Ee]\+/', $value)) {
        // Nahraď čiarku za bodku (európsky formát)
        $value = str_replace(',', '.', $value);
        // Konvertuj na číslo a späť na string
        $value = number_format((float)$value, 0, '', '');
    }
    
    // Odstráň všetko okrem číslic (pre telefón)
    // Ponechaj + na začiatku ak existuje
    if (strpos($value, '+') === 0) {
        $value = '+' . preg_replace('/[^0-9]/', '', substr($value, 1));
    } else {
        $value = preg_replace('/[^0-9]/', '', $value);
    }
    
    // Pridaj +421 prefix ak začína 09
    if (preg_match('/^09[0-9]{8}$/', $value)) {
        $value = '+421' . substr($value, 1);
    }
    
    return $value;
}

/* ==========================
   FIND EXISTING MEMBER
   ========================== */

function spa_find_existing_member($email, $firstname, $lastname, $is_child) {
    
    if ($is_child) {
        $parent = get_user_by('email', $email);
        if (!$parent) return null;
        
        $children = get_users([
            'role' => 'spa_child',
            'meta_key' => 'parent_id',
            'meta_value' => $parent->ID
        ]);
        
        foreach ($children as $child) {
            if (mb_strtolower(get_user_meta($child->ID, 'first_name', true)) === mb_strtolower($firstname) &&
                mb_strtolower(get_user_meta($child->ID, 'last_name', true)) === mb_strtolower($lastname)) {
                return $child;
            }
        }
        return null;
    } else {
        return get_user_by('email', $email);
    }
}

/* ==========================
   GENERATE USERNAME FROM NAME
   Formát: meno.priezvisko (lowercase, bez diakritiky)
   ========================== */

function spa_generate_username_from_name($firstname, $lastname) {
    
    // Odstráň diakritiku
    $firstname = spa_remove_diacritics($firstname);
    $lastname = spa_remove_diacritics($lastname);
    
    // Lowercase a odstráň špeciálne znaky
    $firstname = strtolower(preg_replace('/[^a-z0-9]/i', '', $firstname));
    $lastname = strtolower(preg_replace('/[^a-z0-9]/i', '', $lastname));
    
    // Základný username
    $base_username = $firstname . '.' . $lastname;
    
    // Skráť ak je príliš dlhý
    if (strlen($base_username) > 50) {
        $base_username = substr($base_username, 0, 50);
    }
    
    // Zabezpeč unikátnosť
    $username = $base_username;
    $counter = 1;
    
    while (username_exists($username)) {
        $username = $base_username . $counter;
        $counter++;
    }
    
    return $username;
}

/* ==========================
   REMOVE DIACRITICS
   ========================== */

function spa_remove_diacritics($string) {
    $chars = [
        'á'=>'a', 'ä'=>'a', 'č'=>'c', 'ď'=>'d', 'é'=>'e', 'ě'=>'e', 
        'í'=>'i', 'ľ'=>'l', 'ĺ'=>'l', 'ň'=>'n', 'ó'=>'o', 'ô'=>'o', 
        'ŕ'=>'r', 'ř'=>'r', 'š'=>'s', 'ť'=>'t', 'ú'=>'u', 'ů'=>'u', 
        'ý'=>'y', 'ž'=>'z',
        'Á'=>'A', 'Ä'=>'A', 'Č'=>'C', 'Ď'=>'D', 'É'=>'E', 'Ě'=>'E',
        'Í'=>'I', 'Ľ'=>'L', 'Ĺ'=>'L', 'Ň'=>'N', 'Ó'=>'O', 'Ô'=>'O',
        'Ŕ'=>'R', 'Ř'=>'R', 'Š'=>'S', 'Ť'=>'T', 'Ú'=>'U', 'Ů'=>'U',
        'Ý'=>'Y', 'Ž'=>'Z'
    ];
    
    return strtr($string, $chars);
}

/* ==========================
   IMPORT CHILD - OPRAVENÁ VERZIA
   ========================== */

function spa_import_child($data, $program_id, $import_vs) {
    
    error_log('=== SPA IMPORT CHILD ===');
    error_log('Child: ' . $data['member_firstname'] . ' ' . $data['member_lastname']);
    
    // 1. RODIČ - použi IMPORT verziu
    $parent_id = spa_get_or_create_parent_import(
        $data['parent_email'],
        $data['parent_firstname'],
        $data['parent_lastname'],
        $data['parent_phone'],
        $data['address_street'],
        $data['address_psc'],
        $data['address_city']
    );
    
    if (!$parent_id) {
        return [
            'success' => false, 
            'error' => 'Nepodarilo sa vytvoriť rodiča (email: ' . $data['parent_email'] . ')'
        ];
    }
    
    // 2. DIEŤA
    $child_result = spa_create_child_account_import(
        $data['member_firstname'],
        $data['member_lastname'],
        spa_parse_date($data['member_birthdate']),
        $parent_id,
        '',
        $data['rodne_cislo']
    );
    
    if (!$child_result['success']) {
        return ['success' => false, 'error' => $child_result['error']];
    }
    
    $child_id = $child_result['user_id'];
    $username = $child_result['username'];
    
    // 3. VS
    $vs = '';
    if ($import_vs && !empty($data['variabilny_symbol'])) {
        $vs = preg_replace('/[^0-9]/', '', $data['variabilny_symbol']);
    } else {
        $vs = spa_generate_variabilny_symbol();
    }
    update_user_meta($child_id, 'variabilny_symbol', $vs);
    
    // 4. PIN
    $pin = spa_generate_pin();
    update_user_meta($child_id, 'spa_pin', spa_hash_pin($pin));
    update_user_meta($child_id, 'spa_pin_plain', $pin);
    
    // 5. Registrácia
    $reg_id = spa_create_registration($child_id, $program_id, $parent_id, null);
    spa_set_import_status($reg_id, $data['stav']);
    
    return [
        'success' => true,
        'parent_id' => $parent_id,
        'child_id' => $child_id,
        'username' => $username,
        'vs' => $vs,
        'pin' => $pin
    ];
}

/* ==========================
   CREATE CHILD ACCOUNT - IMPORT VERSION
   Username: meno.priezvisko
   ========================== */

function spa_create_child_account_import($first_name, $last_name, $birthdate, $parent_id, $health_notes = '', $rodne_cislo = '') {
    
    // Generuj username ako meno.priezvisko
    $username = spa_generate_username_from_name($first_name, $last_name);
    
    // Email - interný (deti sa neprihlasujú cez email)
    $email = $username . '@piaseckyacademy.sk';
    
    // Heslo - náhodné (deti používajú PIN)
    $password = wp_generate_password(32);
    
    // Vytvor používateľa
    $user_id = wp_insert_user([
        'user_login' => $username,
        'user_email' => $email,
        'user_pass' => $password,
        'first_name' => sanitize_text_field($first_name),
        'last_name' => sanitize_text_field($last_name),
        'display_name' => $first_name . ' ' . $last_name,
        'role' => 'spa_child'
    ]);
    
    if (is_wp_error($user_id)) {
        return [
            'success' => false,
            'error' => $user_id->get_error_message()
        ];
    }
    
    // Meta data
    update_user_meta($user_id, 'birthdate', sanitize_text_field($birthdate));
    update_user_meta($user_id, 'parent_id', intval($parent_id));
    
    if ($health_notes) {
        update_user_meta($user_id, 'health_notes', sanitize_textarea_field($health_notes));
    }
    
    if ($rodne_cislo) {
        $rc_clean = preg_replace('/[^0-9]/', '', $rodne_cislo);
        update_user_meta($user_id, 'rodne_cislo', $rc_clean);
    }
    
    return [
        'success' => true,
        'user_id' => $user_id,
        'username' => $username
    ];
}

/* ==========================
   IMPORT ADULT (dospelý klient)
   ========================== */

function spa_import_adult($data, $program_id, $import_vs) {
    
    if (empty($data['member_email'])) {
        return ['success' => false, 'error' => 'Dospelý klient nemá email'];
    }
    
    $client_result = spa_get_or_create_client_import(
        $data['member_email'],
        $data['member_firstname'],
        $data['member_lastname'],
        $data['member_phone'],
        spa_parse_date($data['member_birthdate'])
    );
    
    if (!$client_result['success']) {
        return ['success' => false, 'error' => $client_result['error']];
    }
    
    $client_id = $client_result['user_id'];
    $username = $client_result['username'];
    
    if (!empty($data['rodne_cislo'])) {
        $rc = preg_replace('/[^0-9]/', '', $data['rodne_cislo']);
        update_user_meta($client_id, 'rodne_cislo', $rc);
    }
    
    $vs = '';
    if ($import_vs && !empty($data['variabilny_symbol'])) {
        $vs = preg_replace('/[^0-9]/', '', $data['variabilny_symbol']);
        update_user_meta($client_id, 'variabilny_symbol', $vs);
    } else {
        $vs = spa_generate_variabilny_symbol();
        update_user_meta($client_id, 'variabilny_symbol', $vs);
    }
    
    if (!empty($data['address_street'])) {
        update_user_meta($client_id, 'address_street', $data['address_street']);
    }
    if (!empty($data['address_psc'])) {
        update_user_meta($client_id, 'address_psc', $data['address_psc']);
    }
    if (!empty($data['address_city'])) {
        update_user_meta($client_id, 'address_city', $data['address_city']);
    }
    
    $reg_id = spa_create_registration($client_id, $program_id, null, null);
    spa_set_import_status($reg_id, $data['stav']);
    
    return [
        'success' => true,
        'client_id' => $client_id,
        'username' => $username,
        'vs' => $vs
    ];
}

/* ==========================
   GET OR CREATE CLIENT - IMPORT VERSION
   ========================== */

function spa_get_or_create_client_import($email, $first_name, $last_name, $phone, $birthdate) {
    
    $existing = get_user_by('email', $email);
    
    if ($existing) {
        update_user_meta($existing->ID, 'phone', sanitize_text_field($phone));
        update_user_meta($existing->ID, 'birthdate', sanitize_text_field($birthdate));
        
        return [
            'success' => true,
            'user_id' => $existing->ID,
            'username' => $existing->user_login,
            'existing' => true
        ];
    }
    
    $username = spa_generate_username_from_name($first_name, $last_name);
    $password = wp_generate_password(12, true);
    
    $user_id = wp_insert_user([
        'user_login' => $username,
        'user_email' => sanitize_email($email),
        'user_pass' => $password,
        'first_name' => sanitize_text_field($first_name),
        'last_name' => sanitize_text_field($last_name),
        'display_name' => $first_name . ' ' . $last_name,
        'role' => 'spa_client'
    ]);
    
    if (is_wp_error($user_id)) {
        return [
            'success' => false,
            'error' => $user_id->get_error_message()
        ];
    }
    
    update_user_meta($user_id, 'phone', sanitize_text_field($phone));
    update_user_meta($user_id, 'birthdate', sanitize_text_field($birthdate));
    
    return [
        'success' => true,
        'user_id' => $user_id,
        'username' => $username,
        'existing' => false
    ];
}

/* ==========================
   GET OR CREATE PARENT - IMPORT VERSION
   Opravená verzia s korektným mapovaním polí
   ========================== */

function spa_get_or_create_parent_import($email, $first_name, $last_name, $phone, $address_street = '', $address_psc = '', $address_city = '') {
    
    // DEBUG
    error_log('=== SPA CREATE PARENT ===');
    error_log("Email: $email");
    error_log("First: $first_name | Last: $last_name");
    error_log("Phone: $phone");
    error_log("Address: $address_street, $address_psc $address_city");
    
    // Validácia
    if (empty($email)) {
        error_log('SPA ERROR: Parent email is empty!');
        return false;
    }
    
    if (empty($first_name) || empty($last_name)) {
        error_log('SPA ERROR: Parent name is empty!');
        return false;
    }
    
    // Skontroluj existujúceho
    $existing = get_user_by('email', $email);
    
    if ($existing) {
        error_log('SPA: Found existing parent ID ' . $existing->ID);
        
        // Aktualizuj údaje
        wp_update_user([
            'ID' => $existing->ID,
            'first_name' => sanitize_text_field($first_name),
            'last_name' => sanitize_text_field($last_name),
            'display_name' => trim($first_name . ' ' . $last_name),
            'nickname' => spa_generate_username_from_name($first_name, $last_name)
        ]);
        
        update_user_meta($existing->ID, 'phone', sanitize_text_field($phone));
        
        if (!empty($address_street)) {
            update_user_meta($existing->ID, 'address_street', sanitize_text_field($address_street));
        }
        if (!empty($address_psc)) {
            update_user_meta($existing->ID, 'address_psc', sanitize_text_field($address_psc));
        }
        if (!empty($address_city)) {
            update_user_meta($existing->ID, 'address_city', sanitize_text_field($address_city));
        }
        
        return $existing->ID;
    }
    
    // Vytvor nového rodiča
    $username = spa_generate_username_from_name($first_name, $last_name);
    $password = wp_generate_password(12, true);
    
    error_log("SPA: Creating parent with username: $username");
    
    $user_id = wp_insert_user([
        'user_login'   => $username,
        'user_email'   => sanitize_email($email),
        'user_pass'    => $password,
        'first_name'   => sanitize_text_field($first_name),
        'last_name'    => sanitize_text_field($last_name),
        'display_name' => trim($first_name . ' ' . $last_name),
        'nickname'     => $username,
        'role'         => 'spa_parent'
    ]);
    
    if (is_wp_error($user_id)) {
        error_log('SPA ERROR: ' . $user_id->get_error_message());
        return false;
    }
    
    // Meta data
    update_user_meta($user_id, 'phone', sanitize_text_field($phone));
    
    if (!empty($address_street)) {
        update_user_meta($user_id, 'address_street', sanitize_text_field($address_street));
    }
    if (!empty($address_psc)) {
        update_user_meta($user_id, 'address_psc', sanitize_text_field($address_psc));
    }
    if (!empty($address_city)) {
        update_user_meta($user_id, 'address_city', sanitize_text_field($address_city));
    }
    
    error_log("SPA: Created parent ID $user_id");
    
    // Welcome email
    spa_send_welcome_email($email, $username, $password, $first_name);
    
    return $user_id;
}


/* ==========================
   HELPER: Set import status
   ========================== */

function spa_set_import_status($reg_id, $csv_stav) {
    if (!$reg_id) return;
    
    $status = 'approved';
    if (!empty($csv_stav)) {
        $s = mb_strtolower($csv_stav);
        if (strpos($s, 'aktiv') !== false) $status = 'active';
        elseif (strpos($s, 'deaktiv') !== false) $status = 'cancelled';
    }
    
    update_post_meta($reg_id, 'status', $status);
    wp_update_post(['ID' => $reg_id, 'post_status' => 'publish']);
}

/* ==========================
   HELPER: Parse date
   ========================== */

function spa_parse_date($date_str) {
    if (empty($date_str)) return '';
    
    $formats = ['d.m.Y', 'd/m/Y', 'Y-m-d', 'd-m-Y'];
    
    foreach ($formats as $format) {
        $date = DateTime::createFromFormat($format, $date_str);
        if ($date) return $date->format('Y-m-d');
    }
    
    return $date_str;
}