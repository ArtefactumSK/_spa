<?php
/**
 * SPA Trainer Management
 * Frontend správa trénerov pre majiteľa/admina
 * 
 * @package Samuel Piasecký ACADEMY
 * @version 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/* ==========================
   ROLE: Registrácia spa_trainer
   ========================== */

add_action('init', 'spa_register_trainer_role');

function spa_register_trainer_role() {
    if (!get_role('spa_trainer')) {
        add_role('spa_trainer', 'SPA Tréner', array(
            'read' => true,
            'edit_posts' => false,
            'delete_posts' => false,
            'upload_files' => true
        ));
    }
}

/* ==========================
   CAPABILITY CHECK
   ========================== */

function spa_can_manage_trainers() {
    if (!is_user_logged_in()) return false;
    $user = wp_get_current_user();
    return in_array('administrator', (array) $user->roles) || 
           in_array('spa_owner', (array) $user->roles);
}

/* ==========================
   SHORTCODE: Správa trénerov
   [spa_trainer_management]
   ========================== */

add_shortcode('spa_trainer_management', 'spa_trainer_management_shortcode');

function spa_trainer_management_shortcode($atts) {
    
    if (!spa_can_manage_trainers()) {
        return '<div class="spa-alert spa-alert-error">⛔ Nemáš oprávnenie na správu trénerov.</div>';
    }
    
    ob_start();
    ?>
    <div class="spa-trainer-management">
        
        <div class="spa-tm-header">
            <h2>👟 Správa trénerov</h2>
            <button type="button" class="spa-btn spa-btn-primary" id="spa-add-trainer-btn">
                <strong>+</strong> Pridať nového trénera
            </button>
        </div>
        
        <!-- ZOZNAM TRÉNEROV -->
        <div class="spa-trainers-list" id="spa-trainers-list">
            <?php echo spa_render_trainers_list(); ?>
        </div>
        
        <!-- MODAL: Pridanie/Editácia trénera -->
        <div class="spa-modal" id="spa-trainer-modal" style="display:none;">
            <div class="spa-modal-overlay"></div>
            <div class="spa-modal-content">
                <div class="spa-modal-header">
                    <h3 id="spa-modal-title">Pridať trénera 👟</h3>
                    <button type="button" class="spa-modal-close">&times;</button>
                </div>
                <form id="spa-trainer-form" enctype="multipart/form-data">
                    <input type="hidden" name="trainer_id" id="spa-trainer-id" value="">
                    
                    <div class="spa-form-row-2">
                        <div class="spa-form-group">
                            <label for="spa-trainer-firstname">Meno *</label>
                            <input type="text" name="firstname" id="spa-trainer-firstname" required>
                        </div>
                        <div class="spa-form-group">
                            <label for="spa-trainer-lastname">Priezvisko *</label>
                            <input type="text" name="lastname" id="spa-trainer-lastname" required>
                        </div>
                    </div>
                    
                    <div class="spa-form-group">
                        <label for="spa-trainer-email">Email *</label>
                        <input type="email" name="email" id="spa-trainer-email" required>
                    </div>
                    
                    <div class="spa-form-group">
                        <label for="spa-trainer-phone">Telefón</label>
                        <input type="tel" name="phone" id="spa-trainer-phone" placeholder="+421 9XX XXX XXX">
                    </div>
                    
                    <div class="spa-form-group">
                        <label for="spa-trainer-bio">Bio / Popis</label>
                        <textarea name="bio" id="spa-trainer-bio" rows="4" placeholder="Krátky popis trénera, jeho skúsenosti, certifikáty..."></textarea>
                    </div>
                    
                    <div class="spa-form-group">
                        <label for="spa-trainer-photo">Profilová fotka</label>
                        <input type="file" name="photo" id="spa-trainer-photo" accept="image/*">
                        <div id="spa-photo-preview" class="spa-photo-preview"></div>
                    </div>
                    
                    <div class="spa-form-group">
                        <label for="spa-trainer-specialization">Špecializácia</label>
                        <input type="text" name="specialization" id="spa-trainer-specialization" placeholder="napr. Gymnastika, Atletika, Futbal">
                    </div>
                    <div class="spa-form-group">
						<label>Sociálne siete</label>
						<div class="spa-social-inputs">
							<div class="spa-social-input">
								<?php echo spa_icon('instagram', 'spa-icon-input'); ?>
								<input type="url" name="instagram" id="spa-trainer-instagram" placeholder="https://instagram.com/username">
							</div>
							<div class="spa-social-input">
								<?php echo spa_icon('facebook', 'spa-icon-input'); ?>
								<input type="url" name="facebook" id="spa-trainer-facebook" placeholder="https://facebook.com/username">
							</div>
							<div class="spa-social-input">
								<?php echo spa_icon('tiktok', 'spa-icon-input'); ?>
								<input type="url" name="tiktok" id="spa-trainer-tiktok" placeholder="https://tiktok.com/@username">
							</div>
							<div class="spa-social-input">
								<?php echo spa_icon('youtube', 'spa-icon-input'); ?>
								<input type="url" name="youtube" id="spa-trainer-youtube" placeholder="https://youtube.com/@channel">
							</div>
						</div>
					</div>
                    <div class="spa-form-group" id="spa-password-group">
                        <label for="spa-trainer-password">Heslo *</label>
                        <input type="password" name="password" id="spa-trainer-password" minlength="8">
                        <small>Minimálne 8 znakov. Pri editácii nechaj prázdne ak nechceš meniť.</small>
                    </div>
                    
                    <div class="spa-form-group">
                        <label class="spa-checkbox">
                            <input type="checkbox" name="send_email" id="spa-trainer-send-email" checked>
                            Poslať prihlasovacie údaje emailom
                        </label>
                    </div>
                    
                    <div class="spa-modal-footer">
                        <button type="button" class="spa-btn spa-btn-secondary spa-modal-cancel">Zrušiť</button>
                        <button type="submit" class="spa-btn spa-btn-primary">
                            <span class="spa-btn-text">Uložiť</span>
                            <span class="spa-btn-loading" style="display:none;">Ukladám...</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- MODAL: Priradenie programov -->
        <div class="spa-modal" id="spa-programs-modal" style="display:none;">
            <div class="spa-modal-overlay"></div>
            <div class="spa-modal-content">
                <div class="spa-modal-header">
                    <h3>📋 Priradiť programy</h3>
                    <button type="button" class="spa-modal-close">&times;</button>
                </div>
                <form id="spa-programs-form">
                    <input type="hidden" name="trainer_id" id="spa-programs-trainer-id" value="">
                    
                    <div class="spa-programs-list" id="spa-programs-checkboxes">
                        <!-- Načíta sa cez AJAX -->
                    </div>
                    
                    <div class="spa-modal-footer">
                        <button type="button" class="spa-btn spa-btn-secondary spa-modal-cancel">Zrušiť</button>
                        <button type="submit" class="spa-btn spa-btn-primary">Uložiť</button>
                    </div>
                </form>
            </div>
        </div>
        
    </div>
    
    <?php 
    spa_trainer_management_styles();
    spa_trainer_management_scripts();
    ?>
    <?php
    return ob_get_clean();
}

