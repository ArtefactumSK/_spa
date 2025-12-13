<?php
/**
 * SPA Import CSV v2 - Nová štruktúra
 * 
 * @package Samuel Piasecký ACADEMY
 * @subpackage Import
 * @version 2.0.0
 * 
 * PARENT MODULES: 
 * - user/spa-user-parents.php
 * - user/spa-user-children.php
 * - user/spa-user-clients.php
 * - registration/spa-registration-helpers.php
 * 
 * CIEĽ:
 * - Import CSV (children + parents)
 * - Vytvorenie user accounts
 * - Vytvorenie spa_registration CPT s vazbou na spa_group
 * - Uloženie meta polí (VS, PIN, health_notes, atď.)
 */

if (!defined('ABSPATH')) {
    exit;
}

/* ==================================================
   ADMIN PAGE: Import CSV
   ================================================== */

add_action('admin_menu', 'spa_import_csv_v2_menu');

function spa_import_csv_v2_menu() {
    add_submenu_page(
        'edit.php?post_type=spa_registration',
        'CSV Import v2',
        '📥 CSV Import',
        'manage_options',
        'spa-import-csv-v2',
        'spa_import_csv_v2_page'
    );
}

/* ==================================================
   ADMIN PAGE: Render
   ================================================== */

function spa_import_csv_v2_page() {
    ?>
    <div class="wrap">
        <h1>📥 CSV Import - Nová štruktúra (v2)</h1>
        
        <div class="notice notice-info">
            <p>
                <strong>Postup:</strong><br>
                1. Uploadni CSV súbor<br>
                2. Vyber mapovací program (Program_ID)<br>
                3. Skontroluj náhľad<br>
                4. Potvrď import
            </p>
        </div>
        
        <form method="post" enctype="multipart/form-data">
            <?php wp_nonce_field('spa_import_csv_nonce'); ?>
            
            <table class="form-table">
                <tr>
                    <th>
                        <label for="csv_file">CSV Súbor:</label>
                    </th>
                    <td>
                        <input type="file" name="csv_file" id="csv_file" 
                               accept=".csv" required>
                        <p class="description">
                            Očakávané stĺpce: Meno;Priezvisko;Email;Tel.;...;Email(rodič);Meno(rodič);Priezvisko(rodič);...
                        </p>
                    </td>
                </tr>
                
                <tr>
                    <th>
                        <label for="program_id">Program (spa_group ID):</label>
                    </th>
                    <td>
                        <select name="program_id" id="program_id" required>
                            <option value="">-- Vyber program --</option>
                            <?php
                            $programs = get_posts([
                                'post_type' => 'spa_group',
                                'posts_per_page' => -1,
                                'orderby' => 'title',
                                'order' => 'ASC'
                            ]);
                            
                            foreach ($programs as $prog) {
                                printf(
                                    '<option value="%d">%s (ID: %d)</option>',
                                    $prog->ID,
                                    esc_html($prog->post_title),
                                    $prog->ID
                                );
                            }
                            ?>
                        </select>
                    </td>
                </tr>
                
                <tr>
                    <th></th>
                    <td>
                        <button type="submit" name="spa_import_preview" 
                                class="button button-primary">
                            👁️ Náhľad
                        </button>
                        <button type="submit" name="spa_import_process" 
                                class="button button-secondary" 
                                style="margin-left: 10px;">
                            ✅ Importovať
                        </button>
                    </td>
                </tr>
            </table>
        </form>
        
        <?php
        // PREVIEW ALEBO PROCESS
        if (isset($_POST['spa_import_preview']) || isset($_POST['spa_import_process'])) {
            spa_import_csv_v2_handle($_POST);
        }
        ?>
    </div>
    <?php
}

/* ==================================================
   HANDLER: Process CSV
   ================================================== */

