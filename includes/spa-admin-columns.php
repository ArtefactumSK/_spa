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

/* add_filter('manage_spa_registration_posts_columns', 'spa_reg_columns');
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
    $client_user_id = get_post_meta($post_id, 'client_user_id', true);
    $parent_user_id = get_post_meta($post_id, 'parent_user_id', true);

    switch ($column) {
        case 'reg_child':
            if ($client_user_id) {
                $child = get_userdata($client_user_id);
                echo $child ? $child->display_name : 'Nezadané';
            } else {
                echo 'Nezadané';
            }
            break;
        case 'reg_program':
            $program_id = get_post_meta($post_id, 'program_id', true);
            echo get_the_title($program_id);
            break;
        case 'reg_parent':
            if ($parent_user_id) {
                $parent = get_userdata($parent_user_id);
                echo $parent ? $parent->display_name . '<br>' . $parent->user_email : 'Nezadané';
            } else {
                echo 'Nezadané';
            }
            break;
        case 'reg_vs':
            if ($client_user_id) {
                $variable_symbol = get_user_meta($client_user_id, 'variabilny_symbol', true);
                echo !empty($variable_symbol) ? $variable_symbol : 'Nezadané';
            } else {
                echo 'Nezadané';
            }
            break;
        case 'reg_status':
            $status = get_post_meta($post_id, 'status', true);
            echo $status == 'active' ? 'Aktívny' : 'Nezaplatené';
            break;
    }
}
 */




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
        'title'       => '🤸🏻‍♂️ Nazov',spa_grp_column_data
        'grp_place'   => '📍Miesto, špec.',
        'grp_cat'     => '☆Kategória',
        'grp_price'   => '💳 Cena',
        'grp_count'   => '✔ Reg.'
        // 'date'        => 'Dátum'
    );
}

add_action('manage_spa_group_posts_custom_column', 'spa_grp_column_data', 10, 2);
function spa_grp_column_data($column, $post_id) {
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

case 'grp_age':
    $age_from = get_post_meta($post_id, 'spa_age_from', true);
    $age_to = get_post_meta($post_id, 'spa_age_to', true);
    if ($age_from && $age_to) {
        echo esc_html($age_from . '-' . $age_to);
    } else {
        echo '-';
    }
    break;

case 'grp_capacity':
    $cap = get_post_meta($post_id, 'spa_capacity', true);
    echo $cap ? intval($cap) : '-';
    break;

case 'grp_schedule':
    $schedule_json = get_post_meta($post_id, 'spa_schedule', true);
    if ($schedule_json) {
        $schedule = json_decode($schedule_json, true);
        $days_sk = ['monday' => 'Pondelok', 'tuesday' => 'Utorok', 'wednesday' => 'Streda', 
                    'thursday' => 'Štvrtok', 'friday' => 'Piatok', 'saturday' => 'Sobota', 'sunday' => 'Nedeľa'];
        $output = [];
        foreach ($schedule as $item) {
            if (!empty($item['day']) && !empty($item['from'])) {
                $day_label = isset($days_sk[$item['day']]) ? $days_sk[$item['day']] : $item['day'];
                $output[] = $day_label . ' od ' . $item['from'];
            }
        }
        echo !empty($output) ? esc_html(implode(', ', $output)) : '-';
    } else {
        echo '-';
    }
    break;

case 'grp_trainers':
    $trainers = get_post_meta($post_id, 'spa_trainers', true);
    if (is_array($trainers) && !empty($trainers)) {
        $names = [];
        foreach ($trainers as $trainer_id) {
            $trainer = get_userdata($trainer_id);
            if ($trainer) {
                $names[] = $trainer->display_name;
            }
        }
        echo !empty($names) ? esc_html(implode(', ', $names)) : '-';
    } else {
        echo '-';
    }
    break;

case 'grp_price':
    $price_1x = get_post_meta($post_id, 'spa_price_1x_weekly', true);
    $price_2x = get_post_meta($post_id, 'spa_price_2x_weekly', true);
    $prices = [];
    if ($price_1x) $prices[] = number_format(floatval($price_1x), 0) . '€';
    if ($price_2x) $prices[] = number_format(floatval($price_2x), 0) . '€';
    echo !empty($prices) ? esc_html(implode(', ', $prices)) : '-';
    break;
    /* 
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
            
    } */
}