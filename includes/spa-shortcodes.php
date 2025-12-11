<?php
/**
 * SPA Shortcodes - Frontend zobrazenie
 * 
 * @package Samuel Piasecký ACADEMY
 * @version 1.1.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/* ==========================
   SHORTCODE: Zoznam programov (karty)
   Použitie: [spa_programs place="malacky"], [spa_programs category="deti-3-4-roky"]
   ========================== */

add_shortcode('spa_programs', 'spa_programs_shortcode');

function spa_programs_shortcode($atts) {
    
    static $instance = 0;
    $instance++;
    $unique_id = 'spa_inst_' . $instance;
    
    $atts = shortcode_atts([
        'place' => '',
        'category' => '',
        'limit' => -1,
        'view' => 'cards'
    ], $atts);
    
    $args = [
        'post_type' => 'spa_group',
        'posts_per_page' => $atts['limit'],
        'orderby' => 'menu_order',
        'order' => 'ASC',
        'post_status' => 'publish',
        'tax_query' => []
    ];
    
    // Filter podľa miesta
    if (!empty($atts['place'])) {
        $places = array_map('trim', explode(',', $atts['place']));
        
        $place_terms = [];
        foreach ($places as $place) {
            $term = get_term_by('slug', sanitize_title($place), 'spa_place');
            if (!$term) {
                $term = get_term_by('name', $place, 'spa_place');
            }
            if ($term) {
                $place_terms[] = $term->term_id;
            }
        }
        
        if (!empty($place_terms)) {
            $args['tax_query'][] = [
                'taxonomy' => 'spa_place',
                'field' => 'term_id',
                'terms' => $place_terms,
                'operator' => 'IN'
            ];
        }
    }
    
    // Filter podľa kategórie
    if (!empty($atts['category'])) {
        $categories = array_map('trim', explode(',', $atts['category']));
        
        $args['tax_query'][] = [
            'taxonomy' => 'spa_group_category',
            'field' => 'slug',
            'terms' => $categories,
            'operator' => 'IN'
        ];
    }
    
    $query = new WP_Query($args);
    
    if (!$query->have_posts()) {
        return '<div class="spa-no-results"><p>Momentálne nemáme žiadne programy.</p></div>';
    }
    
    ob_start();
    
    if ($atts['view'] === 'cards') {
        spa_programs_cards_view($query, $unique_id);
    } else {
        spa_programs_table_view($query, $unique_id);
    }
    
    wp_reset_postdata();
    
    return ob_get_clean();
}

/* ==========================
   VIEW: Karty (pre registráciu)
   ========================== */

