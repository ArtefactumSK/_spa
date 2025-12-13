<?php
/**
 * SPA CPT: Groups - Programy a skupiny tréningov
 * 
 * @package Samuel Piasecký ACADEMY
 * @subpackage CPT
 * @version 1.0.0
 * 
 * PARENT MODULES: spa-core/spa-constants.php
 * CHILD MODULES: registration, import, frontend
 * 
 * CPT REGISTERED:
 * - spa_group (Programy/Skupiny tréningov)
 * 
 * FUNCTIONS DEFINED:
 * - spa_register_cpt_groups()
 * 
 * DATABASE TABLES:
 * - wp_posts (post_type = spa_group)
 * - wp_postmeta (meta pre programy)
 * 
 * HOOKS USED:
 * - init (CPT registration)
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