/* ==========================
   RENDER: Zoznam trénerov
   ========================== */

function spa_render_trainers_list() {
    $trainers = get_users(array('role' => 'spa_trainer', 'orderby' => 'display_name'));
    
    if (empty($trainers)) {
        return '<div class="spa-empty-state">
            <p>👨‍🏫 Zatiaľ nie sú pridaní žiadni tréneri.</p>
            <p>Klikni na "Pridať trénera" pre vytvorenie prvého.</p>
        </div>';
    }
    
    $html = '<div class="spa-trainers-grid">';
    
    foreach ($trainers as $trainer) {
        $phone = get_user_meta($trainer->ID, 'spa_phone', true);
        $bio = get_user_meta($trainer->ID, 'spa_bio', true);
        $specialization = get_user_meta($trainer->ID, 'spa_specialization', true);
        $photo_id = get_user_meta($trainer->ID, 'spa_photo_id', true);
        $photo_url = $photo_id ? wp_get_attachment_image_url($photo_id, 'thumbnail') : '';
        $programs = spa_get_trainer_programs($trainer->ID);
        
        $html .= '<div class="spa-trainer-card" data-id="' . esc_attr($trainer->ID) . '">';
        
        $html .= '<div class="spa-trainer-photo">';
        if ($photo_url) {
            $html .= '<img src="' . esc_url($photo_url) . '" alt="' . esc_attr($trainer->display_name) . '">';
        } else {
            $html .= '<div class="spa-trainer-avatar">👤</div>';
        }
        $html .= '</div>';
        
        $html .= '<div class="spa-trainer-info">';
        $html .= '<h4>' . esc_html($trainer->display_name) . '</h4>';
        $html .= '<p class="spa-trainer-email">' . esc_html($trainer->user_email) . '</p>';
        
        if ($phone) {
            $html .= '<p class="spa-trainer-phone">' . spa_icon('phone') . ' <a href="tel:' . esc_html($phone) . '">' . esc_html($phone) . '</a></p>';
        }
        
        if ($specialization) {
            $html .= '<p class="spa-trainer-spec">' . spa_icon('qualifications') . ' ' . esc_html($specialization) . '</p>';
        }

		// Sociálne siete
		$instagram = get_user_meta($trainer->ID, 'spa_instagram', true);
		$facebook = get_user_meta($trainer->ID, 'spa_facebook', true);
		$tiktok = get_user_meta($trainer->ID, 'spa_tiktok', true);
		$youtube = get_user_meta($trainer->ID, 'spa_youtube', true);

		if ($instagram || $facebook || $tiktok || $youtube) {
			$html .= '<div class="spa-trainer-social">';
			if ($instagram) {
				$html .= '<a href="' . esc_url($instagram) . '" target="_blank" title="Instagram">' . spa_icon('instagram') . '</a>';
			}
			if ($facebook) {
				$html .= '<a href="' . esc_url($facebook) . '" target="_blank" title="Facebook">' . spa_icon('facebook') . '</a>';
			}
			if ($tiktok) {
				$html .= '<a href="' . esc_url($tiktok) . '" target="_blank" title="TikTok">' . spa_icon('tiktok') . '</a>';
			}
			if ($youtube) {
				$html .= '<a href="' . esc_url($youtube) . '" target="_blank" title="YouTube">' . spa_icon('youtube') . '</a>';
			}
			$html .= '</div>';
		}
        
        if (!empty($programs)) {
            $html .= '<div class="spa-trainer-programs">';
            foreach ($programs as $program) {
                $html .= '<span class="spa-program-badge">' . esc_html($program) . '</span>';
            }
            $html .= '</div>';
        }
        
        $html .= '</div>';
        
        $html .= '<div class="spa-trainer-actions">';
        $html .= '<button type="button" class="spa-btn-icon spa-edit-trainer" title="Upraviť údaje trénera">✏️</button>';
        $html .= '<button type="button" class="spa-btn-icon spa-assign-programs" title="Priradiť programy pre trénera">📋</button>';
        $html .= '<button type="button" class="spa-btn-icon spa-delete-trainer" title="Vymazať trénera">🗑️</button>';
        $html .= '</div>';
        
        $html .= '</div>';
    }
    
    $html .= '</div>';
    
    return $html;
}

