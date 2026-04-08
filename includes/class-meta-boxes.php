<?php
/**
 * Admin Meta Boxes
 */

class VET_CPD_Meta_Boxes {
    
    public static function init() {
        add_action('add_meta_boxes', [__CLASS__, 'add_meta_boxes']);
        add_action('save_post', [__CLASS__, 'save_meta'], 10, 2);
        
        // Remove default tags meta box and add custom one
        add_action('add_meta_boxes', [__CLASS__, 'remove_default_tags_box'], 20);
        
        // Enqueue admin assets
        add_action('admin_enqueue_scripts', [__CLASS__, 'enqueue_assets']);
    }
    
    /**
     * Remove default tags meta box for CPD events
     */
    public static function remove_default_tags_box() {
        remove_meta_box('tagsdiv-cpd_tag', VET_CPD_CPD::POST_TYPE, 'side');
    }
    
    /**
     * Add meta boxes
     */
    public static function add_meta_boxes() {
        // CPD Event meta boxes
        add_meta_box(
            'cpd_event_details',
            __('CPD Details', 'vet-cpd-directory'),
            [__CLASS__, 'render_cpd_details'],
            VET_CPD_CPD::POST_TYPE,
            'normal',
            'high'
        );
        
        add_meta_box(
            'cpd_event_location',
            __('Location & Venues', 'vet-cpd-directory'),
            [__CLASS__, 'render_cpd_location'],
            VET_CPD_CPD::POST_TYPE,
            'normal',
            'high'
        );
        
        add_meta_box(
            'cpd_event_people',
            __('Organisers & Instructors', 'vet-cpd-directory'),
            [__CLASS__, 'render_cpd_people'],
            VET_CPD_CPD::POST_TYPE,
            'normal',
            'high'
        );
        
        add_meta_box(
            'cpd_event_series',
            __('Series', 'vet-cpd-directory'),
            [__CLASS__, 'render_cpd_series'],
            VET_CPD_CPD::POST_TYPE,
            'side',
            'default'
        );
        
        // Custom Tags meta box (checkboxes instead of default input)
        add_meta_box(
            'cpd_event_tags',
            __('Tags', 'vet-cpd-directory'),
            [__CLASS__, 'render_cpd_tags'],
            VET_CPD_CPD::POST_TYPE,
            'side',
            'default'
        );
        
        // Venue meta box
        add_meta_box(
            'cpd_venue_details',
            __('Venue Details', 'vet-cpd-directory'),
            [__CLASS__, 'render_venue_details'],
            VET_CPD_Venue::POST_TYPE,
            'normal',
            'high'
        );
        
        // Organiser meta box
        add_meta_box(
            'cpd_organiser_details',
            __('Organiser Details', 'vet-cpd-directory'),
            [__CLASS__, 'render_organiser_details'],
            VET_CPD_Organiser::POST_TYPE,
            'normal',
            'high'
        );
        
        // Instructor meta box
        add_meta_box(
            'cpd_instructor_details',
            __('Instructor Details', 'vet-cpd-directory'),
            [__CLASS__, 'render_instructor_details'],
            VET_CPD_Instructor::POST_TYPE,
            'normal',
            'high'
        );
    }
    
