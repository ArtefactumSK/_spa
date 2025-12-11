<?php
/**
 * SPA Registration - Registračný systém
 * 
 * @package Samuel Piasecký ACADEMY
 * @version 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/* ==========================
   ENQUEUE: jQuery pre GF formuláre
   ========================== */

add_action('wp_enqueue_scripts', 'spa_enqueue_jquery_for_gf', 5);
function spa_enqueue_jquery_for_gf() {
    if (is_page('registracia')) {
        wp_enqueue_script('jquery');
    }
}

/* ==========================
   GRAVITY FORMS: Hook na spracovanie registrácie
   ========================== */

add_action('gform_after_submission', 'spa_process_registration_form', 10, 2);

function spa_process_registration_form($entry, $form) {
    
    // Zisti ID formulára (nastav podľa tvojho GF ID)
    // Pre detské registrácie: form_id = 1
    // Pre dospelých: form_id = 2
    
    if ($form['id'] == 1) {
        spa_process_child_registration($entry, $form);
    } elseif ($form['id'] == 2) {
        spa_process_adult_registration($entry, $form);
    }
}

/* ==========================
   FUNKCIA: Registrácia dieťaťa
   ========================== */

function spa_process_child_registration($entry, $form) {
    
    // Mapovanie polí z Gravity Forms
    // UPRAV Field ID podľa tvojho formulára!
    
    // === DIEŤA ===
    $child_first_name = rgar($entry, '1.3');  // Meno
    $child_last_name = rgar($entry, '1.6');   // Priezvisko
    $child_birthdate = rgar($entry, '3');     // Dátum narodenia
    $child_rodne_cislo = rgar($entry, '12');  // NOVÉ: Rodné číslo (nastav správne Field ID!)
    
    // === PROGRAM ===
    $selected_place = rgar($entry, '5');      // Miesto
    $program_id = rgar($entry, '4');          // Program
    
    // === RODIČ ===
    $parent_first_name = rgar($entry, '6.3'); // Meno rodiča
    $parent_last_name = rgar($entry, '6.6');  // Priezvisko rodiča
    $parent_email = rgar($entry, '8');        // Email
    $parent_phone = rgar($entry, '9');        // Telefón
    
    // === ADRESA (NOVÉ) ===
    $address_street = rgar($entry, '13.1');   // Ulica (nastav správne Field ID!)
    $address_psc = rgar($entry, '13.5');      // PSČ
    $address_city = rgar($entry, '13.3');     // Mesto
    
    // === OSTATNÉ ===
    $health_notes = rgar($entry, '10');
    $gdpr_consent = rgar($entry, '11');
    
    // Validácia
    if (empty($child_first_name) || empty($parent_email) || empty($program_id)) {
        spa_log('Registration failed: missing required fields', $entry);
        return;
    }
    
    // NOVÉ: Validácia rodného čísla
    if (empty($child_rodne_cislo)) {
        spa_log('Registration failed: missing rodne_cislo', $entry);
        // Môžeš tu pridať notifikáciu adminovi
    }
    
    // 1. VYTVOR/NAJDI RODIČA
    $parent_user_id = spa_get_or_create_parent(
        $parent_email, 
        $parent_first_name, 
        $parent_last_name, 
        $parent_phone,
        $address_street,  // NOVÉ
        $address_psc,     // NOVÉ
        $address_city     // NOVÉ
    );
    
    if (!$parent_user_id) {
        spa_log('Failed to create parent account', ['email' => $parent_email]);
        return;
    }
    
    // 2. VYTVOR DIEŤA
    $child_user_id = spa_create_child_account(
        $child_first_name, 
        $child_last_name, 
        $child_birthdate, 
        $parent_user_id,
        $health_notes,
        $child_rodne_cislo  // NOVÉ
    );
    
    if (!$child_user_id) {
        spa_log('Failed to create child account', ['name' => $child_first_name]);
        return;
    }
    
    // 3. VYTVOR REGISTRÁCIU
    $registration_id = spa_create_registration(
        $child_user_id,
        $program_id,
        $parent_user_id,
        $entry['id']
    );
    
    if (!$registration_id) {
        spa_log('Failed to create registration', ['child' => $child_user_id, 'program' => $program_id]);
        return;
    }
    
    // 4. NOTIFIKÁCIE
    spa_notify_admin_new_registration($registration_id, $parent_email);
    spa_send_registration_confirmation($parent_email, $child_first_name, $program_id);
    
    // 5. LOG
    $vs = get_user_meta($child_user_id, 'variabilny_symbol', true);
    
    spa_log('Registration created successfully', [
        'registration_id' => $registration_id,
        'parent' => $parent_user_id,
        'child' => $child_user_id,
        'variabilny_symbol' => $vs
    ]);
}

/* ==========================
   FUNKCIA: Registrácia dospelého
   ========================== */

function spa_process_adult_registration($entry, $form) {
    
    $first_name = rgar($entry, '1.3');
    $last_name = rgar($entry, '1.6');
    $email = rgar($entry, '3');
    $phone = rgar($entry, '4');
    $birthdate = rgar($entry, '5');
    $program_id = rgar($entry, '6');
    $health_notes = rgar($entry, '7');
    
    // Validácia
    if (empty($first_name) || empty($email) || empty($program_id)) {
        return;
    }
    
    // 1. VYTVOR KLIENTA
    $client_user_id = spa_get_or_create_client($email, $first_name, $last_name, $phone, $birthdate);
    
    if (!$client_user_id) {
        return;
    }
    
    // 2. VYTVOR REGISTRÁCIU
    $registration_id = spa_create_registration(
        $client_user_id,
        $program_id,
        null, // žiadny parent
        $entry['id']
    );
    
    if (!$registration_id) {
        return;
    }
    
    // Pridaj health notes
    if ($health_notes) {
        update_user_meta($client_user_id, 'health_notes', sanitize_textarea_field($health_notes));
    }
    
    // 3. NOTIFIKÁCIE
    spa_notify_admin_new_registration($registration_id, $email);
    spa_send_registration_confirmation($email, $first_name, $program_id);
}

