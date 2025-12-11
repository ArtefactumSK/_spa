<?php
/**
 * SPA Login - Custom prihlasovací systém s AJAX
 * Rodič/Dospelý: Email + Heslo
 * Dieťa: Meno + Priezvisko + PIN
 * 
 * @package Samuel Piasecký ACADEMY
 * @version 1.1.0 - AJAX login
 */

if (!defined('ABSPATH')) {
    exit;
}

/* ==========================
   AJAX HANDLERS
   ========================== */

add_action('wp_ajax_nopriv_spa_ajax_login', 'spa_ajax_login_handler');
add_action('wp_ajax_spa_ajax_login', 'spa_ajax_login_handler');

function spa_ajax_login_handler() {
    
    $login_type = isset($_POST['login_type']) ? sanitize_text_field($_POST['login_type']) : '';
    
    if ($login_type === 'adult') {
        $result = spa_ajax_process_adult_login();
    } elseif ($login_type === 'child') {
        $result = spa_ajax_process_child_login();
    } else {
        wp_send_json_error(array('message' => 'Neplatný typ prihlásenia'));
    }
    
    if (is_wp_error($result)) {
        wp_send_json_error(array('message' => $result->get_error_message()));
    }
    
    wp_send_json_success($result);
}

function spa_ajax_process_adult_login() {
    
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'spa_login_nonce')) {
        return new WP_Error('security', 'Bezpečnostná kontrola zlyhala');
    }
    
    $email = isset($_POST['email']) ? sanitize_email($_POST['email']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    $remember = isset($_POST['remember']) && $_POST['remember'] === 'true';
    
    if (empty($email) || empty($password)) {
        return new WP_Error('empty', 'Vyplň email a heslo');
    }
    
    $user = get_user_by('email', $email);
    
    if (!$user) {
        return new WP_Error('invalid', 'Nesprávny email alebo heslo');
    }
    
    if (in_array('spa_child', (array) $user->roles)) {
        return new WP_Error('wrong_form', 'Pre deti použi prihlásenie cez Meno + Priezvisko + PIN');
    }
    
    $creds = array(
        'user_login' => $user->user_login,
        'user_password' => $password,
        'remember' => $remember
    );
    
    $result = wp_signon($creds, is_ssl());
    
    if (is_wp_error($result)) {
        return new WP_Error('invalid', 'Nesprávny email alebo heslo');
    }
    
    wp_set_current_user($result->ID);
    wp_set_auth_cookie($result->ID, $remember);
    
    return array(
        'redirect' => spa_get_user_dashboard_url($result),
        'message' => 'Prihlásenie úspešné'
    );
}

function spa_ajax_process_child_login() {
    
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'spa_login_nonce')) {
        return new WP_Error('security', 'Bezpečnostná kontrola zlyhala');
    }
    
    $firstname = isset($_POST['firstname']) ? sanitize_text_field($_POST['firstname']) : '';
    $lastname = isset($_POST['lastname']) ? sanitize_text_field($_POST['lastname']) : '';
    $pin = isset($_POST['pin']) ? sanitize_text_field($_POST['pin']) : '';
    
    if (empty($firstname) || empty($lastname) || empty($pin)) {
        return new WP_Error('empty', 'Vyplň meno, priezvisko a PIN');
    }
    
    if (!preg_match('/^[0-9]{4}$/', $pin)) {
        return new WP_Error('invalid_pin', 'PIN musí mať 4 číslice');
    }
    
    $children = get_users(array(
        'role' => 'spa_child',
        'meta_query' => array(
            'relation' => 'AND',
            array('key' => 'first_name', 'value' => $firstname, 'compare' => 'LIKE'),
            array('key' => 'last_name', 'value' => $lastname, 'compare' => 'LIKE')
        )
    ));
    
    if (empty($children)) {
        return new WP_Error('not_found', 'S týmto menom sa nenašiel žiadny člen SPA');
    }
    
    $matched_child = null;
    foreach ($children as $child) {
        $stored_pin = get_user_meta($child->ID, 'spa_pin', true);
        if ($stored_pin && spa_verify_child_pin($pin, $stored_pin)) {
            $matched_child = $child;
            break;
        }
    }
    
    if (!$matched_child) {
        return new WP_Error('invalid_pin', 'Nesprávny PIN');
    }
    
    wp_set_current_user($matched_child->ID);
    wp_set_auth_cookie($matched_child->ID, false);
    
    return array(
        'redirect' => spa_get_user_dashboard_url($matched_child),
        'message' => 'Prihlásenie úspešné'
    );
}

