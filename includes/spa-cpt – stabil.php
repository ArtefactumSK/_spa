<?php
/**
 * spa-cpt.php
 * Registrácia CPT používaných v SPA module
 * Opravená verzia – obsahuje správne PHP tagy a obalené funkcie
 */

// Bezpečnostné: ak sa súbor náhodou volá priamo
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/* -------------------------------------------
   CPT: Skupiny tréningov
-------------------------------------------- */
add_action( 'init', 'spa_register_cpt_groups' );
function spa_register_cpt_groups() {
    $labels = array(
        'name'               => 'Skupiny tréningov',
        'singular_name'      => 'Skupina',
        'menu_name'          => 'Skupiny tréningov',
        'add_new'            => 'Pridať skupinu',
        'add_new_item'       => 'Pridať novú skupinu',
        'edit_item'          => 'Upraviť skupinu',
        'new_item'           => 'Nová skupina',
        'view_item'          => 'Zobraziť skupinu',
        'search_items'       => 'Hľadať skupiny',
        'not_found'          => 'Žiadne skupiny nenájdené',
        'not_found_in_trash' => 'Žiadne skupiny v koši'
    );

    register_post_type( 'spa_group', array(
        'labels'            => $labels,
        'public'            => false,
        'show_ui'           => true,
        'menu_icon'         => 'dashicons-groups',
        'menu_position'     => 20,
        'hierarchical'      => false,
        'supports'          => array( 'title' ),
        'capability_type'   => 'post',
        'show_in_rest'      => false,
    ) );
}

/* -------------------------------------------
   CPT: Registrácie
-------------------------------------------- */
add_action( 'init', 'spa_register_cpt_registrations' );
function spa_register_cpt_registrations() {
    $labels = array(
        'name'               => 'Registrácie',
        'singular_name'      => 'Registrácia',
        'menu_name'          => 'Registrácie',
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

    register_post_type( 'spa_registration', array(
        'labels'            => $labels,
        'public'            => false,
        'show_ui'           => true,
        'menu_icon'         => 'dashicons-clipboard',
        'menu_position'     => 21,
        'hierarchical'      => false,
        'supports'          => ['title'], 
        'capability_type'   => 'post',
        'show_in_rest'      => false,
    ) );
}

/* -------------------------------------------
   CPT: Obsadenosť hál (hall blocks)
-------------------------------------------- */
add_action( 'init', 'spa_register_cpt_hall_blocks' );
function spa_register_cpt_hall_blocks() {
    $labels = array(
        'name'          => 'Obsadenosť hál',
        'singular_name' => 'Rezervácia haly',
        'menu_name'     => 'Obsadenosť hál',
        'add_new'       => 'Pridať rezerváciu',
        'add_new_item'  => 'Pridať novú rezerváciu',
        'edit_item'     => 'Upraviť rezerváciu',
        'search_items'  => 'Hľadať rezervácie'
    );

    register_post_type( 'spa_hall_block', array(
        'labels'            => $labels,
        'public'            => false,
        'show_ui'           => true,
        'menu_icon'         => 'dashicons-calendar-alt',
        'menu_position'     => 22,
        'hierarchical'      => false,
        'supports'          => array( 'title' ),
        'capability_type'   => 'post',
        'capabilities'      => array(
            'edit_post'    => 'edit_posts',
            'delete_post'  => 'delete_posts',
            'edit_posts'   => 'edit_posts',
            'publish_posts'=> 'publish_posts',
        ),
        'show_in_rest' => false,
    ) );
}

/* -------------------------------------------
   CPT: Platby
-------------------------------------------- */
add_action( 'init', 'spa_register_cpt_payments' );
function spa_register_cpt_payments() {
    $labels = array(
        'name'               => 'Platby',
        'singular_name'      => 'Platba',
        'menu_name'          => 'Platby',
        'add_new'            => 'Pridať platbu',
        'add_new_item'       => 'Pridať novú platbu',
        'edit_item'          => 'Upraviť platbu',
        'view_item'          => 'Zobraziť platbu',
        'search_items'       => 'Hľadať platby',
        'not_found'          => 'Žiadne platby nenájdené',
        'all_items'          => 'Všetky platby'
    );

    register_post_type( 'spa_payment', array(
        'labels'            => $labels,
        'public'            => false,
        'show_ui'           => true,
        'menu_icon'         => 'dashicons-money-alt',
        'menu_position'     => 23,
        'hierarchical'      => false,
        'supports'          => array( 'title' ),
        'capability_type'   => 'post',
        'capabilities'      => array(
            'edit_post'    => 'edit_spa_payments',
            'edit_posts'   => 'edit_spa_payments',
            'publish_posts'=> 'edit_spa_payments',
            'read_post'    => 'view_spa_payments',
        ),
        'map_meta_cap' => true,
        'show_in_rest' => false,
    ) );
}