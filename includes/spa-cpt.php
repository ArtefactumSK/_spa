<?php
/**
 * SPA Custom Post Types
 * 
 * @package Samuel Piasecký ACADEMY
 * @version 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/* ==========================
   CPT: Skupiny tréningov (programy)
   ========================== */

add_action('init', 'spa_register_cpt_groups');

function spa_register_cpt_groups() {
    
    $labels = [
        'name' => 'Skupiny tréningov',
        'singular_name' => 'Skupina',
        'menu_name' => 'Skupiny tréningov',
        'add_new' => 'Pridať skupinu',
        'add_new_item' => 'Pridať novú skupinu',
        'edit_item' => 'Upraviť skupinu',
        'new_item' => 'Nová skupina',
        'view_item' => 'Zobraziť skupinu',
        'search_items' => 'Hľadať skupiny',
        'not_found' => 'Žiadne skupiny nenájdené',
        'not_found_in_trash' => 'Žiadne skupiny v koši'
    ];

    register_post_type('spa_group', [
        'labels' => $labels,
        'public' => false,
        'show_ui' => true,
        'menu_icon' => 'dashicons-groups',
        'menu_position' => 20,
        'hierarchical' => false,
        'supports' => ['title'],
        'capability_type' => 'post',
        'show_in_rest' => false
    ]);
}

/* ==========================
   CPT: Registrácie (klienti v programoch)
   ========================== */

add_action('init', 'spa_register_cpt_registrations');

function spa_register_cpt_registrations() {
    
    $labels = [
        'name' => 'Registrácie',
        'singular_name' => 'Registrácia',
        'menu_name' => 'Registrácie',
        'add_new' => 'Pridať registráciu',
        'add_new_item' => 'Pridať novú registráciu',
        'edit_item' => 'Upraviť registráciu',
        'new_item' => 'Nová registrácia',
        'view_item' => 'Zobraziť registráciu',
        'search_items' => 'Hľadať registrácie',
        'not_found' => 'Žiadne registrácie nenájdené',
        'not_found_in_trash' => 'Žiadne registrácie v koši',
        'all_items' => 'Všetky registrácie'
    ];

    register_post_type('spa_registration', [
        'labels' => $labels,
        'public' => false,
        'show_ui' => true,
        'menu_icon' => 'dashicons-clipboard',
        'menu_position' => 21,
        'hierarchical' => false,
        'supports' => ['title'],
        'capability_type' => 'post',
        'show_in_rest' => false
    ]);
}

/* ==========================
   CPT: Obsadenosť hál
   ========================== */

add_action('init', 'spa_register_cpt_hall_blocks');

function spa_register_cpt_hall_blocks() {
    
    $labels = [
        'name' => 'Obsadenosť hál',
        'singular_name' => 'Rezervácia haly',
        'menu_name' => 'Obsadenosť hál',
        'add_new' => 'Pridať rezerváciu',
        'add_new_item' => 'Pridať novú rezerváciu',
        'edit_item' => 'Upraviť rezerváciu',
        'search_items' => 'Hľadať rezervácie'
    ];

    register_post_type('spa_hall_block', [
        'labels' => $labels,
        'public' => false,
        'show_ui' => true,
        'menu_icon' => 'dashicons-calendar-alt',
        'menu_position' => 22,
        'hierarchical' => false,
        'supports' => ['title'],
        'capability_type' => 'post',
        'capabilities' => [
            'edit_post' => 'edit_posts',
            'delete_post' => 'delete_posts',
            'edit_posts' => 'edit_posts',
            'publish_posts' => 'publish_posts'
        ],
        'show_in_rest' => false
    ]);
}

/* ==========================
   CPT: Platby
   ========================== */

add_action('init', 'spa_register_cpt_payments');

function spa_register_cpt_payments() {
    
    $labels = [
        'name' => 'Platby',
        'singular_name' => 'Platba',
        'menu_name' => 'Platby',
        'add_new' => 'Pridať platbu',
        'add_new_item' => 'Pridať novú platbu',
        'edit_item' => 'Upraviť platbu',
        'view_item' => 'Zobraziť platbu',
        'search_items' => 'Hľadať platby',
        'not_found' => 'Žiadne platby nenájdené',
        'all_items' => 'Všetky platby'
    ];

    register_post_type('spa_payment', [
        'labels' => $labels,
        'public' => false,
        'show_ui' => true,
        'menu_icon' => 'dashicons-money-alt',
        'menu_position' => 23,
        'hierarchical' => false,
        'supports' => ['title'],
        'capability_type' => 'post',
        'capabilities' => [
            'edit_post' => 'edit_spa_payments',
            'edit_posts' => 'edit_spa_payments',
            'publish_posts' => 'edit_spa_payments',
            'read_post' => 'view_spa_payments'
        ],
        'map_meta_cap' => true,
        'show_in_rest' => false
    ]);
}



/* ==========================
   CPT: Achievements (odznaky)
   FÁZA 3 - zatiaľ zakomentované
   ========================== */

/*
add_action('init', 'spa_register_cpt_achievements');

function spa_register_cpt_achievements() {
    
    $labels = [
        'name' => 'Odznaky',
        'singular_name' => 'Odznak',
        'menu_name' => 'Odznaky',
        'add_new' => 'Pridať odznak'
    ];

    register_post_type('spa_achievement', [
        'labels' => $labels,
        'public' => false,
        'show_ui' => true,
        'menu_icon' => 'dashicons-awards',
        'supports' => ['title', 'editor', 'thumbnail']
    ]);
}
*/

/* ==========================
   CPT: Správy (messaging)
   FÁZA 3 - zatiaľ zakomentované
   ========================== */

/*
add_action('init', 'spa_register_cpt_messages');

function spa_register_cpt_messages() {
    
    $labels = [
        'name' => 'Správy',
        'singular_name' => 'Správa',
        'menu_name' => 'Správy'
    ];

    register_post_type('spa_message', [
        'labels' => $labels,
        'public' => false,
        'show_ui' => true,
        'menu_icon' => 'dashicons-email',
        'supports' => ['title', 'editor']
    ]);
}
*/

/* ==========================
   CPT: Rating (hodnotenia trénerov)
   FÁZA 3 - zatiaľ zakomentované
   ========================== */

/*
add_action('init', 'spa_register_cpt_ratings');

function spa_register_cpt_ratings() {
    
    $labels = [
        'name' => 'Hodnotenia',
        'singular_name' => 'Hodnotenie',
        'menu_name' => 'Hodnotenia'
    ];

    register_post_type('spa_rating', [
        'labels' => $labels,
        'public' => false,
        'show_ui' => true,
        'menu_icon' => 'dashicons-star-filled',
        'supports' => ['title', 'editor']
    ]);
}
*/