/* ==========================
   HELPER: Získaj label pre status
   ========================== */

function spa_get_status_label($status) {
    $labels = [
        'pending' => 'Čaká na schválenie',
        'approved' => 'Schválené',
        'active' => 'Aktívne',
        'cancelled' => 'Zrušené',
        'completed' => 'Zaregistrované'
    ];
    
    return isset($labels[$status]) ? $labels[$status] : ucfirst($status);
}

/* ==========================
   HELPER: Vytvor/Nájdi rodiča
   ========================== */

function spa_get_or_create_parent($email, $first_name, $last_name, $phone, $address_street = '', $address_psc = '', $address_city = '') {
    
    $user = get_user_by('email', $email);
    
    if ($user) {
        error_log('SPA: Found existing parent - ' . $email);
        
        // Aktualizuj údaje
        update_user_meta($user->ID, 'phone', sanitize_text_field($phone));
        
        if (!empty($first_name)) {
            update_user_meta($user->ID, 'first_name', sanitize_text_field($first_name));
        }
        if (!empty($last_name)) {
            update_user_meta($user->ID, 'last_name', sanitize_text_field($last_name));
        }
        
        // NOVÉ: Aktualizuj adresu ak je zadaná
        if (!empty($address_street)) {
            update_user_meta($user->ID, 'address_street', sanitize_text_field($address_street));
        }
        if (!empty($address_psc)) {
            update_user_meta($user->ID, 'address_psc', sanitize_text_field($address_psc));
        }
        if (!empty($address_city)) {
            update_user_meta($user->ID, 'address_city', sanitize_text_field($address_city));
        }
        
        return $user->ID;
    }
    
    // Vytvor nového rodiča - odstráň diakritiku z username
    $chars = [
        'á'=>'a', 'ä'=>'a', 'č'=>'c', 'ď'=>'d', 'é'=>'e', 'ě'=>'e',
        'í'=>'i', 'ľ'=>'l', 'ĺ'=>'l', 'ň'=>'n', 'ó'=>'o', 'ô'=>'o',
        'ŕ'=>'r', 'ř'=>'r', 'š'=>'s', 'ť'=>'t', 'ú'=>'u', 'ů'=>'u',
        'ý'=>'y', 'ž'=>'z',
        'Á'=>'A', 'Ä'=>'A', 'Č'=>'C', 'Ď'=>'D', 'É'=>'E', 'Ě'=>'E',
        'Í'=>'I', 'Ľ'=>'L', 'Ĺ'=>'L', 'Ň'=>'N', 'Ó'=>'O', 'Ô'=>'O',
        'Ŕ'=>'R', 'Ř'=>'R', 'Š'=>'S', 'Ť'=>'T', 'Ú'=>'U', 'Ů'=>'U',
        'Ý'=>'Y', 'Ž'=>'Z'
    ];

    $first_clean = strtr($first_name, $chars);
    $last_clean = strtr($last_name, $chars);
    $first_clean = strtolower(preg_replace('/[^a-z0-9]/i', '', $first_clean));
    $last_clean = strtolower(preg_replace('/[^a-z0-9]/i', '', $last_clean));

    $base_username = $first_clean . '.' . $last_clean;
    if (strlen($base_username) > 50) {
        $base_username = substr($base_username, 0, 50);
    }

    $username = $base_username;
    $counter = 1;
    while (username_exists($username)) {
        $username = $base_username . $counter;
        $counter++;
    }

    $password = wp_generate_password(12, true);
    
    $user_id = wp_create_user($username, $password, $email);
    
    if (is_wp_error($user_id)) {
        error_log('SPA ERROR: Failed to create parent - ' . $user_id->get_error_message());
        return false;
    }
    
    $user = new WP_User($user_id);
    $user->set_role('spa_parent');
    
    // Meta data
    update_user_meta($user_id, 'first_name', sanitize_text_field($first_name));
    update_user_meta($user_id, 'last_name', sanitize_text_field($last_name));
    update_user_meta($user_id, 'phone', sanitize_text_field($phone));
    
    // NOVÉ: Adresa
    if (!empty($address_street)) {
        update_user_meta($user_id, 'address_street', sanitize_text_field($address_street));
    }
    if (!empty($address_psc)) {
        update_user_meta($user_id, 'address_psc', sanitize_text_field($address_psc));
    }
    if (!empty($address_city)) {
        update_user_meta($user_id, 'address_city', sanitize_text_field($address_city));
    }
    
    // Email s prihlasovacími údajmi
    spa_send_welcome_email($email, $username, $password, $first_name);
    
    error_log('SPA: Created new parent - ' . $email);
    
    return $user_id;
}

/* ==========================
   HELPER: Vytvor dieťa
   ========================== */

function spa_create_child_account($first_name, $last_name, $birthdate, $parent_id, $health_notes = '', $rodne_cislo = '') {
    
    // Virtuálny účet bez prihlásenia
    $username = 'child_' . $parent_id . '_' . uniqid();
    $email = $username . '@piaseckyacademy.local';
    $password = wp_generate_password(32);
    
    $user_id = wp_create_user($username, $password, $email);
    
    if (is_wp_error($user_id)) {
        error_log('SPA ERROR: Failed to create child - ' . $user_id->get_error_message());
        return false;
    }
    
    $user = new WP_User($user_id);
    $user->set_role('spa_child');
    
    // Základné meta data
    update_user_meta($user_id, 'first_name', sanitize_text_field($first_name));
    update_user_meta($user_id, 'last_name', sanitize_text_field($last_name));
    update_user_meta($user_id, 'birthdate', sanitize_text_field($birthdate));
    update_user_meta($user_id, 'parent_id', intval($parent_id));
    
    // Display name
    wp_update_user([
        'ID' => $user_id,
        'display_name' => $first_name . ' ' . $last_name
    ]);
    
    if ($health_notes) {
        update_user_meta($user_id, 'health_notes', sanitize_textarea_field($health_notes));
    }
    
    // NOVÉ: Rodné číslo (ulož bez lomky)
    if ($rodne_cislo) {
        $rc_clean = preg_replace('/[^0-9]/', '', $rodne_cislo);
        update_user_meta($user_id, 'rodne_cislo', $rc_clean);
    }
    
    // NOVÉ: Automatické pridelenie variabilného symbolu
    do_action('spa_after_child_created', $user_id);
    
    error_log('SPA: Created child - ' . $first_name . ' ' . $last_name . ' (ID: ' . $user_id . ')');
    
    return $user_id;
}

