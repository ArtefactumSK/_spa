<?php
/**
 * SPA Meta Boxes - Meta boxy pre registrácie
 * 
 * @package Samuel Piasecký ACADEMY
 * @version 2.0.0 - FIXED
 */

if (!defined('ABSPATH')) {
    exit;
}

// ═══════════════════════════════════════════════════════════════════════════════
// META BOX: REGISTRÁCIA - DETAIL
// ═══════════════════════════════════════════════════════════════════════════════

add_action('add_meta_boxes', 'spa_registration_meta_boxes');

function spa_registration_meta_boxes() {
    add_meta_box(
        'spa_registration_details',
        '📋 Detaily registrácie',
        'spa_registration_details_callback',
        'spa_registration',
        'normal',
        'high'
    );
}

function spa_registration_details_callback($post) {
    wp_nonce_field('spa_registration_save', 'spa_registration_nonce');
    
    // Získaj meta údaje
    $client_id = get_post_meta($post->ID, 'client_user_id', true);
    $parent_id = get_post_meta($post->ID, 'parent_user_id', true);
    $program_id = get_post_meta($post->ID, 'spa_group_id', true);
    $training_day = get_post_meta($post->ID, 'training_day', true);
    $training_time = get_post_meta($post->ID, 'training_time', true);
    $status = get_post_meta($post->ID, 'status', true);
    $variable_symbol = get_post_meta($post->ID, 'variable_symbol', true);
    $pin = get_post_meta($post->ID, 'pin', true);
    
    // Získaj data o dieťati a rodičovi
    $child = $client_id ? get_userdata($client_id) : null;
    $parent = $parent_id ? get_userdata($parent_id) : null;
    
    ?>
    <style>
    .spa-meta-box-table { width: 100%; border-collapse: collapse; }
    .spa-meta-box-table th { text-align: left; padding: 10px; width: 25%; background: #f0f0f1; font-weight: 600; }
    .spa-meta-box-table td { padding: 10px; }
    .spa-meta-box-table tr { border-bottom: 1px solid #dcdcde; }
    .spa-readonly { background: #f9f9f9; cursor: not-allowed; }
    </style>
    
    <table class="spa-meta-box-table">
        
        <!-- DIEŤA / KLIENT -->
        <tr>
            <th>👶 Dieťa/Klient</th>
            <td>
                <?php if ($child): ?>
                    <strong><?php echo esc_html($child->first_name . ' ' . $child->last_name); ?></strong><br>
                    <small>
                        Email: <?php echo esc_html($child->user_email); ?><br>
                        Dátum narodenia: <?php echo esc_html(get_user_meta($client_id, 'date_of_birth', true)); ?><br>
                        Rodné číslo: <?php echo esc_html(get_user_meta($client_id, 'rodne_cislo', true)); ?>
                    </small><br>
                    <a href="<?php echo get_edit_user_link($client_id); ?>" target="_blank" class="button button-small">Upraviť dieťa →</a>
                <?php else: ?>
                    <span style="color:#999;">Nie je priradený</span>
                <?php endif; ?>
                <input type="hidden" name="client_user_id" value="<?php echo esc_attr($client_id); ?>">
            </td>
        </tr>
        
        <!-- RODIČ -->
        <tr>
            <th>👨‍👩‍👧 Rodič</th>
            <td>
                <?php if ($parent): ?>
                    <strong><?php echo esc_html($parent->first_name . ' ' . $parent->last_name); ?></strong><br>
                    <small>
                        Email: <?php echo esc_html($parent->user_email); ?><br>
                        Telefón: <?php echo esc_html(get_user_meta($parent_id, 'phone', true)); ?>
                    </small><br>
                    <a href="<?php echo get_edit_user_link($parent_id); ?>" target="_blank" class="button button-small">Upraviť rodiča →</a>
                <?php else: ?>
                    <span style="color:#999;">Nie je priradený</span>
                <?php endif; ?>
                <input type="hidden" name="parent_user_id" value="<?php echo esc_attr($parent_id); ?>">
            </td>
        </tr>
        
        <!-- PROGRAM -->
        <tr>
            <th>🏋️ Program</th>
            <td>
                <select name="spa_group_id" id="spa_group_id" class="widefat" required>
                    <option value="">-- Vyberte program --</option>
                    <?php
                    $programs = get_posts([
                        'post_type' => 'spa_group',
                        'post_status' => 'publish',
                        'posts_per_page' => -1,
                        'orderby' => 'title',
                        'order' => 'ASC'
                    ]);
                    foreach ($programs as $program) {
                        $selected = ($program_id == $program->ID) ? 'selected="selected"' : '';
                        echo '<option value="' . esc_attr($program->ID) . '" ' . $selected . '>' . esc_html($program->post_title) . '</option>';
                    }
                    ?>
                </select>
            </td>
        </tr>
        
        <!-- DEŇ TRÉNINGU -->
        <tr>
            <th>📅 Deň tréningu</th>
            <td>
                <select name="training_day" id="training_day" class="widefat">
                    <option value="">-- Vyberte deň --</option>
                    <?php
                    $days = [
                        'mo' => 'Pondelok',
                        'tu' => 'Utorok',
                        'we' => 'Streda',
                        'th' => 'Štvrtok',
                        'fr' => 'Piatok',
                        'sa' => 'Sobota',
                        'su' => 'Nedeľa'
                    ];
                    foreach ($days as $value => $label) {
                        $selected = ($training_day === $value) ? 'selected="selected"' : '';
                        echo '<option value="' . esc_attr($value) . '" ' . $selected . '>' . esc_html($label) . '</option>';
                    }
                    ?>
                </select>
            </td>
        </tr>
        
        <!-- ČAS TRÉNINGU -->
        <tr>
            <th>⏰ Čas tréningu</th>
            <td>
                <input type="time" name="training_time" id="training_time" value="<?php echo esc_attr($training_time); ?>" class="widefat">
            </td>
        </tr>
        
        <!-- STATUS -->
        <tr>
            <th>📊 Status</th>
            <td>
                <select name="status" id="status" class="widefat">
                    <?php
                    $statuses = [
                        'pending' => '⏳ Čaká na schválenie',
                        'awaiting_payment' => '💰 Čaká na platbu',
                        'partially_paid' => '💳 Čiastočne zaplatené',
                        'approved' => '✅ Schválené',
                        'active' => '🟢 Aktívny',
                        'blocked' => '🚫 Blokované',
                        'cancelled' => '❌ Zrušené',
                        'completed' => '✔️ Zaregistrované'
                    ];
                    foreach ($statuses as $value => $label) {
                        $selected = ($status === $value) ? 'selected="selected"' : '';
                        echo '<option value="' . esc_attr($value) . '" ' . $selected . '>' . esc_html($label) . '</option>';
                    }
                    ?>
                </select>
            </td>
        </tr>
        
        <!-- VARIABILNÝ SYMBOL -->
        <tr>
            <th>🔢 Variabilný symbol</th>
            <td>
                <input type="text" name="variable_symbol" id="variable_symbol" value="<?php echo esc_attr($variable_symbol); ?>" class="widefat">
                <p class="description">Banková identifikácia pre platby</p>
            </td>
        </tr>
        
        <!-- PIN -->
        <tr>
            <th>🔐 PIN na vstup</th>
            <td>
                <input type="text" name="pin" id="pin" value="<?php echo esc_attr($pin); ?>" class="widefat" maxlength="4" pattern="[0-9]{4}">
                <p class="description">4-ciferný PIN pre vstup dieťaťa do portálu</p>
                <button type="button" class="button button-secondary" id="generate_pin">🔄 Vygenerovať nový PIN</button>
                
                <script>
                jQuery(document).ready(function($) {
                    $('#generate_pin').on('click', function() {
                        var newPin = Math.floor(1000 + Math.random() * 9000);
                        $('#pin').val(newPin);
                        alert('Nový PIN: ' + newPin);
                    });
                });
                </script>
            </td>
        </tr>
        
    </table>
    <?php
}

// Uloženie meta údajov
add_action('save_post_spa_registration', 'spa_registration_save_meta', 10, 2);

function spa_registration_save_meta($post_id, $post) {
    
    // Verifikácia nonce
    if (!isset($_POST['spa_registration_nonce']) || !wp_verify_nonce($_POST['spa_registration_nonce'], 'spa_registration_save')) {
        return;
    }
    
    // Autosave check
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    
    // Oprávnenia
    if (!current_user_can('edit_post', $post_id)) {
        return;
    }
    
    // Ulož meta polia
    $fields = [
        'client_user_id',
        'parent_user_id',
        'spa_group_id',
        'training_day',
        'training_time',
        'status',
        'variable_symbol',
        'pin'
    ];
    
    foreach ($fields as $field) {
        if (isset($_POST[$field])) {
            update_post_meta($post_id, $field, sanitize_text_field($_POST[$field]));
        }
    }
}


// ═══════════════════════════════════════════════════════════════════════════════
// USER EDIT: RODIČ (spa_parent)
// ═══════════════════════════════════════════════════════════════════════════════

add_action('show_user_profile', 'spa_parent_meta_fields');
add_action('edit_user_profile', 'spa_parent_meta_fields');

function spa_parent_meta_fields($user) {
    if (!in_array('spa_parent', (array) $user->roles)) {
        return;
    }
    
    $phone = get_user_meta($user->ID, 'phone', true);
    $address = get_user_meta($user->ID, 'address', true);
    $city = get_user_meta($user->ID, 'city', true);
    $psc = get_user_meta($user->ID, 'psc', true);
    $vs = get_user_meta($user->ID, 'variabilny_symbol', true);
    
    ?>
    <h2>👨‍👩‍👧 Kontakt rodiča</h2>
    <table class="form-table">
        <tr>
            <th><label for="phone">Telefón</label></th>
            <td>
                <input type="text" name="phone" id="phone" value="<?php echo esc_attr($phone); ?>" class="regular-text">
            </td>
        </tr>
        <tr>
            <th><label for="address">Ulica</label></th>
            <td>
                <input type="text" name="address" id="address" value="<?php echo esc_attr($address); ?>" class="regular-text">
            </td>
        </tr>
        <tr>
            <th><label for="city">Mesto</label></th>
            <td>
                <input type="text" name="city" id="city" value="<?php echo esc_attr($city); ?>" class="regular-text">
            </td>
        </tr>
        <tr>
            <th><label for="psc">PSČ</label></th>
            <td>
                <input type="text" name="psc" id="psc" value="<?php echo esc_attr($psc); ?>" class="regular-text">
            </td>
        </tr>
    </table>
    
    <h2>🏫 Samuel Piasecký Academy</h2>
    <table class="form-table">
        <tr>
            <th><label>Variabilný symbol</label></th>
            <td>
                <strong style="font-size:18px; color:#2271b1;"><?php echo esc_html($vs); ?></strong>
                <p class="description">Banková identifikácia pre platby</p>
            </td>
        </tr>
    </table>
    <?php
}

add_action('personal_options_update', 'spa_parent_save_meta');
add_action('edit_user_profile_update', 'spa_parent_save_meta');

function spa_parent_save_meta($user_id) {
    if (!current_user_can('edit_user', $user_id)) {
        return;
    }
    
    $user = get_userdata($user_id);
    if (!in_array('spa_parent', (array) $user->roles)) {
        return;
    }
    
    if (isset($_POST['phone'])) {
        update_user_meta($user_id, 'phone', sanitize_text_field($_POST['phone']));
    }
    if (isset($_POST['address'])) {
        update_user_meta($user_id, 'address', sanitize_text_field($_POST['address']));
    }
    if (isset($_POST['city'])) {
        update_user_meta($user_id, 'city', sanitize_text_field($_POST['city']));
    }
    if (isset($_POST['psc'])) {
        update_user_meta($user_id, 'psc', sanitize_text_field($_POST['psc']));
    }
}


// ═══════════════════════════════════════════════════════════════════════════════
// USER EDIT: DIEŤA (spa_child)
// ═══════════════════════════════════════════════════════════════════════════════

add_action('show_user_profile', 'spa_child_meta_fields');
add_action('edit_user_profile', 'spa_child_meta_fields');

function spa_child_meta_fields($user) {
    if (!in_array('spa_child', (array) $user->roles)) {
        return;
    }
    
    $birthdate = get_user_meta($user->ID, 'date_of_birth', true);
    $birth_number = get_user_meta($user->ID, 'rodne_cislo', true);
    $health_notes = get_user_meta($user->ID, 'health_notes', true);
    $parent_id = get_user_meta($user->ID, 'parent_user_id', true);
    $vs = get_user_meta($user->ID, 'variabilny_symbol', true);
    $pin_plain = get_user_meta($user->ID, 'spa_pin_plain', true);
    
    $parent = $parent_id ? get_userdata($parent_id) : null;
    $parent_name = $parent ? $parent->first_name . ' ' . $parent->last_name : 'Nie je priradený';
    
    ?>
    <h2>👶 Údaje dieťaťa</h2>
    <table class="form-table">
        <tr>
            <th><label for="date_of_birth">Dátum narodenia</label></th>
            <td>
                <input type="text" name="date_of_birth" id="date_of_birth" value="<?php echo esc_attr($birthdate); ?>" class="regular-text" placeholder="dd.mm.rrrr">
            </td>
        </tr>
        <tr>
            <th><label for="rodne_cislo">Rodné číslo</label></th>
            <td>
                <input type="text" name="rodne_cislo" id="rodne_cislo" value="<?php echo esc_attr($birth_number); ?>" class="regular-text" placeholder="yymmdxxxx">
            </td>
        </tr>
        <tr>
            <th><label for="health_notes">Zdravotné obmedzenia</label></th>
            <td>
                <textarea name="health_notes" id="health_notes" rows="3" class="large-text"><?php echo esc_textarea($health_notes); ?></textarea>
            </td>
        </tr>
        <tr>
            <th><label>Rodič</label></th>
            <td>
                <?php echo esc_html($parent_name); ?>
                <?php if ($parent): ?>
                    <br><a href="<?php echo get_edit_user_link($parent_id); ?>" target="_blank">Upraviť rodiča →</a>
                <?php endif; ?>
            </td>
        </tr>
    </table>
    
    <h2>🔐 PIN na vstup</h2>
    <table class="form-table">
        <tr>
            <th><label>Aktuálny PIN</label></th>
            <td>
                <strong style="font-size:24px; color:#2271b1; font-family:monospace;"><?php echo esc_html($pin_plain); ?></strong>
                <p class="description">Tento PIN používa dieťa na vstup do portálu</p>
                
                <p style="margin-top:15px;">
                    <button type="button" class="button button-secondary" id="spa-regenerate-pin">
                        🔄 Vygenerovať nový PIN
                    </button>
                    <input type="hidden" name="regenerate_pin" id="regenerate_pin" value="0">
                </p>
                
                <script>
                jQuery(document).ready(function($) {
                    $('#spa-regenerate-pin').on('click', function() {
                        if (confirm('Naozaj chcete vygenerovať nový PIN? Starý PIN prestane fungovať.')) {
                            $('#regenerate_pin').val('1');
                            alert('PIN sa vygeneruje po uložení profilu.');
                        }
                    });
                });
                </script>
            </td>
        </tr>
    </table>
    
    <h2>🏫 Samuel Piasecký Academy</h2>
    <table class="form-table">
        <tr>
            <th><label>Variabilný symbol</label></th>
            <td>
                <strong style="font-size:18px; color:#2271b1;"><?php echo esc_html($vs); ?></strong>
            </td>
        </tr>
    </table>
    <?php
}

add_action('personal_options_update', 'spa_child_save_meta');
add_action('edit_user_profile_update', 'spa_child_save_meta');

function spa_child_save_meta($user_id) {
    if (!current_user_can('edit_user', $user_id)) {
        return;
    }
    
    $user = get_userdata($user_id);
    if (!in_array('spa_child', (array) $user->roles)) {
        return;
    }
    
    if (isset($_POST['date_of_birth'])) {
        update_user_meta($user_id, 'date_of_birth', sanitize_text_field($_POST['date_of_birth']));
    }
    if (isset($_POST['rodne_cislo'])) {
        update_user_meta($user_id, 'rodne_cislo', sanitize_text_field($_POST['rodne_cislo']));
    }
    if (isset($_POST['health_notes'])) {
        update_user_meta($user_id, 'health_notes', sanitize_textarea_field($_POST['health_notes']));
    }
    
    // REGENERUJ PIN
    if (isset($_POST['regenerate_pin']) && $_POST['regenerate_pin'] === '1') {
        $new_pin = str_pad(rand(1000, 9999), 4, '0', STR_PAD_LEFT);
        update_user_meta($user_id, 'spa_pin', wp_hash_password($new_pin));
        update_user_meta($user_id, 'spa_pin_plain', $new_pin);
        
        error_log('[SPA] PIN regenerated for user ID ' . $user_id . ' - new PIN: ' . $new_pin);
    }
}