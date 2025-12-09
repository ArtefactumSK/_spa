<?php
/**
 * Template Name: Dashboard Rodiča
 */

// Presmeruj ak nie je prihlásený
if (!is_user_logged_in()) {
    wp_redirect(wp_login_url(get_permalink()));
    exit;
}

$current_user = wp_get_current_user();

// Kontrola roly
if (!in_array('spa_parent', $current_user->roles) && !current_user_can('administrator')) {
    wp_die('Nemáte oprávnenie na prístup k tejto stránke.');
}

get_header();
?>

<div class="spa-dashboard-wrapper">
    <div class="spa-dashboard-container">
        
        <!-- HEADER -->
        <div class="spa-dashboard-header">
            <h1>Môj účet</h1>
            <p>Vitajte, <strong><?php echo esc_html($current_user->display_name); ?></strong></p>
            <a href="<?php echo wp_logout_url(home_url()); ?>" class="spa-logout-btn">Odhlásiť sa</a>
        </div>
        
        <!-- MOJE DETI -->
        <div class="spa-dashboard-section">
            <h2>Moje deti</h2>
            
            <?php
            // Získaj všetky deti tohto rodiča
            $children = get_users([
                'role' => 'spa_child',
                'meta_key' => 'parent_id',
                'meta_value' => $current_user->ID,
                'orderby' => 'registered',
                'order' => 'ASC'
            ]);
            
            if (empty($children)) : ?>
                <p class="spa-no-children">Zatiaľ nemáte registrované žiadne deti.</p>
            <?php else : ?>
                
                <div class="spa-children-grid">
                    <?php foreach ($children as $child) : 
                        
                        $child_first_name = get_user_meta($child->ID, 'first_name', true);
                        $child_last_name = get_user_meta($child->ID, 'last_name', true);
                        $child_birthdate = get_user_meta($child->ID, 'birthdate', true);
                        
                        // Získaj aktívne registrácie
                        $registrations = get_posts([
                            'post_type' => 'spa_registration',
                            'meta_key' => 'client_user_id',
                            'meta_value' => $child->ID,
                            'post_status' => ['publish', 'pending'],
                            'posts_per_page' => -1
                        ]);
                        
                    ?>
                        <div class="spa-child-card">
                            
                            <!-- Meno dieťaťa -->
                            <h3><?php echo esc_html($child_first_name . ' ' . $child_last_name); ?></h3>
                            
                            <?php if ($child_birthdate) : 
                                $birth = new DateTime($child_birthdate);
                                $today = new DateTime();
                                $age = $today->diff($birth)->y;
                            ?>
                                <p class="child-age">Vek: <?php echo $age; ?> rokov</p>
                            <?php endif; ?>
                            
                            <!-- Registrácie -->
                            <?php if (!empty($registrations)) : ?>
                                <div class="child-registrations">
                                    <h4>Aktívne programy:</h4>
                                    <?php foreach ($registrations as $registration) : 
                                        
                                        $program_id = get_post_meta($registration->ID, 'program_id', true);
                                        $program = get_post($program_id);
                                        $status = get_post_meta($registration->ID, 'status', true);
                                        
                                        if ($program) :
                                            $places = get_the_terms($program_id, 'spa_place');
                                            $place_names = $places ? implode(', ', wp_list_pluck($places, 'name')) : '';
                                    ?>
                                        <div class="registration-item status-<?php echo esc_attr($status); ?>">
                                            <strong><?php echo esc_html($program->post_title); ?></strong><br>
                                            <small>📍 <?php echo esc_html($place_names); ?></small><br>
                                            <span class="status-badge"><?php echo spa_get_status_label($status); ?></span>
                                        </div>
                                    <?php endif; endforeach; ?>
                                </div>
                            <?php else : ?>
                                <p class="no-registrations">Žiadne aktívne registrácie</p>
                            <?php endif; ?>
                            
                            <!-- Akcie -->
                            <div class="child-actions">
                                <a href="<?php echo home_url('/registracia/?child_id=' . $child->ID); ?>" class="spa-btn-primary">
                                    + Pridať program
                                </a>
                            </div>
                            
                        </div>
                    <?php endforeach; ?>
                </div>
                
            <?php endif; ?>
            
            <!-- Tlačidlo pridať dieťa -->
            <div class="spa-add-child-section">
                <a href="<?php echo home_url('/registracia/'); ?>" class="spa-btn-add-child">
                    + Registrovať ďalšie dieťa
                </a>
            </div>
            
        </div>
        
    </div>
