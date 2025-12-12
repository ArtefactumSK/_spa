<?php
/**
 * SPA Admin Columns - Stlpce v admin tabulkach
 * 
 * @package Samuel Piasecky ACADEMY
 * @version 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/* ==========================
   REGISTRACIE - STLPCE
   ========================== */

add_action('manage_spa_group_posts_custom_column', 'spa_grp_column_data', 10, 2);
function spa_grp_column_data($column, $post_id) {
    
    switch ($column) {
        case 'grp_city':
            $city = get_post_meta($post_id, 'spa_place_city', true);
            echo $city ? esc_html($city) : '-';
            break;
        
        case 'grp_place':
            $place_id = get_post_meta($post_id, 'spa_place_id', true);
            if ($place_id) {
                $place = get_post($place_id);
                echo $place ? esc_html($place->post_title) : '-';
            } else {
                echo '-';
            }
            break;    
        
            
        case 'grp_place':
            $places = get_the_terms($post_id, 'spa_place');
            if ($places && !is_wp_error($places)) {
                $names = array();
                foreach ($places as $place) {
                    $names[] = $place->name;
                }
                echo esc_html(implode(', ', $names));
            } else {
                echo '-';
            }
            break;
            
        case 'grp_cat':
            $cats = get_the_terms($post_id, 'spa_group_category');
            if ($cats && !is_wp_error($cats)) {
                echo esc_html($cats[0]->name);
            } else {
                echo '-';
            }
            break;
            
        case 'grp_price':
            $price = get_post_meta($post_id, 'spa_price', true);
            if ($price) {
                echo '<strong>' . number_format(floatval($price), 2, ',', ' ') . ' EUR</strong>';
            } else {
                echo '-';
            }
            break;
            
        case 'grp_count':
            global $wpdb;
            $count = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $wpdb->postmeta WHERE meta_key = 'program_id' AND meta_value = %d",
                $post_id
            ));
            echo intval($count);
            break;
    }
}