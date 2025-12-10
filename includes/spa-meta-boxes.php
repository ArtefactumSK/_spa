<?php
/**
 * SPA Meta Boxes - Admin formuláre
 * @package Samuel Piasecký ACADEMY
 * @version 1.1.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/* ==========================
   ADD META BOXES
   ========================== */
add_action('add_meta_boxes', 'spa_add_meta_boxes');
function spa_add_meta_boxes() {
    add_meta_box(
        'spa_group_details',            // ID
        'Detaily programu',             // Názov
        'spa_group_details_callback',   // Callback
        'spa_group',                    // CPT
        'normal',                       // Kontext
        'high'                          // Priorita
    );
}

/* ==========================
   META BOX CALLBACK
   ========================== */
function spa_group_details_callback($post) {
    // Bezpečnostný nonce
    wp_nonce_field('spa_save_meta_box', 'spa_meta_box_nonce');

    // Definícia polí s typmi a labelmi
    $fields = [
        'program_name' => ['label'=>'Názov programu', 'type'=>'text'],
        'program_date' => ['label'=>'Dátum programu', 'type'=>'date'],
        'program_location' => ['label'=>'Miesto konania', 'type'=>'text'],
        'program_instructor' => ['label'=>'Lektor', 'type'=>'text'],
        'program_description' => ['label'=>'Popis programu', 'type'=>'textarea'],
        'program_level' => [
            'label'=>'Úroveň',
            'type'=>'select',
            'options'=>[
                ''=>'– vyber –',
                'beginner'=>'Začiatočník',
                'intermediate'=>'Stredne pokročilý',
                'advanced'=>'Pokročilý'
            ]
        ],
    ];

    foreach($fields as $key => $field) {
        $value = get_post_meta($post->ID, $key, true);
        echo '<p>';
        echo '<label for="'.esc_attr($key).'">'.esc_html($field['label']).'</label><br />';
        
        if($field['type'] === 'textarea') {
            echo '<textarea id="'.esc_attr($key).'" name="'.esc_attr($key).'" rows="4" style="width:100%;">'.esc_textarea($value).'</textarea>';
        } elseif($field['type'] === 'select') {
            echo '<select id="'.esc_attr($key).'" name="'.esc_attr($key).'">';
            foreach($field['options'] as $opt_value => $opt_label) {
                $selected = selected($value, $opt_value, false);
                echo '<option value="'.esc_attr($opt_value).'" '.$selected.'>'.esc_html($opt_label).'</option>';
            }
            echo '</select>';
        } else { // text, date
            echo '<input type="'.esc_attr($field['type']).'" id="'.esc_attr($key).'" name="'.esc_attr($key).'" value="'.esc_attr($value).'" style="width:100%;" />';
        }

        echo '</p>';
    }
}

/* ==========================
   SAVE META BOX DATA
   ========================== */
add_action('save_post', 'spa_save_meta_box_data');
function spa_save_meta_box_data($post_id) {
    // Overenie nonce
    if(!isset($_POST['spa_meta_box_nonce']) || !wp_verify_nonce($_POST['spa_meta_box_nonce'], 'spa_save_meta_box')) {
        return;
    }

    // Zabrániť automatickému ukladaniu
    if(defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    // Kontrola oprávnení
    if(!current_user_can('edit_post', $post_id)) {
        return;
    }

    // Polia na uloženie
    $fields = ['program_name','program_date','program_location','program_instructor','program_description','program_level'];

    foreach($fields as $field) {
        if(isset($_POST[$field])) {
            $sanitized = sanitize_text_field($_POST[$field]);
            // textarea môže obsahovať odriadkovanie, takže nahradíme sanitize_textarea_field
            if($field === 'program_description') {
                $sanitized = sanitize_textarea_field($_POST[$field]);
            }
            update_post_meta($post_id, $field, $sanitized);
        }
    }
}
