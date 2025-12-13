<?php
/**
 * SPA Registration Form - GF hooks a spracovanie registrácie
 * 
 * @package Samuel Piasecký ACADEMY
 * @subpackage Registration
 * @version 1.0.0
 * 
 * PARENT MODULES: 
 * - cpt/spa-cpt-registration.php
 * - registration/spa-registration-helpers.php
 * - registration/spa-registration-notifications.php
 * 
 * FUNCTIONS DEFINED:
 * - spa_enqueue_jquery_for_gf()
 * - spa_process_registration_form()
 * - spa_process_child_registration()
 * - spa_process_adult_registration()
 * - spa_populate_cascading_dropdowns()
 * - spa_populate_cascading_dropdowns_adult()
 * - spa_populate_place_field_form2()
 * - spa_force_populate_places_combined()
 * - spa_force_prepopulate_dropdowns()
 * - spa_auto_prepopulate_from_url()
 * + GF JavaScripts (inline)
 * 
 * HOOKS USED:
 * - wp_enqueue_scripts (jQuery)
 * - gform_after_submission (processing)
 * - gform_pre_render_* (GF form filters)
 * - gform_enqueue_scripts_* (GF JS)
 */

if (!defined('ABSPATH')) {
    exit;
}

/* ==================================================
   ENQUEUE: jQuery pre GF formuláre
   ================================================== */

add_action('wp_enqueue_scripts', 'spa_enqueue_jquery_for_gf', 5);

function spa_enqueue_jquery_for_gf() {
    if (is_page('registracia')) {
        wp_enqueue_script('jquery');
    }
}

/* ==================================================
   GRAVITY FORMS: Hook na spracovanie registrácie
   ================================================== */

add_action('gform_after_submission', 'spa_process_registration_form', 10, 2);

function spa_process_registration_form($entry, $form) {
    
    // Zisti ID formulára
    // Pre detské registrácie: form_id = 1
    // Pre dospelých: form_id = 2
    
    if ($form['id'] == 1) {
        spa_process_child_registration($entry, $form);
    } elseif ($form['id'] == 2) {
        spa_process_adult_registration($entry, $form);
    }
}

/* ==================================================
   FUNKCIA: Registrácia dieťaťa
   ================================================== */

function spa_process_child_registration($entry, $form) {
    
    // === MAPOVANIE POLÍ Z GF ===
    $child_first_name = rgar($entry, '1.3');
    $child_last_name = rgar($entry, '1.6');
    $child_birthdate = rgar($entry, '3');
    $child_rodne_cislo = rgar($entry, '12');
    
    $selected_place = rgar($entry, '5');
    $program_id = rgar($entry, '4');
    
    $parent_first_name = rgar($entry, '6.3');
    $parent_last_name = rgar($entry, '6.6');
    $parent_email = rgar($entry, '8');
    $parent_phone = rgar($entry, '9');
    
    $address_street = rgar($entry, '13.1');
    $address_psc = rgar($entry, '13.5');
    $address_city = rgar($entry, '13.3');
    
    $health_notes = rgar($entry, '10');
    $gdpr_consent = rgar($entry, '11');
    
    // === VALIDÁCIA ===
    if (empty($child_first_name) || empty($parent_email) || empty($program_id)) {
        spa_log('Registration failed: missing required fields', $entry);
        return;
    }
    
    if (empty($child_rodne_cislo)) {
        spa_log('Registration warning: missing rodne_cislo', $entry);
    }
    
    // === VYTVORENIE KONT ===
    $parent_user_id = spa_get_or_create_parent(
        $parent_email, 
        $parent_first_name, 
        $parent_last_name, 
        $parent_phone,
        $address_street,
        $address_psc,
        $address_city
    );
    
    if (!$parent_user_id) {
        spa_log('Failed to create parent account', ['email' => $parent_email]);
        return;
    }
    
    $child_user_id = spa_create_child_account(
        $child_first_name, 
        $child_last_name, 
        $child_birthdate, 
        $parent_user_id,
        $health_notes,
        $child_rodne_cislo
    );
    
    if (!$child_user_id) {
        spa_log('Failed to create child account', ['name' => $child_first_name]);
        return;
    }
    
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
    
    // === NOTIFIKÁCIE ===
    spa_notify_admin_new_registration($registration_id, $parent_email);
    spa_send_registration_confirmation($parent_email, $child_first_name, $program_id);
    
    $vs = get_user_meta($child_user_id, 'variabilny_symbol', true);
    
    spa_log('Registration created successfully', [
        'registration_id' => $registration_id,
        'parent' => $parent_user_id,
        'child' => $child_user_id,
        'variabilny_symbol' => $vs
    ]);
}

