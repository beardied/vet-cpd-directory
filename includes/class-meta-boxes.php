<?php
/**
 * Admin Meta Boxes
 */

class VET_CPD_Meta_Boxes {
    
    public static function init() {
        add_action('add_meta_boxes', [__CLASS__, 'add_meta_boxes']);
        add_action('save_post', [__CLASS__, 'save_meta'], 10, 2);
        
        // Enqueue admin assets
        add_action('admin_enqueue_scripts', [__CLASS__, 'enqueue_assets']);
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
            __('Organizer & Instructors', 'vet-cpd-directory'),
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
        
        // Venue meta box
        add_meta_box(
            'cpd_venue_details',
            __('Venue Details', 'vet-cpd-directory'),
            [__CLASS__, 'render_venue_details'],
            VET_CPD_Venue::POST_TYPE,
            'normal',
            'high'
        );
        
        // Person meta box
        add_meta_box(
            'cpd_person_details',
            __('Person Details', 'vet-cpd-directory'),
            [__CLASS__, 'render_person_details'],
            VET_CPD_Person::POST_TYPE,
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
        $date = VET_CPD_CPD::get_meta($post->ID, '_cpd_date');
        $all_day = VET_CPD_CPD::get_meta($post->ID, '_cpd_all_day');
        $hours = VET_CPD_CPD::get_meta($post->ID, '_cpd_hours');
        ?>
        <table class="form-table">
            <tr>
                <th><label for="_cpd_provider_url"><?php _e('Provider URL', 'vet-cpd-directory'); ?></label></th>
                <td>
                    <input type="url" id="_cpd_provider_url" name="_cpd_provider_url" 
                           value="<?php echo esc_url($provider_url); ?>" class="widefat">
                    <p class="description"><?php _e('External link to CPD provider (affiliate link)', 'vet-cpd-directory'); ?></p>
                </td>
            </tr>
            <tr>
                <th><label for="_cpd_date"><?php _e('CPD Date & Time', 'vet-cpd-directory'); ?></label></th>
                <td>
                    <input type="datetime-local" id="_cpd_date" name="_cpd_date" 
                           value="<?php echo esc_attr($date); ?>" class="regular-text">
                </td>
            </tr>
            <tr>
                <th><label for="_cpd_all_day"><?php _e('All Day Event', 'vet-cpd-directory'); ?></label></th>
                <td>
                    <input type="checkbox" id="_cpd_all_day" name="_cpd_all_day" value="1" <?php checked($all_day, '1'); ?>>
                </td>
            </tr>
            <tr>
                <th><label for="_cpd_hours"><?php _e('CPD Hours', 'vet-cpd-directory'); ?></label></th>
                <td>
                    <input type="number" id="_cpd_hours" name="_cpd_hours" 
                           value="<?php echo esc_attr($hours); ?>" class="small-text" step="0.5" min="0">
                    <p class="description"><?php _e('Hours of CPD credit awarded', 'vet-cpd-directory'); ?></p>
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
        $online_url = VET_CPD_CPD::get_meta($post->ID, '_cpd_online_url');
        
        $all_venues = get_posts([
            'post_type'      => VET_CPD_Venue::POST_TYPE,
            'posts_per_page' => -1,
            'orderby'        => 'title',
            'order'          => 'ASC',
        ]);
        ?>
        <table class="form-table">
            <tr>
                <th><label for="_cpd_venues"><?php _e('Venues', 'vet-cpd-directory'); ?></label></th>
                <td>
                    <select id="_cpd_venues" name="_cpd_venues[]" multiple="multiple" style="width: 100%; height: 100px;">
                        <?php foreach ($all_venues as $venue) : ?>
                            <option value="<?php echo $venue->ID; ?>" <?php selected(in_array($venue->ID, (array)$venues)); ?>>
                                <?php echo esc_html($venue->post_title); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <p class="description"><?php _e('Hold Ctrl/Cmd to select multiple venues', 'vet-cpd-directory'); ?></p>
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
            <tr>
                <th><label for="_cpd_online_url"><?php _e('Online URL', 'vet-cpd-directory'); ?></label></th>
                <td>
                    <input type="url" id="_cpd_online_url" name="_cpd_online_url" 
                           value="<?php echo esc_url($online_url); ?>" class="widefat">
                    <p class="description"><?php _e('For online/virtual CPDs', 'vet-cpd-directory'); ?></p>
                </td>
            </tr>
        </table>
        <?php
    }
    
    /**
     * Render CPD People meta box
     */
    public static function render_cpd_people($post) {
        $organizer = VET_CPD_CPD::get_meta($post->ID, '_cpd_organizer');
        $instructors = VET_CPD_CPD::get_meta($post->ID, '_cpd_instructors');
        
        $all_organizers = VET_CPD_Person::get_by_role('organizer');
        $all_instructors = VET_CPD_Person::get_by_role('instructor');
        ?>
        <table class="form-table">
            <tr>
                <th><label for="_cpd_organizer"><?php _e('Organizer', 'vet-cpd-directory'); ?></label></th>
                <td>
                    <select id="_cpd_organizer" name="_cpd_organizer">
                        <option value=""><?php _e('-- Select Organizer --', 'vet-cpd-directory'); ?></option>
                        <?php foreach ($all_organizers as $person) : ?>
                            <option value="<?php echo $person->ID; ?>" <?php selected($organizer, $person->ID); ?>>
                                <?php echo esc_html($person->post_title); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>
            <tr>
                <th><label for="_cpd_instructors"><?php _e('Instructors', 'vet-cpd-directory'); ?></label></th>
                <td>
                    <select id="_cpd_instructors" name="_cpd_instructors[]" multiple="multiple" style="width: 100%; height: 100px;">
                        <?php foreach ($all_instructors as $person) : ?>
                            <option value="<?php echo $person->ID; ?>" <?php selected(in_array($person->ID, (array)$instructors)); ?>>
                                <?php echo esc_html($person->post_title); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <p class="description"><?php _e('Hold Ctrl/Cmd to select multiple instructors', 'vet-cpd-directory'); ?></p>
                </td>
            </tr>
        </table>
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
     * Render Person Details meta box
     */
    public static function render_person_details($post) {
        $role_organizer = VET_CPD_Person::get_meta($post->ID, '_person_role_organizer');
        $role_instructor = VET_CPD_Person::get_meta($post->ID, '_person_role_instructor');
        $phone = VET_CPD_Person::get_meta($post->ID, '_person_phone');
        $website = VET_CPD_Person::get_meta($post->ID, '_person_website');
        $email = VET_CPD_Person::get_meta($post->ID, '_person_email');
        ?>
        <table class="form-table">
            <tr>
                <th><?php _e('Role', 'vet-cpd-directory'); ?></th>
                <td>
                    <label><input type="checkbox" name="_person_role_organizer" value="1" <?php checked($role_organizer, '1'); ?>> <?php _e('Organizer', 'vet-cpd-directory'); ?></label><br>
                    <label><input type="checkbox" name="_person_role_instructor" value="1" <?php checked($role_instructor, '1'); ?>> <?php _e('Instructor', 'vet-cpd-directory'); ?></label>
                </td>
            </tr>
            <tr>
                <th><label for="_person_phone"><?php _e('Phone', 'vet-cpd-directory'); ?></label></th>
                <td><input type="text" id="_person_phone" name="_person_phone" value="<?php echo esc_attr($phone); ?>" class="regular-text"></td>
            </tr>
            <tr>
                <th><label for="_person_website"><?php _e('Website', 'vet-cpd-directory'); ?></label></th>
                <td><input type="url" id="_person_website" name="_person_website" value="<?php echo esc_url($website); ?>" class="widefat"></td>
            </tr>
            <tr>
                <th><label for="_person_email"><?php _e('Email', 'vet-cpd-directory'); ?></label></th>
                <td><input type="email" id="_person_email" name="_person_email" value="<?php echo esc_attr($email); ?>" class="regular-text"></td>
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
            case VET_CPD_Person::POST_TYPE:
                self::save_person_meta($post_id);
                break;
        }
    }
    
    /**
     * Save CPD meta
     */
    private static function save_cpd_meta($post_id) {
        $fields = [
            '_cpd_provider_url',
            '_cpd_date',
            '_cpd_all_day',
            '_cpd_hours',
            '_cpd_show_map',
            '_cpd_show_map_link',
            '_cpd_online_url',
            '_cpd_organizer',
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
        $venues = isset($_POST['_cpd_venues']) ? array_map('intval', $_POST['_cpd_venues']) : [];
        update_post_meta($post_id, '_cpd_venues', $venues);
        
        $instructors = isset($_POST['_cpd_instructors']) ? array_map('intval', $_POST['_cpd_instructors']) : [];
        update_post_meta($post_id, '_cpd_instructors', $instructors);
        
        // Apply auto-tags based on date
        VET_CPD_Auto_Tag::apply_tags($post_id);
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
     * Save Person meta
     */
    private static function save_person_meta($post_id) {
        $fields = [
            '_person_role_organizer',
            '_person_role_instructor',
            '_person_phone',
            '_person_website',
            '_person_email',
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
        if (!in_array(get_current_screen()->post_type, [VET_CPD_CPD::POST_TYPE, VET_CPD_Venue::POST_TYPE, VET_CPD_Person::POST_TYPE, VET_CPD_Series::POST_TYPE])) {
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