/* ==========================
   SHORTCODE: Prihlasovací formulár
   [spa_login]
   ========================== */

add_shortcode('spa_login', 'spa_login_form_shortcode');

function spa_login_form_shortcode($atts) {
    
    if (is_user_logged_in()) {
        $redirect = spa_get_user_dashboard_url();
        return '<div class="spa-login-message">
            <p>Si už prihlásený/á.</p>
            <a href="' . esc_url($redirect) . '" class="spa-btn spa-btn-primary">Prejsť na dashboard</a>
        </div>';
    }
    
    ob_start();
    ?>
    <div class="spa-login-wrapper">
        
        <!-- AJAX error/success message container -->
        <div class="spa-alert-container" style="display: none;"></div>
        
        <div class="spa-login-tabs">
            <button type="button" class="spa-tab-btn active" data-tab="adult">👨‍👩‍👧 Rodič / Klient</button>
            <button type="button" class="spa-tab-btn" data-tab="child">👶 Dieťa</button>
        </div>
        
        <!-- FORMULÁR: Rodič / Dospelý -->
        <div class="spa-login-form spa-tab-content active" id="spa-tab-adult">
            <form id="spa-login-form-adult" class="spa-ajax-login-form" data-type="adult">
                <input type="hidden" name="login_type" value="adult">
                
                <div class="spa-form-group">
                    <label for="spa_email">Email</label>
                    <input type="email" name="email" id="spa_email" required placeholder="vas@email.sk" autocomplete="email">
                </div>
                
                <div class="spa-form-group">
                    <label for="spa_password">Heslo</label>
                    <input type="password" name="password" id="spa_password" required placeholder="••••••••" autocomplete="current-password">
                </div>
                
                <div class="spa-form-group spa-form-options">
                    <label class="spa-checkbox">
                        <input type="checkbox" name="remember" value="1"> Zapamätať si ma
                    </label>
                    <a href="<?php echo esc_url(home_url('/lost-password/')); ?>" class="spa-forgot-link">Zabudnuté heslo?</a>
                </div>
                
                <button type="submit" class="spa-btn spa-btn-primary">
                    <span class="spa-btn-text">Prihlásiť sa</span>
                    <span class="spa-btn-loading" style="display:none;">Prihlasujem...</span>
                </button>
            </form>
        </div>
        
        <!-- FORMULÁR: Dieťa -->
        <div class="spa-login-form spa-tab-content" id="spa-tab-child">
            <form id="spa-login-form-child" class="spa-ajax-login-form" data-type="child">
                <input type="hidden" name="login_type" value="child">
                
                <div class="spa-form-row-2">
                    <div class="spa-form-group">
                        <label for="spa_child_firstname">Meno</label>
                        <input type="text" name="firstname" id="spa_child_firstname" required placeholder="Tamara" autocomplete="given-name">
                    </div>
                    
                    <div class="spa-form-group">
                        <label for="spa_child_lastname">Priezvisko</label>
                        <input type="text" name="lastname" id="spa_child_lastname" required placeholder="Obratná" autocomplete="family-name">
                    </div>
                </div>
                
                <div class="spa-form-group">
                    <label for="spa_child_pin">PIN</label>
                    <input type="text" name="pin" id="spa_child_pin" required placeholder="1234" pattern="[0-9]{4}" maxlength="4" inputmode="numeric" autocomplete="off" class="spa-pin-input">
                </div>
                
                <button type="submit" class="spa-btn spa-btn-primary">
                    <span class="spa-btn-text">Prihlásiť sa</span>
                    <span class="spa-btn-loading" style="display:none;">Prihlasujem...</span>
                </button>
                
                <p class="spa-help-text"><strong>Ak nevieš svoj PIN, spýtaj sa rodiča! 😊</strong></p>
            </form>
        </div>
        
    </div>
    
    <?php 
    spa_login_enqueue_styles();
    spa_login_enqueue_scripts_ajax();
    ?>
    <?php
    return ob_get_clean();
}

