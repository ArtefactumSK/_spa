<?php
/**
 * Theme Name: Blocksy Child - Samuel Piasecký ACADEMY
 * Description: Child theme pre Samuel Piasecký ACADEMY s kompletným training management systémom
 * Author: Artefactum
 * Version: 26.1.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/*Artefactum support*/
include_once( ARTEFACTUM_COMMON . 'Artefactum-supports.php' );
include_once( ARTEFACTUM_COMMON . 'a-wplogin.php' );

// Remove gravity forms nag
function remove_gravity_forms_nag() {
    update_option( 'rg_gforms_message', '' );
    remove_action( 'after_plugin_row_gravityforms/gravityforms.php', array( 'GFForms', 'plugin_row' ) );
}
add_action( 'admin_init', 'remove_gravity_forms_nag' );


/* ==========================
   NAČÍTANIE ŠTÝLOV A CSS
   ========================== */

add_action('wp_enqueue_scripts', 'spa_enqueue_styles', 5);
add_action('admin_enqueue_scripts', 'spa_enqueue_admin_styles', 5);

function spa_enqueue_styles() {
    // Parent theme
    wp_enqueue_style('parent-style', get_template_directory_uri() . '/style.css');
    
    // Child theme - OPRAVA: Použiť presný version
    wp_enqueue_style('child-style', get_stylesheet_directory_uri() . '/style.css', ['parent-style'], '26.1.0');
    
    // SPA CSS - Shared - OPRAVA: Bez SPA_VERSION!
    wp_enqueue_style('spa-variables', get_stylesheet_directory_uri() . '/assets/css/variables.css', [], '26.1.0');
    wp_enqueue_style('spa-notices', get_stylesheet_directory_uri() . '/assets/css/admin/admin-notices.css', ['spa-variables'], '26.1.0');
    
    // jQuery (potrebné pre AJAX)
    wp_enqueue_script('jquery');
}

function spa_enqueue_admin_styles() {
    // Admin CSS - Shared
    wp_enqueue_style('spa-variables', get_stylesheet_directory_uri() . '/assets/css/variables.css', [], '26.1.0');
    
    // Admin CSS - Core
    wp_enqueue_style('spa-admin-core', get_stylesheet_directory_uri() . '/assets/css/admin/admin-core.css', ['spa-variables'], '26.1.0');
    wp_enqueue_style('spa-admin-notices', get_stylesheet_directory_uri() . '/assets/css/admin/admin-notices.css', ['spa-variables'], '26.1.0');
}

/* ==========================
   ARTEFACTUM SUPPORT
   ========================== */

if (defined('ARTEFACTUM_COMMON')) {
    include_once(ARTEFACTUM_COMMON . 'Artefactum-supports.php');
    include_once(ARTEFACTUM_COMMON . 'a-wplogin.php');
}

/**
 * URL systémovej ikony
 */
function spa_icon($name, $class = 'spa-icon') {
    $url = content_url('/uploads/spa-icons/system/' . $name . '.svg');
    return '<img src="' . esc_url($url) . '" class="' . esc_attr($class) . '" alt="">';
}

/*IKONA SVG - napr. echo get_spa_svg_icon(39);*/ 
function get_spa_svg_icon($spasvgsize = 39) {
    $sizesvg = intval($spasvgsize);

    $spa_svg = <<<SVG
<svg class="spa-icon" width="{$sizesvg}" height="{$sizesvg}" viewBox="0 0 {$sizesvg} 100" preserveAspectRatio="xMidYMid meet" aria-hidden="true" style="vertical-align: middle; display: inline-block;">
    <path d="M36.29,0C-3.91,29.7.49,65.3,32.79,69.8-1.91,69-20.51,38.3,36.29,0Z" fill="var(--theme-palette-color-1, #FF1439)"></path>
    <path d="M16.99,60.2c2.5,1.8,5.1,1.8,5.6-.2s-1.1-5.1-3.7-7-5.1-1.8-5.6.2,1.1,5.1,3.7,7Z" fill="var(--theme-palette-color-1, #FF1439)"></path>
    <path d="M16.49,92.4c40.2-29.7,35.8-65.3,3.5-69.8,34.7.8,53.3,31.5-3.5,69.8Z" fill="var(--theme-palette-color-3, #ff1439)"></path>
    <path d="M48.39,30.5c2.6,1.9,5.1,1.8,5.6-.2s-1.1-5.1-3.7-7-5.1-1.8-5.6.2,1.1,5.1,3.7,7Z" fill="var(--theme-palette-color-3, #ff1439)"></path>
</svg>
SVG;

    return $spa_svg;
}

/* ==========================
   GRAVITY FORMS - Remove nag
   ========================== */

add_action('admin_init', function() {
    update_option('rg_gforms_message', '');
    remove_action('after_plugin_row_gravityforms/gravityforms.php', ['GFForms', 'plugin_row']);
});

/* ==========================
   NAČÍTANIE MODULOV
   ========================== */

