<?php
/**
 * SCHEDULE META BOX - Rozvrh tréningov
 * Použitie: v spa-meta-boxes.php
 * 
 * Sprievodca dni v týždni a úložisku tréningov
 */

function spa_group_schedule_meta_box($post) {
    wp_nonce_field('spa_save_group_schedule', 'spa_group_schedule_nonce');
    
    // Načítaj aktuálny rozvrh
    $schedule_days = get_post_meta($post->ID, 'spa_schedule_days', true);
    if (!is_array($schedule_days)) {
        $schedule_days = [];
    }
    
    $schedule_times = get_post_meta($post->ID, 'spa_schedule_times', true);
    if (!is_array($schedule_times)) {
        $schedule_times = [];
    }
    
    $days_of_week = [
        'mo' => '🟦 Pondelok',
        'tu' => '🟩 Utorok',
        'we' => '🟪 Streda',
        'th' => '🟨 Štvrtok',
        'fr' => '🟧 Piatok',
        'sa' => '🟥 Sobota',
        'su' => '⚪ Nedeľa'
    ];
    
    ?>
    <style>
        .spa-schedule-table { width: 100%; border-collapse: collapse; }
        .spa-schedule-table th { background: #f0f0f0; padding: 10px; text-align: left; font-weight: 600; border: 1px solid #ddd; }
        .spa-schedule-table td { padding: 10px; border: 1px solid #ddd; }
        .spa-schedule-table input { width: 100%; max-width: 120px; padding: 6px; }
        .spa-day-label { font-weight: 600; width: 150px; }
    </style>
    
    <p style="color: #666; margin-bottom: 15px;">
        <strong>💡 Nastavenia:</strong> Vyber dni v týždni a pridaj časy tréningov. 
        Prvý vybraný deň = 1x/tyždenne, prvé dva dni = 2x/tyždenne, atď.
    </p>
    
    <table class="spa-schedule-table">
        <thead>
            <tr>
                <th style="width: 150px;">Deň</th>
                <th style="width: 80px;">Zapnutý?</th>
                <th>Čas OD</th>
                <th>Čas DO</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($days_of_week as $day_key => $day_label) : ?>
                <tr>
                    <td class="spa-day-label"><?php echo $day_label; ?></td>
                    <td>
                        <input type="checkbox" 
                            name="spa_schedule_days[]" 
                            value="<?php echo esc_attr($day_key); ?>"
                            <?php echo in_array($day_key, $schedule_days) ? 'checked' : ''; ?>>
                    </td>
                    <td>
                        <input type="time" 
                            name="spa_schedule_times[<?php echo esc_attr($day_key); ?>][from]" 
                            value="<?php echo esc_attr($schedule_times[$day_key]['from'] ?? ''); ?>"
                            <?php echo in_array($day_key, $schedule_days) ? '' : 'disabled'; ?>>
                    </td>
                    <td>
                        <input type="time" 
                            name="spa_schedule_times[<?php echo esc_attr($day_key); ?>][to]" 
                            value="<?php echo esc_attr($schedule_times[$day_key]['to'] ?? ''); ?>"
                            <?php echo in_array($day_key, $schedule_days) ? '' : 'disabled'; ?>>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    
    <script>
    (function() {
        document.querySelectorAll('input[name="spa_schedule_days[]"]').forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                const day = this.value;
                const fromInput = document.querySelector(`input[name="spa_schedule_times[${day}][from]"]`);
                const toInput = document.querySelector(`input[name="spa_schedule_times[${day}][to]"]`);
                
                if (this.checked) {
                    fromInput.disabled = false;
                    toInput.disabled = false;
                } else {
                    fromInput.disabled = true;
                    toInput.disabled = true;
                }
            });
        });
    })();
    </script>
    <?php
}

// SAVE HANDLER
add_action('save_post_spa_group', 'spa_group_schedule_save', 11, 2);

function spa_group_schedule_save($post_id, $post) {
    if (!isset($_POST['spa_group_schedule_nonce']) || !wp_verify_nonce($_POST['spa_group_schedule_nonce'], 'spa_save_group_schedule')) {
        return;
    }
    
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    
    if (!current_user_can('edit_post', $post_id)) {
        return;
    }
    
    // Dni
    $schedule_days = isset($_POST['spa_schedule_days']) ? array_map('sanitize_text_field', $_POST['spa_schedule_days']) : [];
    update_post_meta($post_id, 'spa_schedule_days', $schedule_days);
    
    // Časy
    $schedule_times = [];
    if (isset($_POST['spa_schedule_times']) && is_array($_POST['spa_schedule_times'])) {
        foreach ($_POST['spa_schedule_times'] as $day => $times) {
            $schedule_times[sanitize_text_field($day)] = [
                'from' => sanitize_text_field($times['from'] ?? ''),
                'to' => sanitize_text_field($times['to'] ?? '')
            ];
        }
    }
    update_post_meta($post_id, 'spa_schedule_times', $schedule_times);
}

