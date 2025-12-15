<?php
/**
 * SPA CPT: Registrations - Registrácie do programov
 * 
 * @package Samuel Piasecký ACADEMY
 * @subpackage CPT
 * @version 1.0.0
 * 
 * PARENT MODULES: spa-core/spa-constants.php
 * CHILD MODULES: registration/*, import/*
 * 
 * CPT REGISTERED:
 * - spa_registration (Registrácie)
 * 
 * FUNCTIONS DEFINED:
 * - spa_register_cpt_registrations()
 * - spa_fix_registration_submenu()
 * - spa_handle_registration_redirect()
 * - spa_registration_menu_target_blank()
 * 
 * DATABASE TABLES:
 * - wp_posts (post_type = spa_registration)
 * - wp_postmeta (meta pre registrácie)
 * 
 * HOOKS USED:
 * - init (CPT registration)
 * - admin_menu (menu modification)
 * - admin_init (redirects)
 * - admin_footer (JavaScript)
 * 
 * NOTES:
 * Zmena "Pridať registráciu" na externý link na /registracia/
 */

if (!defined('ABSPATH')) {
    exit;
}

/* ==================================================
   CPT: spa_registration (Registrácie)
   ================================================== */

add_action('init', 'spa_register_cpt_registrations');

function spa_register_cpt_registrations() {
    $labels = array(
        'name'               => '📋 Registrácie',
        'singular_name'      => 'Registrácia',
        'menu_name'          => 'SPA Registrácie',
        'add_new'            => 'Pridať registráciu',
        'add_new_item'       => 'Pridať novú registráciu',
        'edit_item'          => 'Upraviť registráciu',
        'new_item'           => 'Nová registrácia',
        'view_item'          => 'Zobraziť registráciu',
        'search_items'       => 'Hľadať registrácie',
        'not_found'          => 'Žiadne registrácie nenájdené',
        'not_found_in_trash' => 'Žiadne registrácie v koši',
        'all_items'          => 'Všetky registrácie'
    );

    register_post_type('spa_registration', array(
        'labels'            => $labels,
        'public'            => false,
        'show_ui'           => true,
        'menu_icon'         => 'dashicons-clipboard',
        'menu_position'     => 21,
        'hierarchical'      => false,
        'supports'          => array('title'),
        'capability_type'   => 'post',
        'show_in_rest'      => false,
    ));
}

/* ==================================================
   MENU: Zmena "Pridať registráciu" na externý link
   ================================================== */

add_action('admin_menu', 'spa_fix_registration_submenu', 999);
/* ==================================================
   ADMIN INIT: 
   ================================================== */
function spa_fix_registration_submenu() {
    global $submenu;
    
    if (isset($submenu['edit.php?post_type=spa_registration'])) {
        foreach ($submenu['edit.php?post_type=spa_registration'] as $key => $item) {
            // Odstrániť len "post-new.php", zachovať custom submenu (napr. import)
            if (isset($item[2]) && strpos($item[2], 'post-new.php') !== false) {
                unset($submenu['edit.php?post_type=spa_registration'][$key]);
            }
        }
    }
    
    add_submenu_page(
        'edit.php?post_type=spa_registration',
        'Pridať registráciu',
        'Pridať registráciu',
        'edit_posts',
        'spa-add-registration-redirect',
        '__return_null'
    );
}

/* ==================================================
   ADMIN INIT: Redirect na /registracia/
   ================================================== */

add_action('admin_init', 'spa_handle_registration_redirect');

function spa_handle_registration_redirect() {
    if (isset($_GET['page']) && $_GET['page'] === 'spa-add-registration-redirect') {
        wp_redirect(home_url('/registracia/'));
        exit;
    }
}

/* ==================================================
   ADMIN FOOTER: Make link target _blank
   ================================================== */

add_action('admin_footer', 'spa_registration_menu_target_blank');

