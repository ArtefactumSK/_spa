<?php
/**
 * spa-cpt.php
 * Registrácia CPT používaných v SPA module
 * @version 2.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/* -------------------------------------------
   CPT: Skupiny tréningov
-------------------------------------------- */
add_action('init', 'spa_register_cpt_groups');
function spa_register_cpt_groups() {
    $labels = array(
        'name'               => 'Tréningové programy',
        'singular_name'      => 'Tréningový program',
        'menu_name'          => 'SPA tréningy',
        'add_new'            => 'Pridať tréningový program',
        'add_new_item'       => 'Pridať nový program',
        'edit_item'          => 'Upraviť program',
        'new_item'           => 'Nový program',
        'view_item'          => 'Zobraziť program',
        'search_items'       => 'Hladať program',
        'not_found'          => 'Žiadne tréningové programy nenájdené',
        'not_found_in_trash' => 'Žiadne programy v koši'
    );

    register_post_type('spa_group', array(
        'labels'            => $labels,
        'public'            => false,
        'show_ui'           => true,
        'menu_icon'         => 'dashicons-universal-access-alt',
        'menu_position'     => 20,
        'hierarchical'      => false,
        'supports'          => array('title'),
        'capability_type'   => 'post',
        'show_in_rest'      => false,
    ));
}

/* -------------------------------------------
   CPT: Registrácie
-------------------------------------------- */
add_action('init', 'spa_register_cpt_registrations');
function spa_register_cpt_registrations() {
    $labels = array(
        'name'                  => 'Registrácie',
        'singular_name'         => 'Registrácia',
        'menu_name'             => 'SPA Registrácie',
        'add_new'               => 'Pridať registráciu',
        'add_new_item'          => 'Pridať novú registráciu',
        'edit_item'             => 'Upraviť registráciu',
        'new_item'              => 'Nová registrácia',
        'view_item'             => 'Zobraziť registráciu',
        'search_items'          => 'Hľadať registrácie',
        'not_found'             => 'Žiadne registrácie nenájdené',
        'not_found_in_trash'    => 'Žiadne registrácie v koši',
        'all_items'             => 'Všetky registrácie',
        'items_list'            => 'Zoznam registrácií'
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

/* -------------------------------------------
   CPT: Obsadenosť hál (hall blocks)
-------------------------------------------- */
add_action('init', 'spa_register_cpt_hall_blocks');
function spa_register_cpt_hall_blocks() {
    $labels = array(
        'name'          => 'Obsadenosť telocvičien',
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
        'menu_icon'         => 'dashicons-calendar-alt',
        'menu_position'     => 22,
        'hierarchical'      => false,
        'supports'          => array('title'),
        'capability_type'   => 'post',
        'capabilities'      => array(
            'edit_post'    => 'edit_posts',
            'delete_post'  => 'delete_posts',
            'edit_posts'   => 'edit_posts',
            'publish_posts'=> 'publish_posts',
        ),
        'show_in_rest' => false,
    ));
}

/* -------------------------------------------
   CPT: Platby
-------------------------------------------- */
add_action('init', 'spa_register_cpt_payments');
function spa_register_cpt_payments() {
    $labels = array(
        'name'               => 'Platby',
        'singular_name'      => 'Platba',
        'menu_name'          => 'SPA Platby',
        'add_new'            => 'Pridať platbu',
        'add_new_item'       => 'Pridať novú platbu',
        'edit_item'          => 'Upraviť platbu',
        'view_item'          => 'Zobraziť platbu',
        'search_items'       => 'Hľadať platby',
        'not_found'          => 'Žiadne platby nenájdene',
        'all_items'          => 'Všetky platby'
    );

    register_post_type('spa_payment', array(
        'labels'            => $labels,
        'public'            => false,
        'show_ui'           => true,
        'menu_icon'         => 'dashicons-money-alt',
        'menu_position'     => 23,
        'hierarchical'      => false,
        'supports'          => array('title'),
        'capability_type'   => 'post',
        'capabilities'      => array(
            'edit_post'    => 'edit_spa_payments',
            'edit_posts'   => 'edit_spa_payments',
            'publish_posts'=> 'edit_spa_payments',
            'read_post'    => 'view_spa_payments',
        ),
        'map_meta_cap' => true,
        'show_in_rest' => false,
    ));
}

/* ==========================
   ADMIN COLUMNS: Registracie
   ========================== */

add_filter('manage_spa_registration_posts_columns', 'spa_cpt_registration_columns');
function spa_cpt_registration_columns($columns) {
    return array(
        'cb'      => $columns['cb'],
        'title'   => 'Nazov',
        'child'   => 'Dieťa / Klient',
        'program' => 'Program',
        'parent'  => 'Rodič',
        'vs'      => 'VS',
        'status'  => 'Status',
        'date'    => 'Dátum'
    );
}

add_action('manage_spa_registration_posts_custom_column', 'spa_registration_column_content', 10, 2);
function spa_registration_column_content($column, $post_id) {
    
    // Nacitaj meta hodnoty
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
                    if (empty($name)) {
                        $name = $user->display_name;
                    }
                    $edit_url = get_edit_user_link($client_id);
                    echo '<a href="' . esc_url($edit_url) . '">' . esc_html($name) . '</a>';
                } else {
                    echo '<span style="color:#999;">-</span>';
                }
            } else {
                echo '<span style="color:#999;">-</span>';
            }
            break;

        case 'program':
            if ($program_id) {
                $program = get_post($program_id);
                if ($program) {
                    echo esc_html($program->post_title);
                } else {
                    echo '<span style="color:#999;">-</span>';
                }
            } else {
                echo '<span style="color:#999;">-</span>';
            }
            break;

        case 'parent':
            if ($parent_id) {
                $parent = get_userdata($parent_id);
                if ($parent) {
                    $name = trim($parent->first_name . ' ' . $parent->last_name);
                    if (empty($name)) {
                        $name = $parent->user_email;
                    }
                    echo '<a href="' . esc_url(get_edit_user_link($parent_id)) . '">' . esc_html($name) . '</a>';
                } else {
                    echo '<span style="color:#999;">-</span>';
                }
            } else {
                echo '<span style="color:#999;">-</span>';
            }
            break;

        case 'vs':
            if ($client_id) {
                $vs = get_user_meta($client_id, 'variabilny_symbol', true);
                if ($vs) {
                    echo '<strong style="font-family:monospace;font-size:13px;">' . esc_html($vs) . '</strong>';
                } else {
                    echo '<span style="color:#999;">-</span>';
                }
            } else {
                echo '<span style="color:#999;">-</span>';
            }
            break;

        case 'status':
            $labels = array(
                'pending'   => array('Caka na schvalenie', '#FFB81C', '#000'),
                'approved'  => array('Schvalene', '#0066FF', '#fff'),
                'active'    => array('Aktivny', '#00C853', '#fff'),
                'cancelled' => array('Zrusene', '#FF1439', '#fff'),
                'completed' => array('Zaregistrovane', '#777', '#fff')
            );
            $label = isset($labels[$status]) ? $labels[$status] : array('Neznamy', '#999', '#fff');
            echo '<span style="background:' . $label[1] . ';color:' . $label[2] . ';padding:3px 8px;border-radius:3px;font-size:11px;">' . $label[0] . '</span>';
            break;
    }
}

