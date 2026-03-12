<?php
/**
 * Admin functionality
 */

class VET_CPD_Admin {
    
    public static function init() {
        // Add admin menu
        add_action('admin_menu', [__CLASS__, 'admin_menu']);
        
        // Add plugin action links
        add_filter('plugin_action_links_' . VET_CPD_PLUGIN_BASENAME, [__CLASS__, 'action_links']);
        
        // Custom admin columns
        add_filter('manage_cpd_event_posts_columns', [__CLASS__, 'cpd_columns']);
        add_action('manage_cpd_event_posts_custom_column', [__CLASS__, 'cpd_column_content'], 10, 2);
        add_filter('manage_edit-cpd_event_sortable_columns', [__CLASS__, 'cpd_sortable_columns']);
        
        // Bulk edit author support
        add_action('bulk_edit_custom_box', [__CLASS__, 'bulk_edit_author_field'], 10, 2);
        add_action('save_post', [__CLASS__, 'bulk_edit_save_author'], 10, 2);
    }
    
    /**
     * Admin menu
     */
    public static function admin_menu() {
        // Main menu is handled by CPD post type, add submenus
        add_submenu_page(
            'edit.php?post_type=cpd_event',
            __('Settings', 'vet-cpd-directory'),
            __('Settings', 'vet-cpd-directory'),
            'manage_options',
            'cpd_settings',
            [__CLASS__, 'settings_page']
        );
    }
    
    /**
     * Settings page
     */
    public static function settings_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('CPD Directory Settings', 'vet-cpd-directory'); ?></h1>
            <p><?php _e('Use the menu items to manage CPD Events, Venues, Organisers, Instructors, and Series.', 'vet-cpd-directory'); ?></p>
            
            <h2><?php _e('Permalink Settings', 'vet-cpd-directory'); ?></h2>
            <p><?php _e('Archive pages are automatically available at:', 'vet-cpd-directory'); ?></p>
            <ul style="list-style:disc;margin-left:20px;">
                <li><code>/cpd/</code> - <?php _e('All CPD Events', 'vet-cpd-directory'); ?></li>
                <li><code>/cpd-category/{category-name}/</code> - <?php _e('Events by category', 'vet-cpd-directory'); ?></li>
                <li><code>/cpd-type/{tag-name}/</code> - <?php _e('Events by tag (upcoming, on-demand, online, free)', 'vet-cpd-directory'); ?></li>
                <li><code>/cpd-venue/{venue-name}/</code> - <?php _e('Events by venue', 'vet-cpd-directory'); ?></li>
            </ul>
            <p><?php _e('Make sure to visit Settings > Permalinks and click "Save Changes" after activating this plugin.', 'vet-cpd-directory'); ?></p>
            
            <h2><?php _e('Shortcodes', 'vet-cpd-directory'); ?></h2>
            <p><?php _e('Use these shortcodes to display CPD events anywhere on your site:', 'vet-cpd-directory'); ?></p>
            
            <table class="widefat" style="max-width: 900px;">
                <thead>
                    <tr>
                        <th style="width: 30%;"><?php _e('Shortcode', 'vet-cpd-directory'); ?></th>
                        <th style="width: 40%;"><?php _e('Description', 'vet-cpd-directory'); ?></th>
                        <th style="width: 30%;"><?php _e('Parameters', 'vet-cpd-directory'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><code>[cpd_venue_events]</code></td>
                        <td><?php _e('Display events at a specific venue. Perfect for venue pages.', 'vet-cpd-directory'); ?></td>
                        <td>
                            <code>venue_id</code> - <?php _e('ID of the venue', 'vet-cpd-directory'); ?><br>
                            <code>limit</code> - <?php _e('Number of events (default: 5)', 'vet-cpd-directory'); ?>
                        </td>
                    </tr>
                    <tr>
                        <td><code>[cpd_instructor_events]</code></td>
                        <td><?php _e('Display events by a specific instructor. Perfect for instructor pages.', 'vet-cpd-directory'); ?></td>
                        <td>
                            <code>instructor_id</code> - <?php _e('ID of the instructor', 'vet-cpd-directory'); ?><br>
                            <code>limit</code> - <?php _e('Number of events (default: 5)', 'vet-cpd-directory'); ?>
                        </td>
                    </tr>
                    <tr>
                        <td><code>[cpd_organiser_events]</code></td>
                        <td><?php _e('Display events by a specific organiser. Perfect for organiser pages.', 'vet-cpd-directory'); ?></td>
                        <td>
                            <code>organiser_id</code> - <?php _e('ID of the organiser', 'vet-cpd-directory'); ?><br>
                            <code>limit</code> - <?php _e('Number of events (default: 5)', 'vet-cpd-directory'); ?>
                        </td>
                    </tr>
                </tbody>
            </table>
            
            <h3><?php _e('Shortcode Examples', 'vet-cpd-directory'); ?></h3>
            <pre style="background: #f5f5f5; padding: 15px; border-radius: 4px; max-width: 900px;">
&lt;!-- Show events at venue ID 123 --&gt;
[cpd_venue_events venue_id="123" limit="10"]

&lt;!-- Show events by instructor ID 456 --&gt;
[cpd_instructor_events instructor_id="456" limit="3"]

&lt;!-- Show events by organiser ID 789 --&gt;
[cpd_organiser_events organiser_id="789" limit="5"]</pre>
        </div>
        <?php
    }
    
