<?php
/**
 * SPA Taxonomies - OPRAVENÉ
 * 
 * @package Samuel Piasecký ACADEMY
 * @version 1.0.1-FIXED
 * 
 * FIXES:
 * - Pridané 'show_in_quick_edit' => true pre správne zobrazenie v admin paneli
 */

if (!defined('ABSPATH')) {
    exit;
}

/* ==========================
   TAXONÓMIA: Miesta (Malacky, Košice)
   ========================== */

add_action('init', 'spa_register_taxonomy_places');

function spa_register_taxonomy_places() {
    
    $labels = [
        'name' => '📍 Miesta (Značka)',
        'singular_name' => 'Miesto',
        'search_items' => 'Hľadať miesta',
        'all_items' => 'Všetky miesta',
        'edit_item' => 'Upraviť miesto',
        'update_item' => 'Aktualizovať miesto',
        'add_new_item' => 'Pridať miesto',
        'new_item_name' => 'Nové miesto',
        'menu_name' => 'Miesta'
    ];

    register_taxonomy('spa_place', 'spa_group', [
        'labels' => $labels,
        'public' => false,
        'show_ui' => true,
        'show_in_quick_edit' => true,      // ← FIX: Zobrazí v admin paneli!
        'hierarchical' => false,
        'show_admin_column' => true,
        'show_in_rest' => false,
        'rewrite' => false
    ]);
}

/* ==========================
   TAXONÓMIA: Kategórie skupín (vekové)
   ========================== */

add_action('init', 'spa_register_taxonomy_categories');

function spa_register_taxonomy_categories() {
    
    $labels = [
        'name' => '🎯 Kategórie',
        'singular_name' => 'Kategória',
        'search_items' => 'Hľadať kategórie',
        'all_items' => 'Všetky kategórie',
        'parent_item' => 'Nadradená kategória',
        'parent_item_colon' => 'Nadradená kategória:',
        'edit_item' => 'Upraviť kategóriu',
        'update_item' => 'Aktualizovať kategóriu',
        'add_new_item' => 'Pridať kategóriu',
        'new_item_name' => 'Nová kategória',
        'menu_name' => 'Kategórie'
    ];

    register_taxonomy('spa_group_category', 'spa_group', [
        'labels' => $labels,
        'hierarchical' => true,
        'show_ui' => true,
        'show_in_quick_edit' => true,      // ← FIX: Zobrazí v admin paneli!
        'show_admin_column' => true,
        'public' => false,
        'show_in_rest' => false,
        'rewrite' => ['slug' => 'skupiny-kategorie']
    ]);
}

/* ==========================
   AUTOMATICKÉ VYTVORENIE ZÁKLADNÝCH TERMOV
   ========================== */

add_action('after_switch_theme', 'spa_create_default_terms');

function spa_create_default_terms() {
    
    // Kontrola či už boli vytvorené
    if (get_option('spa_default_terms_created')) {
        return;
    }
    
    // MIESTA
    $places = ['Malacky', 'Košice'];
    
    foreach ($places as $place) {
        if (!term_exists($place, 'spa_place')) {
            wp_insert_term($place, 'spa_place', [
                'slug' => sanitize_title($place)
            ]);
        }
    }
    
    // KATEGÓRIE
    $categories = [
        'Deti s rodičmi 1,8-3 roky',
        'Deti 3-4 roky',
        'Deti 5-7 rokov',
        'Deti 8-10 rokov',
        'Deti 10+ rokov',
        'Dospelí'
    ];
    
    foreach ($categories as $category) {
        if (!term_exists($category, 'spa_group_category')) {
            wp_insert_term($category, 'spa_group_category', [
                'slug' => sanitize_title($category)
            ]);
        }
    }
    
    // Označ že boli vytvorené
    update_option('spa_default_terms_created', true);
}

/* ==========================
   KATEGÓRIA PRE ČLÁNKY: Udalosti
   ========================== */

add_action('after_switch_theme', 'spa_create_events_category');

function spa_create_events_category() {
    
    if (!term_exists('udalosti', 'category')) {
        wp_insert_term('Udalosti', 'category', [
            'slug' => 'udalosti',
            'description' => 'Tábory, akcie, špeciálne podujatia'
        ]);
    }
}