/* ==========================
   HELPER: Vytvor/Nájdi dospelého klienta
   ========================== */

function spa_get_or_create_client($email, $first_name, $last_name, $phone, $birthdate) {
    
    $user = get_user_by('email', $email);
    
    if ($user) {
        update_user_meta($user->ID, 'phone', sanitize_text_field($phone));
        update_user_meta($user->ID, 'birthdate', sanitize_text_field($birthdate));
        return $user->ID;
    }
    
    $username = sanitize_user(strtolower($first_name . '.' . $last_name));
    $password = wp_generate_password(12, true);
    
    $base_username = $username;
    $counter = 1;
    while (username_exists($username)) {
        $username = $base_username . $counter;
        $counter++;
    }
    
    $user_id = wp_create_user($username, $password, $email);
    
    if (is_wp_error($user_id)) {
        return false;
    }
    
    $user = new WP_User($user_id);
    $user->set_role('spa_client');
    
    update_user_meta($user_id, 'first_name', sanitize_text_field($first_name));
    update_user_meta($user_id, 'last_name', sanitize_text_field($last_name));
    update_user_meta($user_id, 'phone', sanitize_text_field($phone));
    update_user_meta($user_id, 'birthdate', sanitize_text_field($birthdate));
    
    spa_send_welcome_email($email, $username, $password, $first_name);
    
    return $user_id;
}

/* ==========================
   HELPER: Vytvor registráciu (CPT)
   ========================== */

function spa_create_registration($client_user_id, $program_id, $parent_id = null, $gf_entry_id = null) {
    
    $program = get_post($program_id);
    $user = get_userdata($client_user_id);
    
    if (!$program || !$user) {
        return false;
    }
    
    $title = $user->first_name . ' ' . $user->last_name . ' - ' . $program->post_title;
    
    $registration_id = wp_insert_post([
        'post_type' => 'spa_registration',
        'post_title' => $title,
        'post_status' => 'pending', // Čaká na schválenie
        'post_author' => 1
    ]);
    
    if (!$registration_id || is_wp_error($registration_id)) {
        return false;
    }
    
    // Meta data
    update_post_meta($registration_id, 'client_user_id', intval($client_user_id));
    update_post_meta($registration_id, 'program_id', intval($program_id));
    update_post_meta($registration_id, 'registration_date', current_time('Y-m-d H:i:s'));
    update_post_meta($registration_id, 'status', 'pending');
    
    if ($parent_id) {
        update_post_meta($registration_id, 'parent_user_id', intval($parent_id));
    }
    
    if ($gf_entry_id) {
        update_post_meta($registration_id, 'gf_entry_id', intval($gf_entry_id));
    }
    
    // Získaj cenu programu
    $price = get_post_meta($program_id, 'spa_price', true);
    if ($price) {
        update_post_meta($registration_id, 'registration_price', floatval($price));
    }
    
    return $registration_id;
}

/* ==========================
   EMAIL: Potvrdenie registrácie
   ========================== */

function spa_send_registration_confirmation($to_email, $client_name, $program_id) {
    
    $program = get_post($program_id);
    
    $subject = 'Potvrdenie registrácie - Samuel Piasecký ACADEMY';
    
    $message = "Dobrý deň,\n\n";
    $message .= "Vaša registrácia pre {$client_name} do programu \"{$program->post_title}\" bola úspešne prijatá.\n\n";
    $message .= "Registrácia čaká na schválenie administrátorom. O výsledku Vás budeme informovať emailom.\n\n";
    $message .= "Ďakujeme,\nSamuel Piasecký ACADEMY\n";
    $message .= home_url();
    
    wp_mail($to_email, $subject, $message);
}

/* ==========================
   EMAIL: Welcome email s prihlasovacími údajmi
   ========================== */

function spa_send_welcome_email($to_email, $username, $password, $first_name) {
    
    $subject = 'Vitajte v Samuel Piasecký ACADEMY - Prihlasovacie údaje';
    
    $message = "Dobrý deň {$first_name},\n\n";
    $message .= "Bol Vám vytvorený účet v systéme Samuel Piasecký ACADEMY.\n\n";
    $message .= "Vaše prihlasovacie údaje:\n";
    $message .= "Používateľské meno: {$username}\n";
    $message .= "Heslo: {$password}\n\n";
    $message .= "Prihlásiť sa môžete na: " . home_url('/dashboard/') . "\n\n";
    $message .= "DÔLEŽITÉ: Po prihlásení si odporúčame zmeniť heslo v nastaveniach profilu.\n\n";
    $message .= "Ďakujeme,\nSamuel Piasecký ACADEMY";
    
    wp_mail($to_email, $subject, $message);
}

/* ==========================
   EMAIL: Notifikácia adminovi
   ========================== */

