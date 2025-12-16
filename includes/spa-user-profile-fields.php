<?php
/**
 * SPA User Profile Fields
 * Rozšírené polia pre spa_parent a spa_child
 */

if (!defined('ABSPATH')) {
    exit;
}

add_action('show_user_profile', 'spa_user_profile_fields');
add_action('edit_user_profile', 'spa_user_profile_fields');

function spa_user_profile_fields($user) {
    
    $user_roles = $user->roles;
    $is_parent = in_array('spa_parent', $user_roles);
    $is_child = in_array('spa_child', $user_roles);
    
    if (!($is_parent || $is_child)) {
        return;
    }
    
    $vs = get_user_meta($user->ID, 'variabilny_symbol', true);
    $phone = get_user_meta($user->ID, 'phone', true);
    $birthdate = get_user_meta($user->ID, 'birthdate', true);
    $rodne_cislo = get_user_meta($user->ID, 'rodne_cislo', true);
    $parent_id = get_user_meta($user->ID, 'parent_user_id', true);
    $pin = get_user_meta($user->ID, 'spa_pin_plain', true);
    
    ?>
    <h2>🏛️ Samuel Piasecký Academy</h2>
    <table class="form-table" role="presentation">
        
        <!-- SPOLOČNÉ: VS (READONLY) -->
        <tr>
            <th><label>💳 Variabilný symbol</label></th>
            <td>
                <input type="text" value="<?php echo esc_attr($vs); ?>" readonly style="background:#f5f5f5;font-weight:bold;font-size:18px;width:100px;text-align:center;">
                <p class="description">Banková identifikácia pre platby</p>
            </td>
        </tr>
        
        <!-- RODIČ -->
        <?php if ($is_parent) : ?>
            <tr>
                <th><label for="phone">📞 Telefón</label></th>
                <td>
                    <input type="tel" name="phone" id="phone" value="<?php echo esc_attr($phone); ?>" placeholder="+421 9XX XXX XXX" class="regular-text">
                </td>
            </tr>
            
            <!-- ZOZNAM DETÍ -->
            <tr>
                <th>👶 Moje deti</th>
                <td>
                    <?php
                    $children = get_users([
                        'role' => 'spa_child',
                        'meta_key' => 'parent_user_id',
                        'meta_value' => $user->ID
                    ]);
                    
                    if ($children) {
                        echo '<table style="width:100%;max-width:600px;border-collapse:collapse;">';
                        echo '<thead><tr style="border-bottom:2px solid #ddd;"><th style="text-align:left;padding:8px;">Meno</th><th style="text-align:left;padding:8px;">PIN</th><th style="text-align:left;padding:8px;">VS</th></tr></thead>';
                        echo '<tbody>';
                        foreach ($children as $child) {
                            $child_pin = get_user_meta($child->ID, 'spa_pin_plain', true);
                            $child_vs = get_user_meta($child->ID, 'variabilny_symbol', true);
                            $child_name = $child->first_name . ' ' . $child->last_name;
                            echo '<tr style="border-bottom:1px solid #eee;">';
                            echo '<td style="padding:8px;"><strong>' . esc_html($child_name) . '</strong></td>';
                            echo '<td style="padding:8px;"><code style="background:#e3f2fd;padding:4px 8px;border-radius:3px;">' . esc_html($child_pin ?: '–') . '</code></td>';
                            echo '<td style="padding:8px;"><code style="background:#f3e5f5;padding:4px 8px;border-radius:3px;">' . esc_html($child_vs ?: '–') . '</code></td>';
                            echo '</tr>';
                        }
                        echo '</tbody></table>';
                    } else {
                        echo '<p style="color:#888;font-style:italic;">Žiadne deti nie sú priradené</p>';
                    }
                    ?>
                </td>
            </tr>
        <?php endif; ?>
        
        <!-- DIEŤA -->
        <?php if ($is_child) : ?>
            
            <tr>
                <th><label for="birthdate">📅 Dátum narodenia</label></th>
                <td>
                    <input type="date" name="birthdate" id="birthdate" value="<?php echo esc_attr($birthdate); ?>" class="regular-text" style="max-width:200px;">
                </td>
            </tr>
            
            <tr>
                <th><label for="rodne_cislo">🆔 Rodné číslo</label></th>
                <td>
                    <input type="text" name="rodne_cislo" id="rodne_cislo" value="<?php echo esc_attr($rodne_cislo ? substr($rodne_cislo, 0, 6) . '/' . substr($rodne_cislo, 6) : ''); ?>" placeholder="XXXXXX/XXXX" class="regular-text" style="max-width:150px;">
                </td>
            </tr>
            
            <!-- PIN NA VSTUP (READONLY) -->
            <tr>
                <th><label>🔐 PIN na vstup</label></th>
                <td>
                    <input type="text" value="<?php echo esc_attr($pin); ?>" readonly style="background:#fff3cd;font-weight:bold;font-size:20px;width:120px;text-align:center;letter-spacing:3px;border:2px solid #ffc107;">
                    <p class="description">Tento PIN používa dieťa na vstup do portálu. Pre zmenu kontaktuje správcu.</p>
                </td>
            </tr>
            
            <!-- RODIČ INFO (READONLY) -->
            <?php if ($parent_id) :
                $parent = get_userdata($parent_id);
                if ($parent) :
                    $parent_phone = get_user_meta($parent_id, 'phone', true);
            ?>
                <tr>
                    <th>👨‍👩‍👧 Môj rodič</th>
                    <td>
                        <div style="background:#e8f5e9;padding:15px;border-left:4px solid #4caf50;border-radius:4px;">
                            <p><strong><?php echo esc_html($parent->first_name . ' ' . $parent->last_name); ?></strong></p>
                            <p>📧 Email: <a href="mailto:<?php echo esc_attr($parent->user_email); ?>"><?php echo esc_html($parent->user_email); ?></a></p>
                            <?php if ($parent_phone) : ?>
                                <p>📞 Telefón: <a href="tel:<?php echo esc_attr($parent_phone); ?>"><?php echo esc_html($parent_phone); ?></a></p>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
            <?php 
                endif;
            endif; 
            ?>
        
        <?php endif; ?>
        
    </table>
    
    <?php
}

add_action('personal_options_update', 'spa_user_profile_save');
add_action('edit_user_profile_update', 'spa_user_profile_save');

function spa_user_profile_save($user_id) {
    
    if (!current_user_can('edit_user', $user_id)) {
        return false;
    }
    
    // Telefón
    if (isset($_POST['phone'])) {
        update_user_meta($user_id, 'phone', sanitize_text_field($_POST['phone']));
    }
    
    // Dátum narodenia (pre dieťa)
    if (isset($_POST['birthdate'])) {
        update_user_meta($user_id, 'birthdate', sanitize_text_field($_POST['birthdate']));
    }
    
    // Rodné číslo (pre dieťa)
    if (isset($_POST['rodne_cislo'])) {
        $rc = sanitize_text_field($_POST['rodne_cislo']);
        $rc_clean = preg_replace('/[^0-9]/', '', $rc);
        update_user_meta($user_id, 'rodne_cislo', $rc_clean);
    }
}