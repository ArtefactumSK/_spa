<?php
/**
 * SPA Import - Registration Helper
 * Vytvára registráciu s KOREKTNÝM post_title + všetkými meta
 * 
 * POUŽÍVANÉ IBA: spa-import-csv-v2.php
 * ŽIADNE: zásahy do spa-registration-helpers.php
 * 
 * @package Samuel Piasecký ACADEMY
 * @subpackage Import
 * @version 1.0.0
 */
error_log('LOADED: spa-import-registration.php');

if (!defined('ABSPATH')) {
    exit;
}
// ═══════════════════════════════════════════════════════════════════════════════
// FIX 3: /includes/import/spa-import-registration.php
// OPRAVA: Predvyplnenie training_day z parametrov
// ═══════════════════════════════════════════════════════════════════════════════

function spa_import_create_registration($child_user_id, $parent_user_id, $program_id, $training_day = '', $training_time = '', $filename = '') {
    
    if (empty($child_user_id) || empty($parent_user_id) || empty($program_id)) {
        error_log('[SPA IMPORT] Registration ERROR: Missing params - child=' . $child_user_id . ', parent=' . $parent_user_id . ', program=' . $program_id);
        return false;
    }
    
    // 1. ZISKAJ MENO DIEŤAŤA
    $child_user = get_userdata($child_user_id);
    if (!$child_user) {
        error_log('[SPA IMPORT] Registration ERROR: Child user not found ID=' . $child_user_id);
        return false;
    }
    
    $child_first_name = get_user_meta($child_user_id, 'first_name', true);
    $child_last_name = get_user_meta($child_user_id, 'last_name', true);
    $registration_title = trim($child_first_name . ' ' . $child_last_name);
    
    // 2. VYTVOR REGISTRATION POST
    $registration_id = wp_insert_post([
        'post_type' => 'spa_registration',
        'post_title' => $registration_title,
        'post_status' => 'publish',
        'post_author' => 1
    ], true);
    
    if (is_wp_error($registration_id) || !$registration_id) {
        error_log('[SPA IMPORT] Registration ERROR: wp_insert_post failed - ' . (is_wp_error($registration_id) ? $registration_id->get_error_message() : 'unknown'));
        return false;
    }
    
    error_log('[SPA IMPORT] Registration post created ID=' . $registration_id . ' title=' . $registration_title);
    
    // 3. ULOŽ POVINNÉ META
    update_post_meta($registration_id, 'client_user_id', intval($child_user_id));
    error_log('[SPA IMPORT] Registration meta client_user_id=' . $child_user_id);
    
    update_post_meta($registration_id, 'parent_user_id', intval($parent_user_id));
    error_log('[SPA IMPORT] Registration meta parent_user_id=' . $parent_user_id);
    
    update_post_meta($registration_id, 'program_id', intval($program_id));
    error_log('[SPA IMPORT] Registration meta program_id=' . $program_id);
    
    update_post_meta($registration_id, 'status', 'active');
    update_post_meta($registration_id, 'registration_date', current_time('mysql'));
    
    // 4. ULOŽ VOLITEĽNÉ META - PREDVYPLNENIE training_day (OPRAVENÉ!)
    if (!empty($training_day)) {
        update_post_meta($registration_id, 'training_day', sanitize_text_field($training_day));
        error_log('[SPA IMPORT] Registration meta training_day=' . $training_day);
    }
    
    if (!empty($training_time)) {
        update_post_meta($registration_id, 'training_time', sanitize_text_field($training_time));
        error_log('[SPA IMPORT] Registration meta training_time=' . $training_time);
    }
    
    // 5. ULOŽ IMPORT INFO
    update_post_meta($registration_id, 'import_source', 'csv');
    
    if (!empty($filename)) {
        update_post_meta($registration_id, 'import_filename', sanitize_text_field($filename));
    }
    
    update_post_meta($registration_id, 'import_timestamp', current_time('mysql'));
    
    error_log('[SPA IMPORT] Registration SUCCESS ID=' . $registration_id . ' child=' . $child_user_id . ' parent=' . $parent_user_id . ' program=' . $program_id);
    
    return $registration_id;
}