</div>

<style>
.spa-dashboard-wrapper {
    background: #f5f7fa;
    padding: 40px 20px;
    min-height: 60vh;
}
.spa-dashboard-container {
    max-width: 1200px;
    margin: 0 auto;
}
.spa-dashboard-header {
    background: white;
    padding: 30px;
    border-radius: 12px;
    margin-bottom: 30px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    position: relative;
}
.spa-dashboard-header h1 {
    margin: 0 0 10px 0;
    color: var(--theme-palette-color-1);
}
.spa-logout-btn {
    position: absolute;
    top: 30px;
    right: 30px;
    padding: 8px 20px;
    background: #e4002b;
    color: white;
    text-decoration: none;
    border-radius: 6px;
    font-weight: 600;
    font-size: 14px;
}
.spa-dashboard-section {
    background: white;
    padding: 30px;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
}
.spa-children-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
    gap: 24px;
    margin: 24px 0;
}
.spa-child-card {
    background: #f9fafb;
    border: 2px solid #e5e7eb;
    border-radius: 12px;
    padding: 24px;
    transition: all 0.3s;
}
.spa-child-card:hover {
    border-color: var(--theme-palette-color-1);
    box-shadow: 0 4px 12px rgba(0,114,206,0.15);
}
.spa-child-card h3 {
    margin: 0 0 8px 0;
    color: var(--theme-palette-color-3);
}
.child-age {
    color: #666;
    font-size: 14px;
    margin: 0 0 16px 0;
}
.child-registrations {
    margin: 16px 0;
}
.child-registrations h4 {
    font-size: 14px;
    color: #666;
    margin: 0 0 12px 0;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}
.registration-item {
    background: white;
    padding: 12px;
    border-radius: 8px;
    margin-bottom: 8px;
    border-left: 4px solid var(--theme-palette-color-1);
}
.status-badge {
    display: inline-block;
    padding: 4px 12px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 600;
    margin-top: 8px;
}
.status-pending .status-badge {
    background: #fef3c7;
    color: #92400e;
}
.status-approved .status-badge {
    background: #d1fae5;
    color: #065f46;
}
.child-actions {
    margin-top: 16px;
    padding-top: 16px;
    border-top: 1px solid #e5e7eb;
}
.spa-btn-primary {
    display: inline-block;
    background: var(--theme-palette-color-1);
    color: white;
    padding: 10px 20px;
    border-radius: 8px;
    text-decoration: none;
    font-weight: 600;
    transition: all 0.3s;
}
.spa-btn-primary:hover {
    background: var(--theme-palette-color-3);
    transform: translateY(-2px);
}
.spa-add-child-section {
    margin-top: 24px;
    padding-top: 24px;
    border-top: 2px dashed #e5e7eb;
    text-align: center;
}
.spa-btn-add-child {
    display: inline-block;
    background: linear-gradient(135deg, #e4002b 0%, #c40025 100%);
    color: white;
    padding: 16px 32px;
    border-radius: 10px;
    text-decoration: none;
    font-weight: 700;
    font-size: 16px;
    transition: all 0.3s;
}
.spa-btn-add-child:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(228,0,43,0.4);
}
@media (max-width: 768px) {
    .spa-children-grid {
        grid-template-columns: 1fr;
    }
    .spa-logout-btn {
        position: static;
        display: block;
        margin-top: 16px;
        text-align: center;
    }
}
</style>

<?php
get_footer();