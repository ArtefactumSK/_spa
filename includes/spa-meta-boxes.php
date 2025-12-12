<?php
/** spa-meta-boxes.php
 * SPA Meta Boxes - Admin formuláre pre CPT
 * @package Samuel Piasecký ACADEMY
 * @version 3.0.0 - FÁZA 1: Nové meta boxy podľa AKČNÉHO PLÁNU
 */

if (!defined('ABSPATH')) {
    exit;
}

/* ============================================================
   PRIDANIE VŠETKÝCH META BOXOV
   ============================================================ */
add_action('add_meta_boxes', 'spa_add_all_meta_boxes');
function spa_add_all_meta_boxes() {
    
    // PROGRAMY (spa_group)
    add_meta_box('spa_group_details', '🤸 Detaily programu', 'spa_group_meta_box', 'spa_group', 'normal', 'high');
    add_meta_box('spa_group_pricing', '💰 Cenník programu', 'spa_group_pricing_meta_box', 'spa_group', 'normal', 'high');
    
    // REGISTRÁCIE
    add_meta_box('spa_registration_details', '📋 Detaily registrácie', 'spa_registration_meta_box', 'spa_registration', 'normal', 'high');
    
    // MIESTA (spa_place) - NOVÉ
    add_meta_box('spa_place_details', '📍 Detaily miesta', 'spa_place_meta_box', 'spa_place', 'normal', 'high');
    add_meta_box('spa_place_schedule', '📅 Rozvrh miesta', 'spa_place_schedule_meta_box', 'spa_place', 'normal', 'default');
    
    // UDALOSTI (spa_event) - NOVÉ
    add_meta_box('spa_event_details', '📅 Detaily udalosti', 'spa_event_meta_box', 'spa_event', 'normal', 'high');
    
    // DOCHÁDZKA (spa_attendance) - NOVÉ
    add_meta_box('spa_attendance_details', '✅ Záznam dochádzky', 'spa_attendance_meta_box', 'spa_attendance', 'normal', 'high');
}

/* ============================================================
   META BOX: MIESTO (spa_place) - NOVÉ
   ============================================================ */
