<?php
/**
 * SPA Widgets - Homepage bannery, admin widgety
 * 
 * @package Samuel Piasecký ACADEMY
 * @version 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/* ==========================
   WIDGET: Homepage banner (dôležité oznamy)
   ========================== */

add_action('widgets_init', function() {
    register_widget('SPA_Banner_Widget');
});

class SPA_Banner_Widget extends WP_Widget {
    
    function __construct() {
        parent::__construct(
            'spa_banner_widget',
            '📢 Dôležité oznamy (Homepage)',
            ['description' => 'Pilné oznamy zobrazené na hlavnej stránke']
        );
    }
    
    // Admin formulár
    public function form($instance) {
        $active = isset($instance['active']) ? $instance['active'] : false;
        $message = isset($instance['message']) ? $instance['message'] : '';
        $type = isset($instance['type']) ? $instance['type'] : 'info';
        ?>
        
        <p>
            <input type="checkbox" 
                   id="<?php echo $this->get_field_id('active'); ?>"
                   name="<?php echo $this->get_field_name('active'); ?>"
                   <?php checked($active, true); ?>>
            <label for="<?php echo $this->get_field_id('active'); ?>">
                <strong>✅ Zobrazovať oznámenie</strong>
            </label>
        </p>
        
        <p>
            <label for="<?php echo $this->get_field_id('message'); ?>">
                <strong>Text oznámenia:</strong>
            </label>
            <textarea 
                class="widefat" 
                rows="3"
                id="<?php echo $this->get_field_id('message'); ?>"
                name="<?php echo $this->get_field_name('message'); ?>"
                placeholder="Napr: Prázdniny 24.12-6.1, tréningy nebudú"
            ><?php echo esc_textarea($message); ?></textarea>
        </p>
        
        <p>
            <label for="<?php echo $this->get_field_id('type'); ?>">
                <strong>Typ oznámenia:</strong>
            </label>
            <select class="widefat" 
                    id="<?php echo $this->get_field_id('type'); ?>"
                    name="<?php echo $this->get_field_name('type'); ?>">
                <option value="info" <?php selected($type, 'info'); ?>>ℹ️ Informácia (modrá)</option>
                <option value="warning" <?php selected($type, 'warning'); ?>>⚠️ Upozornenie (oranžová)</option>
                <option value="success" <?php selected($type, 'success'); ?>>✅ Dobrá správa (zelená)</option>
                <option value="urgent" <?php selected($type, 'urgent'); ?>>🚨 Naliehavé (červená)</option>
            </select>
        </p>
        
        <p style="background:#f0f8ff; padding:12px; border-left:4px solid var(--theme-palette-color-1); font-size:12px;">
            <strong>💡 Tip:</strong> Zaškrtni "Zobrazovať" a napíš správu. Banner sa objaví na hlavnej stránke. 
            Keď chceš banner skryť, odškrtni políčko.
        </p>
        
        <?php
    }
    
    // Uloženie
    public function update($new_instance, $old_instance) {
        $instance = [];
        $instance['active'] = isset($new_instance['active']) ? true : false;
        $instance['message'] = sanitize_textarea_field($new_instance['message']);
        $instance['type'] = sanitize_text_field($new_instance['type']);
        return $instance;
    }
    
    // Frontend zobrazenie
    public function widget($args, $instance) {
        if (empty($instance['active']) || empty($instance['message'])) {
            return;
        }
        
        $type = $instance['type'] ?? 'info';
        $message = $instance['message'];
        
        $colors = [
            'info' => ['bg' => 'var(--theme-palette-color-12)', 'border' => '#2196f3', 'icon' => 'ℹ️'],
            'warning' => ['bg' => '#fff3e0', 'border' => '#ff9800', 'icon' => '⚠️'],
            'success' => ['bg' => '#e8f5e9', 'border' => '#4caf50', 'icon' => '✅'],
            'urgent' => ['bg' => '#ffebee', 'border' => '#f44336', 'icon' => '🚨']
        ];
        
        $style = $colors[$type];
        
        echo $args['before_widget'];
        ?>
        <div class="spa-banner-alert" style="background: <?php echo $style['bg']; ?>; border-left: 5px solid <?php echo $style['border']; ?>; padding: 20px; margin: 20px auto; max-width: 1200px; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.1);">
            <div style="display: flex; align-items: center; gap: 16px;">
                <span style="font-size: 32px;"><?php echo $style['icon']; ?></span>
                <p style="margin: 0; font-size: 16px; line-height: 1.5; flex: 1;">
                    <?php echo nl2br(esc_html($message)); ?>
                </p>
            </div>
        </div>
        <?php
        echo $args['after_widget'];
    }
}