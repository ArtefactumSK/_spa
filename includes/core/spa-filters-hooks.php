<?php
/**
 * SPA Filters & Hooks - Globálne filtre, akcie a bezpečnosť
 * 
 * @package Samuel Piasecký ACADEMY
 * @subpackage Core
 * @version 1.0.0
 * 
 * PARENT MODULES:
 * - spa-roles.php
 * - spa-constants.php
 * 
 * CHILD MODULES: všetky
 * 
 * GLOBAL VARIABLES USED:
 * - $_GET['page'], $_GET['reset_capabilities']
 * - $current_user (WP global)
 * 
 * FUNCTIONS DEFINED:
 * - spa_restrict_admin_access()
 * - spa_remove_menu_pages()
 * - spa_hide_admin_elements()
 * - spa_restrict_admin_email_update()
 * - spa_login_redirect()
 * - spa_filter_users_for_editor()
 * - spa_get_option()
 * - spa_update_option()
 * - spa_group_admin_order()
 * - spa_group_order_column()
 * - spa_group_order_column_content()
 * - spa_update_order_ajax()
 * - spa_order_quick_edit_js()
 * 
 * HOOKS USED:
 * - admin_init (access control)
 * - admin_menu (menu removal)
 * - admin_head (CSS hiding)
 * - pre_update_option_admin_email (security)
 * - login_redirect (user redirect)
 * - pre_get_users (user filtering)
 * - pre_get_posts (ordering)
 * - wp_ajax_spa_update_order (AJAX)
 * - admin_footer (JS)
 */

if (!defined('ABSPATH')) {
    exit;
}

/* =============================================
   OBMEDZENIA PRE NON-ADMIN
   ============================================= */

add_action('admin_init', 'spa_restrict_admin_access', 999);

function spa_restrict_admin_access() {
    
    if (defined('DOING_AJAX') && DOING_AJAX) {
        return;
    }
    
    if (current_user_can('administrator')) {
        return;
    }
    
    $page = $_GET['page'] ?? '';
    
    $restricted_pages = [
        'ct-dashboard',
        'ct-dashboard-account',
        'wp-mail-smtp',
        'aiowpsec',
        'litespeed'
    ];
    
    if (in_array($page, $restricted_pages)) {
        wp_die('Nemáte oprávnenie pre vstup do tejto sekcie.');
    }
}

/* =============================================
   SKRYTIE MENU PRE NON-ARTEFACTUM
   ============================================= */

add_action('admin_menu', 'spa_remove_menu_pages', 201);

function spa_remove_menu_pages() {
    global $current_user;
    wp_get_current_user();
    
    if ($current_user->user_login === 'artefactum') {
        return;
    }
    
    $menus_to_remove = [
        'wp-mail-smtp',
        'aiowpsec',
        'litespeed',
        'advanced_db_cleaner',
        'tools.php'
    ];
    
    foreach ($menus_to_remove as $menu) {
        remove_menu_page($menu);
    }
}

/* =============================================
   CSS SKRYTIE PRVKOV V ADMIN
   ============================================= */

add_action('admin_head', 'spa_hide_admin_elements');

function spa_hide_admin_elements() {
    global $current_user;
    wp_get_current_user();
    
    if ($current_user->user_login === 'artefactum') {
        return;
    }
    
    ?>
    <style>
    a.page-title-action[href*="post-new.php?post_type=ct_content_block"],
    #adminmenu a[href*="ct-dashboard-account"],
    #adminmenu .wp-first-item a[href*="ct-dashboard"],
    #adminmenu a[href*="site-editor.php"],
    a.hide-if-no-customize,
    .ab-submenu li a[href*="options-general.php?page=translate-press"],
    #wp_mail_smtp_reports_widget_lite,
    #wp-admin-bar-litespeed-bar-manage,
    #new_admin_email,
    #new_admin_email + p.description,
    label[for="new_admin_email"] {
        display: none !important;
    }
    </style>
    <?php
}

/* =============================================
   ZAMEDZENIE ÚPRAVY ADMIN EMAIL
   ============================================= */

add_filter('pre_update_option_admin_email', 'spa_restrict_admin_email_update', 10, 2);

function spa_restrict_admin_email_update($value, $option) {
    global $current_user;
    wp_get_current_user();
    
    if ($option === 'admin_email' && 
        $current_user->user_login !== 'artefactum' && 
        !current_user_can('administrator')) {
        
        return get_option('admin_email');
    }
    
    return $value;
}