/* ==================================================
   FUNKCIA: Registrácia dospelého
   ================================================== */

function spa_process_adult_registration($entry, $form) {
    
    $first_name = rgar($entry, '1.3');
    $last_name = rgar($entry, '1.6');
    $email = rgar($entry, '3');
    $phone = rgar($entry, '4');
    $birthdate = rgar($entry, '5');
    $program_id = rgar($entry, '6');
    $health_notes = rgar($entry, '7');
    
    if (empty($first_name) || empty($email) || empty($program_id)) {
        return;
    }
    
    $client_user_id = spa_get_or_create_client($email, $first_name, $last_name, $phone, $birthdate);
    
    if (!$client_user_id) {
        return;
    }
    
    $registration_id = spa_create_registration(
        $client_user_id,
        $program_id,
        null,
        $entry['id']
    );
    
    if (!$registration_id) {
        return;
    }
    
    if ($health_notes) {
        update_user_meta($client_user_id, 'health_notes', sanitize_textarea_field($health_notes));
    }
    
    spa_notify_admin_new_registration($registration_id, $email);
    spa_send_registration_confirmation($email, $first_name, $program_id);
}

/* ==================================================
   GF FILTER: Kaskádový dropdown (Form 1 - Deti)
   ================================================== */

add_filter('gform_pre_render_1', 'spa_populate_cascading_dropdowns');
add_filter('gform_pre_validation_1', 'spa_populate_cascading_dropdowns');
add_filter('gform_pre_submission_filter_1', 'spa_populate_cascading_dropdowns');
add_filter('gform_admin_pre_render_1', 'spa_populate_cascading_dropdowns');

function spa_populate_cascading_dropdowns($form) {
    
    foreach ($form['fields'] as &$field) {
        
        if ($field->id == 4) {
            
            $selected_place = isset($_POST['input_5']) ? sanitize_text_field($_POST['input_5']) : '';
            
            if (empty($selected_place)) {
                $field->choices = [
                    ['text' => '-- Najprv vyberte miesto --', 'value' => '']
                ];
                continue;
            }
            
            $place_slugs = array_map('trim', explode(',', $selected_place));
            
            $programs = get_posts([
                'post_type' => 'spa_group',
                'posts_per_page' => -1,
                'orderby' => 'menu_order title',
                'order' => 'ASC',
                'post_status' => 'publish',
                'tax_query' => [
                    'relation' => 'AND',
                    ['taxonomy' => 'spa_place', 'field' => 'slug', 'terms' => $place_slugs]
                ]
            ]);
            
            $field->choices = [['text' => '-- Vyberte program --', 'value' => '', 'isSelected' => false]];
            
            foreach ($programs as $program) {
                $categories = get_the_terms($program->ID, 'spa_group_category');
                $price = get_post_meta($program->ID, 'spa_price', true);
                $category_name = $categories ? $categories[0]->name : '';
                
                $text = $program->post_title;
                if ($category_name) $text .= ' (' . $category_name . ')';
                if ($price) $text .= ' | ' . number_format($price, 2, ',', ' ') . ' €';
                
                $field->choices[] = ['text' => $text, 'value' => $program->ID, 'isSelected' => false];
            }
            
            if (count($field->choices) == 1) {
                $field->choices[] = ['text' => 'V tomto mieste nie sú žiadne programy', 'value' => '', 'isSelected' => false];
            }
        }
    }
    
    return $form;
}

/* ==================================================
   GF FILTER: Kaskádový dropdown (Form 2 - Dospelí)
   ================================================== */

add_filter('gform_pre_render_2', 'spa_populate_cascading_dropdowns_adult');
add_filter('gform_pre_validation_2', 'spa_populate_cascading_dropdowns_adult');
add_filter('gform_pre_submission_filter_2', 'spa_populate_cascading_dropdowns_adult');
add_filter('gform_admin_pre_render_2', 'spa_populate_cascading_dropdowns_adult');

