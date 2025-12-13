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
    
    // Child theme
    wp_enqueue_style('child-style', get_stylesheet_directory_uri() . '/style.css', ['parent-style'], SPA_VERSION);
    
    // SPA Shared CSS
    wp_enqueue_style('spa-variables', SPA_URL . '/assets/css/variables.css', [], SPA_VERSION);
    wp_enqueue_style('spa-notices', SPA_URL . '/assets/css/admin/admin-notices.css', ['spa-variables'], SPA_VERSION);
    
    // jQuery (potrebné pre AJAX)
    wp_enqueue_script('jquery');
}

function spa_enqueue_admin_styles() {
    // Admin CSS
    wp_enqueue_style('spa-variables', SPA_URL . '/assets/css/variables.css', [], SPA_VERSION);
    wp_enqueue_style('spa-admin-core', SPA_URL . '/assets/css/admin/admin-core.css', ['spa-variables'], SPA_VERSION);
    wp_enqueue_style('spa-admin-notices', SPA_URL . '/assets/css/admin/admin-notices.css', ['spa-variables'], SPA_VERSION);
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
   ZÁKLADNÉ KONŠTANTY
   ========================== */

if (!defined('SPA_VERSION')) {
    define('SPA_VERSION', '26.1.0');
}

if (!defined('SPA_PATH')) {
    define('SPA_PATH', get_stylesheet_directory());
}

if (!defined('SPA_URL')) {
    define('SPA_URL', get_stylesheet_directory_uri());
}

if (!defined('SPA_INCLUDES')) {
    define('SPA_INCLUDES', SPA_PATH . '/includes/');
}

/* ==========================
   NAČÍTANIE MODULOV - SPRÁVNE PORADIE
   ========================== */

/**
 * PORADIE NAČÍTAVANIA JE KRITICKÉ!
 * 
 * 1. CORE (Konštanty, Role, Filtre, Bezpečnosť) - POVINNÉ
 * 2. CPT + TAXONOMIES (Custom Post Types) - POVINNÉ
 * 3. USER (User roles a meta fields) - Voliteľné (ak existuje)
 * 4. STARÝ MONOLITNÝ KÓD (Admin, Login, Import, Frontend) - Voliteľné
 * 
 * PRAVIDLO: Ak sú FRAGMENTOVANÉ verzie v podadresároch → načítaj tie
 *           Ak nie sú → načítaj STARÉ verzie z /includes/
 */

// FÁZA 1: CORE - Povinné pri štarte
$spa_core_modules = [
    'core/spa-constants.php',
    'core/spa-roles.php',
    'core/spa-filters-hooks.php',
];

foreach ($spa_core_modules as $module) {
    $file = SPA_INCLUDES . $module;
    if (file_exists($file)) {
        require_once $file;
    } else {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('[SPA CORE] Missing: ' . $file);
        }
    }
}

// FÁZA 2: CPT + TAXONOMIES - Povinné
$spa_cpt_modules = [
    'cpt/spa-cpt-groups.php',
    'cpt/spa-cpt-registration.php',
    'cpt/spa-cpt-place.php',
    'cpt/spa-cpt-event.php',
    'cpt/spa-cpt-attendance.php',
    'helpers/spa-taxonomies.php',
];

foreach ($spa_cpt_modules as $module) {
    $file = SPA_INCLUDES . $module;
    if (file_exists($file)) {
        require_once $file;
    }
}

// FÁZA 3: USER (Ak existuje fragmentovaný) - Voliteľné
$spa_user_modules = [
    'user/spa-user-fields.php',
    'user/spa-user-parents.php',
    'user/spa-user-children.php',
    'user/spa-user-clients.php',
];

$user_modules_exist = false;
foreach ($spa_user_modules as $module) {
    $file = SPA_INCLUDES . $module;
    if (file_exists($file)) {
        $user_modules_exist = true;
        require_once $file;
    }
}
// FÁZA 4: REGISTRATION - FALLBACK NA STARÉ RIEŠENIE
$old_registration = SPA_INCLUDES . 'spa-registration.php.bak';
if (file_exists($old_registration)) {
    require_once $old_registration;
}

// FÁZA 4: REGISTRATION FALLBACK - Ak fragmentácia nefunguje
/* $registration_file = SPA_INCLUDES . 'registration/spa-registration-helpers.php';

if (!file_exists($registration_file)) {
    // Fallback na staré monolitné riešenie
    $old_reg = SPA_INCLUDES . 'spa-registration.php.bak';
    if (file_exists($old_reg)) {
        error_log('[SPA] Using legacy spa-registration.php (fragmentácia nie je dostupná)');
        require_once $old_reg;
    }
} else {
    // Skúš nové fragmentované moduly
    $spa_registration_modules = [
        'registration/spa-registration-helpers.php',
        'registration/spa-registration-notifications.php',
        'registration/spa-registration-form.php',
    ];
    
    foreach ($spa_registration_modules as $module) {
        $file = SPA_INCLUDES . $module;
        if (file_exists($file)) {
            require_once $file;
        }
    }
} */

// ========== KONIEC FALLBACK ==========

// FÁZA 5: STARÉ MONOLITNÉ MODULY
$spa_legacy_modules = [
    'spa-admin-columns.php',
    'spa-meta-boxes.php',
    'spa-calendar.php',
    'spa-shortcodes.php',
    'spa-widgets.php',
    'spa-trainer.php',
    'spa-import.php',
    'spa-login.php',
    'spa-login-popup.php',
];

foreach ($spa_legacy_modules as $module) {
    $file = SPA_INCLUDES . $module;
    if (file_exists($file)) {
        require_once $file;
    }
}

/* 
// FÁZA 4: REGISTRATION (Ak existuje fragmentovaný) - Voliteľné
$spa_registration_modules = [
    'registration/spa-registration-helpers.php',
    'registration/spa-registration-notifications.php',
    'registration/spa-registration-form.php',
];

$registration_modules_exist = false;
foreach ($spa_registration_modules as $module) {
    $file = SPA_INCLUDES . $module;
    if (file_exists($file)) {
        $registration_modules_exist = true;
        require_once $file;
    }
}

// FÁZA 5: STARÉ MONOLITNÉ MODULY (Admin, Login, Frontend, atď.)
// Načítavaj LEN ak NEFUNGUJÚ nové fragmentované verzie
$spa_legacy_modules = [
    'spa-admin-columns.php',      // Admin columns
    'spa-meta-boxes.php',         // Meta boxy
    'spa-calendar.php',           // Calendar
    'spa-shortcodes.php',         // Shortcodes
    'spa-widgets.php',            // Widgets
    'spa-trainer.php',            // Trainer section
    'spa-import.php',             // Import system
    'spa-login.php',              // Login system
    'spa-login-popup.php',        // Login popup
];

foreach ($spa_legacy_modules as $module) {
    $file = SPA_INCLUDES . $module;
    if (file_exists($file)) {
        require_once $file;
    }
}

// FÁZA 6: TAXONOMIES FALLBACK (Ak nie je v podadresári)
if (!file_exists(SPA_INCLUDES . 'helpers/spa-taxonomies.php')) {
    $tax_file = SPA_INCLUDES . 'spa-taxonomies.php';
    if (file_exists($tax_file)) {
        require_once $tax_file;
    }
} */

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
        return true;
    }
    
    return $null;
}