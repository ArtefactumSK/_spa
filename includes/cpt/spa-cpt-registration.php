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

function spa_fix_registration_submenu() {
    global $submenu;
    
    if (isset($submenu['edit.php?post_type=spa_registration'])) {
        foreach ($submenu['edit.php?post_type=spa_registration'] as $key => $item) {
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