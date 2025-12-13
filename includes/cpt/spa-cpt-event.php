<?php
/**
 * SPA CPT: Events - Udalosti a blokovanie priestoru
 * 
 * @package Samuel Piasecký ACADEMY
 * @subpackage CPT
 * @version 1.0.0
 * 
 * PARENT MODULES:
 * - spa-core/spa-constants.php
 * 
 * CHILD MODULES: všetky
 * 
 * CPT REGISTERED:
 * - spa_event (Udalosti)
 * 
 * FUNCTIONS DEFINED:
 * - spa_register_cpt_event()
 * 
 * HOOKS USED:
 * - init (registration)
 * 
 * NOTES:
 * Slúži na blokovanie priestoru jednorazovo alebo opakovane
 * (dovolenka, sviatky, údržba, atď.)
 */

if (!defined('ABSPATH')) {
    exit;
}

/* ==================================================
   CPT: spa_event (Udalosti/Blokovanie)
   ================================================== */

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