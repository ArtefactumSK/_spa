<?php
/**
 * SPA Helpers - Pomocné funkcie
 * 
 * @package Samuel Piasecký ACADEMY
 * @version 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/* ==========================
   DNI TÝŽDŇA - PREKLAD
   ========================== */

function spa_get_day_name($day_en) {
    $days = [
        'monday' => 'Pondelok',
        'tuesday' => 'Utorok',
        'wednesday' => 'Streda',
        'thursday' => 'Štvrtok',
        'friday' => 'Piatok',
        'saturday' => 'Sobota',
        'sunday' => 'Nedeľa'
    ];
    
    return $days[strtolower($day_en)] ?? $day_en;
}

function spa_get_day_value($day_sk) {
    $days = [
        'Pondelok' => 'monday',
        'Utorok' => 'tuesday',
        'Streda' => 'wednesday',
        'Štvrtok' => 'thursday',
        'Piatok' => 'friday',
        'Sobota' => 'saturday',
        'Nedeľa' => 'sunday'
    ];
    
    return $days[$day_sk] ?? strtolower($day_sk);
}

function spa_get_days_array() {
    return [
        'monday' => 'Pondelok',
        'tuesday' => 'Utorok',
        'wednesday' => 'Streda',
        'thursday' => 'Štvrtok',
        'friday' => 'Piatok',
        'saturday' => 'Sobota',
        'sunday' => 'Nedeľa'
    ];
}

/* ==========================
   KATEGÓRIE - FARBY
   ========================== */

function spa_get_category_color($category_name) {
    $colors = [
        // Normalizované názvy (bez diakritiky pre istotu)
        'Deti s rodicmi 1,8-3 roky' => 'var(--theme-palette-color-12)',
        'Deti s rodičmi 1,8-3 roky' => 'var(--theme-palette-color-12)',
        'DETI S RODIČMI 1,8-3 ROKY' => 'var(--theme-palette-color-12)',
        
        'Deti 3-4 roky' => 'var(--theme-palette-color-11)',
        'DETI 3-4 ROKY' => 'var(--theme-palette-color-11)',
        
        'Deti 5-7 rokov' => 'var(--theme-palette-color-1)',
        'DETI 5-7 ROKOV' => 'var(--theme-palette-color-1)',
        
        'Deti 8-10 rokov' => 'var(--theme-palette-color-4)',
        'DETI 8-10 ROKOV' => 'var(--theme-palette-color-4)',

        'Deti 8-12 rokov' => 'var(--theme-palette-color-4)',
        'DETI 8-12 ROKOV' => 'var(--theme-palette-color-4)',
        
        'Deti 10+ rokov' => 'var(--theme-palette-color-14)',
        'DETI 10+ ROKOV' => 'var(--theme-palette-color-14)',
        
        'Deti 8+ rokov' => 'var(--theme-palette-color-2)',
        'DETI 8+ ROKOV' => 'var(--theme-palette-color-2)',
        
        'Deti MS' => 'var(--theme-palette-color-9)',
        'Deti MŠ' => 'var(--theme-palette-color-9)',
        'DETI MŠ' => 'var(--theme-palette-color-9)',

        'juniori' => 'var(--theme-palette-color-13)',
		'Juniori' => 'var(--theme-palette-color-13)',
		'JUNIORI' => 'var(--theme-palette-color-13)',
        
        'Dospeli' => 'var(--theme-palette-color-13)',
        'Dospelí' => 'var(--theme-palette-color-13)',
        'DOSPELÍ' => 'var(--theme-palette-color-13)'
    ];
    
    $normalized = trim($category_name);
    
    // 1. Presná zhoda (case-insensitive)
    foreach ($colors as $cat => $color) {
        if (strcasecmp($normalized, $cat) === 0) {
            return $color;
        }
    }
    
    // 2. Čiastočná zhoda v názve
    $normalized_lower = mb_strtolower($normalized);
    
    if (strpos($normalized_lower, 'rodič') !== false || strpos($normalized_lower, 'rodic') !== false) {
        return 'var(--theme-palette-color-12)';
    }
    if (strpos($normalized_lower, '3-4') !== false) {
        return 'var(--theme-palette-color-11)';
    }
    if (strpos($normalized_lower, '5-7') !== false) {
        return 'var(--theme-palette-color-1)';
    }
    if (strpos($normalized_lower, '8-10') !== false) {
        return '#81C784';
    }
    if (strpos($normalized_lower, '10+') !== false) {
        return 'var(--theme-palette-color-4)';
    }
    if (strpos($normalized_lower, '8+') !== false) {
        return 'var(--theme-palette-color-2)';
    }
    if (strpos($normalized_lower, 'mš') !== false || strpos($normalized_lower, 'ms') !== false) {
        return 'var(--theme-palette-color-9)';
    }
    if (strpos($normalized_lower, 'dospel') !== false) {
        return 'var(--theme-palette-color-13)';
    }
    
    return 'var(--theme-palette-color-1)'; // Default
}

function spa_get_text_color($bg_color) {
    // Svetlé farby → čierny text
    $light_colors = ['var(--theme-palette-color-12)', 'var(--theme-palette-color-11)', '#81C784', 'var(--theme-palette-color-4)', 'var(--theme-palette-color-9)', '#B3E5FC', '#4FC3F7', '#66BB6A', '#FFA726'];
    
    return in_array(strtoupper($bg_color), array_map('strtoupper', $light_colors)) ? '#000' : '#fff';
}

/* ==========================
   FORMATOVANIE
   ========================== */

function spa_format_price($price) {
    return number_format($price, 2, ',', ' ') . ' €';
}

