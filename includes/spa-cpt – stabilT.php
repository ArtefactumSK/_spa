<?php
/**
 * spa-cpt.php
 * Registrácia CPT používaných v SPA module
 * Opravená verzia – obsahuje správne PHP tagy a obalené funkcie
 */

// Bezpečnostné: ak sa súbor náhodou volá priamo
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/* -------------------------------------------
   CPT: Skupiny tréningov
-------------------------------------------- */
add_action( 'init', 'spa_register_cpt_groups' );
function spa_register_cpt_groups() {
    $labels = array(
        'name'               => 'Skupiny tréningov',
        'singular_name'      => 'Skupina',
        'menu_name'          => 'Skupiny tréningov',
        'add_new'            => 'Pridať skupinu',
        'add_new_item'       => 'Pridať novú skupinu',
        'edit_item'          => 'Upraviť skupinu',
        'new_item'           => 'Nová skupina',
        'view_item'          => 'Zobraziť skupinu',
        'search_items'       => 'Hľadať skupiny',
        'not_found'          => 'Žiadne skupiny nenájdené',
        'not_found_in_trash' => 'Žiadne skupiny v koši'
    );

    register_post_type( 'spa_group', array(
        'labels'            => $labels,
        'public'            => false,
        'show_ui'           => true,
        'menu_icon'         => 'dashicons-groups',
        'menu_position'     => 20,
        'hierarchical'      => false,
        'supports'          => array( 'title' ),
        'capability_type'   => 'post',
        'show_in_rest'      => false,
    ) );
}

/* -------------------------------------------
   CPT: Registrácie
-------------------------------------------- */
add_action( 'init', 'spa_register_cpt_registrations' );
function spa_register_cpt_registrations() {
    $labels = array(
        'name'               => 'Registrácie',
        'singular_name'      => 'Registrácia',
        'menu_name'          => 'Registrácie',
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

    register_post_type( 'spa_registration', array(
        'labels'            => $labels,
        'public'            => false,
        'show_ui'           => true,
        'menu_icon'         => 'dashicons-clipboard',
        'menu_position'     => 21,
        'hierarchical'      => false,
        'supports'          => ['title'], 
        'capability_type'   => 'post',
        'show_in_rest'      => false,
    ) );
}

/* -------------------------------------------
   CPT: Obsadenosť hál (hall blocks)
-------------------------------------------- */
add_action( 'init', 'spa_register_cpt_hall_blocks' );
function spa_register_cpt_hall_blocks() {
    $labels = array(
        'name'          => 'Obsadenosť hál',
        'singular_name' => 'Rezervácia haly',
        'menu_name'     => 'Obsadenosť hál',
        'add_new'       => 'Pridať rezerváciu',
        'add_new_item'  => 'Pridať novú rezerváciu',
        'edit_item'     => 'Upraviť rezerváciu',
        'search_items'  => 'Hľadať rezervácie'
    );

    register_post_type( 'spa_hall_block', array(
        'labels'            => $labels,
        'public'            => false,
        'show_ui'           => true,
        'menu_icon'         => 'dashicons-calendar-alt',
        'menu_position'     => 22,
        'hierarchical'      => false,
        'supports'          => array( 'title' ),
        'capability_type'   => 'post',
        'capabilities'      => array(
            'edit_post'    => 'edit_posts',
            'delete_post'  => 'delete_posts',
            'edit_posts'   => 'edit_posts',
            'publish_posts'=> 'publish_posts',
        ),
        'show_in_rest' => false,
    ) );
}

/* -------------------------------------------
   CPT: Platby
-------------------------------------------- */
add_action( 'init', 'spa_register_cpt_payments' );
function spa_register_cpt_payments() {
    $labels = array(
        'name'               => 'Platby',
        'singular_name'      => 'Platba',
        'menu_name'          => 'Platby',
        'add_new'            => 'Pridať platbu',
        'add_new_item'       => 'Pridať novú platbu',
        'edit_item'          => 'Upraviť platbu',
        'view_item'          => 'Zobraziť platbu',
        'search_items'       => 'Hľadať platby',
        'not_found'          => 'Žiadne platby nenájdené',
        'all_items'          => 'Všetky platby'
    );

    register_post_type( 'spa_payment', array(
        'labels'            => $labels,
        'public'            => false,
        'show_ui'           => true,
        'menu_icon'         => 'dashicons-money-alt',
        'menu_position'     => 23,
        'hierarchical'      => false,
        'supports'          => array( 'title' ),
        'capability_type'   => 'post',
        'capabilities'      => array(
            'edit_post'    => 'edit_spa_payments',
            'edit_posts'   => 'edit_spa_payments',
            'publish_posts'=> 'edit_spa_payments',
            'read_post'    => 'view_spa_payments',
        ),
        'map_meta_cap' => true,
        'show_in_rest' => false,
    ) );
}

/* ==========================
   ADMIN COLUMNS: Registrácie
   ========================== */

// Definuj stĺpce
add_filter('manage_spa_registration_posts_columns', 'spa_cpt_registration_columns');
function spa_cpt_registration_columns($columns) {
    $new_columns = [
        'cb' => $columns['cb'],
        'title' => 'Názov',
        'child' => '👶 Dieťa',
        'program' => '🏋️ Program',
        'parent' => '👨‍👩‍👧 Rodič',
        'vs' => 'VS',
        'status' => 'Status',
        'date' => 'Dátum'
    ];
    return $new_columns;
}

// Naplň stĺpce obsahom
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
                    $edit_url = get_edit_user_link($client_id);
                    echo '<a href="' . esc_url($edit_url) . '">' . esc_html($name) . '</a>';
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
                    echo esc_html($program->post_title);
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
                    echo '<a href="' . get_edit_user_link($parent_id) . '">';
                    echo esc_html($parent->user_email);
                    echo '</a>';
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
                    echo '<strong style="font-family: monospace; font-size: 14px;">' . esc_html($vs) . '</strong>';
                } else {
                    echo '<span style="color:#999;">—</span>';
                }
            } else {
                echo '<span style="color:#999;">—</span>';
            }
            break;

        case 'status':
            $labels = [
                'pending' => ['Čaká', '#f0ad4e', '#fff'],
                'approved' => ['Schválené', '#5bc0de', '#fff'],
                'active' => ['Aktívne', '#5cb85c', '#fff'],
                'cancelled' => ['Zrušené', '#d9534f', '#fff'],
                'completed' => ['Dokončené', '#777', '#fff']
            ];

            $label = $labels[$status] ?? ['Neznámy', '#999', '#fff'];
            printf(
                '<span style="background:%s; color:%s; padding:3px 8px; border-radius:3px; font-size:12px;">%s</span>',
                $label[1],
                $label[2],
                $label[0]
            );
            break;
    }
}


