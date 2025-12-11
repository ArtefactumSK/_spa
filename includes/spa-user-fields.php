<?php
/**
 * SPA User Fields - Rozšírené polia používateľov
 * Rodné číslo, Variabilný symbol, PIN, Adresa
 * 
 * @package Samuel Samuel Piasecký ACADEMY
 * @version 2.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/* ==========================
   VARIABILNÝ SYMBOL - GENERÁTOR
   3-miestny kód, unikátny
   ========================== */

function spa_generate_variabilny_symbol() {
    global $wpdb;
    
    $max_vs = $wpdb->get_var("
        SELECT MAX(CAST(meta_value AS UNSIGNED)) 
        FROM {$wpdb->usermeta} 
        WHERE meta_key = 'variabilny_symbol'
        AND meta_value REGEXP '^[0-9]+$'
    ");
    
    $next_vs = $max_vs ? intval($max_vs) + 1 : 100;
    
    if ($next_vs < 100) {
        $next_vs = 100;
    }
    
    while (spa_vs_exists($next_vs)) {
        $next_vs++;
    }
    
    return str_pad($next_vs, 3, '0', STR_PAD_LEFT);
}

function spa_vs_exists($vs) {
    global $wpdb;
    
    return $wpdb->get_var($wpdb->prepare("
        SELECT COUNT(*) FROM {$wpdb->usermeta} 
        WHERE meta_key = 'variabilny_symbol' 
        AND meta_value = %s
    ", $vs)) > 0;
}

/* ==========================
   PIN - GENERÁTOR
   4-miestny kód pre deti
   ========================== */

function spa_generate_pin() {
    return str_pad(rand(1000, 9999), 4, '0', STR_PAD_LEFT);
}

function spa_hash_pin($pin) {
    return wp_hash_password($pin);
}

function spa_verify_pin($pin, $hashed_pin) {
    return wp_check_password($pin, $hashed_pin);
}

/* ==========================
   ADMIN: Polia v profile používateľa
   ========================== */

add_action('show_user_profile', 'spa_extra_user_profile_fields');
add_action('edit_user_profile', 'spa_extra_user_profile_fields');

function spa_extra_user_profile_fields($user) {
    
    $spa_roles = ['spa_parent', 'spa_child', 'spa_client'];
    $user_roles = $user->roles;
    
    if (!array_intersect($spa_roles, $user_roles)) {
        return;
    }
    
    $is_child = in_array('spa_child', $user_roles);
    $is_parent = in_array('spa_parent', $user_roles);
    $is_client = in_array('spa_client', $user_roles);
    
    // Načítaj hodnoty
    $rodne_cislo = get_user_meta($user->ID, 'rodne_cislo', true);
    $variabilny_symbol = get_user_meta($user->ID, 'variabilny_symbol', true);
    $birthdate = get_user_meta($user->ID, 'birthdate', true);
    $parent_id = get_user_meta($user->ID, 'parent_id', true);
    $spa_pin = get_user_meta($user->ID, 'spa_pin_plain', true); // Plain pre admin zobrazenie
    
    // Adresa
    $address_street = get_user_meta($user->ID, 'address_street', true);
    $address_psc = get_user_meta($user->ID, 'address_psc', true);
    $address_city = get_user_meta($user->ID, 'address_city', true);
    $phone = get_user_meta($user->ID, 'phone', true);
    
    ?>
    <h2>Údaje <svg class="spa-icon" width="29" height="29" viewBox="20 0 10 90" style="vertical-align: bottom;display: inline-block;"><path d="M36.29,0C-3.91,29.7.49,65.3,32.79,69.8-1.91,69-20.51,38.3,36.29,0Z" fill="var(--theme-palette-color-1, #FF1439)"></path><path d="M16.99,60.2c2.5,1.8,5.1,1.8,5.6-.2s-1.1-5.1-3.7-7-5.1-1.8-5.6.2,1.1,5.1,3.7,7Z" fill="var(--theme-palette-color-1, #FF1439)"></path><path d="M16.49,92.4c40.2-29.7,35.8-65.3,3.5-69.8,34.7.8,53.3,31.5-3.5,69.8Z" fill="var(--theme-palette-color-3, #ff1439)"></path><path d="M48.39,30.5c2.6,1.9,5.1,1.8,5.6-.2s-1.1-5.1-3.7-7-5.1-1.8-5.6.2,1.1,5.1,3.7,7Z" fill="var(--theme-palette-color-3, #ff1439)"></path></svg> Samuel Piasecký ACADEMY</h2>
    
    <table class="form-table" role="presentation">
        
        <!-- SPOLOČNÉ: Variabilný symbol -->
        <?php if ($is_child || $is_client) : ?>
        <tr>
            <th><label for="variabilny_symbol">Variabilný symbol</label></th>
            <td>
                <input type="text" 
                       name="variabilny_symbol" 
                       id="variabilny_symbol" 
                       value="<?php echo esc_attr($variabilny_symbol); ?>" 
                       class="regular-text"
                       style="width: 100px; font-weight: bold; font-size: 18px; text-align: center;"
                       readonly>
                <?php if (empty($variabilny_symbol) && current_user_can('edit_users')) : ?>
                    <button type="button" class="button" id="spa-generate-vs">Generovať VS</button>
                <?php endif; ?>
                <p class="description">3-miestny kód pre platby</p>
            </td>
        </tr>
        
        <!-- Rodné číslo -->
        <tr>
            <th><label for="rodne_cislo">Rodné číslo</label></th>
            <td>
                <input type="text" 
                       name="rodne_cislo" 
                       id="rodne_cislo" 
                       value="<?php echo esc_attr(spa_format_rodne_cislo($rodne_cislo)); ?>" 
                       class="regular-text"
                       placeholder="XXXXXX/XXXX"
                       style="width: 150px;">
                <p class="description">Formát: XXXXXX/XXXX</p>
            </td>
        </tr>
        
        <!-- Dátum narodenia -->
        <tr>
            <th><label for="birthdate">Dátum narodenia</label></th>
            <td>
                <input type="date" 
                       name="birthdate" 
                       id="birthdate" 
                       value="<?php echo esc_attr($birthdate); ?>" 
                       class="regular-text"
                       style="width: 150px;">
            </td>
        </tr>
        <?php endif; ?>
        
        <!-- DIEŤA: PIN -->
        <?php if ($is_child) : ?>
        <tr>
            <th><label for="spa_pin">PIN pre prihlásenie</label></th>
            <td>
                <div style="display: flex; align-items: center; gap: 10px;">
                    <input type="text" 
                           name="spa_pin_display" 
                           id="spa_pin_display" 
                           value="<?php echo esc_attr($spa_pin); ?>" 
                           class="regular-text"
                           style="width: 100px; font-weight: bold; font-size: 24px; text-align: center; letter-spacing: 8px;"
                           readonly>
                    <?php if (current_user_can('edit_users')) : ?>
                        <button type="button" class="button" id="spa-regenerate-pin">🔄 Nový PIN</button>
                    <?php endif; ?>
                </div>
                <p class="description">
                    <strong>Prihlásenie dieťaťa:</strong> Meno + Priezvisko + PIN<br>
                    <code><?php echo esc_html($user->first_name . ' ' . $user->last_name); ?> + <?php echo esc_html($spa_pin); ?></code>
                </p>
            </td>
        </tr>
        
        <!-- DIEŤA: Rodič -->
        <tr>
            <th><label for="parent_id">Rodič</label></th>
            <td>
                <?php
                $parents = get_users(['role' => 'spa_parent', 'orderby' => 'display_name']);
                ?>
                <select name="parent_id" id="parent_id" style="width: 350px;">
                    <option value="">— Vyber rodiča —</option>
                    <?php foreach ($parents as $parent) : ?>
                        <option value="<?php echo $parent->ID; ?>" <?php selected($parent_id, $parent->ID); ?>>
                            <?php echo esc_html($parent->display_name); ?> (<?php echo $parent->user_email; ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php if ($parent_id) : 
                    $parent_user = get_userdata($parent_id);
                    $parent_phone = get_user_meta($parent_id, 'phone', true);
                    if ($parent_user) :
                ?>
                    <p class="description">
                        📧 <?php echo $parent_user->user_email; ?> | 
                        📱 <?php echo $parent_phone ?: '—'; ?>
                    </p>
                <?php endif; endif; ?>
            </td>
        </tr>
        <?php endif; ?>
        
        <!-- RODIČ: Telefón -->
        <?php if ($is_parent || $is_client) : ?>
        <tr>
            <th><label for="phone">Telefón</label></th>
            <td>
                <input type="tel" 
                       name="phone" 
                       id="phone" 
                       value="<?php echo esc_attr($phone); ?>" 
                       class="regular-text"
                       placeholder="+421 9XX XXX XXX"
                       style="width: 200px;">
            </td>
        </tr>
        
        <!-- Adresa -->
        <tr>
            <th><label for="address_street">Ulica a číslo</label></th>
            <td>
                <input type="text" 
                       name="address_street" 
                       id="address_street" 
                       value="<?php echo esc_attr($address_street); ?>" 
                       class="regular-text"
                       placeholder="Hlavná 123">
            </td>
        </tr>
        
        <tr>
            <th><label for="address_psc">PSČ</label></th>
            <td>
                <input type="text" 
                       name="address_psc" 
                       id="address_psc" 
                       value="<?php echo esc_attr($address_psc); ?>" 
                       class="regular-text"
                       style="width: 100px;"
                       placeholder="90101"
                       maxlength="5">
            </td>
        </tr>
        
        <tr>
            <th><label for="address_city">Mesto</label></th>
            <td>
                <input type="text" 
                       name="address_city" 
                       id="address_city" 
                       value="<?php echo esc_attr($address_city); ?>" 
                       class="regular-text"
                       placeholder="Malacky"
                       style="width: 200px;">
            </td>
        </tr>
        <?php endif; ?>
        
        <!-- RODIČ: Zoznam detí -->
        <?php if ($is_parent) : ?>
        <tr>
            <th>Priradené deti</th>
            <td>
                <?php
                $children = get_users([
                    'role' => 'spa_child',
                    'meta_key' => 'parent_id',
                    'meta_value' => $user->ID
                ]);
                
                if ($children) :
                    echo '<table class="widefat" style="max-width: 500px;">';
                    echo '<thead><tr><th>Meno</th><th>VS</th><th>PIN</th><th></th></tr></thead><tbody>';
                    foreach ($children as $child) :
                        $vs = get_user_meta($child->ID, 'variabilny_symbol', true);
                        $pin = get_user_meta($child->ID, 'spa_pin_plain', true);
                        $edit_link = get_edit_user_link($child->ID);
                ?>
                        <tr>
                            <td><strong><?php echo esc_html($child->display_name); ?></strong></td>
                            <td><code><?php echo $vs ?: '—'; ?></code></td>
                            <td><code><?php echo $pin ?: '—'; ?></code></td>
                            <td><a href="<?php echo $edit_link; ?>" class="button button-small">Upraviť</a></td>
                        </tr>
                <?php 
                    endforeach;
                    echo '</tbody></table>';
                else :
                    echo '<em style="color: #999;">Žiadne deti</em>';
                endif;
                ?>
            </td>
        </tr>
        <?php endif; ?>
        
    </table>
    
    <!-- JavaScript -->
    <script>
    jQuery(document).ready(function($) {
        // Generovanie VS
        $('#spa-generate-vs').on('click', function() {
            var btn = $(this);
            btn.prop('disabled', true).text('Generujem...');
            
            $.post(ajaxurl, {
                action: 'spa_generate_vs_ajax',
                user_id: <?php echo $user->ID; ?>,
                nonce: '<?php echo wp_create_nonce('spa_generate_vs'); ?>'
            }, function(response) {
                if (response.success) {
                    $('#variabilny_symbol').val(response.data.vs);
                    btn.hide();
                } else {
                    alert('Chyba: ' + response.data.message);
                    btn.prop('disabled', false).text('Generovať VS');
                }
            });
        });
        
        // Regenerovanie PIN
        $('#spa-regenerate-pin').on('click', function() {
            if (!confirm('Naozaj chceš vygenerovať nový PIN? Starý PIN prestane fungovať.')) {
                return;
            }
            
            var btn = $(this);
            btn.prop('disabled', true);
            
            $.post(ajaxurl, {
                action: 'spa_regenerate_pin_ajax',
                user_id: <?php echo $user->ID; ?>,
                nonce: '<?php echo wp_create_nonce('spa_regenerate_pin'); ?>'
            }, function(response) {
                if (response.success) {
                    $('#spa_pin_display').val(response.data.pin);
                    alert('Nový PIN: ' + response.data.pin);
                } else {
                    alert('Chyba: ' + response.data.message);
                }
                btn.prop('disabled', false);
            });
        });
    });
    </script>
    <?php
}

/* ==========================
   AJAX: Generovanie VS
   ========================== */

add_action('wp_ajax_spa_generate_vs_ajax', 'spa_generate_vs_ajax');

function spa_generate_vs_ajax() {
    
    if (!wp_verify_nonce($_POST['nonce'], 'spa_generate_vs')) {
        wp_send_json_error(['message' => 'Security check failed']);
    }
    
    if (!current_user_can('edit_users')) {
        wp_send_json_error(['message' => 'Nedostatočné oprávnenia']);
    }
    
    $user_id = intval($_POST['user_id']);
    
    $existing = get_user_meta($user_id, 'variabilny_symbol', true);
    if ($existing) {
        wp_send_json_error(['message' => 'Používateľ už má VS: ' . $existing]);
    }
    
    $vs = spa_generate_variabilny_symbol();
    update_user_meta($user_id, 'variabilny_symbol', $vs);
    
    wp_send_json_success(['vs' => $vs]);
}

/* ==========================
   AJAX: Regenerovanie PIN
   ========================== */

add_action('wp_ajax_spa_regenerate_pin_ajax', 'spa_regenerate_pin_ajax');

function spa_regenerate_pin_ajax() {
    
    if (!wp_verify_nonce($_POST['nonce'], 'spa_regenerate_pin')) {
        wp_send_json_error(['message' => 'Security check failed']);
    }
    
    if (!current_user_can('edit_users')) {
        wp_send_json_error(['message' => 'Nedostatočné oprávnenia']);
    }
    
    $user_id = intval($_POST['user_id']);
    
    // Skontroluj že je to dieťa
    $user = get_userdata($user_id);
    if (!$user || !in_array('spa_child', $user->roles)) {
        wp_send_json_error(['message' => 'Používateľ nie je dieťa']);
    }
    
    $pin = spa_generate_pin();
    update_user_meta($user_id, 'spa_pin', spa_hash_pin($pin));
    update_user_meta($user_id, 'spa_pin_plain', $pin); // Pre admin zobrazenie
    
    // Notifikácia rodičovi
    $parent_id = get_user_meta($user_id, 'parent_id', true);
    if ($parent_id) {
        $parent = get_userdata($parent_id);
        if ($parent) {
            spa_send_new_pin_email($parent->user_email, $user->display_name, $pin);
        }
    }
    
    wp_send_json_success(['pin' => $pin]);
}

/* ==========================
   SAVE: Uloženie extra polí
   ========================== */

add_action('personal_options_update', 'spa_save_extra_user_profile_fields');
add_action('edit_user_profile_update', 'spa_save_extra_user_profile_fields');

function spa_save_extra_user_profile_fields($user_id) {
    
    if (!current_user_can('edit_user', $user_id)) {
        return false;
    }
    
    // Rodné číslo
    if (isset($_POST['rodne_cislo'])) {
        $rc = sanitize_text_field($_POST['rodne_cislo']);
        $rc = preg_replace('/[^0-9]/', '', $rc);
        update_user_meta($user_id, 'rodne_cislo', $rc);
    }
    
    // Variabilný symbol
    if (isset($_POST['variabilny_symbol']) && !empty($_POST['variabilny_symbol'])) {
        update_user_meta($user_id, 'variabilny_symbol', sanitize_text_field($_POST['variabilny_symbol']));
    }
    
    // Dátum narodenia
    if (isset($_POST['birthdate'])) {
        update_user_meta($user_id, 'birthdate', sanitize_text_field($_POST['birthdate']));
    }
    
    // Parent ID
    if (isset($_POST['parent_id'])) {
        update_user_meta($user_id, 'parent_id', intval($_POST['parent_id']));
    }
    
    // Telefón
    if (isset($_POST['phone'])) {
        update_user_meta($user_id, 'phone', sanitize_text_field($_POST['phone']));
    }
    
    // Adresa
    if (isset($_POST['address_street'])) {
        update_user_meta($user_id, 'address_street', sanitize_text_field($_POST['address_street']));
    }
    if (isset($_POST['address_psc'])) {
        update_user_meta($user_id, 'address_psc', sanitize_text_field($_POST['address_psc']));
    }
    if (isset($_POST['address_city'])) {
        update_user_meta($user_id, 'address_city', sanitize_text_field($_POST['address_city']));
    }
}

/* ==========================
   ADMIN COLUMNS: Používatelia
   ========================== */

add_filter('manage_users_columns', 'spa_add_user_columns');

function spa_add_user_columns($columns) {
    $new_columns = [];
    
    foreach ($columns as $key => $value) {
        $new_columns[$key] = $value;
        
        if ($key === 'email') {
            $new_columns['spa_vs'] = 'VS';
            $new_columns['spa_role_info'] = 'SPA Info';
        }
    }
    
    return $new_columns;
}

add_filter('manage_users_custom_column', 'spa_user_column_content', 10, 3);

function spa_user_column_content($value, $column_name, $user_id) {
    
    $user = get_userdata($user_id);
    
    switch ($column_name) {
        case 'spa_vs':
            $vs = get_user_meta($user_id, 'variabilny_symbol', true);
            if ($vs) {
                return '<code style="background: #e8f5e9; padding: 4px 8px; border-radius: 4px; font-weight: bold;">' . esc_html($vs) . '</code>';
            }
            return '—';
            
        case 'spa_role_info':
            if (in_array('spa_child', $user->roles)) {
                $parent_id = get_user_meta($user_id, 'parent_id', true);
                $parent = $parent_id ? get_userdata($parent_id) : null;
                $pin = get_user_meta($user_id, 'spa_pin_plain', true);
                
                $info = '👶 Dieťa';
                if ($parent) {
                    $info .= '<br><small>Rodič: ' . esc_html($parent->display_name) . '</small>';
                }
                if ($pin) {
                    $info .= '<br><small>PIN: <code>' . esc_html($pin) . '</code></small>';
                }
                return $info;
            }
            elseif (in_array('spa_parent', $user->roles)) {
                $children = get_users([
                    'role' => 'spa_child',
                    'meta_key' => 'parent_id',
                    'meta_value' => $user_id
                ]);
                return '👨‍👩‍👧 Rodič<br><small>' . count($children) . ' detí</small>';
            }
            elseif (in_array('spa_client', $user->roles)) {
                return '🏃 Klient';
            }
            return '—';
    }
    
    return $value;
}

/* ==========================
   HELPER: Formátovanie rodného čísla
   ========================== */

function spa_format_rodne_cislo($rc, $with_slash = true) {
    $rc = preg_replace('/[^0-9]/', '', $rc);
    
    if (strlen($rc) >= 9 && $with_slash) {
        return substr($rc, 0, 6) . '/' . substr($rc, 6);
    }
    
    return $rc;
}

/* ==========================
   EMAIL: Nový PIN
   ========================== */

function spa_send_new_pin_email($parent_email, $child_name, $pin) {
    
    $subject = 'Nový PIN pre ' . $child_name . ' - Samuel Piasecký ACADEMY';
    
    $message = "Dobrý deň,\n\n";
    $message .= "Pre dieťa {$child_name} bol vygenerovaný nový PIN pre prihlásenie.\n\n";
    $message .= "Nový PIN: {$pin}\n\n";
    $message .= "Prihlásenie: Meno + Priezvisko + PIN\n";
    $message .= "Príklad: {$child_name} + {$pin}\n\n";
    $message .= "Samuel Piasecký ACADEMY\n";
    $message .= home_url();
    
    wp_mail($parent_email, $subject, $message);
}

/* ==========================
   AUTOMATICKÉ PRIDELENIE VS + PIN
   ========================== */

add_action('spa_after_child_created', 'spa_auto_assign_vs_and_pin', 10, 1);

function spa_auto_assign_vs_and_pin($child_user_id) {
    
    // VS
    $existing_vs = get_user_meta($child_user_id, 'variabilny_symbol', true);
    if (!$existing_vs) {
        $vs = spa_generate_variabilny_symbol();
        update_user_meta($child_user_id, 'variabilny_symbol', $vs);
    }
    
    // PIN
    $existing_pin = get_user_meta($child_user_id, 'spa_pin', true);
    if (!$existing_pin) {
        $pin = spa_generate_pin();
        update_user_meta($child_user_id, 'spa_pin', spa_hash_pin($pin));
        update_user_meta($child_user_id, 'spa_pin_plain', $pin);
    }
}

add_action('spa_after_client_created', 'spa_auto_assign_vs_client', 10, 1);

function spa_auto_assign_vs_client($client_user_id) {
    
    $existing_vs = get_user_meta($client_user_id, 'variabilny_symbol', true);
    if (!$existing_vs) {
        $vs = spa_generate_variabilny_symbol();
        update_user_meta($client_user_id, 'variabilny_symbol', $vs);
    }
}