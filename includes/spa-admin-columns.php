<?php
/**
 * SPA Admin Columns - Stĺpce v admin tabulkach
 * ЕДИНÁ VERZIA - všetky admin columns definované tu
 * 
 * @package Samuel Piasecky ACADEMY
 * @version 2.0.0 - ČISTÁ: bez duplicít zo spa-cpt.php
 */

if (!defined('ABSPATH')) {
    exit;
}

/* ==========================
   REGISTRÁCIE - STĹPCE
   ========================== */

add_filter('manage_spa_registration_posts_columns', 'spa_cpt_registration_columns');
function spa_cpt_registration_columns($columns) {
    return array(
        'cb'      => $columns['cb'],
        'title'   => 'Názov',
        'child'   => '👶 Dieťa/Klient',
        'program' => '🤸 Program',
        'parent'  => '👨‍👩‍👧 Rodič',
        'vs'      => 'VS',
        'status'  => 'Status',
        'date'    => 'Dátum'
    );
}

add_action('manage_spa_registration_posts_custom_column', 'spa_registration_column_content', 10, 2);
function spa_registration_column_content($column, $post_id) {
    
    $client_id = get_post_meta($post_id, 'client_user_id', true);
    $program_id = get_post_meta($post_id, 'program_id', true);
    $parent_id = get_post_meta($post_id, 'parent_user_id', true);
    $status = get_post_meta($post_id, 'status', true);

    switch ($column) {
        
        case 'child':
            if ($client_id) {
                $user = get_userdata($client_id);
                if ($user) {
                    $name = trim($user->first_name . ' ' . $user->last_name);
                    if (empty($name)) $name = $user->display_name;
                    echo '<a href="' . esc_url(get_edit_user_link($client_id)) . '">' . esc_html($name) . '</a>';
                } else {
                    echo '<span style="color:#999;">—</span>';
                }
            } else {
                echo '<span style="color:#999;">—</span>';
            }
            break;

        case 'program':
            if ($program_id) {
                $program = get_post($program_id);
                if ($program) {
                    echo '<a href="' . get_edit_post_link($program_id) . '">' . esc_html($program->post_title) . '</a>';
                } else {
                    echo '<span style="color:#999;">—</span>';
                }
            } else {
                echo '<span style="color:#999;">—</span>';
            }
            break;

        case 'parent':
            if ($parent_id) {
                $parent = get_userdata($parent_id);
                if ($parent) {
                    $name = trim($parent->first_name . ' ' . $parent->last_name);
                    if (empty($name)) $name = $parent->user_email;
                    echo '<a href="' . esc_url(get_edit_user_link($parent_id)) . '">' . esc_html($name) . '</a>';
                } else {
                    echo '<span style="color:#999;">—</span>';
                }
            } else {
                echo '<span style="color:#999;">—</span>';
            }
            break;

        case 'vs':
            if ($client_id) {
                $vs = get_user_meta($client_id, 'variabilny_symbol', true);
                if ($vs) {
                    echo '<strong style="font-family:monospace;font-size:13px;">' . esc_html($vs) . '</strong>';
                } else {
                    echo '<span style="color:#999;">—</span>';
                }
            } else {
                echo '<span style="color:#999;">—</span>';
            }
            break;

        case 'status':
            $labels = array(
                'pending'         => array('⏳ Čaká na schválenie', '#FFB81C', '#000'),
                'awaiting_payment'=> array('💰 Čaká na platbu', '#FF9800', '#fff'),
                'partially_paid'  => array('💳 Čiastočne zaplatené', '#2196F3', '#fff'),
                'approved'        => array('✅ Schválené', '#0066FF', '#fff'),
                'active'          => array('🟢 Aktívny', '#00C853', '#fff'),
                'blocked'         => array('🚫 Blokované', '#9E9E9E', '#fff'),
                'cancelled'       => array('❌ Zrušené', '#FF1439', '#fff'),
                'completed'       => array('✔️ Zaregistrované', '#777', '#fff')
            );
            $label = isset($labels[$status]) ? $labels[$status] : array('❓ Neznámy', '#999', '#fff');
            echo '<span style="background:' . $label[1] . ';color:' . $label[2] . ';padding:4px 10px;border-radius:4px;font-size:11px;white-space:nowrap;">' . $label[0] . '</span>';
            break;
    }
}

/* ==========================
   PROGRAMY - STĹPCE (SPA_GROUP)
   ========================== */