function spa_format_date($date, $format = 'j.n.Y') {
    return date_i18n($format, strtotime($date));
}

function spa_format_time($time) {
    return date('H:i', strtotime($time));
}

/* ==========================
   ZÍSKANIE DÁT POUŽÍVATEĽA
   ========================== */

function spa_get_user_children($parent_id) {
    return get_users([
        'meta_key' => 'parent_id',
        'meta_value' => $parent_id,
        'role' => 'spa_child'
    ]);
}

function spa_get_user_registrations($user_id) {
    $args = [
        'post_type' => 'spa_registration',
        'meta_query' => [[
            'key' => 'child_user_id',
            'value' => $user_id
        ]],
        'posts_per_page' => -1
    ];
    
    return new WP_Query($args);
}

function spa_get_user_programs($user_id) {
    $registrations = spa_get_user_registrations($user_id);
    $programs = [];
    
    while ($registrations->have_posts()) {
        $registrations->the_post();
        $program_id = get_post_meta(get_the_ID(), 'program_id', true);
        if ($program_id) {
            $programs[] = get_post($program_id);
        }
    }
    
    wp_reset_postdata();
    return $programs;
}

/* ==========================
   VALIDÁCIE
   ========================== */

function spa_validate_email($email) {
    return is_email($email);
}

function spa_validate_phone($phone) {
    // SK formát: +421XXXXXXXXX alebo 0XXXXXXXXX
    $phone = preg_replace('/[^0-9+]/', '', $phone);
    return strlen($phone) >= 10;
}

function spa_validate_date($date) {
    $d = DateTime::createFromFormat('Y-m-d', $date);
    return $d && $d->format('Y-m-d') === $date;
}

/* ==========================
   BEZPEČNOSŤ
   ========================== */

function spa_verify_nonce($action) {
    if (!isset($_POST['spa_nonce']) || 
        !wp_verify_nonce($_POST['spa_nonce'], $action)) {
        wp_die('Security check failed');
    }
}

function spa_current_user_can_edit_child($child_id) {
    if (!is_user_logged_in()) {
        return false;
    }
    
    $current_user_id = get_current_user_id();
    $parent_id = get_user_meta($child_id, 'parent_id', true);
    
    return ($parent_id == $current_user_id) || current_user_can('administrator');
}

/* ==========================
   AVATARY
   ========================== */

function spa_get_user_avatar($user_id, $size = 96) {
    return get_avatar_url($user_id, ['size' => $size]);
}

/* ==========================
   NOTIFIKÁCIE (FLASH MESSAGES)
   ========================== */

function spa_set_notice($message, $type = 'success') {
    if (!session_id()) {
        session_start();
    }
    
    $_SESSION['spa_notice'] = [
        'message' => $message,
        'type' => $type // success, error, warning, info
    ];
}

function spa_get_notice() {
    if (!session_id()) {
        session_start();
    }
    
    if (isset($_SESSION['spa_notice'])) {
        $notice = $_SESSION['spa_notice'];
        unset($_SESSION['spa_notice']);
        return $notice;
    }
    
    return null;
}

function spa_display_notice() {
    $notice = spa_get_notice();
    
    if (!$notice) {
        return '';
    }
    
    $icons = [
        'success' => '✅',
        'error' => '❌',
        'warning' => '⚠️',
        'info' => 'ℹ️'
    ];
    
    $colors = [
        'success' => '#4caf50',
        'error' => '#f44336',
        'warning' => '#ff9800',
        'info' => '#2196f3'
    ];
    
    $icon = $icons[$notice['type']] ?? 'ℹ️';
    $color = $colors[$notice['type']] ?? '#2196f3';
    
    return sprintf(
        '<div class="spa-notice spa-notice-%s" style="background: %s; color: white; padding: 16px; border-radius: 8px; margin: 20px 0;">
            <span style="font-size: 20px; margin-right: 12px;">%s</span>
            <span>%s</span>
        </div>',
        esc_attr($notice['type']),
        esc_attr($color),
        $icon,
        esc_html($notice['message'])
    );
}

/* ==========================
   ČASOVÉ FUNKCIE
   ========================== */

function spa_get_current_date() {
    return current_time('Y-m-d');
}

function spa_get_current_time() {
    return current_time('H:i:s');
}

function spa_is_past_date($date) {
    return strtotime($date) < strtotime('today');
}

function spa_is_future_date($date) {
    return strtotime($date) > strtotime('today');
}

/* ==========================
   SLUG GENEROVANIE
   ========================== */

function spa_generate_slug($text) {
    $text = strtolower($text);
    $text = preg_replace('/[^a-z0-9\s-]/', '', $text);
    $text = preg_replace('/[\s-]+/', '-', $text);
    $text = trim($text, '-');
    return $text;
}

/* ==========================
   JSON HANDLING
   ========================== */

function spa_json_encode($data) {
    return wp_json_encode($data, JSON_UNESCAPED_UNICODE);
}

function spa_json_decode($json) {
    return json_decode($json, true);
}

/* ==========================
   PAGINATION
   ========================== */

function spa_paginate($query, $args = []) {
    $defaults = [
        'prev_text' => '« Predošlá',
        'next_text' => 'Ďalšia »',
        'type' => 'list'
    ];
    
    $args = wp_parse_args($args, $defaults);
    
    $big = 999999999;
    
    return paginate_links([
        'base' => str_replace($big, '%#%', esc_url(get_pagenum_link($big))),
        'format' => '?paged=%#%',
        'current' => max(1, get_query_var('paged')),
        'total' => $query->max_num_pages,
        'prev_text' => $args['prev_text'],
        'next_text' => $args['next_text'],
        'type' => $args['type']
    ]);
}