/**
 * ============================================================================
 * CSV IMPORT - DOČASNÝ ADMIN NÁSTROJ
 * Tento blok kódu možno v budúcnosti odstrániť
 * ============================================================================
 */

/**
 * Registrácia admin stránky pre CSV import
 */
function spa_register_import_admin_page() {
    add_submenu_page(
        'edit.php?post_type=spa_registration',  // Parent menu (Registrácie)
        'Import registrácií (CSV)',              // Page title
        'Import',                                // Menu label
        'manage_options',                        // Capability
        'spa-registrations-import',              // Slug
        'spa_render_import_admin_page'           // Callback
    );
}

add_action('admin_menu', 'spa_register_import_admin_page', 1000);

/**
 * Render admin stránky pre CSV import
 */
function spa_render_import_admin_page() {
    ?>
    <div class="wrap">
        <h1>Import registrácií (CSV/ZIP)</h1>
        
        <?php
        // Zobrazenie výsledkov importu
        if (isset($_GET['import']) && $_GET['import'] === 'success') {
            $imported = isset($_GET['imported']) ? intval($_GET['imported']) : 0;
            $errors = isset($_GET['errors']) ? intval($_GET['errors']) : 0;
            $skipped = isset($_GET['skipped']) ? intval($_GET['skipped']) : 0;
            $files = isset($_GET['files']) ? intval($_GET['files']) : 1;
            
            echo '<div class="notice notice-success is-dismissible"><p>';
            echo '<strong>Import dokončený!</strong><br>';
            echo sprintf('Spracovaných súborov: %d<br>', $files);
            echo sprintf('Úspešne importované: %d<br>', $imported);
            echo sprintf('Chyby: %d<br>', $errors);
            echo sprintf('Preskočené: %d', $skipped);
            echo '</p></div>';
        }
        
        // Zobrazenie chýb
        if (isset($_GET['error'])) {
            $error_msg = 'Neznáma chyba';
            switch ($_GET['error']) {
                case 'upload_failed':
                    $error_msg = 'Chyba pri nahrávaní súboru.';
                    break;
                case 'zip_extraction_failed':
                    $error_msg = 'Nepodarilo sa rozbaliť ZIP archív.';
                    break;
                case 'invalid_file_type':
                    $error_msg = 'Neplatný typ súboru. Povolené sú len CSV a ZIP.';
                    break;
                case 'missing_columns':
                    $missing = isset($_GET['missing']) ? sanitize_text_field($_GET['missing']) : '';
                    $error_msg = 'Chýbajúce stĺpce v CSV: ' . $missing;
                    break;
            }
            echo '<div class="notice notice-error is-dismissible"><p><strong>Chyba:</strong> ' . esc_html($error_msg) . '</p></div>';
        }
        ?>
        
        <div class="card" style="max-width: 600px;">
            <h2>Nahrať CSV alebo ZIP súbor</h2>
            
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" enctype="multipart/form-data">
                <?php wp_nonce_field('spa_csv_import', 'spa_csv_import_nonce'); ?>
                <input type="hidden" name="action" value="spa_import_csv">
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="csv_file">Súbor</label>
                        </th>
                        <td>
                            <input type="file" name="csv_file" id="csv_file" accept=".csv,.zip" required>
                            <p class="description">
                                Povolené formáty: CSV, ZIP<br>
                                ZIP môže obsahovať viacero CSV súborov v adresároch (napr. Malacky/, Košice/)
                            </p>
                        </td>
                    </tr>
                </table>
                
                <p class="submit">
                    <input type="submit" name="submit" id="submit" class="button button-primary" value="Importovať">
                </p>
            </form>
            
            <hr>
            
            <h3>Formát CSV súboru</h3>
            <p>Povinné stĺpce:</p>
            <ul>
                <li><code>meno</code> – meno dieťaťa</li>
                <li><code>priezvisko</code> – priezvisko dieťaťa</li>
                <li><code>pohlavie</code> – M/F</li>
                <li><code>datum_narodenia</code> – DD.MM.YYYY</li>
                <li><code>meno_rodica</code></li>
                <li><code>priezvisko_rodica</code></li>
                <li><code>email</code> – email rodiča</li>
                <li><code>telefon</code> – telefón rodiča</li>
            </ul>
            
            <p>Voliteľné stĺpce:</p>
            <ul>
                <li><code>skupiny</code> – názov tréningovej skupiny (ak nie je zadaný, použije sa názov CSV súboru)</li>
                <li><code>predvolena_suma</code> – cena registrácie (napr. 25.50 alebo 25,50)</li>
            </ul>
            
            <p><strong>Poznámka:</strong> Ak registrácia už existuje (to isté dieťa v tej istej skupine), nebude sa duplicitne vytvárať a cena sa nezmení.</p>
        </div>
        
        <div class="card" style="max-width: 600px; margin-top: 20px;">
            <h3>Import logy</h3>
            <p>Logy sa ukladajú do: <code><?php echo esc_html(wp_upload_dir()['basedir'] . '/spa-import-logs/'); ?></code></p>
        </div>
    </div>
    <?php
}