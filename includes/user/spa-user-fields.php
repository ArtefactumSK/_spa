<?php
/**
 * SPA User Fields - Registrácia custom user meta polí
 * 
 * @package Samuel Piasecký ACADEMY
 * @subpackage User/Fields
 * @version 1.0.0
 * 
 * PARENT MODULES: spa-core/spa-roles.php
 * CHILD MODULES: spa-user-parents.php, spa-user-children.php, spa-user-clients.php
 * 
 * DEFINES:
 * - User meta keys a ich validácia
 * - User profile fields v WP admin
 * 
 * META KEYS DEFINED:
 * - PARENT: phone, address_street, address_city, address_psc, vs (variabilný symbol), pin
 * - CHILD: birthdate, parent_id, health_notes, rodne_cislo
 * - CLIENT: birthdate, health_notes, rodne_cislo
 */

if (!defined('ABSPATH')) {
    exit;
}

/* ==================================================
   USER META KEYS - GLOBÁLNE KONŠTANTY
   ================================================== */

// PARENT meta keys
define('SPA_META_PHONE', 'spa_phone');
define('SPA_META_ADDRESS_STREET', 'spa_address_street');
define('SPA_META_ADDRESS_CITY', 'spa_address_city');
define('SPA_META_ADDRESS_PSC', 'spa_address_psc');
define('SPA_META_VS', 'spa_variabilny_symbol');
define('SPA_META_PIN', 'spa_pin');

// CHILD / CLIENT meta keys
define('SPA_META_BIRTHDATE', 'spa_birthdate');
define('SPA_META_PARENT_ID', 'spa_parent_id');
define('SPA_META_HEALTH_NOTES', 'spa_health_notes');
define('SPA_META_RODNE_CISLO', 'spa_rodne_cislo');

/* ==================================================
   REGISTER USER META FIELDS V WP ADMIN
   ================================================== */

add_action('show_user_profile', 'spa_show_user_profile_fields');
add_action('edit_user_profile', 'spa_show_user_profile_fields');

function spa_show_user_profile_fields($user) {
    $user_roles = $user->roles ?? [];
    
    // PARENT fieldy
    if (in_array('spa_parent', $user_roles)) {
        spa_render_parent_fields($user);
    }
    
    // CHILD fieldy
    if (in_array('spa_child', $user_roles)) {
        spa_render_child_fields($user);
    }
    
    // CLIENT fieldy
    if (in_array('spa_client', $user_roles)) {
        spa_render_client_fields($user);
    }
}

/* ==================================================
   RENDER: PARENT FIELDY
   ================================================== */

function spa_render_parent_fields($user) {
    $phone = get_user_meta($user->ID, SPA_META_PHONE, true);
    $street = get_user_meta($user->ID, SPA_META_ADDRESS_STREET, true);
    $city = get_user_meta($user->ID, SPA_META_ADDRESS_CITY, true);
    $psc = get_user_meta($user->ID, SPA_META_ADDRESS_PSC, true);
    $vs = get_user_meta($user->ID, SPA_META_VS, true);
    $pin = get_user_meta($user->ID, SPA_META_PIN, true);
    
    ?>
    <h3>👨‍👩‍👧 Kontakt rodiča</h3>
    <table class="form-table">
        <tr>
            <th><label for="spa_phone">Telefón:</label></th>
            <td>
                <input type="tel" name="spa_phone" id="spa_phone" 
                       value="<?php echo esc_attr($phone); ?>" 
                       class="regular-text" 
                       placeholder="+421 900 000 000">
            </td>
        </tr>
        <tr>
            <th><label for="spa_address_street">Ulica:</label></th>
            <td>
                <input type="text" name="spa_address_street" id="spa_address_street" 
                       value="<?php echo esc_attr($street); ?>" 
                       class="regular-text">
            </td>
        </tr>
        <tr>
            <th><label for="spa_address_city">Mesto:</label></th>
            <td>
                <input type="text" name="spa_address_city" id="spa_address_city" 
                       value="<?php echo esc_attr($city); ?>" 
                       class="regular-text">
            </td>
        </tr>
        <tr>
            <th><label for="spa_address_psc">PSČ:</label></th>
            <td>
                <input type="text" name="spa_address_psc" id="spa_address_psc" 
                       value="<?php echo esc_attr($psc); ?>" 
                       class="regular-text" 
                       placeholder="90101">
            </td>
        </tr>
        <tr>
            <th><label for="spa_variabilny_symbol">Variabilný symbol (VS):</label></th>
            <td>
                <input type="text" name="spa_variabilny_symbol" id="spa_variabilny_symbol" 
                       value="<?php echo esc_attr($vs); ?>" 
                       class="regular-text" 
                       placeholder="575">
            </td>
        </tr>
        <tr>
            <th><label for="spa_pin">PIN/Identifikátor:</label></th>
            <td>
                <input type="text" name="spa_pin" id="spa_pin" 
                       value="<?php echo esc_attr($pin); ?>" 
                       class="regular-text">
            </td>
        </tr>
    </table>
    <?php
}

/* ==================================================
   RENDER: CHILD FIELDY
   ================================================== */