function spa_populate_cascading_dropdowns_adult($form) {
    
    foreach ($form['fields'] as &$field) {
        
        if ($field->id == 5) {
            
            $selected_place = isset($_POST['input_4']) ? sanitize_text_field($_POST['input_4']) : '';
            
            if (empty($selected_place)) {
                $field->choices = [['text' => '-- Najprv vyberte miesto --', 'value' => '']];
                continue;
            }
            
            $programs = get_posts([
                'post_type' => 'spa_group',
                'posts_per_page' => -1,
                'orderby' => 'menu_order title',
                'order' => 'ASC',
                'post_status' => 'publish',
                'tax_query' => [['taxonomy' => 'spa_place', 'field' => 'slug', 'terms' => $selected_place]]
            ]);
            
            $field->choices = [['text' => '-- Vyberte program --', 'value' => '']];
            
            foreach ($programs as $program) {
                $categories = get_the_terms($program->ID, 'spa_group_category');
                $price = get_post_meta($program->ID, 'spa_price', true);
                $category_name = $categories ? $categories[0]->name : '';
                
                $text = $program->post_title;
                if ($category_name) $text .= ' (' . $category_name . ')';
                if ($price) $text .= ' | ' . number_format($price, 2, ',', ' ') . ' €';
                
                $field->choices[] = ['text' => $text, 'value' => $program->ID];
            }
            
            if (count($field->choices) == 1) {
                $field->choices[] = ['text' => 'V tomto mieste nie sú žiadne programy', 'value' => ''];
            }
        }
    }
    
    return $form;
}

/* ==================================================
   GF FILTER: Naplnenie miest (kombinované)
   ================================================== */

add_filter('gform_pre_render_1', 'spa_force_populate_places_combined', 1);
add_filter('gform_pre_validation_1', 'spa_force_populate_places_combined', 1);
add_filter('gform_admin_pre_render_1', 'spa_force_populate_places_combined', 1);

function spa_force_populate_places_combined($form) {
    
    $programs = get_posts([
        'post_type' => 'spa_group',
        'posts_per_page' => -1,
        'post_status' => 'publish'
    ]);
    
    $unique_places = [];
    
    foreach ($programs as $program) {
        $places = get_the_terms($program->ID, 'spa_place');
        
        if ($places && !is_wp_error($places)) {
            usort($places, function($a, $b) { return strcmp($a->name, $b->name); });
            
            $place_names = array_map(function($term) { return $term->name; }, $places);
            $combined_name = implode(', ', $place_names);
            
            $place_slugs = array_map(function($term) { return $term->slug; }, $places);
            sort($place_slugs);
            $combined_slug = implode(',', $place_slugs);
            
            if (!isset($unique_places[$combined_slug])) {
                $unique_places[$combined_slug] = $combined_name;
            }
        }
    }
    
    foreach ($form['fields'] as &$field) {
        if ($field->id == 5) {
            $field->choices = [['text' => '-- Vyberte miesto --', 'value' => '', 'isSelected' => false]];
            
            foreach ($unique_places as $slug => $name) {
                $field->choices[] = ['text' => $name, 'value' => $slug, 'isSelected' => false];
            }
            break;
        }
    }
    
    return $form;
}

/* ==================================================
   GF SCRIPT: Auto-prepopulate z URL parametrov
   ================================================== */

add_action('gform_enqueue_scripts_1', 'spa_auto_prepopulate_from_url', 30, 2);

function spa_auto_prepopulate_from_url($form, $is_ajax) {
    
    if (!isset($_GET['place']) && !isset($_GET['program_id'])) {
        return;
    }
    
    $place = isset($_GET['place']) ? sanitize_text_field($_GET['place']) : '';
    $program_id = isset($_GET['program_id']) ? intval($_GET['program_id']) : 0;
    
    ?>
    <script type="text/javascript">
    (function($) {
        $(document).ready(function() {
            
            setTimeout(function() {
                
                var placeValue = '<?php echo $place; ?>';
                if (placeValue) {
                    var $placeField = $('#input_1_5');
                    $placeField.val(placeValue);
                    $placeField.css({'border': '2px solid #80EF80', 'background': '#E2F9E0'});
                    $placeField.trigger('change');
                    
                    setTimeout(function() {
                        
                        var programId = '<?php echo $program_id; ?>';
                        if (programId && programId !== '0') {
                            var $programField = $('#input_1_4');
                            $programField.val(programId);
                            $programField.css({'border': '2px solid #80EF80', 'background': '#E2F9E0'});
                            
                            var programName = $programField.find('option:selected').text();
                            
                            if (programName && programName !== '-- Vyberte program --') {
                                $('<div class="spa-success-notice" style="background: linear-gradient(135deg, #DDFCDB 0%, #9AED9A 100%); border-left: 5px solid #80EF80; padding: 25px; margin: 20px 0; border-radius: 12px;">' +
                                  '<div style="display: flex; gap: 20px;">' +
                                  '<div style="font-size: 48px;">✅</div>' +
                                  '<div><h3 style="margin: 0 0 8px 0; color: #155724; font-weight: 700;">Vybraný program</h3>' +
                                  '<p style="margin: 0; color: #155724;">' + programName + '</p></div>' +
                                  '</div></div>').prependTo('.gform_wrapper');
                                
                                $('html, body').animate({scrollTop: $('.gform_wrapper').offset().top - 100}, 600);
                            }
                        }
                        
                    }, 2500);
                }
                
            }, 1000);
            
        });
    })(jQuery);
    </script>
    <?php
}

