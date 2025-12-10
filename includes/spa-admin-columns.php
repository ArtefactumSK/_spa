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

add_filter('manage_spa_registration_posts_columns', 'spa_reg_columns');
function spa_reg_columns($columns) {
    return array(
        'cb'      => $columns['cb'],
        'title'   => 'Nazov',
        'reg_child'   => 'Dieta',
        'reg_program' => 'Program',
        'reg_parent'  => 'Rodic',
        'reg_vs'      => 'VS',
        'reg_status'  => 'Status',
        'date'    => 'Datum'
    );
}

add_action('manage_spa_registration_posts_custom_column', 'spa_reg_column_data', 10, 2);
function spa_reg_column_data($column, $post_id) {
    switch ($column) {
        case 'reg_child':
            echo get_post_meta($post_id, '_spa_child_name', true);
            break;
        case 'reg_program':
            echo get_post_meta($post_id, '_spa_program', true);
            break;
        case 'reg_parent':
            $parent_name = get_post_meta($post_id, '_spa_parent_name', true);
            $parent_email = get_post_meta($post_id, '_spa_parent_email', true);
            echo $parent_name . '<br>' . $parent_email;
            break;
        case 'reg_vs':
            echo get_post_meta($post_id, '_spa_variable_symbol', true);
            break;
        case 'reg_status':
            $status = get_post_meta($post_id, '_spa_payment_status', true);
            echo $status ? $status : 'Nezaplatené';
            break;
    }
}

/*
function spa_reg_column_data($column, $post_id) {
    
    switch ($column) {
        
        case 'reg_child':
            $client_id = get_post_meta($post_id, 'client_user_id', true);
            if ($client_id) {
                $user = get_userdata($client_id);
                if ($user) {
                    $name = trim($user->first_name . ' ' . $user->last_name);
                    if (empty($name)) {
                        $name = $user->display_name;
                    }
                    echo '<a href="' . esc_url(get_edit_user_link($client_id)) . '">' . esc_html($name) . '</a>';
                } else {
                    echo '-';
                }
            } else {
                echo '-';
            }
            break;
            
        case 'reg_program':
            $program_id = get_post_meta($post_id, 'program_id', true);
            if ($program_id) {
                $program = get_post($program_id);
                if ($program) {
                    echo esc_html($program->post_title);
                } else {
                    echo '-';
                }
            } else {
                echo '-';
            }
            break;
            
        case 'reg_parent':
            $parent_id = get_post_meta($post_id, 'parent_user_id', true);
            if ($parent_id) {
                $parent = get_userdata($parent_id);
                if ($parent) {
                    echo '<a href="' . esc_url(get_edit_user_link($parent_id)) . '">' . esc_html($parent->user_email) . '</a>';
                } else {
                    echo '-';
                }
            } else {
                echo '-';
            }
            break;
            
        case 'reg_vs':
            $client_id = get_post_meta($post_id, 'client_user_id', true);
            if ($client_id) {
                $vs = get_user_meta($client_id, 'variabilny_symbol', true);
                if ($vs) {
                    echo '<strong>' . esc_html($vs) . '</strong>';
                } else {
                    echo '-';
                }
            } else {
                echo '-';
            }
            break;
            
        case 'reg_status':
            $status = get_post_meta($post_id, 'status', true);
            $colors = array(
                'pending'   => '#f0ad4e',
                'approved'  => '#5bc0de',
                'active'    => '#5cb85c',
                'cancelled' => '#d9534f',
                'completed' => '#777'
            );
            $names = array(
                'pending'   => 'Caka',
                'approved'  => 'Schvalene',
                'active'    => 'Aktivne',
                'cancelled' => 'Zrusene',
                'completed' => 'Dokoncene'
            );
            $color = isset($colors[$status]) ? $colors[$status] : '#999';
            $name = isset($names[$status]) ? $names[$status] : 'Neznamy';
            echo '<span style="background:' . $color . ';color:#fff;padding:3px 8px;border-radius:3px;font-size:11px;">' . $name . '</span>';
            break;
    }
}
*/

/* ==========================
   SKUPINY - STLPCE
   ========================== */

add_filter('manage_spa_group_posts_columns', 'spa_grp_columns');
function spa_grp_columns($columns) {
    return array(
        'cb'          => $columns['cb'],
        'title'       => 'Nazov',
        'grp_place'   => 'Miesto',
        'grp_cat'     => 'Kategoria',
        'grp_price'   => 'Cena',
        'grp_count'   => 'Pocet',
        'date'        => 'Datum'
    );
}

add_action('manage_spa_group_posts_custom_column', 'spa_grp_column_data', 10, 2);
function spa_grp_column_data($column, $post_id) {
    
    switch ($column) {
        
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