function spa_notify_admin_new_registration($registration_id, $client_email) {
    
    // Nájdi editora s capability 'approve_spa_registrations'
    $admins = get_users([
        'role__in' => ['administrator', 'editor'],
        'number' => -1
    ]);
    
    $notify_emails = [];
    foreach ($admins as $admin) {
        if (user_can($admin->ID, 'approve_spa_registrations') || user_can($admin->ID, 'administrator')) {
            $notify_emails[] = $admin->user_email;
        }
    }
    
    if (empty($notify_emails)) {
        $notify_emails[] = get_option('admin_email');
    }
    
    $edit_link = admin_url('post.php?post=' . $registration_id . '&action=edit');
    
    $subject = 'Nová registrácia čaká na schválenie';
    
    $message = "Dobrý deň,\n\n";
    $message .= "Bola prijatá nová registrácia do programu.\n\n";
    $message .= "Email klienta: {$client_email}\n";
    $message .= "Schváliť/upraviť: {$edit_link}\n\n";
    $message .= "Samuel Piasecký ACADEMY systém";
    
    foreach ($notify_emails as $email) {
        wp_mail($email, $subject, $message);
    }
}

/* ==========================
   GRAVITY FORMS: Dynamické naplnenie dropdown programami
   ========================== */

/* ==========================
   GRAVITY FORMS: Kaskádový dropdown (Miesto → Program)
   ========================== */

add_filter('gform_pre_render_1', 'spa_populate_cascading_dropdowns');
add_filter('gform_pre_validation_1', 'spa_populate_cascading_dropdowns');
add_filter('gform_pre_submission_filter_1', 'spa_populate_cascading_dropdowns');
add_filter('gform_admin_pre_render_1', 'spa_populate_cascading_dropdowns');

function spa_populate_cascading_dropdowns($form) {
    
    foreach ($form['fields'] as &$field) {
        
        // POLE 4: Výber programu (filtrované podľa miesta z Field 5)
        if ($field->id == 4) {  // ✅ OPRAVENÉ: Program je Field 4!
            
            // Zisti vybrané miesto z $_POST input_5 (nie input_4!)
            $selected_place = '';
            
            if (isset($_POST['input_5'])) {  // ✅ OPRAVENÉ!
                $selected_place = sanitize_text_field($_POST['input_5']);
            }
            
            // Ak nie je vybrané miesto, zobraz prázdny dropdown
            if (empty($selected_place)) {
                $field->choices = [
                    ['text' => '-- Najprv vyberte miesto --', 'value' => '']
                ];
                continue;
            }
            
            // Získaj programy z vybraného miesta
            $programs = get_posts([
                'post_type' => 'spa_group',
                'posts_per_page' => -1,
                'orderby' => 'menu_order title',
                'order' => 'ASC',
                'post_status' => 'publish',
                'tax_query' => [
                    [
                        'taxonomy' => 'spa_place',
                        'field' => 'slug',
                        'terms' => $selected_place
                    ]
                ]
            ]);
            
            // Vyčisti choices
            $field->choices = [];
            
            // Prázdna možnosť
            $field->choices[] = [
                'text' => '-- Vyberte program --',
                'value' => '',
                'isSelected' => false
            ];
            
            // Pridaj programy
            foreach ($programs as $program) {
                
                $categories = get_the_terms($program->ID, 'spa_group_category');
                $price = get_post_meta($program->ID, 'spa_price', true);
                
                $category_name = $categories ? $categories[0]->name : '';
                
                $text = $program->post_title;
                
                if ($category_name) {
                    $text .= ' (' . $category_name . ')';
                }
                
                if ($price) {
                    $text .= ' | ' . number_format($price, 2, ',', ' ') . ' €';
                }
                
                $field->choices[] = [
                    'text' => $text,
                    'value' => $program->ID,
                    'isSelected' => false
                ];
            }
            
            // Ak žiadne programy, zobraz hlášku
            if (count($field->choices) == 1) {
                $field->choices[] = [
                    'text' => 'V tomto mieste momentálne nie sú žiadne programy',
                    'value' => '',
                    'isSelected' => false
                ];
            }
        }
    }
    
    return $form;
}


/* ==========================
   GF: Aktualizuj URL pri zmene miesta/programu
   ========================== */

add_action('gform_enqueue_scripts_1', 'spa_update_url_on_change', 60, 2);

function spa_update_url_on_change($form, $is_ajax) {
    ?>
    <script type="text/javascript">
    (function($) {
        $(document).ready(function() {
            
            var $placeField = $('#input_1_5'); // Miesto
            var $programField = $('#input_1_4'); // Program
            
            // Funkcia na aktualizáciu URL a info boxu
            function updateURLAndInfoBox() {
                
                var place = $placeField.val();
                var program = $programField.val();
                
                if (!place || !program) {
                    return;
                }
                
                // Získaj texty z vybraných options
                var placeText = $placeField.find('option:selected').text();
                var programText = $programField.find('option:selected').text();
                
                console.log('SPA: Updating - Place:', placeText, 'Program:', programText);
                
                // Extrahuj cenu z programu (napr. "40,00 €")
                var priceMatch = programText.match(/(\d+[,.]?\d*)\s*€/);
                var price = priceMatch ? priceMatch[1].replace(',', '.') : '';
                
                // Aktualizuj URL
                var newURL = new URL(window.location.href);
                newURL.searchParams.set('program_id', program);
                newURL.searchParams.set('place', place);
                newURL.searchParams.set('program_name', programText.split('|')[0].trim());
                
                if (price) {
                    newURL.searchParams.set('price', price);
                }
                
                window.history.replaceState({}, '', newURL.toString());
                
                // ✅ AKTUALIZUJ INFO BOX
                var $infoBox = $('.spa-success-notice, .gform_confirmation_message').first();
                
                if ($infoBox.length === 0) {
                    // Vytvor info box ak neexistuje
                    $infoBox = $('<div class="spa-program-info-box" style="background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%); border-left: 5px solid #28a745; padding: 20px; margin: 20px 0; border-radius: 12px; box-shadow: 0 4px 15px rgba(40,167,69,0.3);">' +
                      '<div style="display: flex; align-items: center; gap: 15px;">' +
                      '<span style="font-size: 32px;">✅</span>' +
                      '<div style="flex: 1;">' +
                      '<h3 style="margin: 0 0 5px 0; font-size: 18px; color: #155724; font-weight: 700;">Vybraný program</h3>' +
                      '<p style="margin: 0; font-size: 14px; color: #155724;" class="info-program-text"></p>' +
                      '</div>' +
                      '</div>' +
                      '</div>');
                    
                    $('.gform_wrapper').prepend($infoBox);
                }
                
                // Aktualizuj text v info boxe
                var infoText = programText;
                if (price) {
                    infoText += ' | ' + price + ' €';
                }
                
                $infoBox.find('.info-program-text, p').last().html(infoText);
                
                // Animácia (fade effect)
                $infoBox.css('opacity', '0.5').animate({ opacity: 1 }, 300);
                
                console.log('SPA: Info box updated');
                
                // Vizuálny feedback
                $placeField.css('border-color', '#80EF80');
                $programField.css('border-color', '#80EF80');
            }
            
            // Trigger na zmenu
            $placeField.on('change', function() {
                setTimeout(updateURLAndInfoBox, 2000); // Počkaj na AJAX
            });
            
            $programField.on('change', updateURLAndInfoBox);
            
        });
    })(jQuery);
    </script>
    <?php
}

