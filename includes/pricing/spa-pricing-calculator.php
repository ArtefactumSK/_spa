<?php
/**
 * SPA Pricing Calculator - Výpočet cien s udalosťami
 * 
 * @package Samuel Piasecký ACADEMY
 * @subpackage Pricing
 * @version 1.0.0
 * 
 * FEATURES:
 * - Výpočet reálnych tréningových dní (bez udalostí)
 * - Prepočet ceny praporcionálne
 * - Kontrola obsadenosti programu
 * - Frontend AJAX kalkulácia
 * 
 * USES:
 * - spa-pricing-meta.php (spa_get_program_price)
 * - spa-cpt-event.php (udalosti)
 * - spa-cpt-place.php (miesta)
 */

if (!defined('ABSPATH')) {
    exit;
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
        wp_send_json_error('Chýbajúce parametre');
    }
    
    $program = get_post($program_id);
    if (!$program || $program->post_type !== 'spa_group') {
        wp_send_json_error('Program neexistuje');
    }
    
    // Zisti dni v týždni z rozvrhu programu
    $schedule_days = get_post_meta($program_id, 'spa_schedule_days', true);
    if (!is_array($schedule_days) || empty($schedule_days)) {
        wp_send_json_error('Rozvrh programu nie je nastavený');
    }
    
    // Filtrovať podľa frekvencie
    if ($frequency === 1) {
        // Najdi prvý deň
        $schedule_days = array_slice($schedule_days, 0, 1);
    } elseif ($frequency === 2) {
        // Prvé dva dni
        $schedule_days = array_slice($schedule_days, 0, 2);
    }
    
    // Vygeneruj všetky dátumy tréningov
    $training_dates = spa_generate_training_dates(
        $start_date,
        $schedule_days,
        $end_month
    );
    
    // Odfiltruj blokované dátumy (udalosti)
    $place_id = get_post_meta($program_id, 'spa_place_id', true);
    $blocked_dates = spa_get_blocked_dates($place_id);
    
    $real_training_dates = array_filter($training_dates, function($date) use ($blocked_dates) {
        return !in_array($date, $blocked_dates);
    });
    
    // Vypočítaj cenu
    $base_price = spa_get_program_price($program_id, $start_date, $frequency, false);
    
    // Prepočet: cena za reálny počet tréningov
    $total_training_count = count($training_dates);
    $real_training_count = count($real_training_dates);
    
    if ($total_training_count === 0) {
        wp_send_json_error('Žiadne tréningy v období');
    }
    
    $final_price = ($base_price / $total_training_count) * $real_training_count;
    $final_price = round($final_price, 2);
    
    wp_send_json_success([
        'base_price' => $base_price,
        'final_price' => $final_price,
        'total_training_count' => $total_training_count,
        'real_training_count' => $real_training_count,
        'blocked_count' => $total_training_count - $real_training_count,
        'training_dates' => $real_training_dates,
        'message' => sprintf(
            'Počet tréningov: %d/%d (blokované: %d)',
            $real_training_count,
            $total_training_count,
            $total_training_count - $real_training_count
        )
    ]);
}

/* ==================================================
   HELPER: Generovanie dátumov tréningov
   ================================================== */

function spa_generate_training_dates($start_date, $schedule_days, $end_month = 12) {
    $dates = [];
    $start = new DateTime($start_date);
    $start->modify('midnight');
    
    // Koniec obdobia (31. deň mesiac/roku)
    $end = clone $start;
    $end->setDate($start->format('Y'), $end_month, 28); // Bezpečný deň
    $end->modify('last day of this month');
    
    // Generuj dátumy pre každý týždeň
    while ($start <= $end) {
        $day_name = strtolower($start->format('l')); // Monday, Tuesday, ...
        $day_key = substr($day_name, 0, 2); // Mo, Tu, We, ...
        
        if (in_array($day_key, $schedule_days)) {
            $dates[] = $start->format('Y-m-d');
        }
        
        $start->modify('+1 day');
    }
    
    return $dates;
}

/* ==================================================
   HELPER: Zisti blokované dátumy (Udalosti)
   ================================================== */

function spa_get_blocked_dates($place_id) {
    if (!$place_id) {
        return [];
    }
    
    // Zisti všetky udalosti pre miesto
    $events = get_posts([
        'post_type' => 'spa_event',
        'posts_per_page' => -1,
        'meta_query' => [
            [
                'key' => 'spa_event_place_id',
                'value' => $place_id,
                'compare' => '='
            ]
        ]
    ]);
    
    $blocked_dates = [];
    
    foreach ($events as $event) {
        $date_from = get_post_meta($event->ID, 'spa_event_date_from', true);
        $date_to = get_post_meta($event->ID, 'spa_event_date_to', true);
        $is_all_day = get_post_meta($event->ID, 'spa_event_all_day', true);
        
        if (!$date_from) continue;
        
        if (!$date_to) {
            $date_to = $date_from;
        }
        
        // Generuj všetky dátumy v rozsahu
        $current = new DateTime($date_from);
        $end = new DateTime($date_to);
        $end->modify('+1 day');
        
        while ($current < $end) {
            $blocked_dates[] = $current->format('Y-m-d');
            $current->modify('+1 day');
        }
    }
    
    return array_unique($blocked_dates);
}

/* ==================================================
   API: Vypočítaj cenu pre GF
   ================================================== */

function spa_calculate_registration_price($program_id, $frequency, $start_date, $end_month = 12) {
    $schedule_days = get_post_meta($program_id, 'spa_schedule_days', true);
    
    if (!is_array($schedule_days) || empty($schedule_days)) {
        return 0;
    }
    
    // Filtruj podľa frekvencie
    if ($frequency === 1) {
        $schedule_days = array_slice($schedule_days, 0, 1);
    } elseif ($frequency === 2) {
        $schedule_days = array_slice($schedule_days, 0, 2);
    }
    
    // Vygeneruj dátumy
    $training_dates = spa_generate_training_dates($start_date, $schedule_days, $end_month);
    
    // Odfiltruj udalosti
    $place_id = get_post_meta($program_id, 'spa_place_id', true);
    $blocked_dates = spa_get_blocked_dates($place_id);
    $real_dates = array_filter($training_dates, fn($date) => !in_array($date, $blocked_dates));
    
    // Cena
    $base_price = spa_get_program_price($program_id, $start_date, $frequency, false);
    
    if (count($training_dates) === 0) {
        return 0;
    }
    
    $final_price = ($base_price / count($training_dates)) * count($real_dates);
    return round($final_price, 2);
}

/* ==================================================
   HELPER: Konverzia dní (Mo, Tu, We, ...)
   ================================================== */

function spa_day_name_to_key($day_name) {
    $map = [
        'monday' => 'mo',
        'tuesday' => 'tu',
        'wednesday' => 'we',
        'thursday' => 'th',
        'friday' => 'fr',
        'saturday' => 'sa',
        'sunday' => 'su'
    ];
    return $map[strtolower($day_name)] ?? null;
}