// Sortovateľné stĺpce
add_filter('manage_edit-spa_registration_sortable_columns', 'spa_registration_sortable_columns');
function spa_registration_sortable_columns($columns) {
    $columns['status'] = 'status';
    $columns['vs'] = 'vs';
    return $columns;
}

// Sortovanie podľa VS
add_action('pre_get_posts', 'spa_registration_orderby_vs');
function spa_registration_orderby_vs($query) {
    if (!is_admin() || !$query->is_main_query()) {
        return;
    }

    if ($query->get('post_type') !== 'spa_registration') {
        return;
    }

    $orderby = $query->get('orderby');
    if ($orderby === 'status') {
        $query->set('meta_key', 'status');
        $query->set('orderby', 'meta_value');
    }
}

/* ==========================
   ADMIN COLUMNS: Skupiny tréningov
   ========================== */

add_filter('manage_spa_group_posts_columns', 'spa_group_columns');
function spa_group_columns($columns) {
    $new_columns = [
        'cb' => $columns['cb'],
        'title' => 'Názov',
        'place' => '📍 Miesto',
        'category' => '📁 Kategória',
        'price' => '💰 Cena',
        'registrations' => '👥 Registrácií',
        'date' => 'Dátum'
    ];
    return $new_columns;
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

        case 'price':
            $price = get_post_meta($post_id, 'spa_price', true);
            if ($price) {
                echo '<strong>' . number_format($price, 2, ',', ' ') . ' €</strong>';
            } else {
                echo '<span style="color:#999;">—</span>';
            }
            break;

        case 'registrations':
            global $wpdb;
            $count = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->postmeta}
                 WHERE meta_key = 'program_id' AND meta_value = %d",
                $post_id
            ));
            echo '<span style="font-weight:600;">' . intval($count) . '</span>';
            break;
    }
}

