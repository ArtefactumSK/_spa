<?php
/**
 * SPA Custom Post Types a meta boxy
 * 
 * @package Samuel Piasecký ACADEMY
 * @version 1.3.0
 */

if (!defined('ABSPATH')) exit;

/* ==========================
   CPT: Skupiny tréningov
========================== */
add_action('init', 'spa_register_cpt_groups');
function spa_register_cpt_groups() {
    $labels = [
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
    ];

    register_post_type('spa_group', [
        'labels' => $labels,
        'public' => false,
        'show_ui' => true,
        'menu_icon' => 'dashicons-groups',
        'menu_position' => 20,
        'hierarchical' => false,
        'supports' => ['title'],
        'capability_type' => 'post',
        'show_in_rest' => false,
    ]);
}

/* ==========================
   CPT: Registrácie
========================== */
add_action('init', 'spa_register_cpt_registrations');
function spa_register_cpt_registrations() {
    $labels = [
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
    ];

    register_post_type('spa_registration', [
        'labels' => $labels,
        'public' => false,
        'show_ui' => true,
        'menu_icon' => 'dashicons-clipboard',
        'menu_position' => 21,
        'hierarchical' => false,
        'supports' => ['title'],
        'capability_type' => 'post',
        'show_in_rest' => false,
    ]);
}

/* ==========================
   CPT: Obsadenosť hál
========================== */
add_action('init', 'spa_register_cpt_hall_blocks');
function spa_register_cpt_hall_blocks() {
    $labels = [
        'name' => 'Obsadenosť hál',
        'singular_name' => 'Rezervácia haly',
        'menu_name' => 'Obsadenosť hál',
        'add_new' => 'Pridať rezerváciu',
        'add_new_item' => 'Pridať novú rezerváciu',
        'edit_item' => 'Upraviť rezerváciu',
        'search_items' => 'Hľadať rezervácie'
    ];

    register_post_type('spa_hall_block', [
        'labels' => $labels,
        'public' => false,
        'show_ui' => true,
        'menu_icon' => 'dashicons-calendar-alt',
        'menu_position' => 22,
        'hierarchical' => false,
        'supports' => ['title'],
        'capability_type' => 'post',
        'capabilities' => [
            'edit_post' => 'edit_posts',
            'delete_post' => 'delete_posts',
            'edit_posts' => 'edit_posts',
            'publish_posts' => 'publish_posts'
        ],
        'show_in_rest' => false,
    ]);
}

/* ==========================
   CPT: Platby
========================== */
add_action('init', 'spa_register_cpt_payments');
function spa_register_cpt_payments() {
    $labels = [
        'name' => 'Platby',
        'singular_name' => 'Platba',
        'menu_name' => 'Platby',
        'add_new' => 'Pridať platbu',
        'add_new_item' => 'Pridať novú platbu',
        'edit_item' => 'Upraviť platbu',
        'view_item' => 'Zobraziť platbu',
        'search_items' => 'Hľadať platby',
        'not_found' => 'Žiadne platby nenájdené',
        'all_items' => 'Všetky platby'
    ];

    register_post_type('spa_payment', [
        'labels' => $labels,
        'public' => false,
        'show_ui' => true,
        'menu_icon' => 'dashicons-money-alt',
        'menu_position' => 23,
        'hierarchical' => false,
        'supports' => ['title'],
        'capability_type' => 'post',
        'capabilities' => [
            'edit_post' => 'edit_spa_payments',
            'edit_posts' => 'edit_spa_payments',
            'publish_posts' => 'edit_spa_payments',
            'read_post' => 'view_spa_payments'
        ],
        'map_meta_cap' => true,
        'show_in_rest' => false,
    ]);
}

/* ==========================
   Zakázanie Gutenberg editoru
========================== */
add_filter('use_block_editor_for_post_type', function($use_block_editor, $post_type) {
    $cpts = ['spa_group','spa_registration','spa_hall_block','spa_payment'];
    return in_array($post_type, $cpts) ? false : $use_block_editor;
}, 10, 2);

