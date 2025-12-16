<?php
/**
 * SPA Import - Shared Helpers
 * Spoločné funkcie pre všetky import helpery
 * 
 * POUŽÍVANÉ: spa-import-user-parent.php, spa-import-user-child.php, spa-import-registration.php
 * 
 * @package Samuel Piasecký ACADEMY
 * @subpackage Import
 * @version 1.0.0
 */
error_log('LOADED: spa-import-helpers.php');

if (!defined('ABSPATH')) {
    exit;
}

/* ==========================
   VARIABILNÝ SYMBOL - GENERATOR
   3-miestny kód, unikátny
   ========================== */

function spa_import_generate_variabilny_symbol() {
    global $wpdb;
    
    $max_vs = $wpdb->get_var("
        SELECT MAX(CAST(meta_value AS UNSIGNED)) 
        FROM {$wpdb->usermeta} 
        WHERE meta_key = 'variabilny_symbol'
        AND meta_value REGEXP '^[0-9]{3,}$'
    ");
    
    $next_vs = $max_vs ? intval($max_vs) + 1 : 100;
    
    if ($next_vs < 100) {
        $next_vs = 100;
    }
    
    while (spa_import_vs_exists($next_vs)) {
        $next_vs++;
    }
    
    return str_pad($next_vs, 3, '0', STR_PAD_LEFT);
}

function spa_import_vs_exists($vs) {
    global $wpdb;
    return $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$wpdb->usermeta} WHERE meta_key = 'variabilny_symbol' AND meta_value = %s",
        $vs
    )) > 0;
}

/* ==========================
   PIN - GENERATOR
   4-miestny kód pre deti
   ========================== */

function spa_import_generate_pin() {
    return str_pad(rand(1000, 9999), 4, '0', STR_PAD_LEFT);
}

function spa_import_hash_pin($pin) {
    return wp_hash_password($pin);
}

/* ==========================
   USERNAME - GENERATOR
   Z emailu alebo mena
   ========================== */

function spa_import_generate_username_from_email($email) {
    $username = sanitize_user(strtolower(str_replace(['@', '.'], ['_', '_'], $email)));
    
    $base_username = $username;
    $counter = 1;
    while (username_exists($username)) {
        $username = $base_username . $counter;
        $counter++;
    }
    
    return $username;
}