    /**
     * Render CPD Details meta box
     */
    public static function render_cpd_details($post) {
        wp_nonce_field('cpd_save_meta', 'cpd_meta_nonce');
        
        $provider_url = VET_CPD_CPD::get_meta($post->ID, '_cpd_provider_url');
        $start_date = VET_CPD_CPD::get_meta($post->ID, '_cpd_start_date');
        $end_date = VET_CPD_CPD::get_meta($post->ID, '_cpd_end_date');
        $all_day = VET_CPD_CPD::get_meta($post->ID, '_cpd_all_day');
        $cost = VET_CPD_CPD::get_meta($post->ID, '_cpd_cost');
        $currency = VET_CPD_CPD::get_meta($post->ID, '_cpd_currency');
        
        $currencies = [
            'GBP' => 'GBP (£)',
            'USD' => 'USD ($)',
            'EUR' => 'EUR (€)',
            'AUD' => 'AUD ($)',
            'CAD' => 'CAD ($)',
            'NZD' => 'NZD ($)',
        ];
        ?>
        <table class="form-table">
            <tr>
                <th><label for="_cpd_provider_url"><?php _e('Event URL', 'vet-cpd-directory'); ?></label></th>
                <td>
                    <input type="url" id="_cpd_provider_url" name="_cpd_provider_url" 
                           value="<?php echo esc_url($provider_url); ?>" class="widefat">
                    <p class="description"><?php _e('External link to CPD provider (affiliate link)', 'vet-cpd-directory'); ?></p>
                </td>
            </tr>
            <tr>
                <th><label for="_cpd_start_date"><?php _e('Start Date & Time', 'vet-cpd-directory'); ?></label></th>
                <td>
                    <input type="datetime-local" id="_cpd_start_date" name="_cpd_start_date" 
                           value="<?php echo esc_attr($start_date); ?>" class="regular-text">
                </td>
            </tr>
            <tr>
                <th><label for="_cpd_end_date"><?php _e('End Date & Time', 'vet-cpd-directory'); ?></label></th>
                <td>
                    <input type="datetime-local" id="_cpd_end_date" name="_cpd_end_date" 
                           value="<?php echo esc_attr($end_date); ?>" class="regular-text">
                </td>
            </tr>
            <tr>
                <th><label for="_cpd_all_day"><?php _e('All Day Event', 'vet-cpd-directory'); ?></label></th>
                <td>
                    <input type="checkbox" id="_cpd_all_day" name="_cpd_all_day" value="1" <?php checked($all_day, '1'); ?>>
                </td>
            </tr>
            <tr>
                <th><label for="_cpd_cost"><?php _e('Cost', 'vet-cpd-directory'); ?></label></th>
                <td>
                    <input type="number" id="_cpd_cost" name="_cpd_cost" step="0.01" min="0"
                           value="<?php echo esc_attr($cost); ?>" class="small-text">
                    <select name="_cpd_currency" style="margin-left: 10px;">
                        <?php foreach ($currencies as $code => $label) : ?>
                            <option value="<?php echo esc_attr($code); ?>" <?php selected($currency, $code); ?>>
                                <?php echo esc_html($label); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <p class="description"><?php _e('Leave blank or 0 for free CPDs', 'vet-cpd-directory'); ?></p>
                </td>
            </tr>
        </table>
        <?php
    }
    
    /**
     * Render CPD Location meta box
     */
    public static function render_cpd_location($post) {
        $venues = VET_CPD_CPD::get_meta($post->ID, '_cpd_venues');
        $show_map = VET_CPD_CPD::get_meta($post->ID, '_cpd_show_map');
        $show_map_link = VET_CPD_CPD::get_meta($post->ID, '_cpd_show_map_link');
        
        $all_venues = VET_CPD_Venue::get_all();
        ?>
        <table class="form-table">
            <tr>
                <th><?php _e('Venues', 'vet-cpd-directory'); ?></th>
                <td>
                    <div id="cpd-venues-container">
                        <?php 
                        $venue_array = (array)$venues;
                        if (empty($venue_array)) $venue_array = [''];
                        foreach ($venue_array as $index => $venue_id) : 
                        ?>
                            <div class="cpd-venue-row" style="margin-bottom: 8px;">
                                <select name="_cpd_venues[]" style="width: 70%;">
                                    <option value=""><?php _e('-- Select Venue --', 'vet-cpd-directory'); ?></option>
                                    <?php foreach ($all_venues as $venue) : ?>
                                        <option value="<?php echo $venue->ID; ?>" <?php selected($venue_id, $venue->ID); ?>>
                                            <?php echo esc_html($venue->post_title); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <?php if ($index > 0 || count($venue_array) > 1) : ?>
                                    <a href="#" class="cpd-remove-venue" style="margin-left: 10px; color: #a00;"><?php _e('Remove', 'vet-cpd-directory'); ?></a>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <p><a href="#" id="cpd-add-venue" class="button"><?php _e('+ Add another venue', 'vet-cpd-directory'); ?></a></p>
                </td>
            </tr>
            <tr>
                <th><?php _e('Map Options', 'vet-cpd-directory'); ?></th>
                <td>
                    <label>
                        <input type="checkbox" name="_cpd_show_map" value="1" <?php checked($show_map, '1'); ?>>
                        <?php _e('Show map', 'vet-cpd-directory'); ?>
                    </label><br>
                    <label>
                        <input type="checkbox" name="_cpd_show_map_link" value="1" <?php checked($show_map_link, '1'); ?>>
                        <?php _e('Show map link', 'vet-cpd-directory'); ?>
                    </label>
                </td>
            </tr>
        </table>
        
        <script>
        jQuery(document).ready(function($) {
            $('#cpd-add-venue').on('click', function(e) {
                e.preventDefault();
                var row = $('.cpd-venue-row:first').clone();
                row.find('select').val('');
                row.find('.cpd-remove-venue').remove();
                row.append('<a href="#" class="cpd-remove-venue" style="margin-left: 10px; color: #a00;"><?php _e('Remove', 'vet-cpd-directory'); ?></a>');
                $('#cpd-venues-container').append(row);
            });
            
            $(document).on('click', '.cpd-remove-venue', function(e) {
                e.preventDefault();
                $(this).closest('.cpd-venue-row').remove();
            });
        });
        </script>
        <?php
    }
    