/* =============================================
   REDIRECT PO PRIHLÁSENÍ
   ============================================= */

add_filter('login_redirect', 'spa_login_redirect', 10, 3);

function spa_login_redirect($redirect_to, $request, $user) {
    
    if (!isset($user->roles) || !is_array($user->roles)) {
        return $redirect_to;
    }
    
    if (in_array('administrator', $user->roles)) {
        return admin_url();
    }
    
    if (in_array('spa_parent', $user->roles) || 
        in_array('spa_client', $user->roles) || 
        in_array('spa_trainer', $user->roles)) {
        
        return home_url('/dashboard/');
    }
    
    return $redirect_to;
}

/* =============================================
   FILTROVANIE POUŽÍVATEĽOV PRE EDITORA
   ============================================= */

add_filter('pre_get_users', 'spa_filter_users_for_editor');

function spa_filter_users_for_editor($query) {
    
    if (!is_admin() || current_user_can('administrator')) {
        return;
    }
    
    if (current_user_can('editor')) {
        $query->set('role__in', ['spa_parent', 'spa_child', 'spa_client', 'spa_trainer']);
    }
}

/* =============================================
   GLOBÁLNE NASTAVENIA
   ============================================= */

function spa_get_option($key, $default = '') {
    $options = get_option('spa_settings', []);
    return $options[$key] ?? $default;
}

function spa_update_option($key, $value) {
    $options = get_option('spa_settings', []);
    $options[$key] = $value;
    update_option('spa_settings', $options);
}

/* =============================================
   DRAG & DROP RADENIE PRE spa_group
   ============================================= */

add_filter('pre_get_posts', 'spa_group_admin_order');
function spa_group_admin_order($query) {
    if (!is_admin() || !$query->is_main_query()) {
        return;
    }
    
    if ($query->get('post_type') === 'spa_group') {
        $query->set('orderby', 'menu_order title');
        $query->set('order', 'ASC');
    }
}

/* =============================================
   ADMIN STĹPEC PORADIA
   ============================================= */

add_filter('manage_spa_group_posts_columns', 'spa_group_order_column');
function spa_group_order_column($columns) {
    $new = [];
    foreach ($columns as $key => $val) {
        if ($key === 'title') {
            $new['menu_order'] = 'Poradie';
        }
        $new[$key] = $val;
    }
    return $new;
}

add_action('manage_spa_group_posts_custom_column', 'spa_group_order_column_content', 10, 2);
function spa_group_order_column_content($column, $post_id) {
    if ($column === 'menu_order') {
        $order = get_post_field('menu_order', $post_id);
        echo '<input type="number" 
                     value="' . esc_attr($order) . '" 
                     class="spa-quick-order" 
                     data-post-id="' . $post_id . '" 
                     style="width:60px;text-align:center;">';
    }
}

/* =============================================
   AJAX: RÝCHLA ZMENA PORADIA
   ============================================= */

add_action('wp_ajax_spa_update_order', 'spa_update_order_ajax');
function spa_update_order_ajax() {
    if (!current_user_can('edit_posts')) {
        wp_die();
    }
    
    $post_id = intval($_POST['post_id'] ?? 0);
    $order = intval($_POST['order'] ?? 0);
    
    if ($post_id) {
        wp_update_post([
            'ID' => $post_id,
            'menu_order' => $order
        ]);
        echo 'OK';
    }
    
    wp_die();
}

/* =============================================
   JAVASCRIPT PRE QUICK EDIT PORADIA
   ============================================= */

add_action('admin_footer', 'spa_order_quick_edit_js');
function spa_order_quick_edit_js() {
    global $typenow;
    if ($typenow !== 'spa_group') return;
    ?>
    <script>
    jQuery(document).ready(function($){
        $('.spa-quick-order').on('change', function(){
            var postId = $(this).data('post-id');
            var order = $(this).val();
            
            $.post(ajaxurl, {
                action: 'spa_update_order',
                post_id: postId,
                order: order
            }, function(){
                $(this).css('background', '#d4edda');
                setTimeout(function(){
                    location.reload();
                }, 500);
            }.bind(this));
        });
    });
    </script>
    <?php
}

/* =============================================
   DEBUG FUNKCIE
   ============================================= */

function spa_log($message, $data = null) {
    if (!WP_DEBUG) {
        return;
    }
    
    $log = '[SPA] ' . $message;
    
    if ($data !== null) {
        $log .= ' | Data: ' . print_r($data, true);
    }
    
    error_log($log);
}