function spa_place_meta_box($post) {
    wp_nonce_field('spa_save_place', 'spa_place_nonce');
    
    $type = get_post_meta($post->ID, 'spa_place_type', true);
    $address = get_post_meta($post->ID, 'spa_place_address', true);
    $city = get_post_meta($post->ID, 'spa_place_city', true);
    $gps_lat = get_post_meta($post->ID, 'spa_place_gps_lat', true);
    $gps_lng = get_post_meta($post->ID, 'spa_place_gps_lng', true);
    $contact = get_post_meta($post->ID, 'spa_place_contact', true);
    $notes = get_post_meta($post->ID, 'spa_place_notes', true);
    
    ?>
    <style>
    .spa-meta-row { display: flex; margin-bottom: 15px; align-items: flex-start; }
    .spa-meta-row label { width: 150px; font-weight: 600; padding-top: 8px; }
    .spa-meta-row .spa-field { flex: 1; }
    .spa-meta-row input[type="text"], .spa-meta-row textarea, .spa-meta-row select { width: 100%; max-width: 400px; padding: 8px; }
    .spa-help { color: #666; font-size: 12px; margin-top: 4px; }
    .spa-section { background: #f9f9f9; padding: 20px; border: 1px solid #ddd; border-radius: 4px; margin-bottom: 20px; }
    .spa-section h4 { margin: 0 0 15px 0; padding-bottom: 10px; border-bottom: 1px solid #ddd; }
    </style>
    
    <div class="spa-section">
        <h4>📍 Základné informácie</h4>
        
        <div class="spa-meta-row">
            <label for="spa_place_type">Typ priestoru:</label>
            <div class="spa-field">
                <select name="spa_place_type" id="spa_place_type">
                    <option value="">-- Vyberte typ --</option>
                    <option value="spa" <?php selected($type, 'spa'); ?>>🏠 Priestory SPA</option>
                    <option value="external" <?php selected($type, 'external'); ?>>🏫 Externé priestory</option>
                </select>
                <p class="spa-help">Externé priestory môžu mať príplatok v cene programu</p>
            </div>
        </div>
        
        <div class="spa-meta-row">
            <label for="spa_place_city">Mesto:</label>
            <div class="spa-field">
                <input type="text" name="spa_place_city" id="spa_place_city" value="<?php echo esc_attr($city); ?>" placeholder="napr. Malacky, Košice">
            </div>
        </div>
        
        <div class="spa-meta-row">
            <label for="spa_place_address">Adresa:</label>
            <div class="spa-field">
                <input type="text" name="spa_place_address" id="spa_place_address" value="<?php echo esc_attr($address); ?>" placeholder="napr. Športová hala Basso, Sasinkova 2">
            </div>
        </div>
        
        <div class="spa-meta-row">
            <label>GPS súradnice:</label>
            <div class="spa-field">
                <input type="text" name="spa_place_gps_lat" value="<?php echo esc_attr($gps_lat); ?>" placeholder="Lat" style="width: 150px; margin-right: 10px;">
                <input type="text" name="spa_place_gps_lng" value="<?php echo esc_attr($gps_lng); ?>" placeholder="Lng" style="width: 150px;">
                <p class="spa-help">Voliteľné - pre zobrazenie na mape</p>
            </div>
        </div>
        
        <div class="spa-meta-row">
            <label for="spa_place_contact">Kontakt:</label>
            <div class="spa-field">
                <input type="text" name="spa_place_contact" id="spa_place_contact" value="<?php echo esc_attr($contact); ?>" placeholder="Telefón alebo email na správcu">
            </div>
        </div>
        
        <div class="spa-meta-row">
            <label for="spa_place_notes">Poznámky:</label>
            <div class="spa-field">
                <textarea name="spa_place_notes" id="spa_place_notes" rows="3" placeholder="Interné poznámky k miestu..."><?php echo esc_textarea($notes); ?></textarea>
            </div>
        </div>
    </div>
    <?php
}

/* ============================================================
   META BOX: ROZVRH MIESTA (spa_place) - NOVÉ
   Týždenný kalendár s časovými slotmi
   ============================================================ */
function spa_place_schedule_meta_box($post) {
    
    $schedule_json = get_post_meta($post->ID, 'spa_place_schedule', true);
    $schedule = $schedule_json ? json_decode($schedule_json, true) : array();
    
    $days = array(
        'monday' => 'Pondelok',
        'tuesday' => 'Utorok',
        'wednesday' => 'Streda',
        'thursday' => 'Štvrtok',
        'friday' => 'Piatok',
        'saturday' => 'Sobota',
        'sunday' => 'Nedeľa'
    );
    
    ?>
    <style>
    .spa-schedule-table { width: 100%; border-collapse: collapse; }
    .spa-schedule-table th, .spa-schedule-table td { padding: 10px; border: 1px solid #ddd; text-align: center; }
    .spa-schedule-table th { background: #f1f1f1; }
    .spa-schedule-table input[type="time"] { width: 90px; }
    </style>
    
    <p><strong>Prevádzková doba miesta:</strong> Zadajte časy, kedy je miesto k dispozícii pre tréningy.</p>
    
    <table class="spa-schedule-table">
        <thead>
            <tr>
                <th>Deň</th>
                <th>Od</th>
                <th>Do</th>
                <th>Kapacita</th>
                <th>Aktívne</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($days as $day_key => $day_label) : 
                $row = isset($schedule[$day_key]) ? $schedule[$day_key] : array();
            ?>
            <tr>
                <td><strong><?php echo $day_label; ?></strong></td>
                <td><input type="time" name="spa_place_schedule[<?php echo $day_key; ?>][from]" value="<?php echo esc_attr($row['from'] ?? ''); ?>"></td>
                <td><input type="time" name="spa_place_schedule[<?php echo $day_key; ?>][to]" value="<?php echo esc_attr($row['to'] ?? ''); ?>"></td>
                <td><input type="number" name="spa_place_schedule[<?php echo $day_key; ?>][capacity]" value="<?php echo esc_attr($row['capacity'] ?? ''); ?>" min="0" style="width: 60px;"></td>
                <td><input type="checkbox" name="spa_place_schedule[<?php echo $day_key; ?>][active]" value="1" <?php checked(!empty($row['active'])); ?>></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    
    <p class="spa-help" style="margin-top: 10px;">Kapacita = maximálny počet ľudí v danom čase. Prázdne = neobmedzené.</p>
    <?php
}

/* ============================================================
   META BOX: UDALOSŤ (spa_event) - NOVÉ
   ============================================================ */
function spa_event_meta_box($post) {
    wp_nonce_field('spa_save_event', 'spa_event_nonce');
    
    $place_id = get_post_meta($post->ID, 'spa_event_place_id', true);
    $type = get_post_meta($post->ID, 'spa_event_type', true);
    $date_from = get_post_meta($post->ID, 'spa_event_date_from', true);
    $date_to = get_post_meta($post->ID, 'spa_event_date_to', true);
    $time_from = get_post_meta($post->ID, 'spa_event_time_from', true);
    $time_to = get_post_meta($post->ID, 'spa_event_time_to', true);
    $all_day = get_post_meta($post->ID, 'spa_event_all_day', true);
    $recurring = get_post_meta($post->ID, 'spa_event_recurring', true);
    
    // Získaj všetky miesta
    $places = get_posts(array('post_type' => 'spa_place', 'posts_per_page' => -1, 'orderby' => 'title', 'order' => 'ASC'));
    
    ?>
    <style>
    .spa-meta-row { display: flex; margin-bottom: 15px; align-items: flex-start; }
    .spa-meta-row label { width: 150px; font-weight: 600; padding-top: 8px; }
    .spa-meta-row .spa-field { flex: 1; }
    .spa-section { background: #f9f9f9; padding: 20px; border: 1px solid #ddd; border-radius: 4px; margin-bottom: 20px; }
    </style>
    
    <div class="spa-section">
        
        <div class="spa-meta-row">
            <label for="spa_event_type">Typ udalosti:</label>
            <div class="spa-field">
                <select name="spa_event_type" id="spa_event_type">
                    <option value="">-- Vyberte typ --</option>
                    <option value="block" <?php selected($type, 'block'); ?>>🚫 Blokovanie (tréningy neprebehajú)</option>
                    <option value="event" <?php selected($type, 'event'); ?>>🎉 Udalosť (špeciálna akcia)</option>
                    <option value="competition" <?php selected($type, 'competition'); ?>>🏆 Súťaž</option>
                    <option value="holiday" <?php selected($type, 'holiday'); ?>>🎄 Sviatok / Prázdniny</option>
                </select>
            </div>
        </div>
        
        <div class="spa-meta-row">
            <label for="spa_event_place_id">Miesto:</label>
            <div class="spa-field">
                <select name="spa_event_place_id" id="spa_event_place_id">
                    <option value="">-- Všetky miesta --</option>
                    <?php foreach ($places as $place) : ?>
                        <option value="<?php echo $place->ID; ?>" <?php selected($place_id, $place->ID); ?>><?php echo esc_html($place->post_title); ?></option>
                    <?php endforeach; ?>
                </select>
                <p class="spa-help">Ak nevyberiete miesto, udalosť platí pre všetky miesta</p>
            </div>
        </div>
        
        <div class="spa-meta-row">
            <label>Dátum:</label>
            <div class="spa-field">
                <input type="date" name="spa_event_date_from" value="<?php echo esc_attr($date_from); ?>" style="margin-right: 10px;">
                <span>do</span>
                <input type="date" name="spa_event_date_to" value="<?php echo esc_attr($date_to); ?>" style="margin-left: 10px;">
                <p class="spa-help">Pre jednodňovú udalosť zadajte len prvý dátum</p>
            </div>
        </div>
        
        <div class="spa-meta-row">
            <label>
                <input type="checkbox" name="spa_event_all_day" value="1" <?php checked($all_day); ?> id="spa_event_all_day">
                Celý deň
            </label>
        </div>
        
        <div class="spa-meta-row" id="spa_event_time_row" style="<?php echo $all_day ? 'display:none;' : ''; ?>">
            <label>Čas:</label>
            <div class="spa-field">
                <input type="time" name="spa_event_time_from" value="<?php echo esc_attr($time_from); ?>" style="margin-right: 10px;">
                <span>do</span>
                <input type="time" name="spa_event_time_to" value="<?php echo esc_attr($time_to); ?>" style="margin-left: 10px;">
            </div>
        </div>
        
        <div class="spa-meta-row">
            <label for="spa_event_recurring">Opakovanie:</label>
            <div class="spa-field">
                <select name="spa_event_recurring" id="spa_event_recurring">
                    <option value="once" <?php selected($recurring, 'once'); ?>>Jednorazovo</option>
                    <option value="weekly" <?php selected($recurring, 'weekly'); ?>>Každý týždeň</option>
                    <option value="monthly" <?php selected($recurring, 'monthly'); ?>>Každý mesiac</option>
                    <option value="yearly" <?php selected($recurring, 'yearly'); ?>>Každý rok</option>
                </select>
            </div>
        </div>
    </div>
    
    <script>
    document.getElementById('spa_event_all_day').addEventListener('change', function() {
        document.getElementById('spa_event_time_row').style.display = this.checked ? 'none' : 'flex';
    });
    </script>
    <?php
}

/* ============================================================
   META BOX: DOCHÁDZKA (spa_attendance) - NOVÉ
   ============================================================ */
function spa_attendance_meta_box($post) {
    wp_nonce_field('spa_save_attendance', 'spa_attendance_nonce');
    
    $client_id = get_post_meta($post->ID, 'spa_att_client_id', true);
    $program_id = get_post_meta($post->ID, 'spa_att_program_id', true);
    $registration_id = get_post_meta($post->ID, 'spa_att_registration_id', true);
    $date = get_post_meta($post->ID, 'spa_att_date', true);
    $status = get_post_meta($post->ID, 'spa_att_status', true);
    $rating = get_post_meta($post->ID, 'spa_att_rating', true);
    $stars = get_post_meta($post->ID, 'spa_att_stars', true);
    $points = get_post_meta($post->ID, 'spa_att_points', true);
    $note = get_post_meta($post->ID, 'spa_att_note', true);
    
    ?>
    <style>
    .spa-meta-row { display: flex; margin-bottom: 15px; align-items: flex-start; }
    .spa-meta-row label { width: 150px; font-weight: 600; padding-top: 8px; }
    .spa-meta-row .spa-field { flex: 1; }
    .spa-section { background: #f9f9f9; padding: 20px; border: 1px solid #ddd; border-radius: 4px; margin-bottom: 20px; }
    .spa-section h4 { margin: 0 0 15px 0; }
    .spa-stars { font-size: 24px; cursor: pointer; }
    .spa-stars span { color: #ddd; transition: color 0.2s; }
    .spa-stars span.active { color: #FFD700; }
    </style>
    
    <div class="spa-section">
        <h4>📋 Základné údaje</h4>
        
        <div class="spa-meta-row">
            <label for="spa_att_date">Dátum tréningu:</label>
            <div class="spa-field">
                <input type="date" name="spa_att_date" id="spa_att_date" value="<?php echo esc_attr($date ?: date('Y-m-d')); ?>">
            </div>
        </div>
        
        <div class="spa-meta-row">
            <label for="spa_att_status">Status:</label>
            <div class="spa-field">
                <select name="spa_att_status" id="spa_att_status">
                    <option value="present" <?php selected($status, 'present'); ?>>✅ Prítomný</option>
                    <option value="absent" <?php selected($status, 'absent'); ?>>❌ Neprítomný</option>
                    <option value="excused" <?php selected($status, 'excused'); ?>>📝 Ospravedlnený</option>
                    <option value="late" <?php selected($status, 'late'); ?>>⏰ Meškanie</option>
                </select>
            </div>
        </div>
    </div>
    
    <div class="spa-section">
        <h4>⭐ Hodnotenie trénera</h4>
        
        <div class="spa-meta-row">
            <label>Hviezdičky:</label>
            <div class="spa-field">
                <div class="spa-stars" id="spa_stars_container">
                    <?php for ($i = 1; $i <= 5; $i++) : ?>
                        <span data-value="<?php echo $i; ?>" class="<?php echo ($stars >= $i) ? 'active' : ''; ?>">★</span>
                    <?php endfor; ?>
                </div>
                <input type="hidden" name="spa_att_stars" id="spa_att_stars" value="<?php echo esc_attr($stars); ?>">
                <p class="spa-help">Pochvala za tréning (1-5 hviezdičiek)</p>
            </div>
        </div>
        
        <div class="spa-meta-row">
            <label for="spa_att_points">Body:</label>
            <div class="spa-field">
                <input type="number" name="spa_att_points" id="spa_att_points" value="<?php echo esc_attr($points); ?>" min="0" max="100" style="width: 80px;">
                <p class="spa-help">Bonusové body za účasť (gamifikácia)</p>
            </div>
        </div>
        
        <div class="spa-meta-row">
            <label for="spa_att_rating">Slovné hodnotenie:</label>
            <div class="spa-field">
                <textarea name="spa_att_rating" id="spa_att_rating" rows="3" placeholder="Krátke hodnotenie od trénera..."><?php echo esc_textarea($rating); ?></textarea>
            </div>
        </div>
        
        <div class="spa-meta-row">
            <label for="spa_att_note">Interná poznámka:</label>
            <div class="spa-field">
                <textarea name="spa_att_note" id="spa_att_note" rows="2" placeholder="Poznámka pre admina (nie je viditeľná pre rodiča)"><?php echo esc_textarea($note); ?></textarea>
            </div>
        </div>
    </div>
    
    <script>
    (function() {
        var container = document.getElementById('spa_stars_container');
        var input = document.getElementById('spa_att_stars');
        var stars = container.querySelectorAll('span');
        
        stars.forEach(function(star) {
            star.addEventListener('click', function() {
                var value = this.getAttribute('data-value');
                input.value = value;
                
                stars.forEach(function(s) {
                    if (parseInt(s.getAttribute('data-value')) <= parseInt(value)) {
                        s.classList.add('active');
                    } else {
                        s.classList.remove('active');
                    }
                });
            });
        });
    })();
    </script>
    <?php
}

/* ============================================================
   META BOX: CENNÍK PROGRAMU (spa_group) - NOVÉ
   Cena za 1x a 2x týždenne
   ============================================================ */
function spa_group_pricing_meta_box($post) {
    wp_nonce_field('spa_save_group_pricing', 'spa_group_pricing_nonce');
    
    $price_1x = get_post_meta($post->ID, 'spa_price_1x_weekly', true);
    $price_2x = get_post_meta($post->ID, 'spa_price_2x_weekly', true);
    $price_monthly = get_post_meta($post->ID, 'spa_price_monthly', true);
    $price_semester = get_post_meta($post->ID, 'spa_price_semester', true);
    $external_surcharge = get_post_meta($post->ID, 'spa_external_surcharge', true);
    
    ?>
    <style>
    .spa-pricing-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px; }
    .spa-price-box { background: #fff; border: 2px solid #ddd; padding: 15px; border-radius: 8px; }
    .spa-price-box h5 { margin: 0 0 10px 0; color: #333; }
    .spa-price-box input { width: 100px; padding: 8px; font-size: 16px; font-weight: bold; }
    .spa-price-box .currency { font-size: 16px; margin-left: 5px; }
    </style>
    
    <div class="spa-pricing-grid">
        <div class="spa-price-box">
            <h5>💰 Cena za 1x týždenne</h5>
            <input type="number" name="spa_price_1x_weekly" value="<?php echo esc_attr($price_1x); ?>" step="0.01" min="0">
            <span class="currency">€</span>
            <p class="spa-help">Mesačná cena pri jednom tréningu týždenne</p>
        </div>
        
        <div class="spa-price-box">
            <h5>💰 Cena za 2x týždenne</h5>
            <input type="number" name="spa_price_2x_weekly" value="<?php echo esc_attr($price_2x); ?>" step="0.01" min="0">
            <span class="currency">€</span>
            <p class="spa-help">Mesačná cena pri dvoch tréningoch týždenne (zvýhodnená)</p>
        </div>
        
        <div class="spa-price-box">
            <h5>📅 Cena mesačne (paušál)</h5>
            <input type="number" name="spa_price_monthly" value="<?php echo esc_attr($price_monthly); ?>" step="0.01" min="0">
            <span class="currency">€</span>
            <p class="spa-help">Voliteľné - fixná mesačná cena</p>
        </div>
        
        <div class="spa-price-box">
            <h5>🎓 Cena za semester</h5>
            <input type="number" name="spa_price_semester" value="<?php echo esc_attr($price_semester); ?>" step="0.01" min="0">
            <span class="currency">€</span>
            <p class="spa-help">Voliteľné - cena za celý školský polrok</p>
        </div>
    </div>
    
    <div style="margin-top: 20px; padding: 15px; background: #fff3cd; border: 1px solid #ffc107; border-radius: 4px;">
        <h5 style="margin: 0 0 10px 0;">🏫 Príplatok za externé priestory</h5>
        <input type="number" name="spa_external_surcharge" value="<?php echo esc_attr($external_surcharge); ?>" step="0.01" min="0" style="width: 80px;">
        <span class="currency">€</span>
        <p class="spa-help" style="margin-top: 5px;">Príplatok k cene ak sa tréning koná v externých priestoroch (prenájom)</p>
    </div>
    <?php
}

/* ============================================================
   ULOŽENIE META HODNÔT
   ============================================================ */

// MIESTO (spa_place)
add_action('save_post_spa_place', 'spa_save_place_meta', 10, 2);
function spa_save_place_meta($post_id, $post) {
    if (!isset($_POST['spa_place_nonce']) || !wp_verify_nonce($_POST['spa_place_nonce'], 'spa_save_place')) return;
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (!current_user_can('edit_post', $post_id)) return;
    
    $fields = array('spa_place_type', 'spa_place_city', 'spa_place_address', 'spa_place_gps_lat', 'spa_place_gps_lng', 'spa_place_contact', 'spa_place_notes');
    
    foreach ($fields as $field) {
        if (isset($_POST[$field])) {
            update_post_meta($post_id, $field, sanitize_text_field($_POST[$field]));
        }
    }
    
    // Rozvrh (JSON)
    if (isset($_POST['spa_place_schedule']) && is_array($_POST['spa_place_schedule'])) {
        $schedule = array();
        foreach ($_POST['spa_place_schedule'] as $day => $data) {
            if (!empty($data['from']) || !empty($data['to'])) {
                $schedule[$day] = array(
                    'from' => sanitize_text_field($data['from']),
                    'to' => sanitize_text_field($data['to']),
                    'capacity' => intval($data['capacity'] ?? 0),
                    'active' => !empty($data['active'])
                );
            }
        }
        update_post_meta($post_id, 'spa_place_schedule', wp_json_encode($schedule));
    }
}

// UDALOSŤ (spa_event)
add_action('save_post_spa_event', 'spa_save_event_meta', 10, 2);
function spa_save_event_meta($post_id, $post) {
    if (!isset($_POST['spa_event_nonce']) || !wp_verify_nonce($_POST['spa_event_nonce'], 'spa_save_event')) return;
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (!current_user_can('edit_post', $post_id)) return;
    
	$fields = array(
	'spa_event_place_id' => 'intval',
	'spa_event_type' => 'sanitize_text_field',
	'spa_event_date_from' => 'sanitize_text_field',
	'spa_event_date_to' => 'sanitize_text_field',
	'spa_event_time_from' => 'sanitize_text_field',
	'spa_event_time_to' => 'sanitize_text_field',
	'spa_event_recurring' => 'sanitize_text_field'
    );
    
    foreach ($fields as $key => $sanitize) {
        if (isset($_POST[$key])) {
            $value = ($sanitize === 'intval') ? intval($_POST[$key]) : sanitize_text_field($_POST[$key]);
            update_post_meta($post_id, $key, $value);
        }
    }
    
    // Checkbox all_day
    update_post_meta($post_id, 'spa_event_all_day', isset($_POST['spa_event_all_day']) ? 1 : 0);
}

// DOCHÁDZKA (spa_attendance)
add_action('save_post_spa_attendance', 'spa_save_attendance_meta', 10, 2);
function spa_save_attendance_meta($post_id, $post) {
    if (!isset($_POST['spa_attendance_nonce']) || !wp_verify_nonce($_POST['spa_attendance_nonce'], 'spa_save_attendance')) return;
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (!current_user_can('edit_post', $post_id)) return;
    
    $fields = array(
        'spa_att_client_id' => 'intval',
        'spa_att_program_id' => 'intval',
        'spa_att_registration_id' => 'intval',
        'spa_att_date' => 'sanitize_text_field',
        'spa_att_status' => 'sanitize_text_field',
        'spa_att_stars' => 'intval',
        'spa_att_points' => 'intval',
        'spa_att_rating' => 'sanitize_textarea_field',
        'spa_att_note' => 'sanitize_textarea_field'
    );
    
    foreach ($fields as $key => $sanitize) {
        if (isset($_POST[$key])) {
            if ($sanitize === 'intval') {
                $value = intval($_POST[$key]);
            } elseif ($sanitize === 'sanitize_textarea_field') {
                $value = sanitize_textarea_field($_POST[$key]);
            } else {
                $value = sanitize_text_field($_POST[$key]);
            }
            update_post_meta($post_id, $key, $value);
        }
    }
    
    // Automatický title
    $client_id = intval($_POST['spa_att_client_id'] ?? 0);
    $date = sanitize_text_field($_POST['spa_att_date'] ?? '');
    
    if ($client_id && $date) {
        $user = get_userdata($client_id);
        if ($user) {
            $name = trim($user->first_name . ' ' . $user->last_name);
            if (empty($name)) $name = $user->display_name;
            $new_title = $name . ' - ' . date_i18n('j.n.Y', strtotime($date));
            
            remove_action('save_post_spa_attendance', 'spa_save_attendance_meta', 10);
            wp_update_post(array('ID' => $post_id, 'post_title' => $new_title));
            add_action('save_post_spa_attendance', 'spa_save_attendance_meta', 10, 2);
        }
    }
}

// CENNÍK PROGRAMU (spa_group)
add_action('save_post_spa_group', 'spa_save_group_pricing_meta', 10, 2);
function spa_save_group_pricing_meta($post_id, $post) {
    if (!isset($_POST['spa_group_pricing_nonce']) || !wp_verify_nonce($_POST['spa_group_pricing_nonce'], 'spa_save_group_pricing')) return;
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (!current_user_can('edit_post', $post_id)) return;
    
    $price_fields = array(
        'spa_price_1x_weekly',
        'spa_price_2x_weekly',
        'spa_price_monthly',
        'spa_price_semester',
        'spa_external_surcharge'
    );
    
    foreach ($price_fields as $field) {
        if (isset($_POST[$field])) {
            $value = floatval(str_replace(',', '.', $_POST[$field]));
            update_post_meta($post_id, $field, $value);
        }
    }
    
    // Spätná kompatibilita - ulož aj do spa_price (1x weekly ako default)
    if (isset($_POST['spa_price_1x_weekly'])) {
        $price = floatval(str_replace(',', '.', $_POST['spa_price_1x_weekly']));
        update_post_meta($post_id, 'spa_price', $price);
    }
}