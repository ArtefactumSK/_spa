<?php
/**
 * Theme Name: Blocksy Child - Samuel Piasecký ACADEMY
 * Description: Child theme pre Samuel Piasecký ACADEMY
 * Author: Artefactum
 * Version: 26.1.3-STABLE
 */

if (!defined('ABSPATH')) {
    exit;
}

/* ==========================
   ARTEFACTUM SUPPORT
   ========================== */
if (defined('ARTEFACTUM_COMMON')) {
    @include_once(ARTEFACTUM_COMMON . 'Artefactum-supports.php');
    @include_once(ARTEFACTUM_COMMON . 'a-wplogin.php');
}

/* ==========================
   GF NAG
   ========================== */
add_action('admin_init', function() {
    update_option('rg_gforms_message', '');
});

/* ==========================
   KONŠTANTY
   ========================== */
define('SPA_VERSION', '26.1.3');
define('SPA_PATH', get_stylesheet_directory());
define('SPA_URL', get_stylesheet_directory_uri());
define('SPA_INCLUDES', SPA_PATH . '/includes/');

/* ==========================
   ENQUEUE STYLES & SCRIPTS
   ========================== */
add_action('wp_enqueue_scripts', 'spa_enqueue_styles', 5);
add_action('admin_enqueue_scripts', 'spa_enqueue_admin_styles', 5);

function spa_enqueue_styles() {
    wp_enqueue_style('parent-style', get_template_directory_uri() . '/style.css');
    wp_enqueue_style('child-style', get_stylesheet_directory_uri() . '/style.css', ['parent-style'], SPA_VERSION);
    
    if (file_exists(SPA_PATH . '/assets/css/variables.css')) {
        wp_enqueue_style('spa-variables', SPA_URL . '/assets/css/variables.css', [], SPA_VERSION);
    }
    if (file_exists(SPA_PATH . '/assets/css/admin/admin-notices.css')) {
        wp_enqueue_style('spa-notices', SPA_URL . '/assets/css/admin/admin-notices.css', ['spa-variables'], SPA_VERSION);
    }
    
    wp_enqueue_script('jquery');
}

function spa_enqueue_admin_styles() {
    if (file_exists(SPA_PATH . '/assets/css/variables.css')) {
        wp_enqueue_style('spa-variables', SPA_URL . '/assets/css/variables.css', [], SPA_VERSION);
    }
    if (file_exists(SPA_PATH . '/assets/css/admin/admin-core.css')) {
        wp_enqueue_style('spa-admin-core', SPA_URL . '/assets/css/admin/admin-core.css', ['spa-variables'], SPA_VERSION);
    }
    if (file_exists(SPA_PATH . '/assets/css/admin/admin-notices.css')) {
        wp_enqueue_style('spa-admin-notices', SPA_URL . '/assets/css/admin/admin-notices.css', ['spa-variables'], SPA_VERSION);
    }
}

/* ==========================
   HELPER: Icons
   ========================== */
function spa_icon($name, $class = 'spa-icon') {
    $url = content_url('/uploads/spa-icons/system/' . $name . '.svg');
    return '<img src="' . esc_url($url) . '" class="' . esc_attr($class) . '" alt="">';
}

function get_spa_svg_icon($spasvgsize = 39) {
    $sizesvg = intval($spasvgsize);
    return '<svg class="spa-icon" width="' . $sizesvg . '" height="' . $sizesvg . '" viewBox="0 0 ' . $sizesvg . ' 100" preserveAspectRatio="xMidYMid meet" aria-hidden="true" style="vertical-align: middle; display: inline-block;">
    <path d="M36.29,0C-3.91,29.7.49,65.3,32.79,69.8-1.91,69-20.51,38.3,36.29,0Z" fill="var(--theme-palette-color-1, #FF1439)"></path>
    <path d="M16.99,60.2c2.5,1.8,5.1,1.8,5.6-.2s-1.1-5.1-3.7-7-5.1-1.8-5.6.2,1.1,5.1,3.7,7Z" fill="var(--theme-palette-color-1, #FF1439)"></path>
    <path d="M16.49,92.4c40.2-29.7,35.8-65.3,3.5-69.8,34.7.8,53.3,31.5-3.5,69.8Z" fill="var(--theme-palette-color-3, #ff1439)"></path>
    <path d="M48.39,30.5c2.6,1.9,5.1,1.8,5.6-.2s-1.1-5.1-3.7-7-5.1-1.8-5.6.2,1.1,5.1,3.7,7Z" fill="var(--theme-palette-color-3, #ff1439)"></path>
    </svg>';
}

/* ==========================
   MODUL LOADER
   ========================== */
function spa_load_module($file) {
    $path = SPA_INCLUDES . $file;
    if (!file_exists($path)) {
        return false;
    }
    require_once $path;
    return true;
}

/* ==========================
   NAČÍTANIE MODULOV - CORE (POVINNÉ)
   ========================== */
spa_load_module('core/spa-constants.php');
spa_load_module('core/spa-roles.php');
spa_load_module('core/spa-filters-hooks.php');

/* ==========================
   NAČÍTANIE MODULOV - CPT (POVINNÉ)
   ========================== */
spa_load_module('cpt/spa-cpt-groups.php');
spa_load_module('cpt/spa-cpt-registration.php');
spa_load_module('cpt/spa-cpt-place.php');
spa_load_module('cpt/spa-cpt-event.php');
spa_load_module('cpt/spa-cpt-attendance.php');
spa_load_module('helpers/spa-taxonomies.php');

/* ==========================
   NAČÍTANIE MODULOV - FÁZA 3: USER
   ========================== */
spa_load_module('user/spa-user-fields.php');
spa_load_module('user/spa-user-parents.php');
spa_load_module('user/spa-user-children.php');
spa_load_module('user/spa-user-clients.php');

/* ==========================
   ADMIN MODULES
   ========================== */
spa_load_module('spa-admin-columns.php');
spa_load_module('spa-meta-boxes.php');
spa_load_module('spa-shortcodes.php');
spa_load_module('spa-widgets.php');
spa_load_module('spa-calendar.php');
spa_load_module('spa-trainer.php');
spa_load_module('spa-login.php');
spa_load_module('spa-login-popup.php');

/* ==========================
   IMPORT - Nová verzia v2
   ========================== */
// Vymaž starý import.php - načítavaj len nový
spa_load_module('import/spa-import-csv-v2.php');

// FALLBACK: Ak starý import ainda existuje (kompatibilita)
// spa_load_module('spa-import.php'); // ← ZAKOMENTOVANÉ

/* ==========================
   REGISTRÁCIA - Nové fragmentované súbory
   ALE: Bez FÁZY 3 (user/) pretože ešte neexistujú
   ========================== */
spa_load_module('registration/spa-registration-helpers.php');
spa_load_module('registration/spa-registration-notifications.php');
spa_load_module('registration/spa-registration-form.php');

/* ==========================
   FALLBACK: Ak registrácia nie je fragmentovaná
   Poznámka: spa-registration.php.bak sa nenačítava (je .bak)
   ========================== */
if (!file_exists(SPA_INCLUDES . 'registration/spa-registration-form.php')) {
    spa_load_module('spa-registration.php');
}