add_filter('manage_spa_group_posts_columns', 'spa_group_columns');
function spa_group_columns($columns) {
    return array(
        'cb'           => $columns['cb'],
        'title'        => 'Názov',
        'grp_city'     => 'Mesto',
        'grp_place'    => 'Miesto',
        'grp_age'      => 'Vek',
        'grp_capacity' => 'Kapacita',
        'grp_schedule' => 'Rozvrh',
        'grp_trainers' => 'Tréneri',
        'grp_price'    => 'Cena',
        'grp_count'    => 'Reg.'
    );
}

add_action('manage_spa_group_posts_custom_column', 'spa_group_column_content', 10, 2);
function spa_group_column_content($column, $post_id) {
    switch ($column) {
        
        case 'grp_city':
            $place_id = get_post_meta($post_id, 'spa_place_id', true);
            if ($place_id) {
                $place = get_post($place_id);
                if ($place) {
                    $city = get_post_meta($place->ID, 'spa_place_city', true);
                    echo $city ? esc_html($city) : '-';
                } else {
                    echo '-';
                }
            } else {
                // Fallback na taxonómiu (starý systém)
                $places = get_the_terms($post_id, 'spa_place');
                if ($places && !is_wp_error($places)) {
                    echo esc_html($places[0]->name);
                } else {
                    echo '-';
                }
            }
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
                if (is_array($schedule)) {
                    $days_sk = array(
                        'monday' => 'Pondelok', 'tuesday' => 'Utorok', 'wednesday' => 'Streda',
                        'thursday' => 'Štvrtok', 'friday' => 'Piatok', 'saturday' => 'Sobota', 'sunday' => 'Nedeľa'
                    );
                    $output = array();
                    foreach ($schedule as $item) {
                        if (!empty($item['day']) && !empty($item['from'])) {
                            $day = isset($days_sk[$item['day']]) ? $days_sk[$item['day']] : $item['day'];
                            $output[] = $day . ' od ' . $item['from'];
                        }
                    }
                    echo !empty($output) ? esc_html(implode(', ', $output)) : '-';
                } else {
                    echo '-';
                }
            } else {
                echo '-';
            }
            break;
        
        case 'grp_trainers':
            $trainers = get_post_meta($post_id, 'spa_trainers', true);
            if (is_array($trainers) && !empty($trainers)) {
                $names = array();
                foreach ($trainers as $trainer_id) {
                    $trainer = get_userdata(intval($trainer_id));
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
            $prices = array();
            if ($price_1x) {
                $prices[] = number_format(floatval($price_1x), 0) . '€';
            }
            if ($price_2x) {
                $prices[] = number_format(floatval($price_2x), 0) . '€';
            }
            echo !empty($prices) ? esc_html(implode(', ', $prices)) : '-';
            break;
        
        case 'grp_count':
            global $wpdb;
            $count = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->postmeta} WHERE meta_key = 'program_id' AND meta_value = %d",
                $post_id
            ));
            $color = $count > 0 ? '#00C853' : '#999';
            echo '<span style="font-weight:600;color:' . $color . ';">' . intval($count) . '</span>';
            break;
    }
}

/* ==========================
   MIESTA - STĹPCE (SPA_PLACE)
   ========================== */

add_filter('manage_spa_place_posts_columns', 'spa_place_columns');
function spa_place_columns($columns) {
    return array(
        'cb'        => $columns['cb'],
        'title'     => '📍 Názov miesta',
        'type'      => 'Typ',
        'address'   => 'Adresa',
        'programs'  => 'Programov',
        'date'      => 'Dátum'
    );
}

add_action('manage_spa_place_posts_custom_column', 'spa_place_column_content', 10, 2);
function spa_place_column_content($column, $post_id) {
    switch ($column) {
        case 'type':
            $type = get_post_meta($post_id, 'spa_place_type', true);
            $types = array(
                'spa' => '🏠 Priestory SPA',
                'external' => '🏫 Externé priestory'
            );
            echo isset($types[$type]) ? $types[$type] : '-';
            break;

        case 'address':
            $address = get_post_meta($post_id, 'spa_place_address', true);
            echo $address ? esc_html($address) : '-';
            break;

        case 'programs':
            global $wpdb;
            $count = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->postmeta} WHERE meta_key = 'spa_place_id' AND meta_value = %d",
                $post_id
            ));
            echo '<span style="font-weight:600;">' . intval($count) . '</span>';
            break;
    }
}

/* ==========================
   UDALOSTI - STĹPCE (SPA_EVENT)
   ========================== */