/* ==========================
   HELPER: Získaj programy trénera
   ========================== */

function spa_get_trainer_programs($trainer_id) {
    $program_ids = get_user_meta($trainer_id, 'spa_assigned_programs', true);
    if (empty($program_ids) || !is_array($program_ids)) return array();
    
    $programs = array();
    foreach ($program_ids as $id) {
        $term = get_term($id, 'spa_program');
        if ($term && !is_wp_error($term)) {
            $programs[] = $term->name;
        }
    }
    return $programs;
}

/* ==========================
   AJAX: Uloženie trénera
   ========================== */

add_action('wp_ajax_spa_save_trainer', 'spa_ajax_save_trainer');

function spa_ajax_save_trainer() {
    
    if (!spa_can_manage_trainers()) {
        wp_send_json_error(array('message' => 'Nedostatočné oprávnenia'));
    }
    
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'spa_trainer_nonce')) {
        wp_send_json_error(array('message' => 'Bezpečnostná kontrola zlyhala'));
    }
    
    $trainer_id = isset($_POST['trainer_id']) ? intval($_POST['trainer_id']) : 0;
    $firstname = sanitize_text_field($_POST['firstname']);
    $lastname = sanitize_text_field($_POST['lastname']);
    $email = sanitize_email($_POST['email']);
    $phone = sanitize_text_field($_POST['phone']);
    $bio = sanitize_textarea_field($_POST['bio']);
    $specialization = sanitize_text_field($_POST['specialization']);
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    $send_email = isset($_POST['send_email']) && $_POST['send_email'] === 'true';
    
    if (empty($firstname) || empty($lastname) || empty($email)) {
        wp_send_json_error(array('message' => 'Vyplň všetky povinné polia'));
    }
    
    // Editácia existujúceho
    if ($trainer_id > 0) {
        $user = get_user_by('ID', $trainer_id);
        if (!$user || !in_array('spa_trainer', (array) $user->roles)) {
            wp_send_json_error(array('message' => 'Tréner neexistuje'));
        }
        
        // Kontrola duplicitného emailu
        $existing = get_user_by('email', $email);
        if ($existing && $existing->ID != $trainer_id) {
            wp_send_json_error(array('message' => 'Tento email už používa iný účet'));
        }
        
        wp_update_user(array(
            'ID' => $trainer_id,
            'user_email' => $email,
            'first_name' => $firstname,
            'last_name' => $lastname,
            'display_name' => $firstname . ' ' . $lastname
        ));
        
        if (!empty($password)) {
            wp_set_password($password, $trainer_id);
        }
        
        $message = 'Tréner bol aktualizovaný';
        
    } else {
        // Nový tréner
        if (empty($password)) {
            wp_send_json_error(array('message' => 'Heslo je povinné pre nového trénera'));
        }
        
        if (strlen($password) < 8) {
            wp_send_json_error(array('message' => 'Heslo musí mať minimálne 8 znakov'));
        }
        
        if (email_exists($email)) {
            wp_send_json_error(array('message' => 'Tento email už používa iný účet'));
        }
        
        $username = sanitize_user(strtolower($firstname . '.' . $lastname));
        $username = spa_generate_unique_username($username);
        
        $trainer_id = wp_insert_user(array(
            'user_login' => $username,
            'user_email' => $email,
            'user_pass' => $password,
            'first_name' => $firstname,
            'last_name' => $lastname,
            'display_name' => $firstname . ' ' . $lastname,
            'role' => 'spa_trainer'
        ));
        
        if (is_wp_error($trainer_id)) {
            wp_send_json_error(array('message' => $trainer_id->get_error_message()));
        }
        
        // Odoslať email s prihlasovacími údajmi
        if ($send_email) {
            spa_send_trainer_welcome_email($trainer_id, $password);
        }
        
        $message = 'Tréner bol vytvorený';
    }
    
    // Uloženie meta údajov
    update_user_meta($trainer_id, 'spa_phone', $phone);
    update_user_meta($trainer_id, 'spa_bio', $bio);
    update_user_meta($trainer_id, 'spa_specialization', $specialization);
    

	// Sociálne siete
	$instagram = isset($_POST['instagram']) ? esc_url_raw($_POST['instagram']) : '';
	$facebook = isset($_POST['facebook']) ? esc_url_raw($_POST['facebook']) : '';
	$tiktok = isset($_POST['tiktok']) ? esc_url_raw($_POST['tiktok']) : '';
	$youtube = isset($_POST['youtube']) ? esc_url_raw($_POST['youtube']) : '';

	update_user_meta($trainer_id, 'spa_instagram', $instagram);
	update_user_meta($trainer_id, 'spa_facebook', $facebook);
	update_user_meta($trainer_id, 'spa_tiktok', $tiktok);
	update_user_meta($trainer_id, 'spa_youtube', $youtube);

    // Upload fotky
    if (!empty($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/media.php');
        
        $attachment_id = media_handle_upload('photo', 0);
        
        if (!is_wp_error($attachment_id)) {
            // Zmaž starú fotku
            $old_photo = get_user_meta($trainer_id, 'spa_photo_id', true);
            if ($old_photo) {
                wp_delete_attachment($old_photo, true);
            }
            update_user_meta($trainer_id, 'spa_photo_id', $attachment_id);
        }
    }
    
    wp_send_json_success(array(
        'message' => 'Programy boli uložené',
        'html' => spa_render_trainers_list()
    ));
}

