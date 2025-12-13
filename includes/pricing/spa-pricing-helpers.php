<?php
/**
 * SPA Pricing Helpers - Cenovací systém (HELPERSKÝ MODUL)
 * 
 * @package Samuel Piasecký ACADEMY
 * @subpackage Pricing
 * @version 1.0.0
 * 
 * POZOR: Tento modul NIE JE meta box!
 * Registruje iba funckie na:
 * - Čítanie cien (starý formát → JSON)
 * - Kalkuláciu cien s udalosťami
 * - AJAX na frontend
 * 
 * Meta box zostáva v spa-meta-boxes.php
 * Uloženie zostáva v spa-meta-boxes.php
 * 
 * Funkcionalita:
 * - spa_get_program_price_by_frequency()
 * - spa_get_season_for_date()
 * - spa_calculate_training_days()
 * - AJAX: spa_ajax_calculate_price
 */

if (!defined('ABSPATH')) {
    exit;
}

/* ==================================================
   PUBLIC API: Získaj cenu podľa sezóny a frekvencie
   ================================================== */
* ==================================================
   PUBLIC API: Získaj cenu podľa sezóny a frekvencie
   ================================================== */

function spa_get_program_price_by_season_and_frequency($program_id, $season_key, $frequency = 1) {
    $program = get_post($program_id);
    if (!$program || $program->post_type !== 'spa_group') {
        return 0;
    }
    
    // NOVÝ FORMÁT: sezónne ceny
    $pricing_seasons = get_post_meta($program_id, 'spa_pricing_seasons', true);
    
    if (is_array($pricing_seasons) && isset($pricing_seasons[$season_key])) {
        $freq_key = $frequency . 'x'; // "1x", "2x", "3x"
        return floatval($pricing_seasons[$season_key][$freq_key] ?? 0);
    }
    
    // FALLBACK: staré polia (pre kompatibilitu)
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
   (Automaticky zistí sezónu z dátumu)
   ================================================== */

function spa_get_program_price_by_frequency($program_id, $frequency = 1, $date_string = null) {
    if ($date_string === null) {
        $date_string = current_time('Y-m-d');
    }
    
    $season = spa_get_season_for_date($date_string);
    return spa_get_program_price_by_season_and_frequency($program_id, $season, $frequency);
}

/* ==================================================
   PUBLIC API: Sezóna podľa dátumu (ŠKOLSKÝ ROK)
   ================================================== */

function spa_get_season_for_date($date_string) {
    try {
        $date = new DateTime($date_string);
        $month = intval($date->format('m'));
    } catch (Exception $e) {
        return 'sep_dec'; // Default
    }
    
    // Školský rok začína SEPTEMBER
    if ($month >= 9 && $month <= 12) return 'sep_dec';    // 09-12 (september-december)
    if ($month >= 1 && $month <= 3) return 'jan_mar';     // 01-03 (január-marec)
    if ($month >= 4 && $month <= 6) return 'apr_jun';     // 04-06 (apríl-jún)
    if ($month >= 7 && $month <= 8) return 'jul_aug';     // 07-08 (júl-august, letné prázdniny)
    
    return 'sep_dec';
}

/* ==================================================
   PUBLIC API: Sezóna pre AKTUÁLNY dátum (dnes)
   ================================================== */

function spa_get_season_for_current_date() {
    $current_month = intval(date('m'));
    
    // Školský rok
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
    
    return 'sep_dec'; // Default
}

/* ==================================================
   PUBLIC API: Zisti kedy končí sezóna
   ================================================== */

function spa_get_season_end_month($season_key) {
    $ends = [
        'oct_dec' => 12,
        'jan_mar' => 3,
        'apr_jun' => 6,
        'jul_sep' => 9
    ];
    return $ends[$season_key] ?? 12;
}

/* ==================================================
   PUBLIC API: Vygeneruj dátumy tréningov
   ================================================== */

function spa_calculate_training_days($program_id, $start_date, $end_month = 12) {
    // Zisti dni v týždni z rozvrhu
    $schedule_days = get_post_meta($program_id, 'spa_schedule_days', true);
    
    if (!is_array($schedule_days) || empty($schedule_days)) {
        return [
            'total_count' => 0,
            'real_count' => 0,
            'blocked_count' => 0,
            'training_dates' => [],
            'blocked_dates' => [],
            'error' => 'Rozvrh programu nie je nastavený'
        ];
    }
    
    try {
        $start = new DateTime($start_date);
    } catch (Exception $e) {
        return [
            'total_count' => 0,
            'real_count' => 0,
            'blocked_count' => 0,
            'training_dates' => [],
            'blocked_dates' => [],
            'error' => 'Neplatný dátum'
        ];
    }
    
    // Koniec obdobia
    $end = clone $start;
    $end->setDate($start->format('Y'), $end_month, 1);
    $end->modify('last day of this month');
    
    // Generuj všetky dátumy
    $all_dates = [];
    $current = clone $start;
    $current->modify('midnight');
    
    while ($current <= $end) {
        $day_num = intval($current->format('N')); // 1=Monday, 7=Sunday
        $day_key = spa_get_day_key_from_weekday_number($day_num);
        
        if (in_array($day_key, $schedule_days)) {
            $all_dates[] = $current->format('Y-m-d');
        }
        
        $current->modify('+1 day');
    }
    
    // Zisti blokované dátumy
    $place_id = get_post_meta($program_id, 'spa_place_id', true);
    $blocked_dates = spa_get_blocked_dates_by_place($place_id);
    
    // Filtruj
    $training_dates = array_filter($all_dates, fn($date) => !in_array($date, $blocked_dates));
    
    return [
        'total_count' => count($all_dates),
        'real_count' => count($training_dates),
        'blocked_count' => count($all_dates) - count($training_dates),
        'training_dates' => array_values($training_dates),
        'blocked_dates' => $blocked_dates,
        'error' => null
    ];
}

/* ==================================================
   HELPER: Konverzia čísla weekday na kľúč
   ================================================== */

function spa_get_day_key_from_weekday_number($day_num) {
    $map = [
        1 => 'mo',  // Monday
        2 => 'tu',  // Tuesday
        3 => 'we',  // Wednesday
        4 => 'th',  // Thursday
        5 => 'fr',  // Friday
        6 => 'sa',  // Saturday
        7 => 'su'   // Sunday
    ];
    return $map[$day_num] ?? null;
}

/* ==================================================
   HELPER: Zisti blokované dátumy (Udalosti)
   ================================================== */

function spa_get_blocked_dates_by_place($place_id) {
    if (!$place_id) {
        return [];
    }
    
    $events = get_posts([
        'post_type' => 'spa_event',
        'posts_per_page' => -1,
        'meta_query' => [
            [
                'key' => 'spa_event_place_id',
                'value' => intval($place_id),
                'compare' => '='
            ]
        ]
    ]);
    
    $blocked_dates = [];
    
    foreach ($events as $event) {
        $date_from = get_post_meta($event->ID, 'spa_event_date_from', true);
        $date_to = get_post_meta($event->ID, 'spa_event_date_to', true);
        
        if (!$date_from) continue;
        if (!$date_to) $date_to = $date_from;
        
        try {
            $current = new DateTime($date_from);
            $end = new DateTime($date_to);
            $end->modify('+1 day');
            
            while ($current < $end) {
                $blocked_dates[] = $current->format('Y-m-d');
                $current->modify('+1 day');
            }
        } catch (Exception $e) {
            continue;
        }
    }
    
    return array_unique($blocked_dates);
}

/* ==================================================
   PUBLIC API: Vypočítaj finálnu cenu
   ================================================== */

function spa_calculate_final_price($program_id, $frequency, $start_date, $end_month = 12) {
    // Základná cena - PODĽA SEZÓNY
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
            'error' => 'Cena nie je nastavená pre túto sezónu/frekvenciu'
        ];
    }
    
    // Zisti dátumy
    $training_info = spa_calculate_training_days($program_id, $start_date, $end_month);
    
    if ($training_info['error']) {
        return [
            'base_price' => 0,
            'final_price' => 0,
            'total_training_days' => 0,
            'real_training_days' => 0,
            'blocked_days' => 0,
            'season' => spa_get_season_for_date($start_date),
            'frequency' => $frequency,
            'error' => $training_info['error']
        ];
    }
    
    $total_days = $training_info['total_count'];
    $real_days = $training_info['real_count'];
    
    if ($total_days === 0) {
        return [
            'base_price' => $base_price,
            'final_price' => 0,
            'total_training_days' => 0,
            'real_training_days' => 0,
            'blocked_days' => 0,
            'season' => spa_get_season_for_date($start_date),
            'frequency' => $frequency,
            'error' => 'Žiadne tréningy v období'
        ];
    }
    
    // Prepočet: (cena_za_všetky_dni / počet_všetkých) * počet_reálnych
    $final_price = ($base_price / $total_days) * $real_days;
    $final_price = round($final_price, 2);
    
    return [
        'base_price' => $base_price,
        'final_price' => $final_price,
        'total_training_days' => $total_days,
        'real_training_days' => $real_days,
        'blocked_days' => $training_info['blocked_count'],
        'season' => spa_get_season_for_date($start_date),
        'frequency' => $frequency,
        'error' => null
    ];
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
        'message' => sprintf(
            'Počet tréningov: %d/%d (blokované: %d)',
            $result['real_training_days'],
            $result['total_training_days'],
            $result['blocked_days']
        )
    ]);
}

/* ==================================================
   ADMIN ENQUEUE: JS na frontend (AJAX)
   ================================================== */

add_action('wp_enqueue_scripts', 'spa_pricing_enqueue_scripts');

function spa_pricing_enqueue_scripts() {
    wp_localize_script('jquery', 'spaAjax', [
        'ajaxurl' => admin_url('admin-ajax.php'),
        'action' => 'spa_calculate_price'
    ]);
}