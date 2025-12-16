<?php
/**
 * SPA Registration Admin Columns
 * Zobrazenie Dieťa + Rodič + Program + Deň + Čas + VS + Status
 */

if (!defined('ABSPATH')) {
    exit;
}

add_filter('manage_spa_registration_posts_columns', 'spa_registration_columns');

function spa_registration_columns($columns) {
    return [
        'cb'      => $columns['cb'],
        'title'   => 'Názov',
        'child'   => '👶 Dieťa/Klient',
        'parent'  => '👨‍👩‍👧 Rodič',
        'program' => '🤸 Program',
        'day'     => '📅 Deň',
        'time'    => '⏰ Čas',
        'vs'      => '💳 VS',
        'status'  => '📌 Status',
        'date'    => 'Dátum'
    ];
}

add_action('manage_spa_registration_posts_custom_column', 'spa_registration_column_content', 10, 2);

function spa_registration_column_content($column, $post_id) {
    
    $client_user_id = get_post_meta($post_id, 'client_user_id', true);
    $parent_user_id = get_post_meta($post_id, 'parent_user_id', true);
    $program_id = get_post_meta($post_id, 'program_id', true);
    $training_day = get_post_meta($post_id, 'training_day', true);
    $training_time = get_post_meta($post_id, 'training_time', true);
    $status = get_post_meta($post_id, 'status', true);
    
    $days_sk = [
        'monday' => 'Po', 'tuesday' => 'Ut', 'wednesday' => 'St',
        'thursday' => 'Št', 'friday' => 'Pi', 'saturday' => 'So', 'sunday' => 'Ne'
    ];
    
    switch ($column) {
        
        case 'child':
            if ($client_user_id) {
                $user = get_userdata($client_user_id);
                if ($user) {
                    $name = trim($user->first_name . ' ' . $user->last_name);
                    echo '<a href="' . esc_url(get_edit_user_link($client_user_id)) . '">' . esc_html($name ?: $user->display_name) . '</a>';
                } else {
                    echo '<span style="color:#999;">–</span>';
                }
            } else {
                echo '<span style="color:#999;">–</span>';
            }
            break;
        
        case 'parent':
            if ($parent_user_id) {
                $user = get_userdata($parent_user_id);
                if ($user) {
                    $name = trim($user->first_name . ' ' . $user->last_name);
                    echo '<a href="' . esc_url(get_edit_user_link($parent_user_id)) . '">' . esc_html($name ?: $user->display_name) . '</a>';
                    echo '<br><small style="color:#666;">' . esc_html($user->user_email) . '</small>';
                } else {
                    echo '<span style="color:#999;">–</span>';
                }
            } else {
                echo '<span style="color:#999;">–</span>';
            }
            break;
        
        case 'program':
            if ($program_id) {
                $program = get_post($program_id);
                if ($program) {
                    echo '<a href="' . get_edit_post_link($program_id) . '">' . esc_html($program->post_title) . '</a>';
                } else {
                    echo '<span style="color:#999;">–</span>';
                }
            } else {
                echo '<span style="color:#999;">–</span>';
            }
            break;
        
        case 'day':
            if ($training_day) {
                $day_short = $days_sk[$training_day] ?? $training_day;
                echo '<strong>' . esc_html($day_short) . '</strong>';
            } else {
                echo '<span style="color:#999;">–</span>';
            }
            break;
        
        case 'time':
            if ($training_time) {
                echo '<code>' . esc_html($training_time) . '</code>';
            } else {
                echo '<span style="color:#999;">–</span>';
            }
            break;
        
        case 'vs':
            if ($client_user_id) {
                $vs = get_user_meta($client_user_id, 'variabilny_symbol', true);
                if ($vs) {
                    echo '<strong style="font-family:monospace;font-size:13px;color:#d32f2f;">' . esc_html($vs) . '</strong>';
                } else {
                    echo '<span style="color:#999;">–</span>';
                }
            } else {
                echo '<span style="color:#999;">–</span>';
            }
            break;
        
        case 'status':
            $labels = [
                'active' => ['✅ Aktívna', '#00C853'],
                'inactive' => ['⏸️ Neaktívna', '#FF9800'],
                'cancelled' => ['❌ Zrušená', '#FF1439']
            ];
            $label = $labels[$status] ?? ['– Neznámy', '#999'];
            echo '<span style="background:' . $label[1] . ';color:#fff;padding:4px 10px;border-radius:4px;font-size:11px;white-space:nowrap;">' . $label[0] . '</span>';
            break;
    }
}