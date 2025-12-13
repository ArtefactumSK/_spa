<?php
/**
 * SPA Roles & Capabilities - Vlastné role a práva prístupu
 * 
 * @package Samuel Piasecký ACADEMY
 * @subpackage Core
 * @version 1.0.0
 * 
 * PARENT MODULES:
 * - spa-constants.php (konštanty)
 * 
 * CHILD MODULES:
 * - spa-core/spa-filters-hooks.php
 * - všetky ostatné moduly
 * 
 * ROLES CREATED:
 * - spa_parent (Rodič - čítanie, vidieť deti)
 * - spa_child (Dieťa - virtuálny účet s PIN)
 * - spa_client (Klient - vlastný účet)
 * - spa_trainer (Tréner - čítanie, úprava, upload)
 * 
 * FUNCTIONS DEFINED:
 * - spa_create_custom_roles()
 * - spa_editor_capabilities()
 * - spa_registration_admin_capabilities()
 * 
 * HOOKS USED:
 * - init (role vytvorenie)
 * - after_switch_theme (capabilities)
 */

if (!defined('ABSPATH')) {
    exit;
}

/* =============================================
   VLASTNÉ ROLE - MULTISITE FIX
   ============================================= */

add_action('init', 'spa_create_custom_roles', 1);

function spa_create_custom_roles() {
    
    // RODIČ - vidí svoje deti, platby, rozvrhy
    if (!get_role('spa_parent')) {
        add_role('spa_parent', 'Rodič (SPA)', [
            'read' => true,
            'edit_posts' => false,
            'delete_posts' => false
        ]);
    }
    
    // DIEŤA - virtuálny účet s PIN prihlásením
    if (!get_role('spa_child')) {
        add_role('spa_child', 'Dieťa (SPA)', [
            'read' => true
        ]);
    }
    
    // DOSPELÝ KLIENT - vlastný účet
    if (!get_role('spa_client')) {
        add_role('spa_client', 'Klient (SPA)', [
            'read' => true,
            'edit_posts' => false,
            'delete_posts' => false
        ]);
    }
    
    // TRÉNER
    if (!get_role('spa_trainer')) {
        add_role('spa_trainer', 'Tréner (SPA)', [
            'read' => true,
            'edit_posts' => true,
            'upload_files' => true
        ]);
    }
}

/* =============================================
   CAPABILITIES PRE EDITOR
   ============================================= */

add_action('after_switch_theme', 'spa_editor_capabilities');

function spa_editor_capabilities() {
    
    // Kontrola či už boli nastavené
    if (get_option('spa_editor_caps_set')) {
        return;
    }
    
    $role = get_role('editor');
    
    if ($role) {
        // Blocksy capabilities
        $role->add_cap('edit_ct_content_blocks');
        $role->add_cap('edit_ct_content_block');
        $role->add_cap('edit_others_ct_content_blocks');
        $role->add_cap('publish_ct_content_blocks');
        $role->add_cap('read_ct_content_block');
        $role->add_cap('delete_ct_content_blocks');
        
        // Gravity Forms capabilities
        $role->add_cap('gravityforms_view_entries');
        $role->add_cap('gravityforms_delete_entries');
        $role->add_cap('gravityforms_view_entry_notes');
        $role->add_cap('gravityforms_export_entries');
        $role->add_cap('gravityforms_edit_entries');
    }
    
    update_option('spa_editor_caps_set', true);
}

/* =============================================
   CAPABILITIES PRE REGISTRAČNÝ ADMINISTRÁTOR
   ============================================= */

add_action('after_switch_theme', 'spa_registration_admin_capabilities');

function spa_registration_admin_capabilities() {
    
    if (get_option('spa_registration_admin_caps_set')) {
        return;
    }
    
    $role = get_role('editor');
    
    if ($role) {
        // Správa registrácií
        $role->add_cap('edit_spa_registrations');
        $role->add_cap('edit_others_spa_registrations');
        $role->add_cap('publish_spa_registrations');
        $role->add_cap('read_spa_registration');
        $role->add_cap('delete_spa_registrations');
        $role->add_cap('approve_spa_registrations');
        
        // Správa klientov
        $role->add_cap('list_users');
        $role->add_cap('edit_users');
        $role->add_cap('create_users');
        
        // Platby
        $role->add_cap('view_spa_payments');
        $role->add_cap('edit_spa_payments');
    }
    
    update_option('spa_registration_admin_caps_set', true);
}