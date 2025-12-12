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

/*=== Capabilities (len pre admin) ===*//*=== Capabilities (len pre admin) ===*/
if (is_admin()) {
    
    // Capabilities sa nastavia LEN RAZ, nie pri každom načítaní
    function allow_editors_capabilities() {
        // Kontrola či už boli capabilities nastavené
        $capabilities_set = get_option('custom_capabilities_initialized', false);
        
        if ($capabilities_set) {
            return; // Už boli nastavené, nerobíme nič
        }
        
        $role = get_role('editor');
        if ($role) {
            // Blocksy capabilities
            $role->add_cap('edit_ct_content_blocks');
            $role->add_cap('edit_ct_content_block');
            $role->add_cap('edit_others_ct_content_blocks');
            $role->add_cap('publish_ct_content_blocks');
            $role->add_cap('read_ct_content_block');
            $role->add_cap('delete_ct_content_blocks');
            
            // Gravity Forms capabilities
            $role->add_cap('gravityforms_view_entries');
            $role->add_cap('gravityforms_delete_entries');
            $role->add_cap('gravityforms_view_entry_notes');
            $role->add_cap('gravityforms_export_entries');
            $role->add_cap('gravityforms_edit_entries');
            
            // UPRAVENÉ: manage_options len ak naozaj treba
            // POZOR: Toto dáva editorom širšie práva, zvážte či je to potrebné
            if (!$role->has_cap('manage_options')) {
                $role->add_cap('manage_options');
            }
        }
        
        // Označíme že capabilities už boli nastavené
        update_option('custom_capabilities_initialized', true);
    }
    
    // Spustíme len raz pri aktivácii témy alebo manuálne
    add_action('after_switch_theme', 'allow_editors_capabilities');
    
    // Ak potrebujete resetovať capabilities, pridajte do URL: ?reset_capabilities=1
    if (isset($_GET['reset_capabilities']) && current_user_can('administrator')) {
        delete_option('custom_capabilities_initialized');
        allow_editors_capabilities();
        wp_die('Capabilities boli resetované. <a href="' . admin_url() . '">Späť na dashboard</a>');
    }

    // Obmedzenia len pre non-admin používateľov
    function restrict_blocksy_dashboard_for_editors() {
        // DÔLEŽITÉ: Nekontrolujeme počas AIOS bezpečnostných kontrol
        if (defined('DOING_AJAX') && DOING_AJAX) {
            return;
        }
        
        if (!current_user_can('administrator')) {
            $page = $_GET['page'] ?? '';
            if (in_array($page, ['ct-dashboard', 'ct-dashboard-account'])) {
                wp_die('Nemáte oprávnenie pre vstup do tejto sekcie.');
            }
        }
    }
    add_action('admin_init', 'restrict_blocksy_dashboard_for_editors', 999); // Vysoká priorita

    // Menu obmedzenia len pre non-artefactum používateľov
    function my_remove_menu_pages() {
        global $current_user;
        wp_get_current_user();
        
        // Len pre non-artefactum používateľov
        if($current_user->user_login != 'artefactum'){
            $menus_to_remove = [
                'wp-mail-smtp','aiowpsec','litespeed','advanced_db_cleaner','tools.php','elementor','edit.php?post_type=elementor_library'];
            
            foreach($menus_to_remove as $menu) {
                remove_menu_page($menu);
            }
            
            $elementor_submenus = [
                'elementor', 'elementor-settings', 'elementor-role-manager','elementor-element-manager', 'elementor-tools', 'elementor-system-info', 'go_knowledge_base_site', 'e-form-submissions', 'elementor_custom_fonts','elementor_custom_icons', 'elementor_custom_code', 'elementor-apps','go_elementor_pro'
            ];
            
            foreach($elementor_submenus as $submenu) {
                remove_submenu_page('elementor', $submenu);
            }
            
            $options_submenus = ['options-permalink.php','options-media.php','cache-enabler','eml_settings'];
            foreach ($options_submenus as $optsubmenu) {
                remove_submenu_page('options-general.php', $optsubmenu);
            }
        }
    }
    add_action('admin_menu', 'my_remove_menu_pages', 201);

    // Dodatočná funkcia pre skrytie Blocksy menu s CSS ako záloha
    function hide_blocksy_menu_css() {
        global $current_user;
        wp_get_current_user();

        // Skrytie ďalších položiek pre všetkých okrem používateľa 'artefactum'
        if ($current_user->user_login != 'artefactum') {
            echo '<style> 
                a.page-title-action[href*="post-new.php?post_type=ct_content_block"],#adminmenu a[href*="ct-dashboard-account"], #adminmenu .wp-first-item a[href*="ct-dashboard"], #adminmenu a[href*="site-editor.php"], #adminmenu a[href*="customize.php?return=%2Fwp-admin%2Fthemes.php"], .theme-actions a[href*="wp-admin%2Fthemes.php"], a.hide-if-no-customize, .ab-submenu li a[href*="options-general.php?page=translate-press"], #wp-admin-bar-elementor-maintenance-on, #wp_mail_smtp_reports_widget_lite, #wp-admin-bar-litespeed-bar-manage, #wp-admin-bar-litespeed-bar-setting, #wp-admin-bar-litespeed-bar-imgoptm,#new_admin_email, #new_admin_email + p.description, label[for="new_admin_email"] {
                    display: none !important;
                }
            </style>';
        }
    }
    add_action('admin_head', 'hide_blocksy_menu_css');
    
    // NOVÁ FUNKCIA: Zamedzenie úpravy admin_email pre non-administrátorov (okrem artefactum)
    function restrict_admin_email_update($value, $option) {
        global $current_user;
        wp_get_current_user();

        if ($option === 'admin_email' && $current_user->user_login != 'artefactum' && !current_user_can('administrator')) {
            // Vráti pôvodnú hodnotu, aby sa zmena neuložila
            return get_option('admin_email');
        }
        return $value;
    }
    add_filter('pre_update_option_admin_email', 'restrict_admin_email_update', 10, 2);
    
    // NOVÁ FUNKCIA: Kompatibilita s AIOS
    function custom_aios_compatibility() {
        // Umožníme AIOS upravovať security settings bez konfliktov
        remove_action('admin_init', 'allow_editors_capabilities');
    }
    add_action('plugins_loaded', 'custom_aios_compatibility', 1);
}


