<?php
/**
 * SPA CPT: Groups/Programs - Programy a skupiny tréningov
 * 
 * @package Samuel Piasecký ACADEMY
 * @subpackage CPT
 * @version 1.0.0
 * 
 * PARENT MODULES:
 * - spa-core/spa-constants.php
 * - spa-core/spa-roles.php
 * 
 * CHILD MODULES: všetky moduly
 * 
 * CPT REGISTERED:
 * - spa_group (Programy/Skupiny)
 * 
 * FUNCTIONS DEFINED:
 * - spa_register_cpt_groups()
 * 
 * HOOKS USED:
 * - init (registration)
 */

if (!defined('ABSPATH')) {
    exit;
}

/* ==================================================
   CPT: spa_group (Programy/Skupiny tréningov)
   ================================================== */

add_action('init', 'spa_register_cpt_groups');

function spa_register_cpt_groups() {
    $labels = array(
        'name'               => '🤸 Programy',
        'singular_name'      => 'Program',
        'menu_name'          => 'SPA Programy',
        'add_new'            => 'Pridať program',
        'add_new_item' => 'Pridať nové miesto',
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