add_filter('manage_spa_event_posts_columns', 'spa_event_columns');
function spa_event_columns($columns) {
    return array(
        'cb'         => $columns['cb'],
        'title'      => '📅 Názov udalosti',
        'place'      => '📍 Miesto',
        'date_range' => '📆 Dátum',
        'time_range' => '⏰ Čas',
        'type'       => 'Typ',
        'date'       => 'Vytvorené'
    );
}

add_action('manage_spa_event_posts_custom_column', 'spa_event_column_content', 10, 2);
function spa_event_column_content($column, $post_id) {
    switch ($column) {
        case 'place':
            $place_id = get_post_meta($post_id, 'spa_event_place_id', true);
            if ($place_id) {
                $place = get_post($place_id);
                echo $place ? esc_html($place->post_title) : '-';
            } else {
                echo '-';
            }
            break;

        case 'date_range':
            $date_from = get_post_meta($post_id, 'spa_event_date_from', true);
            $date_to = get_post_meta($post_id, 'spa_event_date_to', true);
            if ($date_from) {
                $output = date_i18n('j.n.Y', strtotime($date_from));
                if ($date_to && $date_to !== $date_from) {
                    $output .= ' – ' . date_i18n('j.n.Y', strtotime($date_to));
                }
                echo $output;
            } else {
                echo '-';
            }
            break;

        case 'time_range':
            $time_from = get_post_meta($post_id, 'spa_event_time_from', true);
            $time_to = get_post_meta($post_id, 'spa_event_time_to', true);
            if ($time_from) {
                echo esc_html($time_from);
                if ($time_to) echo ' – ' . esc_html($time_to);
            } else {
                echo 'Celý deň';
            }
            break;

        case 'type':
            $type = get_post_meta($post_id, 'spa_event_type', true);
            $types = array(
                'block' => '🚫 Blokovanie',
                'event' => '🎉 Udalosť',
                'competition' => '🏆 Súťaž',
                'holiday' => '🎄 Sviatok'
            );
            echo isset($types[$type]) ? $types[$type] : '-';
            break;
    }
}

/* ==========================
   DOCHÁDZKA - STĹPCE (SPA_ATTENDANCE)
   ========================== */

add_filter('manage_spa_attendance_posts_columns', 'spa_attendance_columns');
function spa_attendance_columns($columns) {
    return array(
        'cb'        => $columns['cb'],
        'title'     => '✅ Záznam',
        'client'    => '👤 Klient',
        'program'   => '🤸 Program',
        'att_date'  => '📅 Dátum tréningu',
        'status'    => 'Status',
        'rating'    => '⭐ Hodnotenie',
        'date'      => 'Vytvorené'
    );
}

add_action('manage_spa_attendance_posts_custom_column', 'spa_attendance_column_content', 10, 2);
function spa_attendance_column_content($column, $post_id) {
    switch ($column) {
        case 'client':
            $client_id = get_post_meta($post_id, 'spa_att_client_id', true);
            if ($client_id) {
                $user = get_userdata($client_id);
                if ($user) {
                    $name = trim($user->first_name . ' ' . $user->last_name);
                    if (empty($name)) $name = $user->display_name;
                    echo '<a href="' . esc_url(get_edit_user_link($client_id)) . '">' . esc_html($name) . '</a>';
                } else {
                    echo '-';
                }
            } else {
                echo '-';
            }
            break;

        case 'program':
            $program_id = get_post_meta($post_id, 'spa_att_program_id', true);
            if ($program_id) {
                $program = get_post($program_id);
                echo $program ? esc_html($program->post_title) : '-';
            } else {
                echo '-';
            }
            break;

        case 'att_date':
            $date = get_post_meta($post_id, 'spa_att_date', true);
            echo $date ? date_i18n('j.n.Y', strtotime($date)) : '-';
            break;

        case 'status':
            $status = get_post_meta($post_id, 'spa_att_status', true);
            $statuses = array(
                'present'   => array('✅ Prítomný', '#00C853'),
                'absent'    => array('❌ Neprítomný', '#FF1439'),
                'excused'   => array('📝 Ospravedlnený', '#FFB81C'),
                'late'      => array('⏰ Meškanie', '#FF9800')
            );
            $s = isset($statuses[$status]) ? $statuses[$status] : array('❓ Neznámy', '#999');
            echo '<span style="color:' . $s[1] . ';font-weight:600;">' . $s[0] . '</span>';
            break;

        case 'rating':
            $stars = get_post_meta($post_id, 'spa_att_stars', true);
            if ($stars) {
                $output = '';
                for ($i = 1; $i <= 5; $i++) {
                    $output .= ($i <= $stars) ? '⭐' : '☆';
                }
                echo $output;
            } else {
                echo '-';
            }
            break;
    }
}