/* ==========================
   ZÁKLADNÉ KONŠTANTY
   ========================== */

define('SPA_VERSION', '26.1.0');
define('SPA_PATH', get_stylesheet_directory());
define('SPA_URL', get_stylesheet_directory_uri());
define('SPA_INCLUDES', SPA_PATH . '/includes/');

/* ==========================
   NAČÍTANIE ŠTÝLOV
   ========================== */

add_action('wp_enqueue_scripts', function() {
    // Parent theme
    wp_enqueue_style('parent-style', get_template_directory_uri() . '/style.css');
    
    // Child theme
    wp_enqueue_style('child-style', get_stylesheet_directory_uri() . '/style.css', ['parent-style'], SPA_VERSION);
    
    // jQuery (potrebné pre AJAX)
    wp_enqueue_script('jquery');
});

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
    'spa-core.php',           // Základné funkcie, role, capabilities
    'spa-helpers.php',        // Pomocné funkcie
    'spa-cpt.php',           // Custom Post Types
    'spa-admin-columns.php',  // Admin columns
    'spa-taxonomies.php',    // Taxonómie    
    'spa-shortcodes.php',    // Frontend shortcodes
    'spa-widgets.php',       // Widgety, bannery
    'spa-calendar.php',      // Kalendár, Obsadenosť hál
    'spa-user-fields.php',   // ✅ Rozšírené polia (rodné číslo, VS, adresa)
    'spa-login.php',         // ✅ Custom login systém (email+heslo / meno+priezvisko+PIN)
    'spa-login-popup.php',   // ✅ login popup
    'spa-registration.php',  // Registračný systém (FÁZA 2)
    'spa-meta-boxes.php',    // Admin meta boxy
    'spa-import.php',        // ✅ NOVÉ: Import nástroj
    'spa-trainer.php',       // ✅ NOVÉ: Správa trénerov
    // 'spa-dashboard.php',     // Dashboardy (FÁZA 2)
    // 'spa-payments.php',      // Platby (FÁZA 2)
    // 'spa-attendance.php',    // Dochádzka (FÁZA 3)
    // 'spa-gamification.php',  // Gamifikácia (FÁZA 3)
    // 'spa-messaging.php',     // Správy (FÁZA 3)
    // 'spa-notifications.php', // Notifikácie (FÁZA 3)
];

foreach ($spa_modules as $module) {
    $file = SPA_INCLUDES . $module;
    if (file_exists($file)) {
        require_once $file;
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
        '<svg class="spa-icon" width="39" height="39" viewBox="12 12 36 76" style="vertical-align: bottom;display: inline-block;"><path d="M36.29,0C-3.91,29.7.49,65.3,32.79,69.8-1.91,69-20.51,38.3,36.29,0Z" fill="var(--theme-palette-color-1, #FF1439)"></path><path d="M16.99,60.2c2.5,1.8,5.1,1.8,5.6-.2s-1.1-5.1-3.7-7-5.1-1.8-5.6.2,1.1,5.1,3.7,7Z" fill="var(--theme-palette-color-1, #FF1439)"></path><path d="M16.49,92.4c40.2-29.7,35.8-65.3,3.5-69.8,34.7.8,53.3,31.5-3.5,69.8Z" fill="var(--theme-palette-color-3, #ff1439)"></path><path d="M48.39,30.5c2.6,1.9,5.1,1.8,5.6-.2s-1.1-5.1-3.7-7-5.1-1.8-5.6.2,1.1,5.1,3.7,7Z" fill="var(--theme-palette-color-3, #ff1439)"></path></svg> Samuel Piasecký ACADEMY - Stav systému',
        function() {
            ?>
            <div style="padding: 12px;">
                <p><strong>Verzia:</strong> <?php echo SPA_VERSION; ?><br>
                <strong>Načítané moduly:</strong> 
                    <?php 
                    $loaded = array_filter(glob(SPA_INCLUDES . '*.php'));
                    echo count($loaded); 
                    ?>
                </p>
                
                <hr>
                
                <h4>Rýchle linky:</h4>
                <ul>
                    <li><a href="<?php echo admin_url('edit.php?post_type=spa_group'); ?>">📋 Skupiny tréningov</a></li>
                    <li><a href="<?php echo admin_url('edit.php?post_type=spa_hall_block'); ?>">📅 Obsadenosť hál</a></li>
                    <li><a href="<?php echo admin_url('widgets.php'); ?>">📢 Bannery (Widgety)</a></li>
                    <li><a href="<?php echo admin_url('admin.php?page=gf_edit_forms'); ?>">📝 Formuláre</a></li>
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