function spa_programs_cards_view($query, $unique_id = '') {
    ?>
    <div class="spa-programs-grid <?php echo $unique_id ? 'spa-grid-' . esc_attr($unique_id) : ''; ?>">
        <?php while ($query->have_posts()) : $query->the_post(); 
            $icon_file = get_post_meta(get_the_ID(), 'spa_icon', true);
            $price = get_post_meta(get_the_ID(), 'spa_price', true);
            $schedule_json = get_post_meta(get_the_ID(), 'spa_schedule', true);
            $schedule = $schedule_json ? json_decode($schedule_json, true) : [];
            
            $places = get_the_terms(get_the_ID(), 'spa_place');
            $categories = get_the_terms(get_the_ID(), 'spa_group_category');
        ?>
        
        <div class="spa-program-card">
            
            <!-- SVG IKONA -->
            <?php if ($icon_file) : 
                $svg_path = WP_CONTENT_DIR . '/uploads/spa-icons/' . $icon_file;
                if (file_exists($svg_path)) : ?>
                    <div class="spa-program-icon">
                        <?php echo file_get_contents($svg_path); ?>
                    </div>
                <?php endif; 
            endif; ?>
            
            <!-- KATEGÓRIA BADGE -->
            <?php if ($categories) : 
                $cat_name = $categories[0]->name;
                $cat_color = spa_get_category_color($cat_name);
                $text_color = spa_get_text_color($cat_color);
            ?>
                <div class="spa-program-category" style="background-color: <?php echo esc_attr($cat_color); ?>; color: <?php echo esc_attr($text_color); ?>;">
                    <?php echo esc_html($cat_name); ?>
                </div>
            <?php endif; ?>
            
            <!-- NÁZOV -->
            <h3 class="spa-program-title">
                <?php 
                $title = get_the_title();
                if (strpos($title, '/') !== false) {
                    $parts = explode('/', $title, 2);
                    echo '<span class="title-main">' . esc_html(trim($parts[0])) . '</span>';
                    echo '<span class="title-sub">' . esc_html(trim($parts[1])) . '</span>';
                } else {
                    echo esc_html($title);
                }
                ?>
            </h3>
            
            <!-- POPIS -->
            <?php 
            $description = get_post_meta(get_the_ID(), 'spa_description', true);
            if ($description) : ?>
                <div class="spa-program-description" style="border-left-color: <?php echo esc_attr($cat_color); ?>;">
                    <?php echo wp_kses_post($description); ?>
                </div>
            <?php endif; ?>
            
            <!-- MIESTO -->
            <?php if ($places) : ?>
                <div class="spa-program-place">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/>
                    </svg>
                    <?php 
                    // Zobraz VŠETKY miesta oddelené čiarkou
                    $place_names = array_map(function($term) {
                        return $term->name;
                    }, $places);
                    echo esc_html(implode(', ', $place_names)); 
                    ?>
                </div>
            <?php endif; ?>
            
            <!-- ROZVRH - KALENDÁR -->
            <?php if (!empty($schedule)) : 
                $days_short = [
                    'monday' => 'Po',
                    'tuesday' => 'Ut',
                    'wednesday' => 'St',
                    'thursday' => 'Št',
                    'friday' => 'Pi',
                    'saturday' => 'So',
                    'sunday' => 'Ne'
                ];
                
                $schedule_map = [];
                foreach ($schedule as $item) {
                    $day = $item['day'];
                    if (!isset($schedule_map[$day])) {
                        $schedule_map[$day] = [];
                    }
                    $schedule_map[$day][] = $item['time'];
                }
            ?>
                <div class="spa-program-schedule-grid">
                    <div class="schedule-header">
                        <?php foreach ($days_short as $day_key => $day_label) : ?>
                            <div class="schedule-day <?php echo isset($schedule_map[$day_key]) ? 'active' : ''; ?>">
                                <?php echo $day_label; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="schedule-times">
                        <?php foreach ($days_short as $day_key => $day_label) : ?>
                            <div class="schedule-time">
                                <?php 
                                if (isset($schedule_map[$day_key])) {
                                    echo esc_html(implode(', ', $schedule_map[$day_key]));
                                } else {
                                    echo '—';
                                }
                                ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
            
            <!-- CENA -->
            <?php if ($price) : 
                $price_color = $categories ? spa_get_category_color($categories[0]->name) : 'var(--theme-palette-color-1)';
            ?>
                <div class="spa-program-price" style="color: <?php echo esc_attr($price_color); ?>;">
                    <?php echo spa_format_price($price); ?>
                </div>
            <?php endif; ?>
            
            <!-- CTA TLAČIDLO -->
            <?php 
            // Získaj údaje programu pre URL parametre
            $program_id = get_the_ID();
            $program_title = get_the_title();
            $program_price = get_post_meta($program_id, 'spa_price', true);

            // Miesto - VŠETKY termy spojené do jedného slugu
            $places = get_the_terms($program_id, 'spa_place');

            if ($places && !is_wp_error($places)) {
                
                // Zoraď termy podľa názvu (aby bol slug konzistentný)
                usort($places, function($a, $b) {
                    return strcmp($a->name, $b->name);
                });
                
                // Vytvor kombinovaný slug: "kosice,september-jun,zs-drabova-3"
                $place_slugs = array_map(function($term) {
                    return $term->slug;
                }, $places);
                
                sort($place_slugs); // Zoraď alfabeticky
                $place_slug = implode(',', $place_slugs);
                
                // Kombinovaný názov pre display
                $place_names = array_map(function($term) {
                    return $term->name;
                }, $places);
                $place_name = implode(', ', $place_names);
                
            } else {
                $place_slug = '';
                $place_name = '';
            }

            // Kategória
            $categories = get_the_terms($program_id, 'spa_group_category');
            $category_name = $categories ? $categories[0]->name : '';

            // Vytvor URL s parametrami
            $registration_url = add_query_arg([
                'program_id' => $program_id,
                'place' => $place_slug, // ✅ Kombinovaný slug
                'program_name' => urlencode($program_title),
                'category' => urlencode($category_name),
                'price' => $program_price
            ], home_url('/registracia/'));
            ?>

            <a href="<?php echo esc_url($registration_url); ?>" 
               class="spa-program-btn spa-register-btn" 
               data-program-id="<?php echo $program_id; ?>">
                Registrovať
            </a>
            
        </div>
        
        <?php endwhile; ?>
    </div>
    
    <?php 
    static $styles_printed = false;
    if (!$styles_printed) {
        spa_programs_styles();
        $styles_printed = true;
    }
    ?>
    <?php
}