// Sortovatelne stlpce
add_filter('manage_edit-spa_registration_sortable_columns', 'spa_registration_sortable_columns');
function spa_registration_sortable_columns($columns) {
    $columns['status'] = 'status';
    return $columns;
}

/* ==========================
   ADMIN COLUMNS: Skupiny treningov
   ========================== */

add_filter('manage_spa_group_posts_columns', 'spa_group_columns');
function spa_group_columns($columns) {
    return array(
        'cb'            => $columns['cb'],
        'title'         => 'Nazov',
        'place'         => 'Miesto',
        'category'      => 'Kategoria',
        'price'         => 'Cena',
        'registrations' => 'Reg.',
        'date'          => 'Datum'
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
                echo '<span style="color:#999;">-</span>';
            }
            break;

        case 'category':
            $cats = get_the_terms($post_id, 'spa_group_category');
            if ($cats && !is_wp_error($cats)) {
                echo esc_html($cats[0]->name);
            } else {
                echo '<span style="color:#999;">-</span>';
            }
            break;

        case 'price':
            $price = get_post_meta($post_id, 'spa_price', true);
            if ($price) {
                echo '<strong>' . number_format(floatval($price), 2, ',', ' ') . ' EUR</strong>';
            } else {
                echo '<span style="color:#999;">-</span>';
            }
            break;

        case 'registrations':
            global $wpdb;
            $count = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->postmeta} WHERE meta_key = 'program_id' AND meta_value = %d",
                $post_id
            ));
            echo '<span style="font-weight:600;">' . intval($count) . '</span>';
            break;
    }
}

/* ==========================
   MENU: Zmena "Pridat registraciu" na externy link
   ========================== */

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