/* ==========================
   EMAIL: Uvítací email trénerovi
   ========================== */

function spa_send_trainer_welcome_email($trainer_id, $password) {
    $user = get_user_by('ID', $trainer_id);
    if (!$user) return;
    
    $login_url = home_url('/login/');
    
    $subject = 'Vitaj v tíme Samuel Piasecký ACADEMY!';
    
    $message = "Ahoj " . $user->first_name . ",\n\n";
    $message .= "Tvoj účet trénera v Samuel Piasecký ACADEMY bol vytvorený.\n\n";
    $message .= "Prihlasovacie údaje:\n";
    $message .= "Email: " . $user->user_email . "\n";
    $message .= "Heslo: " . $password . "\n\n";
    $message .= "Prihlásiť sa môžeš tu: " . $login_url . "\n\n";
    $message .= "Po prvom prihlásení si odporúčame zmeniť heslo.\n\n";
    $message .= "S pozdravom,\n";
    $message .= "Samuel Piasecký ACADEMY";
    
    wp_mail($user->user_email, $subject, $message);
}

/* ==========================
   STYLES
   ========================== */

function spa_trainer_management_styles() {
    ?>
    <style>
    .spa-trainer-management {
        max-width: 1200px;
        margin: 0 auto;
    }
    
    .spa-tm-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 30px;
        flex-wrap: wrap;
        gap: 15px;
    }
    
    .spa-tm-header h2 {
        margin: 0;
    }
    
    .spa-trainers-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
        gap: 20px;
    }
    
    .spa-trainer-card {
        background: #fff;
        border-radius: 12px;
        box-shadow: 0 2px 12px rgba(0,0,0,0.08);
        padding: 20px;
        display: flex;
        flex-direction: column;
        gap: 15px;
        transition: transform 0.2s, box-shadow 0.2s;
    }
    
    .spa-trainer-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 4px 20px rgba(0,0,0,0.12);
    }
    
    .spa-trainer-photo {
        width: 80px;
        height: 80px;
        border-radius: 50%;
        overflow: hidden;
        margin: 0 auto;
        background: #f0f0f0;
    }
    
    .spa-trainer-photo img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    
    .spa-trainer-avatar {
        width: 100%;
        height: 100%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 40px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    }
    
    .spa-trainer-info {
        text-align: center;
    }
    
    .spa-trainer-info h4 {
        margin: 0 0 5px;
        font-size: 18px;
    }
    
    .spa-trainer-email {
        color: #666;
        font-size: 14px;
        margin: 0 0 10px;
    }
    
    .spa-trainer-phone,
    .spa-trainer-spec {
        font-size: 14px;
        margin: 5px 0;
        color: #555;
    }
    
    .spa-trainer-programs {
        display: flex;
        flex-wrap: wrap;
        justify-content: center;
        gap: 5px;
        margin-top: 10px;
    }
    
    .spa-program-badge {
        background: var(--theme-palette-color-1, #0072CE);
        color: #fff;
        padding: 4px 10px;
        border-radius: 20px;
        font-size: 12px;
    }
    
    .spa-trainer-actions {
        display: flex;
        justify-content: center;
        gap: 10px;
        padding-top: 15px;
        border-top: 1px solid #eee;
    }
    
    .spa-btn-icon {
        background: none;
        border: 2px solid #ddd;
        border-radius: 8px;
        padding: 8px 12px;
        cursor: pointer;
        font-size: 16px;
        transition: all 0.2s;
    }
    
    .spa-btn-icon:hover {
        border-color: var(--theme-palette-color-1, #0072CE);
        background: #f5f5f5;
    }
    
    .spa-btn-icon.spa-delete-trainer:hover {
        border-color: #c62828;
        background: #ffebee;
    }
    
    .spa-empty-state {
        text-align: center;
        padding: 60px 20px;
        background: #f9f9f9;
        border-radius: 12px;
        color: #666;
    }
    
    .spa-empty-state p:first-child {
        font-size: 24px;
        margin-bottom: 10px;
    }
    
    /* Modal */
    .spa-modal {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        z-index: 99999;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    .spa-modal-overlay {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0,0,0,0.6);
    }
    
    .spa-modal-content {
        position: relative;
        background: #fff;
        border-radius: 12px;
        width: 90%;
        max-width: 500px;
        max-height: 90vh;
        overflow-y: auto;
    }
    
    .spa-modal-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 20px;
        border-bottom: 1px solid #eee;
    }
    
    .spa-modal-header h3 {
        margin: 0;
    }
    
    .spa-modal-close {
        background: none;
        border: none;
        font-size: 28px;
        cursor: pointer;
        color: #999;
        line-height: 1;
    }
    
    .spa-modal-close:hover {
        color: #333;
    }
    
    .spa-modal form {
        padding: 20px;
    }
    
    .spa-modal-footer {
        display: flex;
        justify-content: flex-end;
        gap: 10px;
        padding-top: 20px;
        border-top: 1px solid #eee;
        margin-top: 20px;
    }
    
    .spa-btn-secondary {
        background: #f0f0f0;
        color: #333;
    }
    
    .spa-btn-secondary:hover {
        background: #e0e0e0;
    }
    
    .spa-photo-preview {
        margin-top: 10px;
    }
    
    .spa-photo-preview img {
        max-width: 100px;
        border-radius: 8px;
    }
    
    .spa-programs-list {
        max-height: 300px;
        overflow-y: auto;
    }
    
    .spa-program-checkbox {
        display: block;
        padding: 12px;
        border-bottom: 1px solid #eee;
        cursor: pointer;
    }
    
    .spa-program-checkbox:hover {
        background: #f9f9f9;
    }
    
    .spa-form-group {
        margin-bottom: 20px;
    }
    
    .spa-form-group label {
        display: block;
        font-weight: 600;
        margin-bottom: 8px;
    }
    
    .spa-form-group input[type="text"],
    .spa-form-group input[type="email"],
    .spa-form-group input[type="tel"],
    .spa-form-group input[type="password"],
    .spa-form-group textarea {
        width: 100%;
        padding: 12px;
        border: 2px solid #e0e0e0;
        border-radius: 8px;
        font-size: 15px;
        box-sizing: border-box;
    }
    
    .spa-form-group input:focus,
    .spa-form-group textarea:focus {
        outline: none;
        border-color: var(--theme-palette-color-1, #0072CE);
    }
    
    .spa-form-group small {
        display: block;
        color: #888;
        margin-top: 5px;
        font-size: 13px;
    }
    
    .spa-form-row-2 {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 15px;
    }
    
    .spa-btn {
        padding: 12px 24px;
        border: none;
        border-radius: 8px;
        font-size: 15px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s;
    }
    
    .spa-btn-primary {
        background: linear-gradient(135deg, var(--theme-palette-color-1, #0072CE) 0%, #005BAA 100%);
        color: #fff;
    }
    
    .spa-btn-primary:hover {
        background: linear-gradient(135deg, var(--theme-palette-color-3, #E4002B) 0%, #C40025 100%);
    }
    
    .spa-alert {
        padding: 16px;
        border-radius: 8px;
        margin-bottom: 20px;
    }
    
    .spa-alert-error {
        background: #ffebee;
        color: #c62828;
    }
    
    @media (max-width: 600px) {
        .spa-form-row-2 {
            grid-template-columns: 1fr;
        }
        
        .spa-trainers-grid {
            grid-template-columns: 1fr;
        }
    }
    </style>
    <?php
}

/* ==========================
   SCRIPTS
   ========================== */

function spa_trainer_management_scripts() {
    $nonce = wp_create_nonce('spa_trainer_nonce');
    ?>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        
        var modal = document.getElementById('spa-trainer-modal');
        var programsModal = document.getElementById('spa-programs-modal');
        var form = document.getElementById('spa-trainer-form');
        var programsForm = document.getElementById('spa-programs-form');
        var ajaxUrl = '<?php echo admin_url('admin-ajax.php'); ?>';
        var nonce = '<?php echo $nonce; ?>';
        
        // Pridať trénera
        document.getElementById('spa-add-trainer-btn').addEventListener('click', function() {
            resetForm();
            document.getElementById('spa-modal-title').textContent = 'Pridať trénera';
            document.getElementById('spa-trainer-password').required = true;
            modal.style.display = 'flex';
        });
        
        // Zavrieť modal
        document.querySelectorAll('.spa-modal-close, .spa-modal-cancel, .spa-modal-overlay').forEach(function(el) {
            el.addEventListener('click', function() {
                modal.style.display = 'none';
                programsModal.style.display = 'none';
            });
        });
        
        // ESC zavrieť
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                modal.style.display = 'none';
                programsModal.style.display = 'none';
            }
        });
        
        // Editovať trénera
        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('spa-edit-trainer')) {
                var card = e.target.closest('.spa-trainer-card');
                var trainerId = card.dataset.id;
                
                fetch(ajaxUrl, {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: 'action=spa_get_trainer&trainer_id=' + trainerId
                })
                .then(r => r.json())
                .then(function(res) {
                    if (res.success) {
                        var d = res.data;
                        document.getElementById('spa-trainer-id').value = d.id;
                        document.getElementById('spa-trainer-firstname').value = d.firstname;
                        document.getElementById('spa-trainer-lastname').value = d.lastname;
                        document.getElementById('spa-trainer-email').value = d.email;
                        document.getElementById('spa-trainer-phone').value = d.phone || '';
                        document.getElementById('spa-trainer-bio').value = d.bio || '';
                        document.getElementById('spa-trainer-specialization').value = d.specialization || '';
						document.getElementById('spa-trainer-instagram').value = d.instagram || '';
						document.getElementById('spa-trainer-facebook').value = d.facebook || '';
						document.getElementById('spa-trainer-tiktok').value = d.tiktok || '';
						document.getElementById('spa-trainer-youtube').value = d.youtube || '';
                        document.getElementById('spa-trainer-password').required = false;
                        
                        var preview = document.getElementById('spa-photo-preview');
                        if (d.photo_url) {
                            preview.innerHTML = '<img src="' + d.photo_url + '">';
                        } else {
                            preview.innerHTML = '';
                        }
                        
                        document.getElementById('spa-modal-title').textContent = '✏️ Úprava trénera';
                        modal.style.display = 'flex';
                    }
                });
            }
        });
        
        // Zmazať trénera
        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('spa-delete-trainer')) {
                if (!confirm('Naozaj chceš zmazať tohto trénera?')) return;
                
                var card = e.target.closest('.spa-trainer-card');
                var trainerId = card.dataset.id;
                
                fetch(ajaxUrl, {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: 'action=spa_delete_trainer&trainer_id=' + trainerId + '&nonce=' + nonce
                })
                .then(r => r.json())
                .then(function(res) {
                    if (res.success) {
                        document.getElementById('spa-trainers-list').innerHTML = res.data.html;
                    } else {
                        alert(res.data.message);
                    }
                });
            }
        });
        
        // Priradiť programy
        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('spa-assign-programs')) {
                var card = e.target.closest('.spa-trainer-card');
                var trainerId = card.dataset.id;
                
                document.getElementById('spa-programs-trainer-id').value = trainerId;
                
                fetch(ajaxUrl, {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: 'action=spa_get_programs_for_trainer&trainer_id=' + trainerId
                })
                .then(r => r.json())
                .then(function(res) {
                    if (res.success) {
                        document.getElementById('spa-programs-checkboxes').innerHTML = res.data.html;
                        programsModal.style.display = 'flex';
                    }
                });
            }
        });
        
        // Uložiť programy
        programsForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            var trainerId = document.getElementById('spa-programs-trainer-id').value;
            var checkboxes = programsForm.querySelectorAll('input[name="programs[]"]:checked');
            var programs = Array.from(checkboxes).map(cb => cb.value);
            
            var data = new FormData();
            data.append('action', 'spa_save_trainer_programs');
            data.append('nonce', nonce);
            data.append('trainer_id', trainerId);
            programs.forEach(p => data.append('programs[]', p));
            
            fetch(ajaxUrl, {method: 'POST', body: data})
            .then(r => r.json())
            .then(function(res) {
                if (res.success) {
                    document.getElementById('spa-trainers-list').innerHTML = res.data.html;
                    programsModal.style.display = 'none';
                } else {
                    alert(res.data.message);
                }
            });
        });
        
        // Uložiť trénera
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            
            var btn = form.querySelector('.spa-btn-primary');
            btn.disabled = true;
            btn.querySelector('.spa-btn-text').style.display = 'none';
            btn.querySelector('.spa-btn-loading').style.display = 'inline';
            
            var data = new FormData(form);
            data.append('action', 'spa_save_trainer');
            data.append('nonce', nonce);
            data.append('send_email', document.getElementById('spa-trainer-send-email').checked ? 'true' : 'false');
            
            fetch(ajaxUrl, {method: 'POST', body: data})
            .then(r => r.json())
            .then(function(res) {
                btn.disabled = false;
                btn.querySelector('.spa-btn-text').style.display = 'inline';
                btn.querySelector('.spa-btn-loading').style.display = 'none';
                
                if (res.success) {
                    document.getElementById('spa-trainers-list').innerHTML = res.data.html;
                    modal.style.display = 'none';
                } else {
                    alert(res.data.message);
                }
            });
        });
        
        // Preview fotky
        document.getElementById('spa-trainer-photo').addEventListener('change', function() {
            var preview = document.getElementById('spa-photo-preview');
            if (this.files && this.files[0]) {
                var reader = new FileReader();
                reader.onload = function(e) {
                    preview.innerHTML = '<img src="' + e.target.result + '">';
                };
                reader.readAsDataURL(this.files[0]);
            }
        });
        
        function resetForm() {
            form.reset();
            document.getElementById('spa-trainer-id').value = '';
            document.getElementById('spa-photo-preview').innerHTML = '';
        }
    });
    </script>
    <?php
}

