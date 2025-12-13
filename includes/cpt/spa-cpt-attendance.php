<?php
/**
 * SPA CPT: Attendance - Dochádzka a účasť na tréningoch
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
 * - spa_attendance (Dochádzka)
 * 
 * FUNCTIONS DEFINED:
 * - spa_register_cpt_attendance()
 * 
 * HOOKS USED:
 * - init (registration)
 * 
 * NOTES:
 * Evidencia účasti na tréningoch - FÁZA 3
 * Meta polia: child_id, program_id, date, status
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
        'not_found_in_trash' => 'Žiadne záznamy v koši',
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