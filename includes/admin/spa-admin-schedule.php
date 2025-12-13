<?php
/**
 * SCHEDULE META BOX - Rozvrh tréningov
 * Použitie: v spa-meta-boxes.php
 * 
 * Sprievodca dni v týždni a úložisku tréningov
 */

function spa_group_schedule_meta_box($post) {
    wp_nonce_field('spa_save_group_schedule', 'spa_group_schedule_nonce');
    
    // Načítaj aktuálny rozvrh
    $schedule_days = get_post_meta($post->ID, 'spa_schedule_days', true);
    if (!is_array($schedule_days)) {
        $schedule_days = [];
    }
    
    $schedule_times = get_post_meta($post->ID, 'spa_schedule_times', true);
    if (!is_array($schedule_times)) {
        $schedule_times = [];
    }
    
    $days_of_week = [
        'mo' => '🟦 Pondelok',
        'tu' => '🟩 Utorok',
        'we' => '🟪 Streda',
        'th' => '🟨 Štvrtok',
        'fr' => '🟧 Piatok',
        'sa' => '🟥 Sobota',
        'su' => '⚪ Nedeľa'
    ];
    
    ?>
    <style>
        .spa-schedule-table { width: 100%; border-collapse: collapse; }
        .spa-schedule-table th { background: #f0f0f0; padding: 10px; text-align: left; font-weight: 600; border: 1px solid #ddd; }
        .spa-schedule-table td { padding: 10px; border: 1px solid #ddd; }
        .spa-schedule-table input { width: 100%; max-width: 120px; padding: 6px; }
        .spa-day-label { font-weight: 600; width: 150px; }
    </style>
    
    <p style="color: #666; margin-bottom: 15px;">
        <strong>💡 Nastavenia:</strong> Vyber dni v týždni a pridaj časy tréningov. 
        Prvý vybraný deň = 1x/tyždenne, prvé dva dni = 2x/tyždenne, atď.
    </p>
    
    <table class="spa-schedule-table">
        <thead>
            <tr>
                <th style="width: 150px;">Deň</th>
                <th style="width: 80px;">Zapnutý?</th>
                <th>Čas OD</th>
                <th>Čas DO</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($days_of_week as $day_key => $day_label) : ?>
                <tr>
                    <td class="spa-day-label"><?php echo $day_label; ?></td>
                    <td>
                        <input type="checkbox" 
                            name="spa_schedule_days[]" 
                            value="<?php echo esc_attr($day_key); ?>"
                            <?php echo in_array($day_key, $schedule_days) ? 'checked' : ''; ?>>
                    </td>
                    <td>
                        <input type="time" 
                            name="spa_schedule_times[<?php echo esc_attr($day_key); ?>][from]" 
                            value="<?php echo esc_attr($schedule_times[$day_key]['from'] ?? ''); ?>"
                            <?php echo in_array($day_key, $schedule_days) ? '' : 'disabled'; ?>>
                    </td>
                    <td>
                        <input type="time" 
                            name="spa_schedule_times[<?php echo esc_attr($day_key); ?>][to]" 
                            value="<?php echo esc_attr($schedule_times[$day_key]['to'] ?? ''); ?>"
                            <?php echo in_array($day_key, $schedule_days) ? '' : 'disabled'; ?>>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    
    <script>
    (function() {
        document.querySelectorAll('input[name="spa_schedule_days[]"]').forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                const day = this.value;
                const fromInput = document.querySelector(`input[name="spa_schedule_times[${day}][from]"]`);
                const toInput = document.querySelector(`input[name="spa_schedule_times[${day}][to]"]`);
                
                if (this.checked) {
                    fromInput.disabled = false;
                    toInput.disabled = false;
                } else {
                    fromInput.disabled = true;
                    toInput.disabled = true;
                }
            });
        });
    })();
    </script>
    <?php
}

// SAVE HANDLER
add_action('save_post_spa_group', 'spa_group_schedule_save', 11, 2);

function spa_group_schedule_save($post_id, $post) {
    if (!isset($_POST['spa_group_schedule_nonce']) || !wp_verify_nonce($_POST['spa_group_schedule_nonce'], 'spa_save_group_schedule')) {
        return;
    }
    
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    
    if (!current_user_can('edit_post', $post_id)) {
        return;
    }
    
    // Dni
    $schedule_days = isset($_POST['spa_schedule_days']) ? array_map('sanitize_text_field', $_POST['spa_schedule_days']) : [];
    update_post_meta($post_id, 'spa_schedule_days', $schedule_days);
    
    // Časy
    $schedule_times = [];
    if (isset($_POST['spa_schedule_times']) && is_array($_POST['spa_schedule_times'])) {
        foreach ($_POST['spa_schedule_times'] as $day => $times) {
            $schedule_times[sanitize_text_field($day)] = [
                'from' => sanitize_text_field($times['from'] ?? ''),
                'to' => sanitize_text_field($times['to'] ?? '')
            ];
        }
    }
    update_post_meta($post_id, 'spa_schedule_times', $schedule_times);
}