/* ==========================
   HELPER: Unikátne username
   ========================== */

function spa_generate_unique_username($base) {
    $username = $base;
    $counter = 1;
    while (username_exists($username)) {
        $username = $base . $counter;
        $counter++;
    }
    return $username;
}

/* ==========================
   AJAX: Zmazanie trénera
   ========================== */

add_action('wp_ajax_spa_delete_trainer', 'spa_ajax_delete_trainer');

function spa_ajax_delete_trainer() {
    
    if (!spa_can_manage_trainers()) {
        wp_send_json_error(array('message' => 'Nedostatočné oprávnenia'));
    }
    
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'spa_trainer_nonce')) {
        wp_send_json_error(array('message' => 'Bezpečnostná kontrola zlyhala'));
    }
    
    $trainer_id = intval($_POST['trainer_id']);
    $user = get_user_by('ID', $trainer_id);
    
    if (!$user || !in_array('spa_trainer', (array) $user->roles)) {
        wp_send_json_error(array('message' => 'Tréner neexistuje'));
    }
    
    $photo_id = get_user_meta($trainer_id, 'spa_photo_id', true);
    if ($photo_id) {
        wp_delete_attachment($photo_id, true);
    }
    
    require_once(ABSPATH . 'wp-admin/includes/user.php');
    wp_delete_user($trainer_id);
    
    wp_send_json_success(array(
        'message' => 'Tréner bol zmazaný',
        'html' => spa_render_trainers_list()
    ));
}

