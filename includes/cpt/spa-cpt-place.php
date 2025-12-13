<?php
/**
 * SPA CPT: Places - Miesta tréningov
 * 
 * @package Samuel Piasecký ACADEMY
 * @subpackage CPT
 * @version 1.0.0
 * 
 * PARENT MODULES: spa-core/spa-constants.php
 * CHILD MODULES: import, frontend
 * 
 * CPT REGISTERED:
 * - spa_place (Miesta tréningov)
 * 
 * FUNCTIONS DEFINED:
 * - spa_register_cpt_place()
 * 
 * DATABASE TABLES:
 * - wp_posts (post_type = spa_place)
 * - wp_postmeta (meta pre miesta)
 * 
 * HOOKS USED:
 * - init (CPT registration)
 * 
 * NOTES:
 * Nahrádza taxonómiu spa_place ako hlavný zdroj
 * Taxonómia zostáva pre spätnú kompatibilitu
 */

if (!defined('ABSPATH')) {
    exit;
}

/* ==================================================
   CPT: spa_place (Miesta)
   ================================================== */

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