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

add_action('init', 'spa_register_cpt_groups');

function spa_register_cpt_groups() {
    
    $labels = array(
        'name' => 'Skupiny treningov',
        'singular_name' => 'Skupina',
        'menu_name' => 'Skupiny treningov',
        'add_new' => 'Pridat skupinu',
        'add_new_item' => 'Pridat novu skupinu',
        'edit_item' => 'Upravit skupinu',
        'new_item' => 'Nova skupina',
        'view_item' => 'Zobrazit skupinu',
        'search_items' => 'Hladat skupiny',
        'not_found' => 'Ziadne skupiny nenajdene',
        'not_found_in_trash' => 'Ziadne skupiny v kosi'
    );

    register_post_type('spa_group', array(
        'labels' => $labels,
        'public' => false,
        'show_ui' => true,
        'menu_icon' => 'dashicons-groups',
        'menu_position' => 20,
        'hierarchical' => false,
        'supports' => array('title'),
        'capability_type' => 'post',
        'show_in_rest' => false
    ));
}

add_action('init', 'spa_register_cpt_registrations');

function spa_register_cpt_registrations() {
    
    $labels = array(
        'name' => 'Registracie',
        'singular_name' => 'Registracia',
        'menu_name' => 'Registracie',
        'add_new' => 'Pridat registraciu',
        'add_new_item' => 'Pridat novu registraciu',
        'edit_item' => 'Upravit registraciu',
        'new_item' => 'Nova registracia',
        'view_item' => 'Zobrazit registraciu',
        'search_items' => 'Hladat registracie',
        'not_found' => 'Ziadne registracie nenajdene',
        'not_found_in_trash' => 'Ziadne registracie v kosi',
        'all_items' => 'Vsetky registracie'
    );

    register_post_type('spa_registration', array(
        'labels' => $labels,
        'public' => false,
        'show_ui' => true,
        'menu_icon' => 'dashicons-clipboard',
        'menu_position' => 21,
        'hierarchical' => false,
        'supports' => array('title'),
        'capability_type' => 'post',
        'show_in_rest' => false
    ));
}

add_action('init', 'spa_register_cpt_hall_blocks');

function spa_register_cpt_hall_blocks() {
    
    $labels = array(
        'name' => 'Obsadenost hal',
        'singular_name' => 'Rezervacia haly',
        'menu_name' => 'Obsadenost hal',
        'add_new' => 'Pridat rezervaciu',
        'add_new_item' => 'Pridat novu rezervaciu',
        'edit_item' => 'Upravit rezervaciu',
        'search_items' => 'Hladat rezervacie'
    );

    register_post_type('spa_hall_block', array(
        'labels' => $labels,
        'public' => false,
        'show_ui' => true,
        'menu_icon' => 'dashicons-calendar-alt',
        'menu_position' => 22,
        'hierarchical' => false,
        'supports' => array('title'),
        'capability_type' => 'post',
        'capabilities' => array(
            'edit_post' => 'edit_posts',
            'delete_post' => 'delete_posts',
            'edit_posts' => 'edit_posts',
            'publish_posts' => 'publish_posts'
        ),
        'show_in_rest' => false
    ));
}

add_action('init', 'spa_register_cpt_payments');

function spa_register_cpt_payments() {
    
    $labels = array(
        'name' => 'Platby',
        'singular_name' => 'Platba',
        'menu_name' => 'Platby',
        'add_new' => 'Pridat platbu',
        'add_new_item' => 'Pridat novu platbu',
        'edit_item' => 'Upravit platbu',
        'view_item' => 'Zobrazit platbu',
        'search_items' => 'Hladat platby',
        'not_found' => 'Ziadne platby nenajdene',
        'all_items' => 'Vsetky platby'
    );

    register_post_type('spa_payment', array(
        'labels' => $labels,
        'public' => false,
        'show_ui' => true,
        'menu_icon' => 'dashicons-money-alt',
        'menu_position' => 23,
        'hierarchical' => false,
        'supports' => array('title'),
        'capability_type' => 'post',
        'capabilities' => array(
            'edit_post' => 'edit_spa_payments',
            'edit_posts' => 'edit_spa_payments',
            'publish_posts' => 'edit_spa_payments',
            'read_post' => 'view_spa_payments'
        ),
        'map_meta_cap' => true,
        'show_in_rest' => false
    ));
}