    /**
     * Render CPD People meta box
     */
    public static function render_cpd_people($post) {
        $organisers = VET_CPD_CPD::get_meta($post->ID, '_cpd_organisers');
        $instructors = VET_CPD_CPD::get_meta($post->ID, '_cpd_instructors');
        
        $all_organisers = VET_CPD_Organiser::get_all();
        $all_instructors = VET_CPD_Instructor::get_all();
        ?>
        <table class="form-table">
            <tr>
                <th><?php _e('Organisers', 'vet-cpd-directory'); ?></th>
                <td>
                    <div id="cpd-organisers-container">
                        <?php 
                        $organiser_array = (array)$organisers;
                        if (empty($organiser_array)) $organiser_array = [''];
                        foreach ($organiser_array as $index => $organiser_id) : 
                        ?>
                            <div class="cpd-organiser-row" style="margin-bottom: 8px;">
                                <select name="_cpd_organisers[]" style="width: 70%;">
                                    <option value=""><?php _e('-- Select Organiser --', 'vet-cpd-directory'); ?></option>
                                    <?php foreach ($all_organisers as $organiser) : ?>
                                        <option value="<?php echo $organiser->ID; ?>" <?php selected($organiser_id, $organiser->ID); ?>>
                                            <?php echo esc_html($organiser->post_title); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <?php if ($index > 0 || count($organiser_array) > 1) : ?>
                                    <a href="#" class="cpd-remove-organiser" style="margin-left: 10px; color: #a00;"><?php _e('Remove', 'vet-cpd-directory'); ?></a>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <p><a href="#" id="cpd-add-organiser" class="button"><?php _e('+ Add another organiser', 'vet-cpd-directory'); ?></a></p>
                </td>
            </tr>
            <tr>
                <th><?php _e('Instructors', 'vet-cpd-directory'); ?></th>
                <td>
                    <div id="cpd-instructors-container">
                        <?php 
                        $instructor_array = (array)$instructors;
                        if (empty($instructor_array)) $instructor_array = [''];
                        foreach ($instructor_array as $index => $instructor_id) : 
                        ?>
                            <div class="cpd-instructor-row" style="margin-bottom: 8px;">
                                <select name="_cpd_instructors[]" style="width: 70%;">
                                    <option value=""><?php _e('-- Select Instructor --', 'vet-cpd-directory'); ?></option>
                                    <?php foreach ($all_instructors as $instructor) : ?>
                                        <option value="<?php echo $instructor->ID; ?>" <?php selected($instructor_id, $instructor->ID); ?>>
                                            <?php echo esc_html($instructor->post_title); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <?php if ($index > 0 || count($instructor_array) > 1) : ?>
                                    <a href="#" class="cpd-remove-instructor" style="margin-left: 10px; color: #a00;"><?php _e('Remove', 'vet-cpd-directory'); ?></a>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <p><a href="#" id="cpd-add-instructor" class="button"><?php _e('+ Add another instructor', 'vet-cpd-directory'); ?></a></p>
                </td>
            </tr>
        </table>
        
        <script>
        jQuery(document).ready(function($) {
            // Organisers
            $('#cpd-add-organiser').on('click', function(e) {
                e.preventDefault();
                var row = $('.cpd-organiser-row:first').clone();
                row.find('select').val('');
                row.find('.cpd-remove-organiser').remove();
                row.append('<a href="#" class="cpd-remove-organiser" style="margin-left: 10px; color: #a00;"><?php _e('Remove', 'vet-cpd-directory'); ?></a>');
                $('#cpd-organisers-container').append(row);
            });
            
            $(document).on('click', '.cpd-remove-organiser', function(e) {
                e.preventDefault();
                $(this).closest('.cpd-organiser-row').remove();
            });
            
            // Instructors
            $('#cpd-add-instructor').on('click', function(e) {
                e.preventDefault();
                var row = $('.cpd-instructor-row:first').clone();
                row.find('select').val('');
                row.find('.cpd-remove-instructor').remove();
                row.append('<a href="#" class="cpd-remove-instructor" style="margin-left: 10px; color: #a00;"><?php _e('Remove', 'vet-cpd-directory'); ?></a>');
                $('#cpd-instructors-container').append(row);
            });
            
            $(document).on('click', '.cpd-remove-instructor', function(e) {
                e.preventDefault();
                $(this).closest('.cpd-instructor-row').remove();
            });
        });
        </script>
        <?php
    }
    