/* ==========================
   AJAX: Načítanie trénera pre editáciu
   ========================== */

add_action('wp_ajax_spa_get_trainer', 'spa_ajax_get_trainer');

function spa_ajax_get_trainer() {
    
    if (!spa_can_manage_trainers()) {
        wp_send_json_error(array('message' => 'Nedostatočné oprávnenia'));
    }
    
    $trainer_id = intval($_POST['trainer_id']);
    $user = get_user_by('ID', $trainer_id);
    
    if (!$user || !in_array('spa_trainer', (array) $user->roles)) {
        wp_send_json_error(array('message' => 'Tréner neexistuje'));
    }
    
    $photo_id = get_user_meta($trainer_id, 'spa_photo_id', true);
    
    wp_send_json_success(array(
        'id' => $user->ID,
        'firstname' => $user->first_name,
        'lastname' => $user->last_name,
        'email' => $user->user_email,
        'phone' => get_user_meta($trainer_id, 'spa_phone', true),
        'bio' => get_user_meta($trainer_id, 'spa_bio', true),
        'specialization' => get_user_meta($trainer_id, 'spa_specialization', true),
        'photo_url' => $photo_id ? wp_get_attachment_image_url($photo_id, 'thumbnail') : '',
		'instagram' => get_user_meta($trainer_id, 'spa_instagram', true),
		'facebook' => get_user_meta($trainer_id, 'spa_facebook', true),
		'tiktok' => get_user_meta($trainer_id, 'spa_tiktok', true),
		'youtube' => get_user_meta($trainer_id, 'spa_youtube', true)
    ));
}