    /**
     * Plugin action links
     */
    public static function action_links($links) {
        $settings_link = '<a href="' . admin_url('edit.php?post_type=cpd_event&page=cpd_settings') . '">' . __('Settings', 'vet-cpd-directory') . '</a>';
        array_unshift($links, $settings_link);
        return $links;
    }
    
    /**
     * Custom columns for CPD list
     */
    public static function cpd_columns($columns) {
        // Remove default taxonomy columns to avoid duplication
        if (isset($columns['taxonomy-cpd_tag'])) {
            unset($columns['taxonomy-cpd_tag']);
        }
        if (isset($columns['taxonomy-cpd_category'])) {
            unset($columns['taxonomy-cpd_category']);
        }
        
        $new_columns = [];
        foreach ($columns as $key => $value) {
            $new_columns[$key] = $value;
            if ($key === 'title') {
                $new_columns['cpd_start_date'] = __('Start Date', 'vet-cpd-directory');
                $new_columns['cpd_start_time'] = __('Start Time', 'vet-cpd-directory');
                $new_columns['cpd_end_date'] = __('End Date', 'vet-cpd-directory');
                $new_columns['cpd_end_time'] = __('End Time', 'vet-cpd-directory');
                $new_columns['cpd_cost'] = __('Cost', 'vet-cpd-directory');
                $new_columns['cpd_venues'] = __('Venues', 'vet-cpd-directory');
                $new_columns['cpd_instructors'] = __('Instructors', 'vet-cpd-directory');
                $new_columns['cpd_organisers'] = __('Organisers', 'vet-cpd-directory');
                $new_columns['cpd_categories'] = __('Categories', 'vet-cpd-directory');
                $new_columns['cpd_tags'] = __('Tags', 'vet-cpd-directory');
            }
        }
        return $new_columns;
    }
    