    /**
     * Render CPD Series meta box
     */
    public static function render_cpd_series($post) {
        $series = VET_CPD_CPD::get_meta($post->ID, '_cpd_series');
        $series_order = VET_CPD_CPD::get_meta($post->ID, '_cpd_series_order');
        
        $all_series = get_posts([
            'post_type'      => VET_CPD_Series::POST_TYPE,
            'posts_per_page' => -1,
            'orderby'        => 'title',
            'order'          => 'ASC',
        ]);
        ?>
        <p>
            <label for="_cpd_series"><?php _e('Series', 'vet-cpd-directory'); ?></label><br>
            <select id="_cpd_series" name="_cpd_series" style="width: 100%;">
                <option value=""><?php _e('-- Not part of a series --', 'vet-cpd-directory'); ?></option>
                <?php foreach ($all_series as $s) : ?>
                    <option value="<?php echo $s->ID; ?>" <?php selected($series, $s->ID); ?>>
                        <?php echo esc_html($s->post_title); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </p>
        <p>
            <label for="_cpd_series_order"><?php _e('Order in Series', 'vet-cpd-directory'); ?></label><br>
            <input type="number" id="_cpd_series_order" name="_cpd_series_order" 
                   value="<?php echo esc_attr($series_order); ?>" class="small-text" min="1">
        </p>
        <?php
    }
    