/* ==========================
   AJAX: Načítanie programov
   ========================== */

add_action('wp_ajax_spa_get_programs_for_trainer', 'spa_ajax_get_programs_for_trainer');

function spa_ajax_get_programs_for_trainer() {
    
    if (!spa_can_manage_trainers()) {
        wp_send_json_error(array('message' => 'Nedostatočné oprávnenia'));
    }
    
    $trainer_id = intval($_POST['trainer_id']);
    $assigned = get_user_meta($trainer_id, 'spa_assigned_programs', true);
    if (!is_array($assigned)) $assigned = array();
    
    $terms = get_terms(array(
        'taxonomy' => 'spa_program',
        'hide_empty' => false
    ));
    
    $html = '';
    if (!empty($terms) && !is_wp_error($terms)) {
        foreach ($terms as $term) {
            $checked = in_array($term->term_id, $assigned) ? 'checked' : '';
            $html .= '<label class="spa-program-checkbox">';
            $html .= '<input type="checkbox" name="programs[]" value="' . esc_attr($term->term_id) . '" ' . $checked . '>';
            $html .= ' ' . esc_html($term->name);
            $html .= '</label>';
        }
    } else {
        $html = '<p>Zatiaľ nie sú vytvorené žiadne programy.</p>';
    }
    
    wp_send_json_success(array('html' => $html));
}

/* ==========================
   AJAX: Uloženie programov trénera
   ========================== */

add_action('wp_ajax_spa_save_trainer_programs', 'spa_ajax_save_trainer_programs');

function spa_ajax_save_trainer_programs() {
    
    if (!spa_can_manage_trainers()) {
        wp_send_json_error(array('message' => 'Nedostatočné oprávnenia'));
    }
    
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'spa_trainer_nonce')) {
        wp_send_json_error(array('message' => 'Bezpečnostná kontrola zlyhala'));
    }
    
    $trainer_id = intval($_POST['trainer_id']);
    $programs = isset($_POST['programs']) ? array_map('intval', $_POST['programs']) : array();
    
    update_user_meta($trainer_id, 'spa_assigned_programs', $programs);
    
    wp_send_json_success(array(
        'message' => 'Programy boli uložené',
        'html' => spa_render_trainers_list()
    ));
}