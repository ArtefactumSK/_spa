<?php
/**
 * spa-cpt.php
 * Registrácia CPT používaných v SPA module
 * @version 2.1.0 - ČISTÁ: admin columns presunuté do spa-admin-columns.php
 */

if (!defined('ABSPATH')) {
    exit;
}

/* ============================================================
   CPT: spa_group (Programy/Skupiny tréningov)
   ============================================================ */
add_action('init', 'spa_register_cpt_groups');
function spa_register_cpt_groups() {
    $labels = array(
        'name'               => '🤸 Programy',
        'singular_name'      => 'Program',
        'menu_name'          => 'SPA Programy',
        'add_new'            => 'Pridať program',
        'add_new_item'       => 'Pridať nový program',
        'edit_item'          => 'Upraviť program',
        'new_item'           => 'Nový program',
        'view_item'          => 'Zobraziť program',
        'search_items'       => 'Hľadať programy',
        'not_found'          => 'Žiadne programy nenájdené',
        'not_found_in_trash' => 'Žiadne programy v koši'
    );

    register_post_type('spa_group', array(
        'labels'            => $labels,
        'public'            => false,
        'show_ui'           => true,
        'menu_icon'         => 'dashicons-universal-access-alt',
        'menu_position'     => 20,
        'hierarchical'      => false,
        'supports'          => array('title', 'editor'),
        'capability_type'   => 'post',
        'show_in_rest'      => false,
    ));
}

/* ============================================================
   CPT: spa_registration (Registrácie)
   ============================================================ */
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

/* ============================================================
   CPT: spa_place (Miesto)
   ============================================================ */
add_action('init', 'spa_register_cpt_place');
function spa_register_cpt_place() {
    $labels = array(
        'name'               => '📍 Miesta',
        'singular_name'      => 'Miesto',
        'menu_name'          => 'SPA Miesta',
        'add_new'            => 'Pridať miesto',
        'add_new_item'       => 'Pridať nové miesto',
        'edit_item'          => 'Upraviť miesto',
        'new_item'           => 'Nové miesto',
        'view_item'          => 'Zobraziť miesto',
        'search_items'       => 'Hľadať miesta',
        'not_found'          => 'Žiadne miesta nenájdené',
        'not_found_in_trash' => 'Žiadne miesta v koši',
        'all_items'          => 'Všetky miesta'
    );

    register_post_type('spa_place', array(
        'labels'            => $labels,
        'public'            => false,
        'show_ui'           => true,
        'menu_icon'         => 'dashicons-location',
        'menu_position'     => 24,
        'hierarchical'      => false,
        'supports'          => array('title'),
        'capability_type'   => 'post',
        'show_in_rest'      => false,
    ));
}

/* ============================================================
   CPT: spa_event (Udalosť/Blokovanie)
   ============================================================ */
add_action('init', 'spa_register_cpt_event');
function spa_register_cpt_event() {
    $labels = array(
        'name'               => '📅 Udalosti',
        'singular_name'      => 'Udalosť',
        'menu_name'          => 'SPA Udalosti',
        'add_new'            => 'Pridať udalosť',
        'add_new_item'       => 'Pridať novú udalosť',
        'edit_item'          => 'Upraviť udalosť',
        'new_item'           => 'Nová udalosť',
        'view_item'          => 'Zobraziť udalosť',
        'search_items'       => 'Hľadať udalosti',
        'not_found'          => 'Žiadne udalosti nenájdené',
        'not_found_in_trash' => 'Žiadne udalosti v koši',
        'all_items'          => 'Všetky udalosti'
    );

    register_post_type('spa_event', array(
        'labels'            => $labels,
        'public'            => false,
        'show_ui'           => true,
        'menu_icon'         => 'dashicons-calendar-alt',
        'menu_position'     => 25,
        'hierarchical'      => false,
        'supports'          => array('title', 'editor'),
        'capability_type'   => 'post',
        'show_in_rest'      => false,
    ));
}

/* ============================================================
   CPT: spa_attendance (Dochádzka)
   ============================================================ */
add_action('init', 'spa_register_cpt_attendance');
function spa_register_cpt_attendance() {
    $labels = array(
        'name'               => '✅ Dochádzka',
        'singular_name'      => 'Záznam dochádzky',
        'menu_name'          => 'SPA Dochádzka',
        'add_new'            => 'Pridať záznam',
        'add_new_item'       => 'Pridať záznam dochádzky',
        'edit_item'          => 'Upraviť záznam',
        'search_items'       => 'Hľadať záznamy',
        'not_found'          => 'Žiadne záznamy nenájdené',
        'all_items'          => 'Všetky záznamy'
    );

    register_post_type('spa_attendance', array(
        'labels'            => $labels,
        'public'            => false,
        'show_ui'           => true,
        'menu_icon'         => 'dashicons-yes-alt',
        'menu_position'     => 26,
        'hierarchical'      => false,
        'supports'          => array('title'),
        'capability_type'   => 'post',
        'show_in_rest'      => false,
    ));
}

/* ============================================================
   CPT: spa_payment (Platby)
   ============================================================ */
add_action('init', 'spa_register_cpt_payments');
function spa_register_cpt_payments() {
    $labels = array(
        'name'               => '💳 Platby',
        'singular_name'      => 'Platba',
        'menu_name'          => 'SPA Platby',
        'add_new'            => 'Pridať platbu',
        'add_new_item'       => 'Pridať novú platbu',
        'edit_item'          => 'Upraviť platbu',
        'view_item'          => 'Zobraziť platbu',
        'search_items'       => 'Hľadať platby',
        'not_found'          => 'Žiadne platby nenájdené',
        'all_items'          => 'Všetky platby'
    );

    register_post_type('spa_payment', array(
        'labels'            => $labels,
        'public'            => false,
        'show_ui'           => true,
        'menu_icon'         => 'dashicons-money-alt',
        'menu_position'     => 27,
        'hierarchical'      => false,
        'supports'          => array('title'),
        'capability_type'   => 'post',
        'show_in_rest'      => false,
    ));
}

/* ============================================================
   CPT: spa_hall_block (Obsadenosť hál - deprecated)
   ============================================================ */
add_action('init', 'spa_register_cpt_hall_blocks');
function spa_register_cpt_hall_blocks() {
    $labels = array(
        'name'          => '🏟️ Obsadenosť telocvičien',
        'singular_name' => 'Rezervácia telocvične',
        'menu_name'     => 'SPA telocvične',
        'add_new'       => 'Pridať rezerváciu',
        'add_new_item'  => 'Rezervovať telocvičňu',
        'edit_item'     => 'Upraviť rezerváciu',
        'search_items'  => 'Hľadať rezervácie'
    );

    register_post_type('spa_hall_block', array(
        'labels'            => $labels,
        'public'            => false,
        'show_ui'           => true,
        'show_in_menu'      => false,
        'menu_position'     => 28,
        'hierarchical'      => false,
        'supports'          => array('title'),
        'capability_type'   => 'post',
        'show_in_rest'      => false,
    ));
}

/* ============================================================
   MENU: Zmena "Pridať registráciu" na externý link
   ============================================================ */

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

add_action('admin_init', 'spa_handle_registration_redirect');
function spa_handle_registration_redirect() {
    if (isset($_GET['page']) && $_GET['page'] === 'spa-add-registration-redirect') {
        wp_redirect(home_url('/registracia/'));
        exit;
    }
}

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