    /**
     * Render CPD Tags meta box (checkboxes like categories)
     */
    public static function render_cpd_tags($post) {
        // Get all tags
        $all_tags = get_terms([
            'taxonomy'   => VET_CPD_Taxonomies::TAG,
            'hide_empty' => false,
        ]);
        
        // Get assigned tags
        $assigned_tag_ids = wp_get_post_terms($post->ID, VET_CPD_Taxonomies::TAG, ['fields' => 'ids']);
        
        // Separate system tags and custom tags
        $system_tags = ['upcoming', 'on-demand', 'online', 'free', 'physical-event'];
        $system_tag_objs = [];
        $custom_tag_objs = [];
        
        foreach ($all_tags as $tag) {
            if (in_array($tag->slug, $system_tags)) {
                $system_tag_objs[] = $tag;
            } else {
                $custom_tag_objs[] = $tag;
            }
        }
        
        wp_nonce_field('cpd_save_tags', 'cpd_tags_nonce');
        ?>
        <style>
            .cpd-tags-section { margin-bottom: 15px; }
            .cpd-tags-section h4 { margin: 0 0 8px; font-size: 12px; color: #555; }
            .cpd-tags-list { max-height: 120px; overflow-y: auto; background: #f9f9f9; padding: 8px; border: 1px solid #ddd; }
            .cpd-tag-item { margin-bottom: 4px; }
            .cpd-tag-item label { display: block; padding: 2px 0; }
            .cpd-tag-item input[type="checkbox"] { margin-right: 6px; }
            .cpd-system-tags { background: #f0f6fc; border-color: #c5d9ed; }
            .cpd-tag-info { font-size: 11px; color: #666; font-style: italic; margin-top: 4px; }
        </style>
        
        <div class="cpd-tags-section">
            <h4><?php _e('System Tags (auto-managed)', 'vet-cpd-directory'); ?></h4>
            <div class="cpd-tags-list cpd-system-tags">
                <?php foreach ($system_tag_objs as $tag) : ?>
                    <div class="cpd-tag-item">
                        <label>
                            <input type="checkbox" name="cpd_tags[]" value="<?php echo esc_attr($tag->term_id); ?>" 
                                <?php checked(in_array($tag->term_id, $assigned_tag_ids)); ?>>
                            <?php echo esc_html($tag->name); ?>
                            <?php if ($tag->slug === 'upcoming' || $tag->slug === 'on-demand') : ?>
                                <span class="cpd-tag-info">(auto-applied)</span>
                            <?php endif; ?>
                        </label>
                    </div>
                <?php endforeach; ?>
                <?php if (empty($system_tag_objs)) : ?>
                    <em><?php _e('System tags not created yet.', 'vet-cpd-directory'); ?></em>
                <?php endif; ?>
            </div>
            <p class="cpd-tag-info">
                <?php _e('Note: "upcoming" and "on-demand" are automatically managed based on event dates.', 'vet-cpd-directory'); ?>
            </p>
        </div>
        
        <div class="cpd-tags-section">
            <h4><?php _e('Custom Tags', 'vet-cpd-directory'); ?></h4>
            <div class="cpd-tags-list">
                <?php foreach ($custom_tag_objs as $tag) : ?>
                    <div class="cpd-tag-item">
                        <label>
                            <input type="checkbox" name="cpd_tags[]" value="<?php echo esc_attr($tag->term_id); ?>" 
                                <?php checked(in_array($tag->term_id, $assigned_tag_ids)); ?>>
                            <?php echo esc_html($tag->name); ?>
                        </label>
                    </div>
                <?php endforeach; ?>
                <?php if (empty($custom_tag_objs)) : ?>
                    <em><?php _e('No custom tags created yet.', 'vet-cpd-directory'); ?></em>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="cpd-tags-section">
            <h4><?php _e('Add New Tag', 'vet-cpd-directory'); ?></h4>
            <input type="text" id="cpd_new_tag" name="cpd_new_tag" style="width: 100%;" placeholder="<?php esc_attr_e('Enter tag name...', 'vet-cpd-directory'); ?>">
            <p class="cpd-tag-info">
                <?php _e('Enter a new tag name above and it will be added when you save.', 'vet-cpd-directory'); ?>
            </p>
        </div>
        <?php
    }
    
    /**
     * Render Venue Details meta box
     */
    public static function render_venue_details($post) {
        $fields = VET_CPD_Venue::get_meta_fields();
        $values = [];
        foreach ($fields as $key => $default) {
            $values[$key] = VET_CPD_Venue::get_meta($post->ID, $key);
        }
        ?>
        <table class="form-table">
            <tr>
                <th><label for="_venue_address"><?php _e('Address', 'vet-cpd-directory'); ?></label></th>
                <td><input type="text" id="_venue_address" name="_venue_address" value="<?php echo esc_attr($values['_venue_address']); ?>" class="widefat"></td>
            </tr>
            <tr>
                <th><label for="_venue_city"><?php _e('City', 'vet-cpd-directory'); ?></label></th>
                <td><input type="text" id="_venue_city" name="_venue_city" value="<?php echo esc_attr($values['_venue_city']); ?>" class="regular-text"></td>
            </tr>
            <tr>
                <th><label for="_venue_state"><?php _e('State/Province', 'vet-cpd-directory'); ?></label></th>
                <td><input type="text" id="_venue_state" name="_venue_state" value="<?php echo esc_attr($values['_venue_state']); ?>" class="regular-text"></td>
            </tr>
            <tr>
                <th><label for="_venue_postal_code"><?php _e('Postal Code', 'vet-cpd-directory'); ?></label></th>
                <td><input type="text" id="_venue_postal_code" name="_venue_postal_code" value="<?php echo esc_attr($values['_venue_postal_code']); ?>" class="regular-text"></td>
            </tr>
            <tr>
                <th><label for="_venue_country"><?php _e('Country', 'vet-cpd-directory'); ?></label></th>
                <td>
                    <select id="_venue_country" name="_venue_country">
                        <option value=""><?php _e('-- Select Country --', 'vet-cpd-directory'); ?></option>
                        <?php foreach (self::get_countries() as $code => $name) : ?>
                            <option value="<?php echo esc_attr($code); ?>" <?php selected($values['_venue_country'], $code); ?>>
                                <?php echo esc_html($name); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>
            <tr>
                <th><label for="_venue_phone"><?php _e('Phone', 'vet-cpd-directory'); ?></label></th>
                <td><input type="text" id="_venue_phone" name="_venue_phone" value="<?php echo esc_attr($values['_venue_phone']); ?>" class="regular-text"></td>
            </tr>
            <tr>
                <th><label for="_venue_website"><?php _e('Website', 'vet-cpd-directory'); ?></label></th>
                <td><input type="url" id="_venue_website" name="_venue_website" value="<?php echo esc_url($values['_venue_website']); ?>" class="widefat"></td>
            </tr>
            <tr>
                <th><?php _e('Map Options', 'vet-cpd-directory'); ?></th>
                <td>
                    <label><input type="checkbox" name="_venue_show_map" value="1" <?php checked($values['_venue_show_map'], '1'); ?>> <?php _e('Show map', 'vet-cpd-directory'); ?></label><br>
                    <label><input type="checkbox" name="_venue_show_map_link" value="1" <?php checked($values['_venue_show_map_link'], '1'); ?>> <?php _e('Show map link', 'vet-cpd-directory'); ?></label><br>
                    <label><input type="checkbox" name="_venue_use_coords" value="1" <?php checked($values['_venue_use_coords'], '1'); ?>> <?php _e('Use custom coordinates', 'vet-cpd-directory'); ?></label>
                </td>
            </tr>
            <tr>
                <th><label for="_venue_latitude"><?php _e('Latitude', 'vet-cpd-directory'); ?></label></th>
                <td><input type="text" id="_venue_latitude" name="_venue_latitude" value="<?php echo esc_attr($values['_venue_latitude']); ?>" class="regular-text"></td>
            </tr>
            <tr>
                <th><label for="_venue_longitude"><?php _e('Longitude', 'vet-cpd-directory'); ?></label></th>
                <td><input type="text" id="_venue_longitude" name="_venue_longitude" value="<?php echo esc_attr($values['_venue_longitude']); ?>" class="regular-text"></td>
            </tr>
        </table>
        <?php
    }
    
    /**
     * Render Organiser Details meta box
     */
    public static function render_organiser_details($post) {
        $fields = VET_CPD_Organiser::get_meta_fields();
        $values = [];
        foreach ($fields as $key => $default) {
            $values[$key] = VET_CPD_Organiser::get_meta($post->ID, $key);
        }
        ?>
        <table class="form-table">
            <tr>
                <th><label for="_organiser_phone"><?php _e('Phone', 'vet-cpd-directory'); ?></label></th>
                <td><input type="text" id="_organiser_phone" name="_organiser_phone" value="<?php echo esc_attr($values['_organiser_phone']); ?>" class="regular-text"></td>
            </tr>
            <tr>
                <th><label for="_organiser_website"><?php _e('Website', 'vet-cpd-directory'); ?></label></th>
                <td><input type="url" id="_organiser_website" name="_organiser_website" value="<?php echo esc_url($values['_organiser_website']); ?>" class="widefat"></td>
            </tr>
            <tr>
                <th><label for="_organiser_email"><?php _e('Email', 'vet-cpd-directory'); ?></label></th>
                <td><input type="email" id="_organiser_email" name="_organiser_email" value="<?php echo esc_attr($values['_organiser_email']); ?>" class="regular-text"></td>
            </tr>
        </table>
        <?php
    }
    
    /**
     * Render Instructor Details meta box
     */
    public static function render_instructor_details($post) {
        $fields = VET_CPD_Instructor::get_meta_fields();
        $values = [];
        foreach ($fields as $key => $default) {
            $values[$key] = VET_CPD_Instructor::get_meta($post->ID, $key);
        }
        ?>
        <table class="form-table">
            <tr>
                <th><label for="_instructor_phone"><?php _e('Phone', 'vet-cpd-directory'); ?></label></th>
                <td><input type="text" id="_instructor_phone" name="_instructor_phone" value="<?php echo esc_attr($values['_instructor_phone']); ?>" class="regular-text"></td>
            </tr>
            <tr>
                <th><label for="_instructor_website"><?php _e('Website', 'vet-cpd-directory'); ?></label></th>
                <td><input type="url" id="_instructor_website" name="_instructor_website" value="<?php echo esc_url($values['_instructor_website']); ?>" class="widefat"></td>
            </tr>
            <tr>
                <th><label for="_instructor_email"><?php _e('Email', 'vet-cpd-directory'); ?></label></th>
                <td><input type="email" id="_instructor_email" name="_instructor_email" value="<?php echo esc_attr($values['_instructor_email']); ?>" class="regular-text"></td>
            </tr>
        </table>
        <?php
    }
    
    /**
     * Save meta data
     */
    public static function save_meta($post_id, $post) {
        // Check nonce
        if (!isset($_POST['cpd_meta_nonce']) || !wp_verify_nonce($_POST['cpd_meta_nonce'], 'cpd_save_meta')) {
            return;
        }
        
        // Check autosave
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        
        // Check permissions
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        
        // Save based on post type
        switch ($post->post_type) {
            case VET_CPD_CPD::POST_TYPE:
                self::save_cpd_meta($post_id);
                break;
            case VET_CPD_Venue::POST_TYPE:
                self::save_venue_meta($post_id);
                break;
            case VET_CPD_Organiser::POST_TYPE:
                self::save_organiser_meta($post_id);
                break;
            case VET_CPD_Instructor::POST_TYPE:
                self::save_instructor_meta($post_id);
                break;
        }
    }
    
    /**
     * Save CPD meta
     */
    private static function save_cpd_meta($post_id) {
        $fields = [
            '_cpd_provider_url',
            '_cpd_start_date',
            '_cpd_end_date',
            '_cpd_all_day',
            '_cpd_cost',
            '_cpd_currency',
            '_cpd_show_map',
            '_cpd_show_map_link',
            '_cpd_series',
            '_cpd_series_order',
        ];
        
        foreach ($fields as $field) {
            if (isset($_POST[$field])) {
                $value = sanitize_text_field($_POST[$field]);
                update_post_meta($post_id, $field, $value);
            } else {
                delete_post_meta($post_id, $field);
            }
        }
        
        // Arrays
        $venues = isset($_POST['_cpd_venues']) ? array_map('intval', array_filter($_POST['_cpd_venues'])) : [];
        update_post_meta($post_id, '_cpd_venues', $venues);
        
        $organisers = isset($_POST['_cpd_organisers']) ? array_map('intval', array_filter($_POST['_cpd_organisers'])) : [];
        update_post_meta($post_id, '_cpd_organisers', $organisers);
        
        $instructors = isset($_POST['_cpd_instructors']) ? array_map('intval', array_filter($_POST['_cpd_instructors'])) : [];
        update_post_meta($post_id, '_cpd_instructors', $instructors);
        
        // Save tags from checkboxes
        self::save_cpd_tags($post_id);
        
        // Note: Auto-tags (upcoming, on-demand, free) are applied via save_post hook
    }
    
    /**
     * Save CPD tags from checkboxes
     */
    private static function save_cpd_tags($post_id) {
        // Verify nonce
        if (!isset($_POST['cpd_tags_nonce']) || !wp_verify_nonce($_POST['cpd_tags_nonce'], 'cpd_save_tags')) {
            return;
        }
        
        $tag_ids = isset($_POST['cpd_tags']) ? array_map('intval', $_POST['cpd_tags']) : [];
        
        // Handle new tag creation
        if (!empty($_POST['cpd_new_tag'])) {
            $new_tag_name = sanitize_text_field($_POST['cpd_new_tag']);
            $existing = term_exists($new_tag_name, VET_CPD_Taxonomies::TAG);
            
            if ($existing) {
                // Tag exists, add its ID
                $tag_ids[] = intval($existing['term_id']);
            } else {
                // Create new tag
                $new_term = wp_insert_term($new_tag_name, VET_CPD_Taxonomies::TAG);
                if (!is_wp_error($new_term)) {
                    $tag_ids[] = intval($new_term['term_id']);
                }
            }
        }
        
        // Remove duplicates
        $tag_ids = array_unique($tag_ids);
        
        // Apply terms (this replaces all existing terms)
        wp_set_object_terms($post_id, $tag_ids, VET_CPD_Taxonomies::TAG, false);
    }
    
    /**
     * Save Venue meta
     */
    private static function save_venue_meta($post_id) {
        $fields = [
            '_venue_address',
            '_venue_city',
            '_venue_country',
            '_venue_state',
            '_venue_postal_code',
            '_venue_phone',
            '_venue_website',
            '_venue_show_map',
            '_venue_show_map_link',
            '_venue_use_coords',
            '_venue_latitude',
            '_venue_longitude',
        ];
        
        foreach ($fields as $field) {
            if (isset($_POST[$field])) {
                $value = sanitize_text_field($_POST[$field]);
                update_post_meta($post_id, $field, $value);
            } else {
                delete_post_meta($post_id, $field);
            }
        }
    }
    
    /**
     * Save Organiser meta
     */
    private static function save_organiser_meta($post_id) {
        $fields = [
            '_organiser_phone',
            '_organiser_website',
            '_organiser_email',
        ];
        
        foreach ($fields as $field) {
            if (isset($_POST[$field])) {
                $value = sanitize_text_field($_POST[$field]);
                update_post_meta($post_id, $field, $value);
            } else {
                delete_post_meta($post_id, $field);
            }
        }
    }
    
    /**
     * Save Instructor meta
     */
    private static function save_instructor_meta($post_id) {
        $fields = [
            '_instructor_phone',
            '_instructor_website',
            '_instructor_email',
        ];
        
        foreach ($fields as $field) {
            if (isset($_POST[$field])) {
                $value = sanitize_text_field($_POST[$field]);
                update_post_meta($post_id, $field, $value);
            } else {
                delete_post_meta($post_id, $field);
            }
        }
    }
    
    /**
     * Enqueue admin assets
     */
    public static function enqueue_assets($hook) {
        if (!in_array(get_current_screen()->post_type, [VET_CPD_CPD::POST_TYPE, VET_CPD_Venue::POST_TYPE, VET_CPD_Organiser::POST_TYPE, VET_CPD_Instructor::POST_TYPE, VET_CPD_Series::POST_TYPE])) {
            return;
        }
        
        wp_enqueue_style('vet-cpd-admin', VET_CPD_PLUGIN_URL . 'assets/css/admin.css', [], VET_CPD_VERSION);
    }
    
    /**
     * Get country list
     */
    private static function get_countries() {
        return [
            'GB' => 'United Kingdom',
            'IE' => 'Ireland',
            'US' => 'United States',
            'CA' => 'Canada',
            'AU' => 'Australia',
            'NZ' => 'New Zealand',
            'DE' => 'Germany',
            'FR' => 'France',
            'ES' => 'Spain',
            'IT' => 'Italy',
            'NL' => 'Netherlands',
            'BE' => 'Belgium',
            'CH' => 'Switzerland',
            'AT' => 'Austria',
            'SE' => 'Sweden',
            'NO' => 'Norway',
            'DK' => 'Denmark',
            'FI' => 'Finland',
        ];
    }
}