function spa_import_csv_v2_handle($post_data) {
    // Validácia nonce
    if (!isset($post_data['_wpnonce']) || !wp_verify_nonce($post_data['_wpnonce'], 'spa_import_csv_nonce')) {
        wp_die('Security check failed');
    }
    
    // File upload
    if (empty($_FILES['csv_file']['tmp_name'])) {
        echo '<div class="notice notice-error"><p>❌ Súbor nebol uploadnutý.</p></div>';
        return;
    }
    
    $csv_file = $_FILES['csv_file']['tmp_name'];
    $program_id = intval($post_data['program_id']);
    
    if (!$program_id) {
        echo '<div class="notice notice-error"><p>❌ Vyber program.</p></div>';
        return;
    }
    
    // Parse CSV
    $rows = spa_parse_csv_file($csv_file);
    
    if (empty($rows)) {
        echo '<div class="notice notice-error"><p>❌ CSV súbor je prázdny alebo chybný.</p></div>';
        return;
    }
    
    // PREVIEW mode
    if (isset($post_data['spa_import_preview'])) {
        spa_import_csv_preview($rows, $program_id);
        return;
    }
    
    // PROCESS mode
    if (isset($post_data['spa_import_process'])) {
        spa_import_csv_process($rows, $program_id);
    }
}

/* ==================================================
   PARSER: CSV → Array
   ================================================== */

function spa_parse_csv_file($file_path) {
    $rows = [];
    
    if (($handle = fopen($file_path, 'r')) !== false) {
        // Skip header
        fgetcsv($handle, 0, ';');
        
        while (($data = fgetcsv($handle, 0, ';')) !== false) {
            if (count($data) < 5) continue; // Skip invalid rows
            
            $rows[] = [
                'child_first' => trim($data[0] ?? ''),
                'child_last' => trim($data[1] ?? ''),
                'child_email' => trim($data[2] ?? ''),
                'child_phone' => trim($data[3] ?? ''),
                'child_birthdate' => trim($data[4] ?? ''),
                'child_rodne_cislo' => trim($data[5] ?? ''),
                'vs' => trim($data[8] ?? ''), // Variabilný symbol
                'parent_email' => trim($data[30] ?? ''),
                'parent_first' => trim($data[31] ?? ''),
                'parent_last' => trim($data[32] ?? ''),
                'parent_phone' => trim($data[33] ?? ''),
                'parent_street' => trim($data[35] ?? ''),
                'parent_psc' => trim($data[36] ?? ''),
                'parent_city' => trim($data[37] ?? ''),
            ];
        }
        fclose($handle);
    }
    
    return $rows;
}

/* ==================================================
   PREVIEW: Náhľad
   ================================================== */

function spa_import_csv_preview($rows, $program_id) {
    $program = get_post($program_id);
    
    echo '<div class="notice notice-info" style="margin-top: 20px;">';
    echo '<h2>👁️ Náhľad importu</h2>';
    echo '<p><strong>Program:</strong> ' . esc_html($program->post_title) . ' (ID: ' . $program_id . ')</p>';
    echo '<p><strong>Počet riadkov:</strong> ' . count($rows) . '</p>';
    echo '<table class="wp-list-table fixed striped">';
    echo '<thead><tr>';
    echo '<th>Dieťa (Meno)</th><th>Rodič (Email)</th><th>VS</th><th>Stav</th>';
    echo '</tr></thead>';
    echo '<tbody>';
    
    foreach (array_slice($rows, 0, 5) as $row) {
        $status = 'OK';
        if (empty($row['child_first']) || empty($row['parent_email'])) {
            $status = '❌ Chýbajúce údaje';
        }
        
        echo '<tr>';
        echo '<td>' . esc_html($row['child_first'] . ' ' . $row['child_last']) . '</td>';
        echo '<td>' . esc_html($row['parent_email']) . '</td>';
        echo '<td>' . esc_html($row['vs']) . '</td>';
        echo '<td>' . $status . '</td>';
        echo '</tr>';
    }
    
    if (count($rows) > 5) {
        echo '<tr><td colspan="4" style="text-align: center;"><em>...a ďalších ' . (count($rows) - 5) . ' riadkov</em></td></tr>';
    }
    
    echo '</tbody>';
    echo '</table>';
    echo '</div>';
}

