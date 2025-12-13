<?php
/**
 * SPA Pricing Helpers - Cenovací systém (HELPERSKÝ MODUL)
 * OPRAVENÉ: Správne sezóny (sep_dec, jan_mar, apr_jun, jul_aug)
 */

if (!defined('ABSPATH')) {
    exit;
}

/* ==================================================
   PUBLIC API: Sezóna podľa dátumu (ŠKOLSKÝ ROK)
   ================================================== */

function spa_get_season_for_date($date_string) {
    try {
        $date = new DateTime($date_string);
        $month = intval($date->format('m'));
    } catch (Exception $e) {
        return 'sep_dec';
    }
    
    if ($month >= 9 && $month <= 12) return 'sep_dec';    // 09-12
    if ($month >= 1 && $month <= 3) return 'jan_mar';     // 01-03
    if ($month >= 4 && $month <= 6) return 'apr_jun';     // 04-06
    if ($month >= 7 && $month <= 8) return 'jul_aug';     // 07-08
    
    return 'sep_dec';
}

/* ==================================================
   PUBLIC API: Sezóna pre AKTUÁLNY dátum (dnes)
   ================================================== */

function spa_get_season_for_current_date() {
    $current_month = intval(date('m'));
    
    if ($current_month >= 9 && $current_month <= 12) {
        return 'sep_dec';
    }
    if ($current_month >= 1 && $current_month <= 3) {
        return 'jan_mar';
    }
    if ($current_month >= 4 && $current_month <= 6) {
        return 'apr_jun';
    }
    if ($current_month >= 7 && $current_month <= 8) {
        return 'jul_aug';
    }
    
    return 'sep_dec';
}

/* ==================================================
   PUBLIC API: Získaj cenu podľa sezóny a frekvencie
   ================================================== */

function spa_get_program_price_by_season_and_frequency($program_id, $season_key, $frequency = 1) {
    $program = get_post($program_id);
    if (!$program || $program->post_type !== 'spa_group') {
        return 0;
    }
    
    $pricing_seasons = get_post_meta($program_id, 'spa_pricing_seasons', true);
    
    if (is_array($pricing_seasons) && isset($pricing_seasons[$season_key])) {
        $freq_key = $frequency . 'x';
        return floatval($pricing_seasons[$season_key][$freq_key] ?? 0);
    }
    
    switch ($frequency) {
        case 1:
            $price = floatval(get_post_meta($program_id, 'spa_price_1x_weekly', true));
            break;
        case 2:
            $price = floatval(get_post_meta($program_id, 'spa_price_2x_weekly', true));
            break;
        default:
            $price = 0;
    }
    
    return $price;
}

/* ==================================================
   PUBLIC API: Získaj cenu podľa dátumu a frekvencie
   ================================================== */

function spa_get_program_price_by_frequency($program_id, $frequency = 1, $date_string = null) {
    if ($date_string === null) {
        $date_string = current_time('Y-m-d');
    }
    
    $season = spa_get_season_for_date($date_string);
    return spa_get_program_price_by_season_and_frequency($program_id, $season, $frequency);
}

/* ==================================================
   PUBLIC API: Zisti koniec sezóny
   ================================================== */

function spa_get_season_end_month($season_key) {
    $ends = [
        'sep_dec' => 12,
        'jan_mar' => 3,
        'apr_jun' => 6,
        'jul_aug' => 8
    ];
    return $ends[$season_key] ?? 12;
}

/* ==================================================
   FRONTEND AJAX: Kalkulácia ceny
   ================================================== */

add_action('wp_ajax_spa_calculate_price', 'spa_ajax_calculate_price');
add_action('wp_ajax_nopriv_spa_calculate_price', 'spa_ajax_calculate_price');

function spa_ajax_calculate_price() {
    $program_id = intval($_POST['program_id'] ?? 0);
    $frequency = intval($_POST['frequency'] ?? 1);
    $start_date = sanitize_text_field($_POST['start_date'] ?? '');
    $end_month = intval($_POST['end_month'] ?? 12);
    
    if (!$program_id || !$start_date) {
        wp_send_json_error(['message' => 'Chýbajúce parametre']);
    }
    
    $result = spa_calculate_final_price($program_id, $frequency, $start_date, $end_month);
    
    if ($result['error']) {
        wp_send_json_error(['message' => $result['error']]);
    }
    
    wp_send_json_success([
        'base_price' => $result['base_price'],
        'final_price' => $result['final_price'],
        'total_training_days' => $result['total_training_days'],
        'real_training_days' => $result['real_training_days'],
        'blocked_days' => $result['blocked_days'],
        'season' => $result['season'],
        'frequency' => $result['frequency'],
        'message' => sprintf(
            'Počet tréningov: %d/%d (blokované: %d)',
            $result['real_training_days'],
            $result['total_training_days'],
            $result['blocked_days']
        )
    ]);
}

/* ==================================================
   ADMIN ENQUEUE: JS na frontend
   ================================================== */

add_action('wp_enqueue_scripts', 'spa_pricing_enqueue_scripts');

function spa_pricing_enqueue_scripts() {
    wp_localize_script('jquery', 'spaAjax', [
        'ajaxurl' => admin_url('admin-ajax.php'),
        'action' => 'spa_calculate_price'
    ]);
}

/* ==================================================
   PUBLIC API: Vypočítaj finálnu cenu s dňami
   ================================================== */

function spa_calculate_final_price($program_id, $frequency, $start_date, $end_month = 12) {
    $base_price = spa_get_program_price_by_frequency($program_id, $frequency, $start_date);
    
    if ($base_price <= 0) {
        return [
            'base_price' => 0,
            'final_price' => 0,
            'total_training_days' => 0,
            'real_training_days' => 0,
            'blocked_days' => 0,
            'season' => spa_get_season_for_date($start_date),
            'frequency' => $frequency,
            'error' => 'Cena nie je nastavená'
        ];
    }
    
    $total_days = 1; // Simplified - pre kalkuláciu bez tréningových dní
    $real_days = 1;
    $final_price = $base_price;
    
    return [
        'base_price' => $base_price,
        'final_price' => round($final_price, 2),
        'total_training_days' => $total_days,
        'real_training_days' => $real_days,
        'blocked_days' => 0,
        'season' => spa_get_season_for_date($start_date),
        'frequency' => $frequency,
        'error' => null
    ];
}