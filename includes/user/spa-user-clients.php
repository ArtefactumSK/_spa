<?php
/**
 * SPA User Clients - Funkcie pre dospelých klientov
 * 
 * @package Samuel Piasecký ACADEMY
 * @subpackage User/Clients
 * @version 1.0.0
 * 
 * PARENT MODULES: spa-user-fields.php
 * CHILD MODULES: registration/spa-registration-form.php
 * 
 * FUNCTIONS DEFINED:
 * - spa_get_or_create_client()
 * - spa_client_exists()
 * - spa_get_client_registrations()
 */

if (!defined('ABSPATH')) {
    exit;
}

/* ==================================================
   CLIENT: Get or Create (Dospelý)
   ================================================== */

function spa_get_or_create_client($email, $first_name, $last_name, $phone = '', $birthdate = '', $health_notes = '', $rodne_cislo = '') {
    
    // 1. Hľadaj existujúceho
    $client = get_user_by('email', $email);
    
    if ($client && in_array('spa_client', $client->roles ?? [])) {
        // Update existujúceho
        update_user_meta($client->ID, SPA_META_PHONE, sanitize_text_field($phone));
        if ($birthdate) {
            update_user_meta($client->ID, SPA_META_BIRTHDATE, sanitize_text_field($birthdate));
        }
        if ($health_notes) {
            update_user_meta($client->ID, SPA_META_HEALTH_NOTES, sanitize_textarea_field($health_notes));
        }
        if ($rodne_cislo) {
            update_user_meta($client->ID, SPA_META_RODNE_CISLO, sanitize_text_field($rodne_cislo));
        }
        
        spa_log('Client updated: ' . $email, ['client_id' => $client->ID]);
        return $client->ID;
    }
    
    // 2. Vytvor nového klienta
    $username = spa_generate_username($first_name, $last_name);
    $password = wp_generate_password(12, true);
    
    $client_id = wp_create_user($username, $password, $email);
    
    if (is_wp_error($client_id)) {
        spa_log('Error creating client: ' . $client_id->get_error_message());
        return false;
    }
    
    // Role + meta
    $client = new WP_User($client_id);
    $client->set_role('spa_client');
    
    wp_update_user([
        'ID' => $client_id,
        'first_name' => sanitize_text_field($first_name),
        'last_name' => sanitize_text_field($last_name),
        'display_name' => $first_name . ' ' . $last_name
    ]);
    
    update_user_meta($client_id, SPA_META_PHONE, sanitize_text_field($phone));
    if ($birthdate) {
        update_user_meta($client_id, SPA_META_BIRTHDATE, sanitize_text_field($birthdate));
    }
    if ($health_notes) {
        update_user_meta($client_id, SPA_META_HEALTH_NOTES, sanitize_textarea_field($health_notes));
    }
    if ($rodne_cislo) {
        update_user_meta($client_id, SPA_META_RODNE_CISLO, sanitize_text_field($rodne_cislo));
    }
    
    spa_send_welcome_email($email, $username, $password, $first_name);
    spa_log('Client created: ' . $email, ['client_id' => $client_id]);
    
    return $client_id;
}

/* ==================================================
   CLIENT: Check existence
   ================================================== */

function spa_client_exists($email) {
    $user = get_user_by('email', $email);
    return $user && in_array('spa_client', $user->roles ?? []);
}

/* ==================================================
   CLIENT: Get all registrations
   ================================================== */

function spa_get_client_registrations($client_id) {
    global $wpdb;
    
    $registrations = $wpdb->get_results($wpdb->prepare(
        "SELECT p.ID, p.post_title, pm.meta_value as program_id, pm2.meta_value as status
         FROM {$wpdb->posts} p
         JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
         JOIN {$wpdb->postmeta} pm2 ON p.ID = pm2.post_id
         WHERE p.post_type = %s
         AND pm.meta_key = %s AND pm.meta_value = %s
         AND pm2.meta_key = %s",
        'spa_registration', 'spa_client_id', $client_id, 'spa_status'
    ));
    
    return $registrations ?: [];
}

/* ==================================================
   HELPER: spa_send_welcome_email (fallback)
   ================================================== */

if (!function_exists('spa_send_welcome_email')) {
    function spa_send_welcome_email($email, $username, $password, $name) {
        $subject = 'Vitajte v Samuel Piašecký Academy!';
        $message = sprintf(
            "Ahoj %s,\n\n" .
            "Váš účet bol vytvorený!\n\n" .
            "Prihlasovacie údaje:\n" .
            "Používateľ: %s\n" .
            "Heslo: %s\n\n" .
            "Prihlásiť sa môžete na: %s\n\n" .
            "Odporúčame zmeniť heslo po prvom prihlásení.\n\n" .
            "S pozdravom,\nSamuel Piašecký Academy",
            $name, $username, $password, wp_login_url()
        );
        
        wp_mail($email, $subject, $message);
    }
}

/* ==================================================
   HELPER: spa_generate_username (fallback)
   ================================================== */

if (!function_exists('spa_generate_username')) {
    function spa_generate_username($first_name, $last_name) {
        // Remove diacritics
        $chars = [
            'á'=>'a', 'ä'=>'a', 'č'=>'c', 'ď'=>'d', 'é'=>'e', 'ě'=>'e',
            'í'=>'i', 'ľ'=>'l', 'ĺ'=>'l', 'ň'=>'n', 'ó'=>'o', 'ô'=>'o',
            'ŕ'=>'r', 'ř'=>'r', 'š'=>'s', 'ť'=>'t', 'ú'=>'u', 'ů'=>'u',
            'ý'=>'y', 'ž'=>'z',
            'Á'=>'A', 'Ä'=>'A', 'Č'=>'C', 'Ď'=>'D', 'É'=>'E', 'Ě'=>'E',
            'Í'=>'I', 'Ľ'=>'L', 'Ĺ'=>'L', 'Ň'=>'N', 'Ó'=>'O', 'Ô'=>'O',
            'Ŕ'=>'R', 'Ř'=>'R', 'Š'=>'S', 'Ť'=>'T', 'Ú'=>'U', 'Ů'=>'U',
            'Ý'=>'Y', 'Ž'=>'Z'
        ];
        
        $first = strtolower(preg_replace('/[^a-z0-9]/i', '', strtr($first_name, $chars)));
        $last = strtolower(preg_replace('/[^a-z0-9]/i', '', strtr($last_name, $chars)));
        
        $base = $first . '.' . $last;
        if (strlen($base) > 50) {
            $base = substr($base, 0, 50);
        }
        
        $username = $base;
        $counter = 1;
        while (username_exists($username)) {
            $username = $base . $counter;
            $counter++;
        }
        
        return $username;
    }
}