$spa_modules = [
    // === CORE - ZÁKLADNÉ FUNKCIE ===
    'core/spa-constants.php',         // Konštanty
    'core/spa-roles.php',             // Role a capabilities
    'core/spa-filters-hooks.php',     // Globálne filtre, bezpečnosť, hooks
    
    // === CPT - CUSTOM POST TYPES ===
    'cpt/spa-cpt-groups.php',         // Programy
    'cpt/spa-cpt-registration.php',   // Registrácie
    'cpt/spa-cpt-place.php',          // Miesta
    'cpt/spa-cpt-event.php',          // Udalosti
    'cpt/spa-cpt-attendance.php',     // Dochádzka
    
    // === TAXONOMIES ===
    'helpers/spa-taxonomies.php',     // Taxonómie
    
    // === USER MANAGEMENT ===
    'user/spa-user-fields.php',       // User meta polia
    'user/spa-user-parents.php',      // Funkcie pre rodičov
    'user/spa-user-children.php',     // Funkcie pre deti
    'user/spa-user-clients.php',      // Funkcie pre klientov
    
    // === REGISTRATION ===
    'registration/spa-registration-helpers.php',      // Helper funkcie
    'registration/spa-registration-notifications.php', // Notifikácie
    'registration/spa-registration-form.php',         // GF hooky
    
    // === IMPORT ===
    'import/spa-import-helpers.php',     // Helper funkcie
    'import/spa-import-children.php',    // Import detí
    'import/spa-import-adults.php',      // Import dospelých
    'import/spa-import-processor.php',   // Spracovanie
    'import/spa-import-ui.php',          // Admin UI
    
    // === LOGIN ===
    'login/spa-login.php',           // Email+heslo login
    'login/spa-login-popup.php',     // Login popup
    
    // === ADMIN ===
    'admin/spa-admin-columns.php',   // Admin columns
    'admin/spa-meta-boxes.php',      // Meta boxy
    
    // === FRONTEND ===
    'frontend/spa-shortcodes.php',   // Shortcodes
    'frontend/spa-widgets.php',      // Widgety
    'frontend/spa-calendar.php',     // Kalendár
    'frontend/spa-trainer.php',      // Tréner
];

foreach ($spa_modules as $module) {
    $file = SPA_INCLUDES . $module;
    if (file_exists($file)) {
        require_once $file;
    } else {
        // Debug: v budúcnosti skontroluj chýbajúce súbory
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('[SPA] Missing module: ' . $file);
        }
    }
}

/* ==========================
   DEBUG MODE (vývojové)
   ========================== */

if (defined('WP_DEBUG') && WP_DEBUG && current_user_can('administrator')) {
    
    // Zobraz načítané moduly
    add_action('admin_notices', function() {
        global $spa_modules;
        
        echo '<div class="notice notice-info" style="border-left-color:#f60;"><p><strong>Programové moduly SPA:</strong><span style="color:#f60;"> ';
        echo count($spa_modules) . '</span> načítaných';
        echo '</p></div>';
    });
}

/* ==========================
   ADMIN DASHBOARD WIDGET
   ========================== */

add_action('wp_dashboard_setup', function() {
    wp_add_dashboard_widget(
        'spa_system_status',
        get_spa_svg_icon(39).' Samuel Piasecký ACADEMY - Stav systému',
        function() {
            ?>
            <div style="padding: 12px;">
                <p><strong>Verzia:</strong> <?php echo SPA_VERSION; ?><br>
                <strong>Načítané moduly SPA:</strong> 
                    <?php 
                    $loaded = array_filter(glob(SPA_INCLUDES . '*.php'));
                    echo count($loaded); 
                    ?>
                </p>
                
                <hr>
                
                <h4>Rýchle linky:</h4>
                <ul>
                    <li><a href="<?php echo admin_url('edit.php?post_type=spa_group'); ?>">🤸 Programy SPA</a></li>
                    <li><a href="<?php echo admin_url('edit.php?post_type=spa_registration'); ?>">📋 Registrácie SPA</a></li>
                    <li><a href="<?php echo admin_url('edit.php?post_type=spa_hall_block'); ?>">📅 Udalosti SPA</a></li>
                    <li><a href="<?php echo admin_url('edit.php?post_type=spa_attendance'); ?>">✅ Dochádzka</a></li>
                    <li><a href="<?php echo admin_url('edit.php?post_type=spa_payment'); ?>">💳 Prehľad platieb</a></li>                    
                    <!-- <li><a href="<?php echo admin_url('widgets.php'); ?>">📢 Bannery (Widgety)</a></li> -->
                    <!-- <li><a href="<?php echo admin_url('admin.php?page=gf_edit_forms'); ?>">📝 Formuláre</a></li> -->
                </ul>
                
                <hr>
                
                <p style="background: rgb(196 181 174 / 39%); padding: 8px; border-radius: 4px; font-size: 12px;">
                    <strong>💡 Potrebuješ pomoc?</strong> → <a href="mailto:support@artefactum.sk">support@artefactum.sk</a>
                </p>
            </div>
            <?php
        }
    );
});

// BLOKOVANIE EMAILOV NA TESTOVACEJ DOMÉNE
add_filter('pre_wp_mail', 'spa_block_test_emails', 10, 2);
function spa_block_test_emails($null, $atts) {
    $current_host = $_SERVER['HTTP_HOST'] ?? '';
    
    if (strpos($current_host, 'spa.artepaint.eu') !== false) {
        error_log('EMAIL BLOCKED on test domain: To=' . ($atts['to'] ?? 'unknown'));
        return true; // Vráti true = email sa neodošle, ale nespôsobí chybu
    }
    
    return $null; // Normálne pokračovanie
}