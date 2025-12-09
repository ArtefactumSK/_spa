<?php
/**
 * SPA Calendar - Kalendár obsadení hál
 * 
 * @package Samuel Piasecký ACADEMY
 * @version 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/* ==========================
   SHORTCODE: Kalendár obsadení
   Použitie: [spa_hall_calendar place="malacky"]
   ========================== */

add_shortcode('spa_hall_calendar', 'spa_hall_calendar_shortcode');

function spa_hall_calendar_shortcode($atts) {
    
    $atts = shortcode_atts(['place' => ''], $atts);
    
    $args = [
        'post_type' => 'spa_hall_block',
        'posts_per_page' => -1,
        'meta_query' => [
            [
                'key' => 'show_on_calendar',
                'value' => '1'
            ],
            [
                'key' => 'block_date',
                'value' => date('Y-m-d'),
                'compare' => '>=',
                'type' => 'DATE'
            ]
        ],
        'orderby' => 'meta_value',
        'meta_key' => 'block_date',
        'order' => 'ASC'
    ];
    
    if (!empty($atts['place'])) {
        $args['meta_query'][] = [
            'key' => 'block_place',
            'value' => $atts['place']
        ];
    }
    
    $query = new WP_Query($args);
    
    if (!$query->have_posts()) {
        return '<p class="spa-calendar-empty">Žiadne obmedzenia v kalendári.</p>';
    }
    
    ob_start();
    ?>
    <div class="spa-hall-calendar">
        <h3>📅 Obsadené termíny</h3>
        
        <div class="calendar-list">
            <?php while ($query->have_posts()) : $query->the_post();
                $date = get_post_meta(get_the_ID(), 'block_date', true);
                $time_from = get_post_meta(get_the_ID(), 'block_time_from', true);
                $time_to = get_post_meta(get_the_ID(), 'block_time_to', true);
                $reason = get_post_meta(get_the_ID(), 'block_reason', true);
                $place = get_post_meta(get_the_ID(), 'block_place', true);
            ?>
                <div class="calendar-item">
                    <div class="calendar-date">
                        <span class="date-day"><?php echo date('j', strtotime($date)); ?></span>
                        <span class="date-month"><?php echo date_i18n('M', strtotime($date)); ?></span>
                    </div>
                    
                    <div class="calendar-info">
                        <h4><?php the_title(); ?></h4>
                        <p class="calendar-meta">
                            <span class="meta-item">📍 <?php echo ucfirst($place); ?></span>
                            <?php if ($time_from && $time_to) : ?>
                                <span class="meta-item">🕐 <?php echo $time_from; ?> - <?php echo $time_to; ?></span>
                            <?php else : ?>
                                <span class="meta-item">🕐 Celý deň</span>
                            <?php endif; ?>
                        </p>
                        <?php if ($reason) : ?>
                            <p class="calendar-reason"><?php echo esc_html($reason); ?></p>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    </div>
    
    <style>
    .spa-hall-calendar {
        background: white;
        border-radius: 16px;
        padding: 24px;
        box-shadow: 0 2px 12px rgba(0,0,0,0.08);
        margin: 32px 0;
    }
    .spa-hall-calendar h3 {
        margin-bottom: 20px;
        font-size: 24px;
        color: #333;
    }
    .calendar-list {
        display: flex;
        flex-direction: column;
        gap: 16px;
    }
    .calendar-item {
        display: flex;
        gap: 20px;
        padding: 16px;
        background: #f6f7f9;
        border-radius: 12px;
        border-left: 4px solid #ff9800;
        transition: all 0.3s ease;
    }
    .calendar-item:hover {
        background: #fff3e0;
        transform: translateX(4px);
    }
    .calendar-date {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        background: white;
        border-radius: 8px;
        padding: 12px;
        min-width: 70px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }
    .date-day {
        font-size: 28px;
        font-weight: 700;
        color: #ff9800;
        line-height: 1;
    }
    .date-month {
        font-size: 13px;
        color: #666;
        text-transform: uppercase;
        margin-top: 4px;
    }
    .calendar-info {
        flex: 1;
    }
    .calendar-info h4 {
        margin: 0 0 8px 0;
        font-size: 18px;
        color: #333;
    }
    .calendar-meta {
        display: flex;
        gap: 16px;
        margin: 8px 0;
        font-size: 14px;
        color: #666;
        flex-wrap: wrap;
    }
    .calendar-reason {
        margin: 8px 0 0 0;
        font-size: 14px;
        color: #999;
        font-style: italic;
    }
    .spa-calendar-empty {
        text-align: center;
        padding: 40px 20px;
        color: #999;
        background: #f6f7f9;
        border-radius: 8px;
    }
    @media (max-width: 768px) {
        .calendar-item {
            flex-direction: column;
        }
        .calendar-date {
            align-self: flex-start;
        }
    }
    </style>
    <?php
    wp_reset_postdata();
    return ob_get_clean();
}