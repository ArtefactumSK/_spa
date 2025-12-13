<?php
/**
 * SPA Registration Notifications - Email notifikácie
 * 
 * @package Samuel Piasecký ACADEMY
 * @subpackage Registration
 * @version 1.0.0
 * 
 * PARENT MODULES: 
 * - spa-registration-form.php
 * - spa-registration-helpers.php
 * 
 * FUNCTIONS DEFINED:
 * - spa_send_registration_confirmation() - Potvrdenie registrácie
 * - spa_send_welcome_email() - Welcome email s heslo
 * - spa_notify_admin_new_registration() - Admin notifikácia
 * 
 * EMAIL TEMPLATES:
 * Všetky emaily sú v SK jazyku
 */

if (!defined('ABSPATH')) {
    exit;
}

/* ==================================================
   EMAIL: Potvrdenie registrácie
   ================================================== */

function spa_send_registration_confirmation($to_email, $client_name, $program_id) {
    
    $program = get_post($program_id);
    
    if (!$program) {
        return false;
    }
    
    $subject = 'Potvrdenie registrácie - Samuel Piasecký ACADEMY';
    
    $message = "Dobrý deň,\n\n";
    $message .= "Vaša registrácia pre {$client_name} do programu \"{$program->post_title}\" bola úspešne prijatá.\n\n";
    $message .= "Registrácia čaká na schválenie administrátorom. O výsledku Vás budeme informovať emailom.\n\n";
    $message .= "Ďakujeme,\n";
    $message .= "Samuel Piasecký ACADEMY\n";
    $message .= home_url();
    
    return wp_mail($to_email, $subject, $message);
}

/* ==================================================
   EMAIL: Welcome email s prihlasovacími údajmi
   ================================================== */

function spa_send_welcome_email($to_email, $username, $password, $first_name) {
    
    $subject = 'Vitajte v Samuel Piasecký ACADEMY - Prihlasovacie údaje';
    
    $message = "Dobrý deň {$first_name},\n\n";
    $message .= "Bol Vám vytvorený účet v systéme Samuel Piasecký ACADEMY.\n\n";
    $message .= "Vaše prihlasovacie údaje:\n";
    $message .= "Používateľské meno: {$username}\n";
    $message .= "Heslo: {$password}\n\n";
    $message .= "Prihlásiť sa môžete na: " . home_url('/dashboard/') . "\n\n";
    $message .= "DÔLEŽITÉ: Po prihlásení si odporúčame zmeniť heslo v nastaveniach profilu.\n\n";
    $message .= "Ďakujeme,\n";
    $message .= "Samuel Piasecký ACADEMY";
    
    return wp_mail($to_email, $subject, $message);
}

/* ==================================================
   EMAIL: Notifikácia adminovi
   ================================================== */

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
    
    // Fallback na admin email
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