/* ==========================
   HELPER: Verifikácia PIN
   ========================== */

function spa_verify_child_pin($pin, $hashed_pin) {
    return wp_check_password($pin, $hashed_pin);
}

/* ==========================
   HELPER: Dashboard URL podľa roly
   ========================== */

function spa_get_user_dashboard_url($user = null) {
    
    if (!$user) {
        $user = wp_get_current_user();
    }
    
    if (!$user || !$user->ID) {
        return home_url('/');
    }
    
    $roles = (array) $user->roles;
    
    if (in_array('administrator', $roles)) {
        return admin_url();
    }
    
    if (in_array('spa_child', $roles)) {
        return home_url('/moj-profil/');
    }
    
    if (in_array('spa_trainer', $roles)) {
        return home_url('/trener/');
    }
    
    if (in_array('spa_parent', $roles) || in_array('spa_client', $roles)) {
        return home_url('/dashboard/');
    }
    
    return home_url('/dashboard/');
}

/* ==========================
   STYLES
   ========================== */

function spa_login_enqueue_styles() {
    ?>
    <style>
    .spa-login-wrapper {
        max-width: 420px;
        margin: 40px auto;
    }
    
    .spa-alert-container {
        margin-bottom: 20px;
    }
    
    .spa-login-tabs {
        display: flex;
        border-bottom: 2px solid #e0e0e0;
        margin-bottom: 30px;
    }
    
    .spa-tab-btn {
        flex: 1;
        padding: 16px 20px;
        border: none;
        background: none;
        font-size: 16px;
        font-weight: 600;
        color: #888;
        cursor: pointer;
        transition: all 0.3s;
        border-bottom: 3px solid transparent;
        margin-bottom: -2px;
    }
    
    .spa-tab-btn:hover {
        color: var(--theme-palette-color-3, #FF1439);
    }
    
    .spa-tab-btn.active {
        color: var(--theme-palette-color-8, #ffffff);
        border-bottom-color: var(--theme-palette-color-3, #FF1439);
    }
    
    .spa-tab-content {
        display: none;
    }
    
    .spa-tab-content.active {
        display: block;
        animation: spaFadeIn 0.3s ease;
    }
    
    @keyframes spaFadeIn {
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: translateY(0); }
    }
    
    .spa-login-form {
        background: #fff;
        padding: 30px;
        border-radius: 12px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.08);
    }
    
    .spa-form-group {
        margin-bottom: 20px;
    }
    
    .spa-form-group label {
        display: block;
        font-weight: 600;
        margin-bottom: 8px;
        color: #333;
    }
    
    .spa-form-group input[type="text"],
    .spa-form-group input[type="email"],
    .spa-form-group input[type="password"] {
        width: 100%;
        padding: 14px 16px;
        border: 2px solid #e0e0e0;
        border-radius: 8px;
        font-size: 16px;
        transition: border-color 0.3s;
        box-sizing: border-box;
    }
    
    .spa-form-group input:focus {
        outline: none;
        border-color: var(--theme-palette-color-1, #0072CE);
    }
    
    .spa-form-group input.spa-input-error {
        border-color: #c62828;
        background: #ffebee;
    }
    
    .spa-form-row-2 {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 15px;
    }
    
    .spa-form-options {
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    .spa-checkbox {
        display: flex;
        align-items: center;
        gap: 8px;
        cursor: pointer;
    }
    
    .spa-forgot-link {
        color: var(--theme-palette-color-1, #0072CE);
        text-decoration: none;
        font-size: 14px;
    }
    
    .spa-forgot-link:hover {
        text-decoration: underline;
    }
    
    .spa-btn {
        display: inline-block;
        padding: 14px 28px;
        border: none;
        border-radius: 8px;
        font-size: 16px;
        font-weight: 600;
        cursor: pointer;
        text-decoration: none;
        transition: all 0.3s;
    }
    
    .spa-btn-primary {
        width: 100%;
        background: linear-gradient(135deg, var(--theme-palette-color-1, #0072CE) 0%, #005BAA 100%);
        color: #fff;
    }
    
    .spa-btn-primary:hover:not(:disabled) {
        background: linear-gradient(135deg, var(--theme-palette-color-3, #E4002B) 0%, #C40025 100%);
        transform: translateY(-2px);
        box-shadow: 0 4px 15px rgba(228,0,43,0.3);
    }
    
    .spa-btn-primary:disabled {
        opacity: 0.7;
        cursor: not-allowed;
        transform: none;
    }
    
    .spa-pin-input {
        text-align: center;
        font-size: 28px !important;
        font-weight: 700;
        letter-spacing: 12px;
        font-family: monospace;
    }
    
    .spa-help-text {
        text-align: center;
        color: #888;
        font-size: 14px;
        margin-top: 20px;
    }
    
    .spa-alert {
        padding: 16px 20px;
        border-radius: 8px;
        margin-bottom: 20px;
        font-weight: 500;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    
    .spa-alert-error {
        background: #ffebee;
        color: #c62828;
        border-left: 4px solid #c62828;
    }
    
    .spa-alert-success {
        background: #e8f5e9;
        color: #2e7d32;
        border-left: 4px solid #2e7d32;
    }
    
    .spa-login-message {
        text-align: center;
        padding: 40px;
    }
    
    /* Shake animation for errors */
    @keyframes spaShake {
        0%, 100% { transform: translateX(0); }
        20%, 60% { transform: translateX(-5px); }
        40%, 80% { transform: translateX(5px); }
    }
    
    .spa-shake {
        animation: spaShake 0.4s ease;
    }
    
    @media (max-width: 480px) {
        .spa-login-wrapper {
            margin: 20px;
        }
        
        .spa-login-form {
            padding: 20px;
        }
        
        .spa-form-row-2 {
            grid-template-columns: 1fr;
        }
        
        .spa-tab-btn {
            font-size: 14px;
            padding: 12px 10px;
        }
    }
    </style>
    <?php
}

/* ==========================
   AJAX SCRIPTS
   ========================== */

function spa_login_enqueue_scripts_ajax() {
    $nonce = wp_create_nonce('spa_login_nonce');
    ?>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        
        // Tab switching
        var tabs = document.querySelectorAll('.spa-tab-btn');
        var contents = document.querySelectorAll('.spa-tab-content');
        
        tabs.forEach(function(tab) {
            tab.addEventListener('click', function() {
                var targetId = 'spa-tab-' + this.dataset.tab;
                
                tabs.forEach(function(t) { t.classList.remove('active'); });
                contents.forEach(function(c) { c.classList.remove('active'); });
                
                this.classList.add('active');
                var target = document.getElementById(targetId);
                if (target) {
                    target.classList.add('active');
                }
                
                // Clear errors on tab switch
                hideAlert();
            });
        });
        
        // PIN input filter
        var pinInput = document.getElementById('spa_child_pin');
        if (pinInput) {
            pinInput.addEventListener('input', function() {
                this.value = this.value.replace(/[^0-9]/g, '').slice(0, 4);
            });
        }
        
        // AJAX Login Forms
        var forms = document.querySelectorAll('.spa-ajax-login-form');
        
        forms.forEach(function(form) {
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                
                var formType = this.dataset.type;
                var btn = this.querySelector('.spa-btn-primary');
                var btnText = btn.querySelector('.spa-btn-text');
                var btnLoading = btn.querySelector('.spa-btn-loading');
                
                // Clear previous errors
                hideAlert();
                clearInputErrors(this);
                
                // Show loading
                btn.disabled = true;
                btnText.style.display = 'none';
                btnLoading.style.display = 'inline';
                
                // Collect form data
                var data = new FormData();
                data.append('action', 'spa_ajax_login');
                data.append('nonce', '<?php echo $nonce; ?>');
                data.append('login_type', formType);
                
                if (formType === 'adult') {
                    data.append('email', this.querySelector('[name="email"]').value);
                    data.append('password', this.querySelector('[name="password"]').value);
                    data.append('remember', this.querySelector('[name="remember"]').checked ? 'true' : 'false');
                } else {
                    data.append('firstname', this.querySelector('[name="firstname"]').value);
                    data.append('lastname', this.querySelector('[name="lastname"]').value);
                    data.append('pin', this.querySelector('[name="pin"]').value);
                }
                
                // AJAX request
                fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
                    method: 'POST',
                    body: data,
                    credentials: 'same-origin'
                })
                .then(function(response) {
                    return response.json();
                })
                .then(function(result) {
                    btn.disabled = false;
                    btnText.style.display = 'inline';
                    btnLoading.style.display = 'none';
                    
                    if (result.success) {
                        showAlert('success', '✅ ' + result.data.message);
                        setTimeout(function() {
                            window.location.href = result.data.redirect;
                        }, 500);
                    } else {
                        showAlert('error', '❌ ' + result.data.message);
                        shakeForm(form);
                    }
                })
                .catch(function(error) {
                    btn.disabled = false;
                    btnText.style.display = 'inline';
                    btnLoading.style.display = 'none';
                    showAlert('error', '❌ Vyplnené údaje nie sú správne. Skús to znova!');
                    console.error('Login error:', error);
                });
            });
        });
        
        function showAlert(type, message) {
            var container = document.querySelector('.spa-alert-container');
            container.innerHTML = '<div class="spa-alert spa-alert-' + type + '">' + message + '</div>';
            container.style.display = 'block';
            container.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }
        
        function hideAlert() {
            var container = document.querySelector('.spa-alert-container');
            container.style.display = 'none';
            container.innerHTML = '';
        }
        
        function clearInputErrors(form) {
            form.querySelectorAll('.spa-input-error').forEach(function(input) {
                input.classList.remove('spa-input-error');
            });
        }
        
        function shakeForm(form) {
            form.classList.add('spa-shake');
            setTimeout(function() {
                form.classList.remove('spa-shake');
            }, 400);
        }
    });
    </script>
    <?php
}

/* ==========================
   REDIRECT: Ochrana dashboard stránok
   ========================== */

add_action('template_redirect', 'spa_protect_dashboard_pages');

function spa_protect_dashboard_pages() {
    
    if (is_admin()) {
        return;
    }
    
    $protected_slugs = array('dashboard', 'moj-profil', 'moje-registracie', 'trener');
    
    if (!is_user_logged_in() && is_page($protected_slugs)) {
        wp_redirect(home_url('/login/'));
        exit;
    }
}

/* ==========================
   SHORTCODE: Zabudnuté heslo
   [spa_lost_password]
   ========================== */

add_shortcode('spa_lost_password', 'spa_lost_password_shortcode');

function spa_lost_password_shortcode($atts) {
    
    if (is_user_logged_in()) {
        return '<div class="spa-login-message">
            <p>Si už prihlásený/á.</p>
            <a href="' . esc_url(home_url('/dashboard/')) . '" class="spa-btn spa-btn-primary">Prejsť na dashboard</a>
        </div>';
    }
    
    $message = '';
    $error = '';
    
    if (isset($_POST['spa_lost_password_submit'])) {
        $result = spa_process_lost_password();
        if (is_wp_error($result)) {
            $error = $result->get_error_message();
        } else {
            $message = $result;
        }
    }
    
    ob_start();
    ?>
    <div class="spa-login-wrapper">
        
        <div class="spa-login-form">
            
            <h2 style="text-align: center; margin-bottom: 10px;">🔑 Zabudnuté heslo</h2>
            <p style="text-align: center; color: #666; margin-bottom: 25px;">Pre obnovenie hesla, zadaj svoj e-mail.</p>
            
            <?php if ($error) : ?>
                <div class="spa-alert spa-alert-error">❌ <?php echo esc_html($error); ?></div>
            <?php endif; ?>
            
            <?php if ($message) : ?>
                <div class="spa-alert spa-alert-success">✅ <?php echo esc_html($message); ?></div>
            <?php else : ?>
            
            <form method="post" action="">
                <?php wp_nonce_field('spa_lost_password_nonce', 'spa_lost_password_nonce_field'); ?>
                
                <div class="spa-form-group">
                    <label for="spa_lost_email">E-mail</label>
                    <input type="email" name="spa_lost_email" id="spa_lost_email" required placeholder="registracny@email.sk" autocomplete="email">
                </div>
                
                <button type="submit" name="spa_lost_password_submit" class="spa-btn spa-btn-primary">Odoslať odkaz</button>
            </form>
            
            <?php endif; ?>
            
            <p style="text-align: center; margin-top: 20px;">
                <a href="<?php echo esc_url(home_url('/login/')); ?>">← Späť na prihlásenie</a>
            </p>
            
        </div>
        
    </div>
    
    <?php 
    spa_login_enqueue_styles();
    ?>
    <?php
    return ob_get_clean();
}

function spa_process_lost_password() {
    
    if (!isset($_POST['spa_lost_password_nonce_field']) || 
        !wp_verify_nonce($_POST['spa_lost_password_nonce_field'], 'spa_lost_password_nonce')) {
        return new WP_Error('security', 'Bezpečnostná kontrola zlyhala');
    }
    
    $email = isset($_POST['spa_lost_email']) ? sanitize_email($_POST['spa_lost_email']) : '';
    
    if (empty($email)) {
        return new WP_Error('empty', 'Zadaj emailovú adresu');
    }
    
    $user = get_user_by('email', $email);
    
    if (!$user) {
        return 'Ak tento email existuje v našom systéme, poslali sme ti odkaz na obnovenie hesla.';
    }
    
    if (in_array('spa_child', (array) $user->roles)) {
        return new WP_Error('child', 'Pre deti nie je možné obnoviť heslo. Kontaktuj svojho rodiča.');
    }
    
    $reset_key = get_password_reset_key($user);
    
    if (is_wp_error($reset_key)) {
        return new WP_Error('error', 'Nastala chyba. Skús to znova neskôr.');
    }
    
    $reset_url = add_query_arg(array(
        'action' => 'rp',
        'key' => $reset_key,
        'login' => rawurlencode($user->user_login)
    ), home_url('/nove-heslo/'));
    
    $subject = 'Obnovenie hesla - Samuel Piasecký ACADEMY';
    
    $message = "Dobrý deň,\n\n";
    $message .= "Niekto požiadal o obnovenie hesla pre váš účet na Samuel Piasecký ACADEMY.\n\n";
    $message .= "Ak ste to boli vy, kliknite na nasledujúci odkaz:\n";
    $message .= $reset_url . "\n\n";
    $message .= "Odkaz je platný 24 hodín.\n\n";
    $message .= "Ak ste o obnovenie hesla nežiadali, tento email ignorujte.\n\n";
    $message .= "Samuel Piasecký ACADEMY\n";
    $message .= home_url();
    
    $sent = wp_mail($email, $subject, $message);
    
    if (!$sent) {
        return new WP_Error('email', 'Nepodarilo sa odoslať email. Skús to znova.');
    }
    
    return 'Ak tento email existuje v našom systéme, poslali sme ti odkaz na obnovenie hesla.';
}

/* ==========================
   SHORTCODE: Nové heslo (reset)
   [spa_reset_password]
   ========================== */

add_shortcode('spa_reset_password', 'spa_reset_password_shortcode');

function spa_reset_password_shortcode($atts) {
    
    if (is_user_logged_in()) {
        return '<div class="spa-login-message">
            <p>Si už prihlásený/á.</p>
            <a href="' . esc_url(home_url('/dashboard/')) . '" class="spa-btn spa-btn-primary">Prejsť na dashboard</a>
        </div>';
    }
    
    $key = isset($_GET['key']) ? sanitize_text_field($_GET['key']) : '';
    $login = isset($_GET['login']) ? sanitize_text_field($_GET['login']) : '';
    
    $message = '';
    $error = '';
    $show_form = true;
    
    if (empty($key) || empty($login)) {
        $error = 'Neplatný odkaz na obnovenie hesla.';
        $show_form = false;
    } else {
        $user = check_password_reset_key($key, $login);
        if (is_wp_error($user)) {
            $error = 'Odkaz na obnovenie hesla vypršal alebo je neplatný.';
            $show_form = false;
        }
    }
    
    if ($show_form && isset($_POST['spa_reset_password_submit'])) {
        $result = spa_process_reset_password($key, $login);
        if (is_wp_error($result)) {
            $error = $result->get_error_message();
        } else {
            $message = $result;
            $show_form = false;
        }
    }
    
    ob_start();
    ?>
    <div class="spa-login-wrapper">
        <div class="spa-login-form">
            <h2 style="text-align: center; margin-bottom: 10px;">🔐 Nové heslo</h2>
            
            <?php if ($error) : ?>
                <div class="spa-alert spa-alert-error">❌ <?php echo esc_html($error); ?></div>
            <?php endif; ?>
            
            <?php if ($message) : ?>
                <div class="spa-alert spa-alert-success">✅ <?php echo esc_html($message); ?></div>
                <p style="text-align: center; margin-top: 20px;">
                    <a href="<?php echo esc_url(home_url('/login/')); ?>" class="spa-btn spa-btn-primary">Prihlásiť sa</a>
                </p>
            <?php elseif ($show_form) : ?>
                <p style="text-align: center; color: #666; margin-bottom: 25px;">Zadaj svoje nové heslo.</p>
                <form method="post" action="">
                    <?php wp_nonce_field('spa_reset_password_nonce', 'spa_reset_password_nonce_field'); ?>
                    <div class="spa-form-group">
                        <label for="spa_new_password">Nové heslo</label>
                        <input type="password" name="spa_new_password" id="spa_new_password" required placeholder="••••••••" minlength="8" autocomplete="new-password">
                        <small style="color: #888;">Minimálne 8 znakov</small>
                    </div>
                    <div class="spa-form-group">
                        <label for="spa_new_password_confirm">Potvrď heslo</label>
                        <input type="password" name="spa_new_password_confirm" id="spa_new_password_confirm" required placeholder="••••••••" minlength="8" autocomplete="new-password">
                    </div>
                    <button type="submit" name="spa_reset_password_submit" class="spa-btn spa-btn-primary">Uložiť nové heslo</button>
                </form>
            <?php else : ?>
                <p style="text-align: center; margin-top: 20px;">
                    <a href="<?php echo esc_url(home_url('/lost-password/')); ?>">Požiadať o nový odkaz</a>
                </p>
            <?php endif; ?>
        </div>
    </div>
    <?php 
    spa_login_enqueue_styles();
    return ob_get_clean();
}

function spa_process_reset_password($key, $login) {
    if (!isset($_POST['spa_reset_password_nonce_field']) || 
        !wp_verify_nonce($_POST['spa_reset_password_nonce_field'], 'spa_reset_password_nonce')) {
        return new WP_Error('security', 'Bezpečnostná kontrola zlyhala');
    }
    
    $password = isset($_POST['spa_new_password']) ? $_POST['spa_new_password'] : '';
    $password_confirm = isset($_POST['spa_new_password_confirm']) ? $_POST['spa_new_password_confirm'] : '';
    
    if (empty($password) || empty($password_confirm)) {
        return new WP_Error('empty', 'Vyplň obe polia');
    }
    if (strlen($password) < 8) {
        return new WP_Error('short', 'Heslo musí mať minimálne 8 znakov');
    }
    if ($password !== $password_confirm) {
        return new WP_Error('mismatch', 'Heslá sa nezhodujú');
    }
    
    $user = check_password_reset_key($key, $login);
    if (is_wp_error($user)) {
        return new WP_Error('expired', 'Odkaz na obnovenie hesla vypršal');
    }
    
    reset_password($user, $password);
    return 'Heslo bolo úspešne zmenené! Teraz sa môžeš prihlásiť.';
}

/* ==========================
   FILTER: Presmerovanie WP lost password na custom
   ========================== */

add_filter('lostpassword_url', 'spa_custom_lostpassword_url', 10, 2);
function spa_custom_lostpassword_url($lostpassword_url, $redirect) {
    return home_url('/lost-password/');
}