    /**
     * Column content
     */
    public static function cpd_column_content($column, $post_id) {
        switch ($column) {
            case 'cpd_start_date':
                $date = VET_CPD_CPD::get_meta($post_id, '_cpd_start_date');
                if ($date) {
                    echo esc_html(date_i18n(get_option('date_format'), strtotime($date)));
                } else {
                    echo '<em>' . __('—', 'vet-cpd-directory') . '</em>';
                }
                break;
                
            case 'cpd_start_time':
                $date = VET_CPD_CPD::get_meta($post_id, '_cpd_start_date');
                if ($date) {
                    echo esc_html(date_i18n(get_option('time_format'), strtotime($date)));
                } else {
                    echo '<em>' . __('—', 'vet-cpd-directory') . '</em>';
                }
                break;
                
            case 'cpd_end_date':
                $date = VET_CPD_CPD::get_meta($post_id, '_cpd_end_date');
                if ($date) {
                    echo esc_html(date_i18n(get_option('date_format'), strtotime($date)));
                } else {
                    echo '<em>' . __('—', 'vet-cpd-directory') . '</em>';
                }
                break;
                
            case 'cpd_end_time':
                $date = VET_CPD_CPD::get_meta($post_id, '_cpd_end_date');
                if ($date) {
                    echo esc_html(date_i18n(get_option('time_format'), strtotime($date)));
                } else {
                    echo '<em>' . __('—', 'vet-cpd-directory') . '</em>';
                }
                break;
                
            case 'cpd_cost':
                $cost = VET_CPD_CPD::get_meta($post_id, '_cpd_cost');
                $currency = VET_CPD_CPD::get_meta($post_id, '_cpd_currency') ?: 'GBP';
                if ($cost && $cost !== '0') {
                    $symbol = $currency === 'GBP' ? '£' : ($currency === 'EUR' ? '€' : '$');
                    echo esc_html($symbol . $cost);
                } else {
                    echo '<em>' . __('Free', 'vet-cpd-directory') . '</em>';
                }
                break;
                
            case 'cpd_venues':
                $venue_ids = VET_CPD_CPD::get_meta($post_id, '_cpd_venues');
                if (is_array($venue_ids) && !empty($venue_ids)) {
                    $links = [];
                    foreach ($venue_ids as $venue_id) {
                        $venue = get_post($venue_id);
                        if ($venue) {
                            $links[] = '<a href="' . get_edit_post_link($venue_id) . '">' . esc_html($venue->post_title) . '</a>';
                        }
                    }
                    echo implode(', ', $links);
                } else {
                    echo '<em>' . __('—', 'vet-cpd-directory') . '</em>';
                }
                break;
                
            case 'cpd_instructors':
                $instructor_ids = VET_CPD_CPD::get_meta($post_id, '_cpd_instructors');
                if (is_array($instructor_ids) && !empty($instructor_ids)) {
                    $links = [];
                    foreach ($instructor_ids as $instructor_id) {
                        $instructor = get_post($instructor_id);
                        if ($instructor) {
                            $links[] = '<a href="' . get_edit_post_link($instructor_id) . '">' . esc_html($instructor->post_title) . '</a>';
                        }
                    }
                    echo implode(', ', $links);
                } else {
                    echo '<em>' . __('—', 'vet-cpd-directory') . '</em>';
                }
                break;
                
            case 'cpd_organisers':
                $organiser_ids = VET_CPD_CPD::get_meta($post_id, '_cpd_organisers');
                if (is_array($organiser_ids) && !empty($organiser_ids)) {
                    $links = [];
                    foreach ($organiser_ids as $organiser_id) {
                        $organiser = get_post($organiser_id);
                        if ($organiser) {
                            $links[] = '<a href="' . get_edit_post_link($organiser_id) . '">' . esc_html($organiser->post_title) . '</a>';
                        }
                    }
                    echo implode(', ', $links);
                } else {
                    echo '<em>' . __('—', 'vet-cpd-directory') . '</em>';
                }
                break;
                
            case 'cpd_categories':
                $cats = get_the_term_list($post_id, 'cpd_category', '', ', ', '');
                if ($cats) {
                    echo $cats;
                } else {
                    echo '<em>' . __('—', 'vet-cpd-directory') . '</em>';
                }
                break;
                
            case 'cpd_tags':
                $tags = get_the_term_list($post_id, 'cpd_tag', '', ', ', '');
                if ($tags) {
                    echo $tags;
                } else {
                    echo '<em>' . __('—', 'vet-cpd-directory') . '</em>';
                }
                break;
        }
    }
    
    /**
     * Sortable columns
     */
    public static function cpd_sortable_columns($columns) {
        $columns['cpd_start_date'] = '_cpd_start_date';
        $columns['cpd_end_date'] = '_cpd_end_date';
        $columns['cpd_cost'] = '_cpd_cost';
        return $columns;
    }
    
    /**
     * Add author dropdown to bulk edit
     */
    public static function bulk_edit_author_field($column_name, $post_type) {
        if ($post_type !== 'cpd_event') {
            return;
        }
        
        // Only show in the 'author' column position (which comes after tags)
        if ($column_name !== 'cpd_tags') {
            return;
        }
        ?>
        <fieldset class="inline-edit-col-right" style="margin-top: 20px;">
            <div class="inline-edit-col">
                <label>
                    <span class="title"><?php _e('Author', 'vet-cpd-directory'); ?></span>
                    <?php
                    wp_dropdown_users([
                        'name'             => 'post_author',
                        'id'               => 'bulk_cpd_author',
                        'show_option_none' => __('— No Change —', 'vet-cpd-directory'),
                        'option_none_value'=> -1,
                        'class'            => 'authors',
                    ]);
                    ?>
                </label>
            </div>
        </fieldset>
        <?php
    }
    
    /**
     * Save bulk edit author changes
     */
    public static function bulk_edit_save_author($post_id, $post) {
        // Check if this is a bulk edit request
        if (!isset($_REQUEST['bulk_edit']) || empty($_REQUEST['post_author'])) {
            return;
        }
        
        // Only for CPD events
        if ($post->post_type !== 'cpd_event') {
            return;
        }
        
        // Check permissions
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        
        $author_id = intval($_REQUEST['post_author']);
        
        // -1 means no change
        if ($author_id === -1) {
            return;
        }
        
        // Update the post author
        wp_update_post([
            'ID'          => $post_id,
            'post_author' => $author_id,
        ]);
    }
}
