<?php
/**
 * spa-cpt.php
 * Registrácia CPT používaných v SPA module
 * @version 2.0.0 - FÁZA 1: Nové CPT podľa AKČNÉHO PLÁNU
 */

if (!defined('ABSPATH')) {
    exit;
}

/* ============================================================
   CPT: spa_group (Programy/Skupiny tréningov)
   EXISTUJÚCI - ponechaný, rozšírený o nové polia
   ============================================================ */
add_action('init', 'spa_register_cpt_groups');
function spa_register_cpt_groups() {
    $labels = array(
        'name'               => '🤸 Programy',
        'singular_name'      => 'Program',
        'menu_name'          => 'SPA Programy',
        'add_new'            => 'Pridať program',
        'add_new_item'       => 'Pridať nový program',
        'edit_item'          => 'Upraviť program',
        'new_item'           => 'Nový program',
        'view_item'          => 'Zobraziť program',
        'search_items'       => 'Hľadať programy',
        'not_found'          => 'Žiadne programy nenájdené',
        'not_found_in_trash' => 'Žiadne programy v koši'
    );

    register_post_type('spa_group', array(
        'labels'            => $labels,
        'public'            => false,
        'show_ui'           => true,
        'menu_icon'         => 'dashicons-universal-access-alt',
        'menu_position'     => 20,
        'hierarchical'      => false,
        'supports'          => array('title', 'editor'),
        'capability_type'   => 'post',
        'show_in_rest'      => false,
    ));
}

/* ============================================================
   CPT: spa_registration (Registrácie)
   EXISTUJÚCI - ponechaný, rozšírený o nové polia
   ============================================================ */