/* ==========================
   META BOX: Registrácie
========================== */
add_action('add_meta_boxes', 'spa_add_registration_meta_box');
function spa_add_registration_meta_box($post_type) {
    if($post_type !== 'spa_registration') return;
    add_meta_box(
        'spa_registration_details',
        'Detaily registrácie',
        'spa_registration_details_callback',
        'spa_registration',
        'normal',
        'high'
    );
}

function spa_registration_details_callback($post) {
    wp_nonce_field('spa_save_registration_meta', 'spa_registration_nonce');

    $fields = [
        'child_first_name' => ['label'=>'Meno dieťaťa', 'type'=>'text'],
        'child_last_name' => ['label'=>'Priezvisko dieťaťa', 'type'=>'text'],
        'vs_number' => ['label'=>'VS', 'type'=>'text'],
        'program_id' => ['label'=>'Program (ID)', 'type'=>'text'],
        'parent_user_id' => ['label'=>'Rodič (user ID)', 'type'=>'text'],
        'registration_status' => [
            'label'=>'Status',
            'type'=>'select',
            'options'=>[
                'new'=>'Nová registrácia',
                'confirmed'=>'Potvrdená',
                'cancelled'=>'Zrušená'
            ]
        ]
    ];

    foreach($fields as $key=>$field) {
        $value = get_post_meta($post->ID, $key, true);
        if(!$value && $key==='registration_status') $value='new';

        echo '<p><label for="'.esc_attr($key).'">'.esc_html($field['label']).'</label><br />';
        if($field['type']==='select'){
            echo '<select id="'.esc_attr($key).'" name="'.esc_attr($key).'">';
            foreach($field['options'] as $opt_value=>$opt_label){
                $selected = selected($value,$opt_value,false);
                echo '<option value="'.esc_attr($opt_value).'" '.$selected.'>'.esc_html($opt_label).'</option>';
            }
            echo '</select>';
        } else {
            echo '<input type="text" id="'.esc_attr($key).'" name="'.esc_attr($key).'" value="'.esc_attr($value).'" style="width:100%;">';
        }
        echo '</p>';
    }
}

/* ==========================
   SAVE META BOX DATA
========================== */
add_action('save_post', 'spa_save_registration_meta');
function spa_save_registration_meta($post_id){
    if(!isset($_POST['spa_registration_nonce']) || !wp_verify_nonce($_POST['spa_registration_nonce'],'spa_save_registration_meta')) return;
    if(defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if(!current_user_can('edit_post',$post_id)) return;

    $fields = ['child_first_name','child_last_name','vs_number','program_id','parent_user_id','registration_status'];
    foreach($fields as $field){
        if(isset($_POST[$field])){
            update_post_meta($post_id,$field,sanitize_text_field($_POST[$field]));
        }
    }
}

/* ==========================
   ADMIN COLUMNS: Registrácie
========================== */
add_filter('manage_spa_registration_posts_columns', function($columns){
    return [
        'cb'=>$columns['cb'],
        'title'=>'Názov',
        'child'=>'Dieťa',
        'program'=>'Program',
        'vs'=>'VS',
        'status'=>'Status',
        'date'=>$columns['date']
    ];
});

add_action('manage_spa_registration_posts_custom_column', function($column,$post_id){
    switch($column){
        case 'child':
            echo esc_html(get_post_meta($post_id,'child_first_name',true).' '.get_post_meta($post_id,'child_last_name',true));
            break;
        case 'program':
            $program_id = get_post_meta($post_id,'program_id',true);
            $program = get_post($program_id);
            echo $program ? esc_html($program->post_title) : '—';
            break;
        case 'vs':
            echo esc_html(get_post_meta($post_id,'vs_number',true));
            break;
        case 'status':
            echo esc_html(get_post_meta($post_id,'registration_status',true));
            break;
    }
},10,2);