function spa_render_child_fields($user) {
    $birthdate = get_user_meta($user->ID, SPA_META_BIRTHDATE, true);
    $parent_id = get_user_meta($user->ID, SPA_META_PARENT_ID, true);
    $health = get_user_meta($user->ID, SPA_META_HEALTH_NOTES, true);
    $rodne_cislo = get_user_meta($user->ID, SPA_META_RODNE_CISLO, true);
    
    ?>
    <h3>👧 Údaje dieťaťa</h3>
    <table class="form-table">
        <tr>
            <th><label for="spa_birthdate">Dátum narodenia:</label></th>
            <td>
                <input type="date" name="spa_birthdate" id="spa_birthdate" 
                       value="<?php echo esc_attr($birthdate); ?>">
            </td>
        </tr>
        <tr>
            <th><label for="spa_rodne_cislo">Rodné číslo:</label></th>
            <td>
                <input type="text" name="spa_rodne_cislo" id="spa_rodne_cislo" 
                       value="<?php echo esc_attr($rodne_cislo); ?>" 
                       class="regular-text"
                       placeholder="yymmddxxxx">
            </td>
        </tr>
        <tr>
            <th><label for="spa_health_notes">Zdravotné obmedzenia:</label></th>
            <td>
                <textarea name="spa_health_notes" id="spa_health_notes" 
                          rows="4" class="large-text"><?php echo esc_textarea($health); ?></textarea>
            </td>
        </tr>
        <tr>
            <th><label>Rodič:</label></th>
            <td>
                <?php 
                if ($parent_id) {
                    $parent = get_user_by('id', $parent_id);
                    echo $parent ? esc_html($parent->display_name) . ' (#' . $parent_id . ')' : 'Rodič nenájdený';
                } else {
                    echo '<em>Nie je priradený rodič</em>';
                }
                ?>
            </td>
        </tr>
    </table>
    <?php
}

/* ==================================================
   RENDER: CLIENT FIELDY
   ================================================== */

function spa_render_client_fields($user) {
    $birthdate = get_user_meta($user->ID, SPA_META_BIRTHDATE, true);
    $health = get_user_meta($user->ID, SPA_META_HEALTH_NOTES, true);
    $rodne_cislo = get_user_meta($user->ID, SPA_META_RODNE_CISLO, true);
    $phone = get_user_meta($user->ID, SPA_META_PHONE, true);
    
    ?>
    <h3>🧑 Údaje klienta (Dospelý)</h3>
    <table class="form-table">
        <tr>
            <th><label for="spa_birthdate">Dátum narodenia:</label></th>
            <td>
                <input type="date" name="spa_birthdate" id="spa_birthdate" 
                       value="<?php echo esc_attr($birthdate); ?>">
            </td>
        </tr>
        <tr>
            <th><label for="spa_rodne_cislo">Rodné číslo:</label></th>
            <td>
                <input type="text" name="spa_rodne_cislo" id="spa_rodne_cislo" 
                       value="<?php echo esc_attr($rodne_cislo); ?>" 
                       class="regular-text">
            </td>
        </tr>
        <tr>
            <th><label for="spa_phone">Telefón:</label></th>
            <td>
                <input type="tel" name="spa_phone" id="spa_phone" 
                       value="<?php echo esc_attr($phone); ?>" 
                       class="regular-text">
            </td>
        </tr>
        <tr>
            <th><label for="spa_health_notes">Zdravotné obmedzenia:</label></th>
            <td>
                <textarea name="spa_health_notes" id="spa_health_notes" 
                          rows="4" class="large-text"><?php echo esc_textarea($health); ?></textarea>
            </td>
        </tr>
    </table>
    <?php
}

/* ==================================================
   SAVE: User meta fieldy
   ================================================== */

add_action('personal_options_update', 'spa_save_user_meta_fields');
add_action('edit_user_profile_update', 'spa_save_user_meta_fields');

function spa_save_user_meta_fields($user_id) {
    if (!current_user_can('edit_user', $user_id)) {
        return;
    }
    
    // PARENT
    if (isset($_POST['spa_phone'])) {
        update_user_meta($user_id, SPA_META_PHONE, sanitize_text_field($_POST['spa_phone']));
    }
    if (isset($_POST['spa_address_street'])) {
        update_user_meta($user_id, SPA_META_ADDRESS_STREET, sanitize_text_field($_POST['spa_address_street']));
    }
    if (isset($_POST['spa_address_city'])) {
        update_user_meta($user_id, SPA_META_ADDRESS_CITY, sanitize_text_field($_POST['spa_address_city']));
    }
    if (isset($_POST['spa_address_psc'])) {
        update_user_meta($user_id, SPA_META_ADDRESS_PSC, sanitize_text_field($_POST['spa_address_psc']));
    }
    if (isset($_POST['spa_variabilny_symbol'])) {
        update_user_meta($user_id, SPA_META_VS, sanitize_text_field($_POST['spa_variabilny_symbol']));
    }
    if (isset($_POST['spa_pin'])) {
        update_user_meta($user_id, SPA_META_PIN, sanitize_text_field($_POST['spa_pin']));
    }
    
    // CHILD / CLIENT
    if (isset($_POST['spa_birthdate'])) {
        update_user_meta($user_id, SPA_META_BIRTHDATE, sanitize_text_field($_POST['spa_birthdate']));
    }
    if (isset($_POST['spa_rodne_cislo'])) {
        update_user_meta($user_id, SPA_META_RODNE_CISLO, sanitize_text_field($_POST['spa_rodne_cislo']));
    }
    if (isset($_POST['spa_health_notes'])) {
        update_user_meta($user_id, SPA_META_HEALTH_NOTES, sanitize_textarea_field($_POST['spa_health_notes']));
    }
}