/* ==================================================
   GF SCRIPT: Ukazovanie/skrývanie rodiča (18+ check)
   ================================================== */

add_action('gform_enqueue_scripts_1', 'spa_conditional_parent_section', 50, 2);

function spa_conditional_parent_section($form, $is_ajax) {
    ?>
    <script type="text/javascript">
    (function($) {
        $(document).ready(function() {
            
            var $birthdateField = $('#input_1_3');
            var parentFields = ['#field_1_6', '#field_1_7'];
            
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
            
            function toggleParentFields() {
                var birthdate = $birthdateField.val();
                
                if (!birthdate) {
                    $.each(parentFields, function(i, selector) {
                        $(selector).show().find('input, select, textarea').prop('disabled', false);
                    });
                    return;
                }
                
                var age = calculateAge(birthdate);
                
                if (age >= 18) {
                    $.each(parentFields, function(i, selector) {
                        $(selector).hide().find('input, select, textarea').prop('disabled', true);
                    });
                    $('label[for="input_1_1"]').html('Meno a priezvisko <span class="gfield_required">*</span>');
                } else {
                    $.each(parentFields, function(i, selector) {
                        $(selector).show().find('input, select, textarea').prop('disabled', false);
                    });
                    $('label[for="input_1_1"]').html('Meno a priezvisko dieťaťa <span class="gfield_required">*</span>');
                }
            }
            
            $birthdateField.on('change blur', toggleParentFields);
            setTimeout(toggleParentFields, 500);
            
        });
    })(jQuery);
    </script>
    <?php
}

/* ==================================================
   AJAX: Načítanie programov (async)
   ================================================== */

add_action('wp_ajax_spa_get_programs_by_place', 'spa_ajax_get_programs_by_place');
add_action('wp_ajax_nopriv_spa_get_programs_by_place', 'spa_ajax_get_programs_by_place');

function spa_ajax_get_programs_by_place() {
    
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'spa_ajax_nonce')) {
        wp_send_json_error(['message' => 'Security check failed']);
    }
    
    $place = sanitize_text_field($_POST['place'] ?? '');
    
    if (empty($place)) {
        wp_send_json_error(['message' => 'No place selected']);
    }
    
    $place_slugs = array_map('trim', explode(',', $place));
    
    $args = [
        'post_type' => 'spa_group',
        'posts_per_page' => -1,
        'orderby' => 'menu_order title',
        'order' => 'ASC',
        'post_status' => 'publish',
        'tax_query' => ['relation' => 'AND']
    ];
    
    foreach ($place_slugs as $slug) {
        $args['tax_query'][] = [
            'taxonomy' => 'spa_place',
            'field' => 'slug',
            'terms' => $slug
        ];
    }
    
    $programs = get_posts($args);
    $programs_data = [];
    
    foreach ($programs as $program) {
        $categories = get_the_terms($program->ID, 'spa_group_category');
        $price = get_post_meta($program->ID, 'spa_price', true);
        $category_name = $categories ? $categories[0]->name : '';
        
        $text = $program->post_title;
        if ($category_name) $text .= ' (' . $category_name . ')';
        if ($price) $text .= ' | ' . number_format($price, 2, ',', ' ') . ' €';
        
        $programs_data[] = ['id' => $program->ID, 'text' => $text];
    }
    
    wp_send_json_success(['programs' => $programs_data]);
}

/* ==================================================
   GF SCRIPT: Cascading dropdown via AJAX
   ================================================== */

add_action('gform_enqueue_scripts_1', 'spa_enqueue_cascading_script_form1', 10, 2);

function spa_enqueue_cascading_script_form1($form, $is_ajax) {
    ?>
    <script type="text/javascript">
    (function($) {
        $(document).ready(function() {
            
            $('#input_1_5').on('change', function() {
                
                var selectedPlace = $(this).val();
                var $programField = $('#input_1_4');
                
                if (!selectedPlace) {
                    $programField.html('<option value="">-- Vyberte miesto --</option>');
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
                        
                        if (response.success) {
                            var options = '<option value="">-- Vyberte program --</option>';
                            
                            if (response.data.programs.length === 0) {
                                options += '<option value="">V tomto mieste nie sú programy</option>';
                            } else {
                                $.each(response.data.programs, function(i, program) {
                                    options += '<option value="' + program.id + '">' + program.text + '</option>';
                                });
                            }
                            
                            $programField.html(options);
                        }
                    },
                    error: function() {
                        $programField.html('<option value="">Chyba načítania</option>');
                    }
                });
            });
        });
    })(jQuery);
    </script>
    <?php
}