/* ==========================
   VIEW: Tabuľka (pre rozvrh)
   ========================== */

function spa_programs_table_view($query) {
    
    $schedule_by_day = [];
    $days_order = ['Pondelok', 'Utorok', 'Streda', 'Štvrtok', 'Piatok', 'Sobota', 'Nedeľa'];
    
    while ($query->have_posts()) {
        $query->the_post();
        
        $schedule_json = get_post_meta(get_the_ID(), 'spa_schedule', true);
        $schedule = $schedule_json ? json_decode($schedule_json, true) : [];
        $categories = get_the_terms(get_the_ID(), 'spa_group_category');
        $category_name = $categories ? $categories[0]->name : '';
        
        foreach ($schedule as $item) {
            $day = spa_get_day_name($item['day']);
            $time = $item['time'];
            
            if (!isset($schedule_by_day[$day])) {
                $schedule_by_day[$day] = [];
            }
            
            if (!isset($schedule_by_day[$day][$time])) {
                $schedule_by_day[$day][$time] = [];
            }
            
            $schedule_by_day[$day][$time][] = [
                'title' => get_the_title(),
                'category' => $category_name,
                'id' => get_the_ID()
            ];
        }
    }
    
    ?>
    <div class="spa-schedule-table">
        <table>
            <thead>
                <tr>
                    <th>ČAS</th>
                    <?php foreach ($days_order as $day) : ?>
                        <?php if (isset($schedule_by_day[$day])) : ?>
                            <th><?php echo strtoupper($day); ?></th>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <?php 
                $all_times = [];
                foreach ($schedule_by_day as $day => $times) {
                    $all_times = array_merge($all_times, array_keys($times));
                }
                $all_times = array_unique($all_times);
                sort($all_times);
                
                foreach ($all_times as $time) : ?>
                    <tr>
                        <td class="time-col"><strong><?php echo $time; ?></strong></td>
                        <?php foreach ($days_order as $day) : ?>
                            <?php if (isset($schedule_by_day[$day])) : ?>
                                <td>
                                    <?php if (isset($schedule_by_day[$day][$time])) : ?>
                                        <?php foreach ($schedule_by_day[$day][$time] as $program) : 
                                            $cat_color = spa_get_category_color($program['category']);
                                        ?>
                                            <div class="schedule-item" style="border-left-color: <?php echo esc_attr($cat_color); ?>;">
                                                <strong><?php echo esc_html($program['title']); ?></strong>
                                                <?php if ($program['category']) : ?>
                                                    <br><small style="color: <?php echo esc_attr($cat_color); ?>;">(<?php echo esc_html($program['category']); ?>)</small>
                                                <?php endif; ?>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </td>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    
    <?php 
    static $table_styles_printed = false;
    if (!$table_styles_printed) {
        spa_schedule_table_styles();
        $table_styles_printed = true;
    }
    ?>
    <?php
}

/* ==========================
   STYLES: Cards
   ========================== */

