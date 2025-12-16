<?php
/**
 * SPA Meta Box: Registrácia - OPRAVENÁ
 * 
 * OPRAVY:
 * - Odstrániť duplikátny blok "Dieťa/Klient"
 * - Horný info blok: display-only (bez <input>)
 * - Zobrazovať: meno, email, DOB, rodné číslo, PIN
 * - Editovateľné: Program, Deň, Čas, Status
 * - PIN a RC: read-only (žiadna edícia)
 * 
 * @package Samuel Piasecký ACADEMY
 * @version 1.0.0 - FIXED
 */

/* ============================================================
   META BOX: DETAILY REGISTRÁCIE (spa_registration)
   ============================================================ */

function spa_registration_meta_box($post) {
    wp_nonce_field('spa_save_registration', 'spa_registration_nonce');
    
    // Ziskaj meta údaje
    $parent_user_id = get_post_meta($post->ID, 'parent_user_id', true);
    $client_user_id = get_post_meta($post->ID, 'client_user_id', true);
    $program_id = get_post_meta($post->ID, 'program_id', true);
    $training_day = get_post_meta($post->ID, 'training_day', true);
    $training_time = get_post_meta($post->ID, 'training_time', true);
    $status = get_post_meta($post->ID, 'status', true);
    
    // Načítaj dostupné programy
    $programs = get_posts([
        'post_type' => 'spa_group',
        'posts_per_page' => -1,
        'orderby' => 'title',
        'order' => 'ASC'
    ]);
    
    // Načítaj dostupných rodičov
    $parents = get_users(['role' => 'spa_parent', 'orderby' => 'display_name']);
    
    // Mapy dní a statusov
    $days_map = [
        'mo' => 'Pondelok',
        'tu' => 'Utorok',
        'we' => 'Streda',
        'th' => 'Štvrtok',
        'fr' => 'Piatok',
        'sa' => 'Sobota',
        'su' => 'Nedeľa'
    ];
    
    $statuses = [
        'pending' => 'Čaká na schválenie',
        'awaiting_payment' => 'Čaká na platbu',
        'partially_paid' => 'Čiastočne zaplatené',
        'approved' => 'Schválené',
        'active' => 'Aktívny',
        'blocked' => 'Blokované',
        'cancelled' => 'Zrušené',
        'completed' => 'Zaregistrované'
    ];
    
    ?>
    <style>
    .spa-meta-row { display: flex; margin-bottom: 20px; align-items: flex-start; }
    .spa-meta-row label { width: 160px; font-weight: 600; padding-top: 8px; }
    .spa-meta-row .spa-field { flex: 1; }
    .spa-meta-row input[type="text"], .spa-meta-row input[type="time"], .spa-meta-row select { 
        width: 100%; 
        max-width: 400px; 
        padding: 8px; 
        border: 1px solid #ddd;
        border-radius: 4px;
    }
    .spa-help { color: #666; font-size: 12px; margin-top: 4px; }
    
    .spa-info-box { 
        background: #e7f3ff; 
        padding: 15px; 
        border-left: 4px solid #0073aa; 
        margin-bottom: 20px; 
        border-radius: 4px;
    }
    .spa-info-box strong { display: block; margin-bottom: 8px; color: #0073aa; font-size: 14px; }
    .spa-info-box .info-line { margin: 6px 0; padding: 6px 0; border-bottom: 1px solid rgba(0,115,170,0.2); }
    .spa-info-box .info-line:last-child { border-bottom: none; }
    .spa-info-label { font-weight: 600; color: #333; min-width: 140px; display: inline-block; }
    .spa-info-value { color: #555; }
    .spa-info-value.empty { color: #999; font-style: italic; }
    
    .spa-readonly { 
        background: #f5f5f5; 
        padding: 8px; 
        border: 1px solid #ddd; 
        border-radius: 4px;
        color: #666;
    }
    
    .spa-section-title {
        font-size: 14px;
        font-weight: 600;
        color: #333;
        padding: 12px 0;
        margin: 20px 0 15px 0;
        border-bottom: 2px solid #0073aa;
    }
    </style>
    
    <!-- ============================================================
         SEKCIA 1: INFO - DISPLAY ONLY (Dieťa + Rodič)
         ============================================================ -->
    
    <div class="spa-info-box">
        <strong>👶 Dieťa/Klient (z importu)</strong>
        
        <?php 
        if ($client_user_id) {
            $child = get_userdata($client_user_id);
            if ($child) {
                $child_first_name = get_user_meta($child_user_id, 'first_name', true);
                $child_last_name = get_user_meta($child_user_id, 'last_name', true);
                $date_of_birth = get_user_meta($child_user_id, 'date_of_birth', true);
                $rodne_cislo = get_user_meta($child_user_id, 'rodne_cislo', true);
                $spa_pin = get_user_meta($child_user_id, 'spa_pin_plain', true);
                
                echo '<div class="info-line">';
                echo '<span class="spa-info-label">Meno:</span>';
                echo '<span class="spa-info-value">' . esc_html($child_first_name . ' ' . $child_last_name) . '</span>';
                echo '</div>';
                
                echo '<div class="info-line">';
                echo '<span class="spa-info-label">Email:</span>';
                echo '<span class="spa-info-value">' . esc_html($child->user_email) . '</span>';
                echo '</div>';
                
                if (!empty($date_of_birth)) {
                    echo '<div class="info-line">';
                    echo '<span class="spa-info-label">Dátum narodenia:</span>';
                    echo '<span class="spa-info-value">' . esc_html($date_of_birth) . '</span>';
                    echo '</div>';
                }
                
                if (!empty($rodne_cislo)) {
                    echo '<div class="info-line">';
                    echo '<span class="spa-info-label">Rodné číslo:</span>';
                    echo '<span class="spa-info-value"><strong>' . esc_html($rodne_cislo) . '</strong></span>';
                    echo '</div>';
                }
                
                if (!empty($spa_pin)) {
                    echo '<div class="info-line">';
                    echo '<span class="spa-info-label">PIN:</span>';
                    echo '<span class="spa-info-value"><strong>' . esc_html($spa_pin) . '</strong></span>';
                    echo '</div>';
                }
            } else {
                echo '<div class="spa-info-value empty">Neznáme ID: ' . intval($client_user_id) . '</div>';
            }
        } else {
            echo '<div class="spa-info-value empty">Nie je nastavené</div>';
        }
        ?>
    </div>
    
    <!-- ============================================================
         SEKCIA 2: EDITOVATEĽNÉ POLIA
         ============================================================ -->
    
    <div class="spa-section-title">✏️ Upraviť registráciu</div>
    
    <!-- Rodič (editovateľný) -->
    <div class="spa-meta-row">
        <label for="spa_parent_id">👨‍👩‍👧 Rodič:</label>
        <div class="spa-field">
            <select name="spa_parent_id" id="spa_parent_id">
                <option value="">-- Vyberte rodiča --</option>
                <?php foreach ($parents as $parent) : ?>
                    <option value="<?php echo $parent->ID; ?>" <?php selected($parent_user_id, $parent->ID); ?>>
                        <?php echo esc_html($parent->first_name . ' ' . $parent->last_name . ' (' . $parent->user_email . ')'); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <p class="spa-help">Zmena rodiča pre túto registráciu</p>
        </div>
    </div>
    
    <!-- Program (editovateľný) -->
    <div class="spa-meta-row">
        <label for="spa_group_id">🏋️ Program:</label>
        <div class="spa-field">
            <select name="spa_group_id" id="spa_group_id">
                <option value="">-- Vyberte program --</option>
                <?php foreach ($programs as $program) : ?>
                    <option value="<?php echo $program->ID; ?>" <?php selected($program_id, $program->ID); ?>>
                        <?php echo esc_html($program->post_title); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <p class="spa-help">Zmena tréningového programu</p>
        </div>
    </div>
    
    <!-- Deň (editovateľný) -->
    <div class="spa-meta-row">
        <label for="spa_day">⏰ Deň v týždni:</label>
        <div class="spa-field">
            <select name="spa_day" id="spa_day">
                <option value="">-- Vyberte deň --</option>
                <?php foreach ($days_map as $key => $label) : ?>
                    <option value="<?php echo $key; ?>" <?php selected($training_day, $key); ?>>
                        <?php echo esc_html($label); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <p class="spa-help">Zmena dňa tréningu</p>
        </div>
    </div>
    
    <!-- Čas (editovateľný) -->
    <div class="spa-meta-row">
        <label for="spa_time">🕐 Čas:</label>
        <div class="spa-field">
            <input type="time" name="spa_time" id="spa_time" value="<?php echo esc_attr($training_time); ?>">
            <p class="spa-help">Zmena času začiatku tréningu</p>
        </div>
    </div>
    
    <!-- Status (editovateľný) -->
    <div class="spa-meta-row">
        <label for="spa_status">📊 Status:</label>
        <div class="spa-field">
            <select name="spa_status" id="spa_status">
                <option value="">-- Vyberte status --</option>
                <?php foreach ($statuses as $key => $label) : ?>
                    <option value="<?php echo $key; ?>" <?php selected($status, $key); ?>>
                        <?php echo esc_html($label); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <p class="spa-help">Aktuálny stav registrácie</p>
        </div>
    </div>
    
    <?php
}

/* ============================================================
   SAVE META BOX: REGISTRÁCIA
   ============================================================ */

add_action('save_post_spa_registration', 'spa_save_registration_meta', 11, 2);

function spa_save_registration_meta($post_id, $post) {
    if (!isset($_POST['spa_registration_nonce'])) {
        return;
    }
    
    if (!wp_verify_nonce($_POST['spa_registration_nonce'], 'spa_save_registration')) {
        return;
    }
    
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    
    if (!current_user_can('edit_post', $post_id)) {
        return;
    }
    
    // Ulož meta
    if (isset($_POST['spa_parent_id'])) {
        update_post_meta($post_id, 'parent_user_id', intval($_POST['spa_parent_id']));
    }
    
    if (isset($_POST['spa_group_id'])) {
        update_post_meta($post_id, 'program_id', intval($_POST['spa_group_id']));
    }
    
    if (isset($_POST['spa_day'])) {
        update_post_meta($post_id, 'training_day', sanitize_text_field($_POST['spa_day']));
    }
    
    if (isset($_POST['spa_time'])) {
        update_post_meta($post_id, 'training_time', sanitize_text_field($_POST['spa_time']));
    }
    
    if (isset($_POST['spa_status'])) {
        update_post_meta($post_id, 'status', sanitize_text_field($_POST['spa_status']));
    }
}