add_action('init', 'spa_register_cpt_registrations');
function spa_register_cpt_registrations() {
    $labels = array(
        'name'               => '📋 Registrácie',
        'singular_name'      => 'Registrácia',
        'menu_name'          => 'SPA Registrácie',
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

    register_post_type('spa_registration', array(
        'labels'            => $labels,
        'public'            => false,
        'show_ui'           => true,
        'menu_icon'         => 'dashicons-clipboard',
        'menu_position'     => 21,
        'hierarchical'      => false,
        'supports'          => array('title'),
        'capability_type'   => 'post',
        'show_in_rest'      => false,
    ));
}

/* ============================================================
   CPT: spa_place (Miesto) - NOVÉ!
   Nahrádza taxonómiu spa_place ako hlavný zdroj
   Taxonómia zostáva pre spätnú kompatibilitu
   ============================================================ */
add_action('init', 'spa_register_cpt_place');
function spa_register_cpt_place() {
    $labels = array(
        'name'               => '📍 Miesta',
        'singular_name'      => 'Miesto',
        'menu_name'          => 'SPA Miesta',
        'add_new'            => 'Pridať miesto',
        'add_new_item'       => 'Pridať nové miesto',
        'edit_item'          => 'Upraviť miesto',
        'new_item'           => 'Nové miesto',
        'view_item'          => 'Zobraziť miesto',
        'search_items'       => 'Hľadať miesta',
        'not_found'          => 'Žiadne miesta nenájdené',
        'not_found_in_trash' => 'Žiadne miesta v koši',
        'all_items'          => 'Všetky miesta'
    );

    register_post_type('spa_place', array(
        'labels'            => $labels,
        'public'            => false,
        'show_ui'           => true,
        'menu_icon'         => 'dashicons-location',
        'menu_position'     => 24,
        'hierarchical'      => false,
        'supports'          => array('title'),
        'capability_type'   => 'post',
        'show_in_rest'      => false,
    ));
}

/* ============================================================
   CPT: spa_event (Udalosť/Blokovanie) - NOVÉ!
   Slúži na blokovanie priestoru jednorazovo alebo opakovane
   ============================================================ */
add_action('init', 'spa_register_cpt_event');
function spa_register_cpt_event() {
    $labels = array(
        'name'               => '📅 Udalosti',
        'singular_name'      => 'Udalosť',
        'menu_name'          => 'SPA Udalosti',
        'add_new'            => 'Pridať udalosť',
        'add_new_item'       => 'Pridať novú udalosť',
        'edit_item'          => 'Upraviť udalosť',
        'new_item'           => 'Nová udalosť',
        'view_item'          => 'Zobraziť udalosť',
        'search_items'       => 'Hľadať udalosti',
        'not_found'          => 'Žiadne udalosti nenájdené',
        'not_found_in_trash' => 'Žiadne udalosti v koši',
        'all_items'          => 'Všetky udalosti'
    );

    register_post_type('spa_event', array(
        'labels'            => $labels,
        'public'            => false,
        'show_ui'           => true,
        'menu_icon'         => 'dashicons-calendar-alt',
        'menu_position'     => 25,
        'hierarchical'      => false,
        'supports'          => array('title', 'editor'),
        'capability_type'   => 'post',
        'show_in_rest'      => false,
    ));
}

/* ============================================================
   CPT: spa_attendance (Dochádzka) - NOVÉ!
   Evidencia účasti na tréningoch
   ============================================================ */
add_action('init', 'spa_register_cpt_attendance');
function spa_register_cpt_attendance() {
    $labels = array(
        'name'               => '✅ Dochádzka',
        'singular_name'      => 'Záznam dochádzky',
        'menu_name'          => 'SPA Dochádzka',
        'add_new'            => 'Pridať záznam',
        'add_new_item'       => 'Pridať záznam dochádzky',
        'edit_item'          => 'Upraviť záznam',
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

/* ============================================================
   CPT: spa_payment (Platby)
   EXISTUJÚCI - ponechaný
   ============================================================ */
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

/* ============================================================
   CPT: spa_hall_block (Obsadenosť hál)
   EXISTUJÚCI - ponechaný pre spätnú kompatibilitu
   Bude nahradený spa_event v budúcnosti
   ============================================================ */
add_action('init', 'spa_register_cpt_hall_blocks');
function spa_register_cpt_hall_blocks() {
    $labels = array(
        'name'          => '🏟️ Obsadenosť telocvičien',
        'singular_name' => 'Rezervácia  telocvične',
        'menu_name'     => 'SPA telocvične',
        'add_new'       => 'Pridať rezerváciu',
        'add_new_item'  => 'Rezervovať telocvičňu',
        'edit_item'     => 'Upraviť rezerváciu',
        'search_items'  => 'Hľadat rezervácie'
    );

    register_post_type('spa_hall_block', array(
        'labels'            => $labels,
        'public'            => false,
        'show_ui'           => true,
        'show_in_menu'      => false, // Skryté - nahradené spa_event
        'menu_position'     => 28,
        'hierarchical'      => false,
        'supports'          => array('title'),
        'capability_type'   => 'post',
        'show_in_rest'      => false,
    ));
}

/* ============================================================
   ADMIN COLUMNS: Registrácie
   ============================================================ */
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

/* ============================================================
   ADMIN COLUMNS: Programy (spa_group)
   ============================================================ */
add_filter('manage_spa_group_posts_columns', 'spa_group_columns');
function spa_group_columns($columns) {
    return array(
        'cb'            => $columns['cb'],
        'title'         => '🤸 Názov',
        'place'         => '📍 Miesto',
        'category'      => '📁 Kategória',
        'schedule'      => '📅 Rozvrh',
        'price'         => '💰 Cena',
        'registrations' => '👥 Reg.',
        'date'          => 'Dátum'
    );
}

add_action('manage_spa_group_posts_custom_column', 'spa_group_column_content', 10, 2);
function spa_group_column_content($column, $post_id) {
    switch ($column) {
        case 'place':
            $places = get_the_terms($post_id, 'spa_place');
            if ($places && !is_wp_error($places)) {
                $names = wp_list_pluck($places, 'name');
                echo esc_html(implode(', ', $names));
            } else {
                echo '<span style="color:#999;">—</span>';
            }
            break;

        case 'category':
            $cats = get_the_terms($post_id, 'spa_group_category');
            if ($cats && !is_wp_error($cats)) {
                echo esc_html($cats[0]->name);
            } else {
                echo '<span style="color:#999;">—</span>';
            }
            break;

        case 'schedule':
            $schedule_json = get_post_meta($post_id, 'spa_schedule', true);
            if ($schedule_json) {
                $schedule = json_decode($schedule_json, true);
                if (is_array($schedule) && !empty($schedule)) {
                    $days_sk = array(
                        'monday' => 'Po', 'tuesday' => 'Ut', 'wednesday' => 'St',
                        'thursday' => 'Št', 'friday' => 'Pi', 'saturday' => 'So', 'sunday' => 'Ne'
                    );
                    $parts = array();
                    foreach ($schedule as $row) {
                        if (!empty($row['day']) && !empty($row['time'])) {
                            $day_label = isset($days_sk[$row['day']]) ? $days_sk[$row['day']] : $row['day'];
                            $parts[] = $day_label . ' ' . $row['time'];
                        }
                    }
                    echo '<span style="font-size:12px;">' . esc_html(implode(', ', $parts)) . '</span>';
                } else {
                    echo '<span style="color:#999;">—</span>';
                }
            } else {
                echo '<span style="color:#999;">—</span>';
            }
            break;

        case 'price':
            $price = get_post_meta($post_id, 'spa_price', true);
            if ($price) {
                echo '<strong>' . number_format(floatval($price), 2, ',', ' ') . ' €</strong>';
            } else {
                echo '<span style="color:#999;">—</span>';
            }
            break;

        case 'registrations':
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

/* ============================================================
   ADMIN COLUMNS: Miesta (spa_place)
   ============================================================ */
add_filter('manage_spa_place_posts_columns', 'spa_place_columns');
function spa_place_columns($columns) {
    return array(
        'cb'        => $columns['cb'],
        'title'     => '📍 Názov miesta',
        'type'      => 'Typ',
        'address'   => 'Adresa',
        'programs'  => '🤸 Programov',
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
            echo isset($types[$type]) ? $types[$type] : '<span style="color:#999;">—</span>';
            break;

        case 'address':
            $address = get_post_meta($post_id, 'spa_place_address', true);
            echo $address ? esc_html($address) : '<span style="color:#999;">—</span>';
            break;

        case 'programs':
            // Spočítaj programy prepojené na toto miesto
            // Zatiaľ cez taxonómiu (spätná kompatibilita)
            $term = get_term_by('name', get_the_title($post_id), 'spa_place');
            if ($term) {
                $count = $term->count;
                echo '<span style="font-weight:600;">' . intval($count) . '</span>';
            } else {
                echo '<span style="color:#999;">0</span>';
            }
            break;
    }
}

/* ============================================================
   ADMIN COLUMNS: Udalosti (spa_event)
   ============================================================ */
add_filter('manage_spa_event_posts_columns', 'spa_event_columns');
function spa_event_columns($columns) {
    return array(
        'cb'        => $columns['cb'],
        'title'     => '📅 Názov udalosti',
        'place'     => '📍 Miesto',
        'date_range'=> '📆 Dátum',
        'time_range'=> '⏰ Čas',
        'type'      => 'Typ',
        'date'      => 'Vytvorené'
    );
}

add_action('manage_spa_event_posts_custom_column', 'spa_event_column_content', 10, 2);
function spa_event_column_content($column, $post_id) {
    switch ($column) {
        case 'place':
            $place_id = get_post_meta($post_id, 'spa_event_place_id', true);
            if ($place_id) {
                $place = get_post($place_id);
                echo $place ? esc_html($place->post_title) : '<span style="color:#999;">—</span>';
            } else {
                echo '<span style="color:#999;">—</span>';
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
                echo '<span style="color:#999;">—</span>';
            }
            break;

        case 'time_range':
            $time_from = get_post_meta($post_id, 'spa_event_time_from', true);
            $time_to = get_post_meta($post_id, 'spa_event_time_to', true);
            if ($time_from) {
                echo esc_html($time_from);
                if ($time_to) echo ' – ' . esc_html($time_to);
            } else {
                echo '<span style="color:#999;">Celý deň</span>';
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
            echo isset($types[$type]) ? $types[$type] : '<span style="color:#999;">—</span>';
            break;
    }
}

/* ============================================================
   ADMIN COLUMNS: Dochádzka (spa_attendance)
   ============================================================ */
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
                    echo '<span style="color:#999;">—</span>';
                }
            } else {
                echo '<span style="color:#999;">—</span>';
            }
            break;

        case 'program':
            $program_id = get_post_meta($post_id, 'spa_att_program_id', true);
            if ($program_id) {
                $program = get_post($program_id);
                echo $program ? esc_html($program->post_title) : '<span style="color:#999;">—</span>';
            } else {
                echo '<span style="color:#999;">—</span>';
            }
            break;

        case 'att_date':
            $date = get_post_meta($post_id, 'spa_att_date', true);
            echo $date ? date_i18n('j.n.Y', strtotime($date)) : '<span style="color:#999;">—</span>';
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
		    $rating = get_post_meta($post_id, 'spa_att_rating', true);
		    $stars = get_post_meta($post_id, 'spa_att_stars', true);
		    if ($stars) {
			$output = '';
			for ($i = 1; $i <= 5; $i++) {
			    $output .= ($i <= $stars) ? '⭐' : '☆';
			}
			echo $output;
		    } else {
			echo '<span style="color:#999;">—</span>';
		    }
		    break;
	    }
	}

/* ============================================================
   MENU: Zmena "Pridať registráciu" na externý link
   ============================================================ */
add_action('admin_menu', 'spa_fix_registration_submenu', 999);
function spa_fix_registration_submenu() {
    global $submenu;
    
    if (isset($submenu['edit.php?post_type=spa_registration'])) {
        foreach ($submenu['edit.php?post_type=spa_registration'] as $key => $item) {
            if (isset($item[2]) && strpos($item[2], 'post-new.php') !== false) {
                unset($submenu['edit.php?post_type=spa_registration'][$key]);
            }
        }
    }
    
    add_submenu_page(
        'edit.php?post_type=spa_registration',
        'Pridať registráciu',
        'Pridať registráciu',
        'edit_posts',
        'spa-add-registration-redirect',
        '__return_null'
    );
}

add_action('admin_init', 'spa_handle_registration_redirect');
function spa_handle_registration_redirect() {
    if (isset($_GET['page']) && $_GET['page'] === 'spa-add-registration-redirect') {
        wp_redirect(home_url('/registracia/'));
        exit;
    }
}

add_action('admin_footer', 'spa_registration_menu_target_blank');
function spa_registration_menu_target_blank() {
    $url = esc_url(home_url('/registracia/'));
    ?>
    <script type="text/javascript">
    (function() {
        var links = document.querySelectorAll('a[href*="spa-add-registration-redirect"]');
        links.forEach(function(link) {
            link.setAttribute('href', '<?php echo $url; ?>');
            link.setAttribute('target', '_blank');
        });
        var addBtn = document.querySelector('.page-title-action[href*="post-new.php?post_type=spa_registration"]');
        if (addBtn) {
            addBtn.setAttribute('href', '<?php echo $url; ?>');
            addBtn.setAttribute('target', '_blank');
        }
    })();
    </script>
    <?php
}