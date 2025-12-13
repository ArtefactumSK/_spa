<?php
/**
 * SPA Meta Boxes - Minimálna verzia
 * Iba pre spa_group (programy)
 * 
 * @package Samuel Piasecký ACADEMY
 * @subpackage Admin
 * @version 1.0.0-MINIMAL
 */

if (!defined('ABSPATH')) {
    exit;
}

/* ==================================================
   REGISTER: Meta boxy pre spa_group
   ================================================== */

add_action('add_meta_boxes_spa_group', 'spa_add_group_meta_boxes');

function spa_add_group_meta_boxes() {
    add_meta_box(
        'spa_group_basic',
        '📋 Základné informácie',
        'spa_render_group_basic',
        'spa_group',
        'normal',
        'high'
    );
    
    add_meta_box(
        'spa_group_taxonomies',
        '📍 Miesta a Kategórie',
        'spa_render_group_taxonomies',
        'spa_group',
        'normal',
        'high'
    );
    
    add_meta_box(
        'spa_group_schedule',
        '⏰ Rozvrh a Kapacita',
        'spa_render_group_schedule',
        'spa_group',
        'normal',
        'high'
    );
}

/* ==================================================
   RENDER: Základné info
   ================================================== */

function spa_render_group_basic($post) {
    $price = get_post_meta($post->ID, 'spa_price', true);
    $frequency = get_post_meta($post->ID, 'spa_frequency', true);
    $trainer = get_post_meta($post->ID, 'spa_trainer', true);
    ?>
    <table class="form-table">
        <tr>
            <th><label for="spa_price">Cena (€):</label></th>
            <td>
                <input type="number" name="spa_price" id="spa_price" 
                       value="<?php echo esc_attr($price); ?>" 
                       step="0.01" class="regular-text">
            </td>
        </tr>
        <tr>
            <th><label for="spa_frequency">Frekvencia:</label></th>
            <td>
                <input type="text" name="spa_frequency" id="spa_frequency" 
                       value="<?php echo esc_attr($frequency); ?>" 
                       class="regular-text"
                       placeholder="napr. týždenne, mesačne">
            </td>
        </tr>
        <tr>
            <th><label for="spa_trainer">Tréner:</label></th>
            <td>
                <input type="text" name="spa_trainer" id="spa_trainer" 
                       value="<?php echo esc_attr($trainer); ?>" 
                       class="regular-text">
            </td>
        </tr>
    </table>
    <?php
    wp_nonce_field('spa_group_nonce', 'spa_group_nonce_field');
}

/* ==================================================
   RENDER: Taxonómie (Miesta, Kategórie)
   ================================================== */

function spa_render_group_taxonomies($post) {
    ?>
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
        
        <!-- Miesta -->
        <div>
            <h4>📍 Miesta:</h4>
            <?php
            $terms = get_terms([
                'taxonomy' => 'spa_place',
                'hide_empty' => false
            ]);
            
            $selected_terms = wp_get_post_terms($post->ID, 'spa_place', ['fields' => 'ids']);
            
            foreach ($terms as $term) {
                $checked = in_array($term->term_id, $selected_terms) ? 'checked' : '';
                echo sprintf(
                    '<label style="display: block; margin: 5px 0;">
                        <input type="checkbox" name="spa_place[]" value="%d" %s>
                        %s
                    </label>',
                    $term->term_id,
                    $checked,
                    esc_html($term->name)
                );
            }
            ?>
        </div>
        
        <!-- Kategórie -->
        <div>
            <h4>🎯 Kategórie:</h4>
            <?php
            $terms = get_terms([
                'taxonomy' => 'spa_group_category',
                'hide_empty' => false
            ]);
            
            $selected_terms = wp_get_post_terms($post->ID, 'spa_group_category', ['fields' => 'ids']);
            
            foreach ($terms as $term) {
                $checked = in_array($term->term_id, $selected_terms) ? 'checked' : '';
                echo sprintf(
                    '<label style="display: block; margin: 5px 0;">
                        <input type="checkbox" name="spa_group_category[]" value="%d" %s>
                        %s
                    </label>',
                    $term->term_id,
                    $checked,
                    esc_html($term->name)
                );
            }
            ?>
        </div>
        
    </div>
    <?php
}

/* ==================================================
   RENDER: Rozvrh a Kapacita
   ================================================== */

function spa_render_group_schedule($post) {
    $capacity = get_post_meta($post->ID, 'spa_capacity', true);
    $schedule = get_post_meta($post->ID, 'spa_schedule', true);
    ?>
    <table class="form-table">
        <tr>
            <th><label for="spa_capacity">Kapacita (počet detí):</label></th>
            <td>
                <input type="number" name="spa_capacity" id="spa_capacity" 
                       value="<?php echo esc_attr($capacity); ?>" 
                       min="1" class="regular-text">
            </td>
        </tr>
        <tr>
            <th><label for="spa_schedule">Rozvrh (dni a časy):</label></th>
            <td>
                <textarea name="spa_schedule" id="spa_schedule" 
                          rows="4" class="large-text"><?php echo esc_textarea($schedule); ?></textarea>
                <p class="description">napr. Pondelok 16:00-17:00, Streda 17:00-18:00</p>
            </td>
        </tr>
    </table>
    <?php
}

/* ==================================================
   SAVE: Meta boxy
   ================================================== */

add_action('save_post_spa_group', 'spa_save_group_meta');

function spa_save_group_meta($post_id) {
    // Validácia nonce
    if (!isset($_POST['spa_group_nonce_field']) || 
        !wp_verify_nonce($_POST['spa_group_nonce_field'], 'spa_group_nonce')) {
        return;
    }
    
    // Autosave check
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    
    // Permission check
    if (!current_user_can('edit_post', $post_id)) {
        return;
    }
    
    // SAVE: Meta fieldy
    if (isset($_POST['spa_price'])) {
        update_post_meta($post_id, 'spa_price', floatval($_POST['spa_price']));
    }
    
    if (isset($_POST['spa_frequency'])) {
        update_post_meta($post_id, 'spa_frequency', sanitize_text_field($_POST['spa_frequency']));
    }
    
    if (isset($_POST['spa_trainer'])) {
        update_post_meta($post_id, 'spa_trainer', sanitize_text_field($_POST['spa_trainer']));
    }
    
    if (isset($_POST['spa_capacity'])) {
        update_post_meta($post_id, 'spa_capacity', intval($_POST['spa_capacity']));
    }
    
    if (isset($_POST['spa_schedule'])) {
        update_post_meta($post_id, 'spa_schedule', sanitize_textarea_field($_POST['spa_schedule']));
    }
    
    // SAVE: Taxonómie
    if (isset($_POST['spa_place'])) {
        wp_set_post_terms($post_id, array_map('intval', $_POST['spa_place']), 'spa_place');
    } else {
        wp_set_post_terms($post_id, [], 'spa_place');
    }
    
    if (isset($_POST['spa_group_category'])) {
        wp_set_post_terms($post_id, array_map('intval', $_POST['spa_group_category']), 'spa_group_category');
    } else {
        wp_set_post_terms($post_id, [], 'spa_group_category');
    }
}