/* ==========================
   GF: Skry sekciu rodiča ak dieťa má 18+
   ========================== */

add_action('gform_enqueue_scripts_1', 'spa_conditional_parent_section', 50, 2);

function spa_conditional_parent_section($form, $is_ajax) {
    ?>
    <script type="text/javascript">
    (function($) {
        $(document).ready(function() {
            
            // Field ID 3 = Dátum narodenia dieťaťa
            var $birthdateField = $('#input_1_3');
            
            // Sekcia "Informácie o rodičovi" - Field IDs 6,7,8,9
            //var parentFields = ['#input_1_7_3', '#input_1_7_6'];
            var parentFields = ['#field_1_6','#field_1_7'];
            //var $parentSection = $('.gform_fields'); // Alebo presný selector pre sekciu
            
            // Funkcia na výpočet veku
            function calculateAge(birthdate) {
                var today = new Date();
                var birth = new Date(birthdate);
                var age = today.getFullYear() - birth.getFullYear();
                var monthDiff = today.getMonth() - birth.getMonth();
                
                if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birth.getDate())) {
                    age--;
                }
                
                return age;
            }
            
            // Funkcia na toggle parent fields
            function toggleParentFields() {
                var birthdate = $birthdateField.val();
                
                if (!birthdate) {
                    // Ak nie je vyplnený, zobraz parent fields
                    $.each(parentFields, function(i, selector) {
                        $(selector).show().find('input, select, textarea').prop('disabled', false);
                    });
                    return;
                }
                
                var age = calculateAge(birthdate);
                
                console.log('SPA: Calculated age:', age);
                
                if (age >= 18) {
                    // DOSPELÝ - skry parent fields
                    $.each(parentFields, function(i, selector) {
                        $(selector).hide().find('input, select, textarea').prop('disabled', true);
                    });
                    
                    // Zmeň label
                    $('label[for="input_1_1"]').html('Meno a priezvisko <span class="gfield_required">*</span>');
                    
                    console.log('SPA: Adult detected - hiding parent fields');
                    
                } else {
                    // DIEŤA - zobraz parent fields
                    $.each(parentFields, function(i, selector) {
                        $(selector).show().find('input, select, textarea').prop('disabled', false);
                    });
                    
                    $('label[for="input_1_1"]').html('Meno a priezvisko dieťaťa <span class="gfield_required">*</span>');
                    
                    console.log('SPA: Child detected - showing parent fields');
                }
            }
            
            // Trigger na zmenu dátumu
            $birthdateField.on('change blur', toggleParentFields);
            
            // Initial check
            setTimeout(toggleParentFields, 500);
            
        });
    })(jQuery);
    </script>
    <?php
}

/* ==========================
   AJAX: Načítanie programov podľa miesta
   ========================== */

add_action('wp_ajax_spa_get_programs_by_place', 'spa_ajax_get_programs_by_place');
add_action('wp_ajax_nopriv_spa_get_programs_by_place', 'spa_ajax_get_programs_by_place');

function spa_ajax_get_programs_by_place() {
    
    // Nonce check
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'spa_ajax_nonce')) {
        wp_send_json_error(['message' => 'Security check failed']);
    }
    
    $place = sanitize_text_field($_POST['place'] ?? '');
    
    if (empty($place)) {
        wp_send_json_error(['message' => 'No place selected']);
    }
    
    error_log('SPA AJAX: Received place = ' . $place);
    
    // Split kombinovaný slug: "kosice,september-jun,zs-drabova-3"
    $place_slugs = array_map('trim', explode(',', $place));
    
    error_log('SPA AJAX: Split into slugs: ' . print_r($place_slugs, true));
    
    // Získaj programy ktoré majú VŠETKY tieto termy
    $args = [
        'post_type' => 'spa_group',
        'posts_per_page' => -1,
        'orderby' => 'menu_order title',
        'order' => 'ASC',
        'post_status' => 'publish',
        'tax_query' => [
            'relation' => 'AND', // Musí mať VŠETKY termy
        ]
    ];
    
    // Pridaj každý slug ako podmienku
    foreach ($place_slugs as $slug) {
        $args['tax_query'][] = [
            'taxonomy' => 'spa_place',
            'field' => 'slug',
            'terms' => $slug
        ];
    }
    
    error_log('SPA AJAX: Query args: ' . print_r($args, true));
    
    $programs = get_posts($args);
    
    error_log('SPA AJAX: Found ' . count($programs) . ' programs');
    
    $programs_data = [];
    
    foreach ($programs as $program) {
        
        $categories = get_the_terms($program->ID, 'spa_group_category');
        $price = get_post_meta($program->ID, 'spa_price', true);
        
        $category_name = $categories ? $categories[0]->name : '';
        
        $text = $program->post_title;
        
        if ($category_name) {
            $text .= ' (' . $category_name . ')';
        }
        
        if ($price) {
            $text .= ' | ' . number_format($price, 2, ',', ' ') . ' €';
        }
        
        $programs_data[] = [
            'id' => $program->ID,
            'text' => $text
        ];
        
        error_log('SPA AJAX: Added program - ' . $program->post_title . ' (ID: ' . $program->ID . ')');
    }
    
    wp_send_json_success(['programs' => $programs_data]);
}