function spa_programs_styles() {
    ?>
    <style>
    .spa-programs-grid {display: grid;grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));gap: 32px;margin: 8px auto;max-width: 1200px;}
    .spa-program-card {background: var(--theme-palette-color-8);border: 2px solid var(--theme-palette-color-10);border-radius: 16px;padding: 32px;transition: all 0.3s ease;position: relative;overflow: hidden;}
    .spa-program-card::before {content: '';position: absolute;top: 0;left: 0;width: 100%;height: 4px;background: linear-gradient(90deg, var(--theme-palette-color-1), var(--theme-palette-color-2), var(--theme-palette-color-3), var(--theme-palette-color-4));transform: scaleX(0);transition: transform 0.3s ease;}
    .spa-program-card:hover {transform: translateY(-8px);box-shadow: 0 12px 32px rgba(0,0,0,0.12);border-color: var(--theme-palette-color-1);}
    .spa-program-card:hover::before {transform: scaleX(1);}    
    .spa-program-icon {text-align: center;margin-bottom: 9px;position: relative;overflow: visible;}
    .spa-program-icon svg {width: 72px;height: 72px;fill: var(--theme-palette-color-1);transition: all 1s cubic-bezier(0.68, -0.55, 0.265, 1.55);transform-origin: 50% 75%;position: relative;}
    .spa-program-card:hover .spa-program-icon svg {fill: var(--theme-palette-color-3);transform: translateX(-100px) translateY(10px) rotate(-360deg) scale(1.1);}
    .spa-program-category {display: inline-block;padding: 6px 16px;border-radius: 20px;font-size: 12px;font-weight: 600;text-transform: uppercase;letter-spacing: 0.5px;margin-bottom: 16px;}
    .spa-program-title .title-sub {display: block;font-family:Michroma, Sans-Serif;font-size: 12px;font-weight: 400;color: var(--theme-text-color);margin-top: 8px;}
    .spa-program-title {font-size: 24px;font-weight: 700;margin: 6px 0;color: var(--theme-palette-color-3);line-height: 1.3;text-align: center;}
    .spa-program-title .title-main {display: block;}
    .spa-program-description {font-size: 14px;line-height: 1.6;color: #555;margin: 12px 0;padding: 12px;background: #f9f9fb;border-radius: 8px;border-left: 3px solid var(--theme-palette-color-1);}
    .spa-program-description p {margin: 0;}
    .spa-program-place {display: flex;align-items: flex-start;gap: 8px;font-size: 15px;color: var(--theme-text-color);margin: 12px 0;line-height: 1.4;}
    .spa-program-place svg {flex-shrink: 0;margin-top: 2px;}
    .spa-program-schedule-grid {margin: 20px 0;background: #f6f7f9;border-radius: 8px;overflow: hidden;border: 2px solid var(--theme-palette-color-10);}
    .schedule-header {display: grid;grid-template-columns: repeat(7, 1fr);background: linear-gradient(135deg, var(--theme-palette-color-1) 0%, #005BAA 100%);}
    .schedule-day {padding: 8px 4px;text-align: center;font-size: 11px;font-weight: 700;color: rgba(255,255,255,0.5);text-transform: uppercase;border-right: 1px solid rgba(255,255,255,0.1);}
    .schedule-day:last-child {border-right: none;}
    .schedule-day.active {color: #fff;background: rgba(255,255,255,0.1);}
    .schedule-times {display: grid;grid-template-columns: repeat(7, 1fr);background: #fff;}
    .schedule-time {padding: 10px 4px;text-align: center;font-size: 13px;font-weight: 600;color: #000;border-right: 1px solid var(--theme-palette-color-10);min-height: 40px;display: flex;align-items: center;justify-content: center;}
    .schedule-time:last-child {border-right: none;}
    .spa-program-price {font-size: 32px;font-weight: 700;color: var(--theme-palette-color-1);margin: 20px 0;text-align: center;}
    .spa-program-btn {display: block;background: linear-gradient(135deg, var(--theme-palette-color-1) 0%, #005BAA 100%);color: #fff;text-align: center;padding: 16px 32px;border-radius: 10px;text-decoration: none;font-weight: 700;font-size: 16px;transition: all 0.3s ease;box-shadow: 0 4px 12px rgba(0,114,206,0.3);}
    .spa-program-btn:hover {background: linear-gradient(135deg, var(--theme-palette-color-3) 0%, #C40025 100%);transform: translateY(-2px);box-shadow: 0 6px 20px rgba(228,0,43,0.4);color: #fff;}
    .spa-no-results {text-align: center;padding: 60px 20px;color: #666;}
    @media (max-width: 768px) {
        .spa-programs-grid {grid-template-columns: 1fr;gap: 24px;margin: 32px 20px;}
        .spa-program-card {padding: 24px;}
        .schedule-header,.schedule-times {grid-template-columns: repeat(7, 1fr);font-size: 10px;}
        .schedule-day {padding: 6px 2px;font-size: 9px;}
        .schedule-time {padding: 8px 2px;font-size: 11px;}
        .spa-program-card:hover .spa-program-icon svg {transform: translateX(-70px) translateY(10px) rotate(-360deg) scale(1.05);}
    }
    </style>
    <?php
}

/* ==========================
   STYLES: Table
   ========================== */

function spa_schedule_table_styles() {
    ?>
    <style>
    .spa-schedule-table {overflow-x: auto;margin: 40px 0;}
    .spa-schedule-table table {width: 100%;border-collapse: collapse;background: #fff;box-shadow: 0 2px 12px rgba(0,0,0,0.08);border-radius: 12px;overflow: hidden;}
    .spa-schedule-table th {background: linear-gradient(135deg, var(--theme-palette-color-1) 0%, #005BAA 100%);color: #fff;padding: 16px;text-align: center;font-weight: 700;font-size: 14px;letter-spacing: 0.5px;}
    .spa-schedule-table td {padding: 16px;border: 1px solid var(--theme-palette-color-10);vertical-align: top;min-width: 120px;}
    .spa-schedule-table .time-col {background: #f6f7f9;font-weight: 700;color: #333;text-align: center;white-space: nowrap;}
    .spa-schedule-table .schedule-item {background: #f0f8ff;border-left: 4px solid var(--theme-palette-color-1);padding: 8px 12px;margin-bottom: 8px;border-radius: 4px;font-size: 14px;}
    .spa-schedule-table .schedule-item:last-child {margin-bottom: 0;}
    .spa-schedule-table .schedule-item strong {color: #333;}
    .spa-schedule-table .schedule-item small {font-weight: 600;}
    @media (max-width: 768px) {
        .spa-schedule-table {font-size: 13px;}
        .spa-schedule-table th,.spa-schedule-table td {padding: 10px;min-width: 100px;}
    }
    </style>
    <?php
}

/* ==========================
   SHORTCODE: Udalosti (články)
   ========================== */

add_shortcode('spa_events', 'spa_events_shortcode');

function spa_events_shortcode($atts) {
    
    $atts = shortcode_atts(['limit' => 6], $atts);
    
    $args = [
        'post_type' => 'post',
        'category_name' => 'udalosti',
        'posts_per_page' => $atts['limit'],
        'orderby' => 'date',
        'order' => 'DESC'
    ];
    
    $query = new WP_Query($args);
    
    if (!$query->have_posts()) {
        return '<p>Žiadne nadchádzajúce udalosti.</p>';
    }
    
    ob_start();
    ?>
    <div class="spa-events-grid">
        <?php while ($query->have_posts()) : $query->the_post(); ?>
            <article class="event-card">
                <?php if (has_post_thumbnail()) : ?>
                    <div class="event-image">
                        <?php the_post_thumbnail('medium'); ?>
                    </div>
                <?php endif; ?>
                
                <div class="event-content">
                    <span class="event-date"><?php echo get_the_date(); ?></span>
                    <h3><?php the_title(); ?></h3>
                    <div class="event-excerpt">
                        <?php the_excerpt(); ?>
                    </div>
                    <a href="<?php the_permalink(); ?>" class="event-link">
                        Čítať viac →
                    </a>
                </div>
            </article>
        <?php endwhile; ?>
    </div>
    
    <style>
    .spa-events-grid {display: grid;grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));gap: 24px;margin: 32px 0;}
    .event-card {background: white;border-radius: 16px;overflow: hidden;box-shadow: 0 4px 20px rgba(0,0,0,0.08);transition: all 0.3s ease;}
    .event-card:hover {transform: translateY(-8px);box-shadow: 0 12px 32px rgba(0,0,0,0.15);}
    .event-image {position: relative;padding-top: 60%;overflow: hidden;}
    .event-image img {position: absolute;top: 0;left: 0;width: 100%;height: 100%;object-fit: cover;}
    .event-content {padding: 24px;}
    .event-date {display: inline-block;background: var(--theme-palette-color-1);color: white;padding: 4px 12px;border-radius: 12px;font-size: 12px;font-weight: 600;margin-bottom: 12px;}
    .event-card h3 {font-size: 20px;margin: 12px 0;line-height: 1.3;}
    .event-excerpt {color: #666;font-size: 14px;line-height: 1.6;margin: 12px 0;}
    .event-link {color: var(--theme-palette-color-1);font-weight: 600;text-decoration: none;transition: all 0.3s;}
    .event-link:hover {color: var(--theme-palette-color-3);}
    </style>
    <?php
    wp_reset_postdata();
    return ob_get_clean();
}