function spa_registration_menu_target_blank() {
    $url = esc_url(home_url('/registracia/'));
    ?>
    <script type="text/javascript">
    (function() {
        var links = document.querySelectorAll('a[href*="spa-add-registration-redirect"]');
        links.forEach(function(link) {
            link.setAttribute('href', '<?php echo $url; ?>');
            link.setAttribute('target', '_blank');
        });
        var addBtn = document.querySelector('.page-title-action[href*="post-new.php?post_type=spa_registration"]');
        if (addBtn) {
            addBtn.setAttribute('href', '<?php echo $url; ?>');
            addBtn.setAttribute('target', '_blank');
        }
    })();
    </script>
    <?php
}

/**
 * ============================================================================
 * CSV IMPORT SUBMENU - DOČASNÝ ADMIN NÁSTROJ
 * ============================================================================
 */

function spa_register_import_admin_page() {
    add_submenu_page(
        'edit.php?post_type=spa_registration',
        'Import registrácií (CSV)',
        'Import',
        'manage_options',
        'spa-registrations-import',
        'spa_render_import_admin_page'
    );
}
add_action('admin_menu', 'spa_register_import_admin_page', 50);

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
            
            // DEBUG: Zobraziť všetky URL parametre
            echo '<div style="background:#f0f0f0;padding:10px;margin:10px 0;"><strong>DEBUG URL params:</strong><pre>';
            print_r($_GET);
            echo '</pre></div>';
            
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
                case 'missing_schedule_params':
                    $error_msg = 'Musíte vyplniť: Program, Mesto, Deň v týždni a Čas začiatku.';
                    break;
                case 'group_not_found':
                    $params = isset($_GET['params']) ? sanitize_text_field($_GET['params']) : '';
                    $error_msg = 'Skupina sa nenašla pre zadané parametre: ' . $params;
                    break;
                default:
                    $error_msg = 'Neznáma chyba (kod: ' . sanitize_text_field($_GET['error']) . ')';
            }
            echo '<div class="notice notice-error is-dismissible"><p><strong>Chyba:</strong> ' . esc_html($error_msg) . '</p></div>';
        }
        ?>
        
        <div class="card" style="max-width: 800px;">
            <h2>1. Vyberte tréningový termín</h2>
            <p class="description" style="margin-bottom: 20px;">
                <strong>⚠️ Dôležité:</strong> Všetky polia sú povinné. Import priradí registrácie presne k skupine s týmto termínom.
            </p>
            
            <form id="spa-import-form" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" enctype="multipart/form-data">
                <?php wp_nonce_field('spa_csv_import', 'spa_csv_import_nonce'); ?>
                <input type="hidden" name="action" value="spa_import_csv">
                <input type="hidden" name="import_group_id" id="import_group_id" value="">
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="import_program">Program <span style="color:red;">*</span></label>
                        </th>
                        <td>
                            <select name="import_program" id="import_program" required style="width: 100%; max-width: 400px;">
                            <option value="">-- Vyberte program --</option>
                            <?php
                            // Načítať VŠETKY programy - BEZ akýchkoľvek filtrov
                            $programs_query = new WP_Query([
                                'post_type' => 'spa_group',
                                'post_status' => 'publish',
                                'posts_per_page' => -1,
                                'orderby' => 'title',
                                'order' => 'ASC',
                                'no_found_rows' => true,
                                'update_post_meta_cache' => false,
                                'update_post_term_cache' => false
                            ]);
                            
                            if ($programs_query->have_posts()) {
                                while ($programs_query->have_posts()) {
                                    $programs_query->the_post();
                                    printf(
                                        '<option value="%d">%s</option>',
                                        get_the_ID(),
                                        esc_html(get_the_title())
                                    );
                                }
                                wp_reset_postdata();
                            } else {
                                echo '<option value="" disabled>⚠️ Žiadne programy nenájdené</option>';
                                
                                // DEBUG - Skontrolovať či existujú programy
                                $debug_count = wp_count_posts('spa_group');
                                if (isset($debug_count->publish) && $debug_count->publish > 0) {
                                    printf(
                                        '<option value="" disabled>DEBUG: Existuje %d publikovaných programov, ale nezobrazujú sa</option>',
                                        $debug_count->publish
                                    );
                                }
                            }
                            ?>
                        </select>
                            <p class="description">Vyberte tréningový program (nezávisle od miesta)</p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="import_city">SPA miesto (adresa) <span style="color:red;">*</span></label>
                        </th>
                        <td>
                            <select name="import_city" id="import_city" required style="width: 100%; max-width: 400px;">
                                <option value="">-- Vyberte SPA miesto --</option>
                                <?php
                                $places = get_posts([
                                    'post_type' => 'spa_place',
                                    'post_status' => 'publish',
                                    'posts_per_page' => -1,
                                    'orderby' => 'title',
                                    'order' => 'ASC'
                                ]);
                                foreach ($places as $place) {
                                    printf(
                                        '<option value="%d">%s</option>',
                                        $place->ID,
                                        esc_html($place->post_title)
                                    );
                                }
                                ?>
                            </select>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="import_day">Deň v týždni <span style="color:red;">*</span></label>
                        </th>
                        <td>
                            <select name="import_day" id="import_day" required style="width: 100%; max-width: 400px;">
                                <option value="">-- Vyberte deň --</option>
                                <option value="mo">Pondelok</option>
                                <option value="tu">Utorok</option>
                                <option value="we">Streda</option>
                                <option value="th">Štvrtok</option>
                                <option value="fr">Piatok</option>
                                <option value="sa">Sobota</option>
                                <option value="su">Nedeľa</option>
                            </select>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="import_time">Čas začiatku <span style="color:red;">*</span></label>
                        </th>
                        <td>
                            <input type="time" 
                                   name="import_time" 
                                   id="import_time" 
                                   required 
                                   min="06:00" 
                                   max="22:00"
                                   style="width: 150px;">
                            <p class="description">Formát: HH:MM (napr. 16:00)</p>
                        </td>
                    </tr>
                </table>
                
                <hr style="margin: 30px 0;">
                
                <h2>2. Nahrajte CSV/ZIP súbor</h2>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="csv_file">Súbor <span style="color:red;">*</span></label>
                        </th>
                        <td>
                            <input type="file" name="csv_file" id="csv_file" accept=".csv,.zip" required>
                            <p class="description">
                                Povolené formáty: CSV, ZIP<br>
                                ZIP môže obsahovať viacero CSV súborov v adresároch
                            </p>
                        </td>
                    </tr>
                </table>
                
                <p class="submit">
                    <input type="submit" name="submit" id="submit" class="button button-primary" value="Importovať registrácie">
                </p>
            </form>
            
            <script>
            jQuery(document).ready(function($) {
                $('#spa-import-form').on('submit', function(e) {
                    var programId = $('#import_program').val();
                    
                    if (!programId) {
                        alert('Musíte vybrať program!');
                        e.preventDefault();
                        return false;
                    }
                    
                    $('#import_group_id').val(programId);
                    console.log('Odosielam group_id: ' + programId);
                });
            });
            </script>
            
            <hr>
            
            <h3>Formát CSV súboru</h3>
            <p>Povinné stĺpce:</p>
            <ul>
                <li><code>meno</code>, <code>priezvisko</code>, <code>pohlavie</code> (M/F)</li>
                <li><code>datum_narodenia</code> (DD.MM.YYYY)</li>
                <li><code>meno_rodica</code>, <code>priezvisko_rodica</code></li>
                <li><code>email</code>, <code>telefon</code></li>
            </ul>
            
            <p>Voliteľné stĺpce:</p>
            <ul>
                <li><code>predvolena_suma</code> – cena (25.50 alebo 25,50)</li>
            </ul>
            
            <p><strong>⚠️ Dôležité:</strong> Deň a čas sa NEBERÚ z CSV, ale z vybraných polí vyššie.</p>
            
            <p><strong>Duplicity:</strong> Ak registrácia už existuje (dieťa v danej skupine), nebude sa vytvárať znova a cena ostane pôvodná.</p>
            
            <p><strong>Logy:</strong> <code><?php echo esc_html(wp_upload_dir()['basedir'] . '/spa-import-logs/'); ?></code></p>
        </div>
    </div>
    <?php
}