/* ==========================
   AJAX JAVASCRIPT: Kaskádový dropdown
   ========================== */

add_action('gform_enqueue_scripts_1', 'spa_enqueue_cascading_script_form1', 10, 2);

function spa_enqueue_cascading_script_form1($form, $is_ajax) {
    ?>
    <script type="text/javascript">
    (function($) {
        if (typeof $ === 'undefined') {
            console.error('SPA Cascading: jQuery not loaded!');
            return;
        }
        
        $(document).ready(function() {
            
            console.log('SPA Cascading: Script loaded for Form 1');
            
            // Počúvaj zmenu na poli "Miesto" (Field 5)
            $('#input_1_5').on('change', function() {
                
                var selectedPlace = $(this).val();
                console.log('SPA: Place changed to:', selectedPlace);
                
                var $programField = $('#input_1_4');
                
                if (!selectedPlace) {
                    $programField.html('<option value="">-- Najprv vyberte miesto --</option>');
                    return;
                }
                
                $programField.html('<option value="">Načítavam programy...</option>');
                
                $.ajax({
                    url: '<?php echo admin_url('admin-ajax.php'); ?>',
                    type: 'POST',
                    data: {
                        action: 'spa_get_programs_by_place',
                        place: selectedPlace,
                        form_id: 1,
                        nonce: '<?php echo wp_create_nonce('spa_ajax_nonce'); ?>'
                    },
                    success: function(response) {
                        
                        console.log('SPA AJAX response:', response);
                        
                        if (response.success) {
                            var options = '<option value="">-- Vyberte program --</option>';
                            
                            if (response.data.programs.length === 0) {
                                options += '<option value="">V tomto mieste nie sú žiadne programy</option>';
                            } else {
                                $.each(response.data.programs, function(i, program) {
                                    options += '<option value="' + program.id + '">' + program.text + '</option>';
                                });
                            }
                            
                            $programField.html(options);
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('SPA AJAX error:', error);
                        $programField.html('<option value="">Chyba načítania</option>');
                    }
                });
            });
        });
    })(jQuery);
    </script>
    <?php
}




/* ==========================
   GRAVITY FORMS: Kaskádový dropdown pre dospelých (Form ID 2)
   ========================== */

add_filter('gform_pre_render_2', 'spa_populate_cascading_dropdowns_adult');
add_filter('gform_pre_validation_2', 'spa_populate_cascading_dropdowns_adult');
add_filter('gform_pre_submission_filter_2', 'spa_populate_cascading_dropdowns_adult');
add_filter('gform_admin_pre_render_2', 'spa_populate_cascading_dropdowns_adult');

function spa_populate_cascading_dropdowns_adult($form) {
    
    foreach ($form['fields'] as &$field) {
        
        // Pre dospelých bude miesto Field ID 4, program Field ID 5
        if ($field->id == 5) {
            
            $selected_place = isset($_POST['input_4']) ? sanitize_text_field($_POST['input_4']) : '';
            
            if (empty($selected_place)) {
                $field->choices = [
                    ['text' => '-- Najprv vyberte miesto --', 'value' => '']
                ];
                continue;
            }
            
            $programs = get_posts([
                'post_type' => 'spa_group',
                'posts_per_page' => -1,
                'orderby' => 'menu_order title',
                'order' => 'ASC',
                'post_status' => 'publish',
                'tax_query' => [
                    [
                        'taxonomy' => 'spa_place',
                        'field' => 'slug',
                        'terms' => $selected_place
                    ]
                ]
            ]);
            
            $field->choices = [
                ['text' => '-- Vyberte program --', 'value' => '']
            ];
            
            foreach ($programs as $program) {
                
                $categories = get_the_terms($program->ID, 'spa_group_category');
                $price = get_post_meta($program->ID, 'spa_price', true);
                $category_name = $categories ? $categories[0]->name : '';
                
                $text = $program->post_title;
                if ($category_name) $text .= ' (' . $category_name . ')';
                if ($price) $text .= ' | ' . number_format($price, 2, ',', ' ') . ' €';
                
                $field->choices[] = [
                    'text' => $text,
                    'value' => $program->ID
                ];
            }
            
            if (count($field->choices) == 1) {
                $field->choices[] = [
                    'text' => 'V tomto mieste nie sú žiadne programy',
                    'value' => ''
                ];
            }
        }
    }
    
    return $form;
}

// AJAX script 
/* ==========================
   FIX: Načítaj miesta s NAJVYŠŠOU prioritou
   ========================== */

/* ==========================
   FIX: Načítaj miesta ako KOMBINOVANÝ text
   ========================== */

add_filter('gform_pre_render_1', 'spa_force_populate_places_combined', 1);
add_filter('gform_pre_validation_1', 'spa_force_populate_places_combined', 1);
add_filter('gform_admin_pre_render_1', 'spa_force_populate_places_combined', 1);

function spa_force_populate_places_combined($form) {
    
    error_log('=== SPA COMBINED PLACES: Start ===');
    
    // Získaj VŠETKY programy
    $programs = get_posts([
        'post_type' => 'spa_group',
        'posts_per_page' => -1,
        'post_status' => 'publish'
    ]);
    
    // Vytvor unikátne kombinácie miest
    $unique_places = [];
    
    foreach ($programs as $program) {
        
        // Získaj VŠETKY termy pre tento program
        $places = get_the_terms($program->ID, 'spa_place');
        
        if ($places && !is_wp_error($places)) {
            
            // Zoraď termy podľa názvu
            usort($places, function($a, $b) {
                return strcmp($a->name, $b->name);
            });
            
            // Vytvor kombinovaný text: "Košice, september-jún, ZŠ Drábova 3"
            $place_names = array_map(function($term) {
                return $term->name;
            }, $places);
            
            $combined_name = implode(', ', $place_names);
            
            // Vytvor kombinovaný slug (pre matching)
            $place_slugs = array_map(function($term) {
                return $term->slug;
            }, $places);
            
            sort($place_slugs); // Zoraď aby bol konzistentný
            $combined_slug = implode(',', $place_slugs);
            
            // Pridaj do unique choices
            if (!isset($unique_places[$combined_slug])) {
                $unique_places[$combined_slug] = $combined_name;
            }
        }
    }
    
    error_log('SPA: Found ' . count($unique_places) . ' unique place combinations');
    
    // Aplikuj na Field 5
    foreach ($form['fields'] as &$field) {
        
        if ($field->id == 5) {
            
            error_log('SPA: Populating Field 5 with combined places');
            
            $field->choices = [];
            
            // Prázdna možnosť
            $field->choices[] = [
                'text' => '-- Najprv vyberte miesto --',
                'value' => '',
                'isSelected' => false
            ];
            
            // Pridaj unikátne kombinácie
            foreach ($unique_places as $slug => $name) {
                $field->choices[] = [
                    'text' => $name,
                    'value' => $slug,
                    'isSelected' => false
                ];
                
                error_log('SPA: Added choice - ' . $name . ' (slug: ' . $slug . ')');
            }
            
            error_log('SPA: Field 5 now has ' . count($field->choices) . ' choices');
            break;
        }
    }
    
    error_log('=== SPA COMBINED PLACES: End ===');
    
    return $form;
}


// Ak máš Form 2, pridaj rovnakú funkciu
add_filter('gform_pre_render_2', 'spa_populate_place_field_form2', 1);
add_filter('gform_pre_validation_2', 'spa_populate_place_field_form2', 1);

// gform_pre_render na manuálne nastavenie

function spa_force_prepopulate_dropdowns($form) {
    
    // Získaj GET parametre
    $place_param = isset($_GET['place']) ? sanitize_text_field($_GET['place']) : '';
    $program_param = isset($_GET['program_id']) ? intval($_GET['program_id']) : 0;
    
    if (empty($place_param) && empty($program_param)) {
        return $form; // Žiadne parametre
    }
    
    // Zisti Field IDs dynamicky
    $place_field_id = false;
    $program_field_id = false;
    
    foreach ($form['fields'] as $field) {
        if (stripos($field->label, 'miesto') !== false) {
            $place_field_id = $field->id;
        }
        if (stripos($field->label, 'program') !== false) {
            $program_field_id = $field->id;
        }
    }
    
    foreach ($form['fields'] as &$field) {
        
        // POLE: Miesto
        if ($field->id == $place_field_id && !empty($place_param)) {
            
            error_log('SPA GF: Forcing place = ' . $place_param);
            
            foreach ($field->choices as &$choice) {
                if ($choice['value'] == $place_param) {
                    $choice['isSelected'] = true;
                    error_log('SPA GF: ✅ Matched place: ' . $place_param);
                } else {
                    $choice['isSelected'] = false;
                }
            }
        }
        
        // POLE: Program
        if ($field->id == $program_field_id && !empty($program_param)) {
            
            error_log('SPA GF: Setting program default value = ' . $program_param);
            $field->defaultValue = $program_param;
        }
    }
    
    return $form;
}

/* ==========================
   AUTO-PREPOPULATE: Predvyplnenie z URL parametrov
   ========================== */

add_action('gform_enqueue_scripts_1', 'spa_auto_prepopulate_from_url', 30, 2);

function spa_auto_prepopulate_from_url($form, $is_ajax) {
    
    // Len ak sú v URL parametre
    if (!isset($_GET['place']) && !isset($_GET['program_id'])) {
        return;
    }
    
    $place = isset($_GET['place']) ? sanitize_text_field($_GET['place']) : '';
    $program_id = isset($_GET['program_id']) ? intval($_GET['program_id']) : 0;
    
    ?>
    <script type="text/javascript">
    (function($) {
        if (typeof $ === 'undefined') {
            console.error('SPA AUTO-PREP: jQuery not loaded!');
            
            // Fallback na vanilla JS
            window.addEventListener('DOMContentLoaded', function() {
                console.log('SPA: Using vanilla JS fallback');
                var placeField = document.getElementById('input_1_5');
                var programField = document.getElementById('input_1_4');
                
                if (placeField && '<?php echo $place; ?>') {
                    placeField.value = '<?php echo $place; ?>';
                    placeField.dispatchEvent(new Event('change', { bubbles: true }));
                    
                    setTimeout(function() {
                        if (programField && '<?php echo $program_id; ?>') {
                            programField.value = '<?php echo $program_id; ?>';
                        }
                    }, 2000);
                }
            });
            return;
        }
        
        $(document).ready(function() {
            
            console.log('=== SPA AUTO-PREPOPULATE ===');
            console.log('URL Place:', '<?php echo $place; ?>');
            console.log('URL Program ID:', <?php echo $program_id; ?>);
            
            // Počkaj na načítanie formulára
            setTimeout(function() {
                
                // 1. NASTAV MIESTO (Field 5)
                var placeValue = '<?php echo $place; ?>';
                if (placeValue) {
                    var $placeField = $('#input_1_5');
                    
                    console.log('Setting place field to:', placeValue);
                    console.log('Place field found:', $placeField.length > 0);
                    
                    $placeField.val(placeValue);
                    
                    // Vizuálny feedback
                    $placeField.css({
                        'border': '2px solid #80EF80',
                        'background': '#E2F9E0'
                    });
                    
                    // CRITICAL: Trigger change event
                    $placeField.trigger('change');
                    console.log('✅ Place field set and triggered');
                    
                    // 2. Po načítaní programov, nastav program
                    setTimeout(function() {
                        
                        var programId = '<?php echo $program_id; ?>';
                        if (programId && programId !== '0') {
                            var $programField = $('#input_1_4');
                            
                            console.log('Setting program field to:', programId);
                            console.log('Program field found:', $programField.length > 0);
                            console.log('Available options:', $programField.find('option').length);
                            
                            $programField.val(programId);
                            
                            // Vizuálny feedback
                            $programField.css({
                                'border': '2px solid #80EF80',
                                'background': '#E2F9E0'
                            });
                            
                            console.log('✅ Program field set to:', programId);
                            
                            // Zobraz potvrdenie
                            var programName = $programField.find('option:selected').text();
                            
                            if (programName && programName !== '-- Vyberte program --' && programName !== '') {
                                
                                $('<div class="spa-success-notice" style="background: linear-gradient(135deg, #DDFCDB 0%, #9AED9A 100%); border-left: 5px solid #80EF80; padding: 25px; margin: 20px 0; border-radius: 12px; box-shadow: 0 4px 15px rgba(49,206,49,0.3); animation: slideInDown 0.5s ease-out;">' +
                                  '<div style="display: flex; align-items: center; gap: 20px;">' +
                                  '<div style="font-size: 48px; line-height: 1;">✅</div>' +
                                  '<div style="flex: 1;">' +
                                  '<h3 style="margin: 0 0 8px 0; font-size: 22px; color: #155724; font-weight: 700;">Vybraný program</h3>' +
                                  '<p style="margin: 0; font-size: 16px; color: #155724; line-height: 1.5;">' + programName + '</p>' +
                                  '</div>' +
                                  '</div>' +
                                  '</div>').prependTo('.gform_wrapper');
                                
                                // Scroll na formulár
                                $('html, body').animate({
                                    scrollTop: $('.gform_wrapper').offset().top - 100
                                }, 600);
                                
                                console.log('✅ Success notice displayed');
                            } else {
                                console.warn('⚠️ Program name is empty or default:', programName);
                            }
                        }
                        
                    }, 2500); // Počkaj 2.5s na AJAX
                }
                
            }, 1000); // Počkaj 1s na načítanie formulára
            
        });
    })(jQuery);
    </script>
    <style>
    @keyframes slideInDown {
        from {
            opacity: 0;
            transform: translateY(-30px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    </style>
    <?php
}

/* ============================================================
   REGISTER POST TYPE: spa_registration
   ============================================================ */

add_action('init', 'spa_register_registration_cpt');
function spa_register_registration_cpt() {

    $labels = [
        'name' => 'Registrácie',
        'singular_name' => 'Registrácia',
        'add_new' => 'Pridať registráciu',
        'add_new_item' => 'Pridať registráciu',
        'edit_item' => 'Upraviť registráciu',
        'new_item' => 'Nová registrácia',
        'view_item' => 'Zobraziť registráciu',
        'search_items' => 'Hľadať registrácie',
        'not_found' => 'Žiadne registrácie',
        'menu_name' => 'Registrácie'
    ];

    register_post_type('spa_registration', [
        'labels' => $labels,
        'public' => false,
        'show_ui' => true,
        'show_in_menu' => true,
        'menu_icon' => 'dashicons-forms',
        'supports' => ['title'],
        'capability_type' => 'post',
        'map_meta_cap' => true,
    ]);
}




/* ============================================================
   ADMIN COLUMNS – FIX (plne funkčné, bezpečné)
   ============================================================ */

/* add_filter('manage_spa_registration_posts_columns', 'spa_registration_columns');
function spa_registration_columns($columns) {

    $new = [
        'cb'     => '<input type="checkbox" />',
        'title'  => 'Registrácia',
        'child'  => 'Dieťa / Klient',
        'parent' => 'Rodič',
        'vs'     => 'VS',
        'program'=> 'Program',
        'status' => 'Status',
        'date'   => 'Dátum'
    ];

    return $new;
}

add_action('manage_spa_registration_posts_custom_column', 'spa_registration_columns_content', 10, 2);
function spa_registration_columns_content($column, $post_id) {

    $client_id = (int) get_post_meta($post_id, 'client_user_id', true);
    $parent_id = (int) get_post_meta($post_id, 'parent_user_id', true);
    $program_id = (int) get_post_meta($post_id, 'program_id', true);

    switch ($column) {

        case 'child':
            if ($client_id) {
                $fname = get_user_meta($client_id, 'first_name', true);
                $lname = get_user_meta($client_id, 'last_name', true);
                echo esc_html(trim("$fname $lname"));
            } else {
                echo '—';
            }
            break;

        case 'parent':
            if ($parent_id) {
                $fname = get_user_meta($parent_id, 'first_name', true);
                $lname = get_user_meta($parent_id, 'last_name', true);
                echo esc_html(trim("$fname $lname"));
            } else {
                echo '—';
            }
            break;

        case 'vs':
            echo esc_html(get_user_meta($client_id, 'variabilny_symbol', true) ?: '—');
            break;

        case 'program':
            echo esc_html(get_the_title($program_id) ?: '—');
            break;

        case 'status':
            $status = get_post_meta($post_id, 'status', true);
            echo esc_html($status ?: '—');
            break;
    }
} */