/* ==================================================
   PROCESS: Import
   ================================================== */

function spa_import_csv_process($rows, $program_id) {
    $imported = 0;
    $failed = 0;
    $errors = [];
    
    foreach ($rows as $idx => $row) {
        // Validácia
        if (empty($row['child_first']) || empty($row['parent_email'])) {
            $failed++;
            $errors[] = "Riadok " . ($idx + 2) . ": Chýbajúce kritické údaje";
            continue;
        }
        
        // 1. Get or Create Parent
        $parent_id = spa_get_or_create_parent(
            $row['parent_email'],
            $row['parent_first'],
            $row['parent_last'],
            $row['parent_phone'],
            $row['parent_street'],
            $row['parent_psc'],
            $row['parent_city'],
            '', // vs parent
            ''  // pin parent
        );
        
        if (!$parent_id) {
            $failed++;
            $errors[] = "Riadok " . ($idx + 2) . ": Chyba vytvárania rodiča";
            continue;
        }
        
        // 2. Create Child Account
        $child_id = spa_create_child_account(
            $row['child_first'],
            $row['child_last'],
            $row['child_birthdate'],
            $parent_id,
            '', // health notes
            $row['child_rodne_cislo']
        );
        
        if (!$child_id) {
            $failed++;
            $errors[] = "Riadok " . ($idx + 2) . ": Chyba vytvárania dieťaťa";
            continue;
        }
        
        // 3. Create Registration
        $registration_id = spa_create_registration(
            $child_id,
            $program_id,
            $parent_id,
            0 // gravity forms entry ID (žiaden)
        );
        
        if (!$registration_id) {
            $failed++;
            $errors[] = "Riadok " . ($idx + 2) . ": Chyba vytvárania registrácie";
            continue;
        }
        
        // 4. Save VS
        if (!empty($row['vs'])) {
            update_user_meta($parent_id, SPA_META_VS, sanitize_text_field($row['vs']));
        }
        
        $imported++;
    }
    
    // Report
    echo '<div class="notice notice-success" style="margin-top: 20px;">';
    echo '<h2>✅ Import hotový</h2>';
    echo '<p><strong>Importované:</strong> ' . $imported . '</p>';
    echo '<p><strong>Neúspešne:</strong> ' . $failed . '</p>';
    
    if (!empty($errors)) {
        echo '<h3>Chyby:</h3>';
        echo '<ul style="list-style: disc; margin-left: 20px;">';
        foreach ($errors as $error) {
            echo '<li>' . esc_html($error) . '</li>';
        }
        echo '</ul>';
    }
    
    echo '</div>';
}

/* ==================================================
   HELPER: Create Registration CPT
   ================================================== */

function spa_create_registration($child_id, $program_id, $parent_id, $gf_entry_id) {
    $child = get_user_by('id', $child_id);
    $program = get_post($program_id);
    
    $title = $child->display_name . ' - ' . $program->post_title;
    
    $registration = wp_insert_post([
        'post_type' => 'spa_registration',
        'post_title' => $title,
        'post_status' => 'publish',
        'post_author' => $child_id
    ]);
    
    if (is_wp_error($registration)) {
        return false;
    }
    
    // Meta
    update_post_meta($registration, 'spa_child_id', $child_id);
    update_post_meta($registration, 'spa_program_id', $program_id);
    update_post_meta($registration, 'spa_parent_id', $parent_id);
    update_post_meta($registration, 'spa_status', 'active');
    update_post_meta($registration, 'spa_gf_entry_id', $gf_entry_id);
    
    return $registration;
}