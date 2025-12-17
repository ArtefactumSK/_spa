<?php
/**
 * Theme Name: Blocksy Child - Samuel Piasecký ACADEMY
 * Description: Child theme pre Samuel Piasecký ACADEMY
 * Author: Artefactum
 * Version: 26.1.5-STABLE
 * 
 * POZNÁMKA: Toto je MINIMÁLNA STABILNÁ konfigurácia
 * Zakomentované moduly budú pridané v nasledujúcich iteráciách
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
define('SPA_VERSION', '26.1.5');
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
   PORADIE NAČÍTANIA - MINIMÁLNA STABILNÁ KONFIGURÁCIA
   
   ✅ HOTOVÉ A AKTÍVNE:
   - FÁZA 0: Core (constants, roles, filters)
   - FÁZA 1-2: CPT + Taxonomies
   - FÁZA 3: User fields + helpers
   - FÁZA 4: Import CSV v2
   
   ❌ ZAKOMENTOVANÉ (Problematické/budúcnosť):
   - Staré admin moduly (duplicity, závislosti)
   - Registračné notifikácie (duplicity)
   ========================== */

// FÁZA 0: CORE (Povinné)
spa_load_module('core/spa-constants.php');
spa_load_module('core/spa-roles.php');
spa_load_module('core/spa-filters-hooks.php');

// FÁZA 1-2: CPT + TAXONOMIES (Povinné)
spa_load_module('cpt/spa-cpt-groups.php');
spa_load_module('cpt/spa-cpt-registration.php');
spa_load_module('cpt/spa-cpt-place.php');
spa_load_module('cpt/spa-cpt-event.php');
spa_load_module('cpt/spa-cpt-attendance.php');
spa_load_module('helpers/spa-taxonomies.php');

// FÁZA 3: USER FIELDS + HELPERS
spa_load_module('user/spa-user-fields.php');
spa_load_module('user/spa-user-parents.php');
spa_load_module('user/spa-user-children.php');
spa_load_module('user/spa-user-clients.php');

// FÁZA 3+: REGISTRÁCIA (Fragmentované)
// Poznámka: Helpers a notifications sú zakomentované (duplicity s user modulmi)
// spa_load_module('registration/spa-registration-helpers.php');
// spa_load_module('registration/spa-registration-notifications.php');
spa_load_module('registration/spa-registration-form.php');

spa_load_module('admin/spa-registration-meta-box.php');
spa_load_module('admin/spa-registration-admin-columns.php');

// FÁZA 4: IMPORT CSV V2
spa_load_module('import/spa-import-csv-v2.php');

// FÁZA 4+: PRICING
 spa_load_module('pricing/spa-pricing-migration.php');
 spa_load_module('pricing/spa-pricing-helpers.php');


/* ==========================
   ADMIN MODULY - NOVO FRAGMENTOVANÉ
   ========================== */
spa_load_module('admin/spa-registration-meta-box.php');
spa_load_module('admin/spa-registration-admin-columns.php');
spa_load_module('spa-user-profile-fields.php');

// ZAKOMENTOVANÉ: Staré duplicitné súbory (duplicity s fragmentovanými modulmi)
// spa_load_module('spa-meta-boxes.php');
// spa_load_module('spa-admin-columns.php');


/* ==========================
   ZAKOMENTOVANÉ MODULY (Budúce alebo problematické)
   
   Staré admin moduly majú závislosti a duplicity.
   Budú zrefaktorované v nasledujúcich iteráciách.
   ========================== */

// spa_load_module('spa-admin-columns.php');
// spa_load_module('spa-meta-boxes.php');
// spa_load_module('spa-shortcodes.php');
// spa_load_module('spa-widgets.php');
// spa_load_module('spa-calendar.php');
// spa_load_module('spa-trainer.php');
// spa_load_module('spa-login.php');
// spa_load_module('spa-login-popup.php');

/* ==========================
   FALLBACK: Staré registrácia (AK fragmentovaná neexistuje)
   ========================== */
if (!file_exists(SPA_INCLUDES . 'registration/spa-registration-form.php')) {
    spa_load_module('spa-registration.php');
}
