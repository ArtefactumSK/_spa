<?php
/**
 * SPA CPT: Attendance + Payments - Dochádzka a platby
 * 
 * @package Samuel Piasecký ACADEMY
 * @subpackage CPT
 * @version 1.0.0
 * 
 * PARENT MODULES: spa-core/spa-constants.php
 * CHILD MODULES: frontend, admin
 * 
 * CPT REGISTERED:
 * - spa_attendance (Dochádzka)
 * - spa_payment (Platby)
 * - spa_hall_block (Obsadenosť hál - deprecated)
 * 
 * FUNCTIONS DEFINED:
 * - spa_register_cpt_attendance()
 * - spa_register_cpt_payments()
 * - spa_register_cpt_hall_blocks()
 * 
 * DATABASE TABLES:
 * - wp_posts (post_type = spa_attendance)
 * - wp_posts (post_type = spa_payment)
 * - wp_posts (post_type = spa_hall_block)
 * - wp_postmeta (meta pre záznamy)
 * 
 * HOOKS USED:
 * - init (CPT registration)
 * 
 * NOTES:
 * spa_hall_block je deprecated - budúci nahradenie cez spa_event
 */

if (!defined('ABSPATH')) {
    exit;
}

/* ==================================================
   CPT: spa_attendance (Dochádzka)
   ================================================== */

add_action('init', 'spa_register_cpt_attendance');

function spa_register_cpt_attendance() {
    $labels = array(
        'name'               => '✅ Dochádzka',
        'singular_name'      => 'Záznam dochádzky',
        'menu_name'          => 'SPA Dochádzka',
        'add_new'            => 'Pridať záznam',
        'add_new_item'       => 'Pridať záznam dochádzky',
        'edit_item'          => 'Upraviť záznam',
        'new_item'           => 'Nový záznam',
        'view_item'          => 'Zobraziť záznam',
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

/* ==================================================
   CPT: spa_payment (Platby)
   ================================================== */

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

/* ==================================================
   CPT: spa_hall_block (Obsadenosť hál - DEPRECATED)
   ================================================== */

add_action('init', 'spa_register_cpt_hall_blocks');

function spa_register_cpt_hall_blocks() {
    $labels = array(
        'name'          => '🏟️ Obsadenosť telocviční',
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