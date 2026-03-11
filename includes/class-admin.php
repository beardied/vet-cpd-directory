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
            <p><?php _e('Use the menu items to manage CPD Events, Venues, People, and Series.', 'vet-cpd-directory'); ?></p>
            
            <h2><?php _e('Shortcodes', 'vet-cpd-directory'); ?></h2>
            <table class="widefat">
                <thead>
                    <tr>
                        <th><?php _e('Shortcode', 'vet-cpd-directory'); ?></th>
                        <th><?php _e('Description', 'vet-cpd-directory'); ?></th>
                        <th><?php _e('Example', 'vet-cpd-directory'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><code>[cpd_list]</code></td>
                        <td><?php _e('Display list of CPD events', 'vet-cpd-directory'); ?></td>
                        <td><code>[cpd_list tag="free" category="canine"]</code></td>
                    </tr>
                    <tr>
                        <td><code>[cpd_upcoming]</code></td>
                        <td><?php _e('Display upcoming CPD events', 'vet-cpd-directory'); ?></td>
                        <td><code>[cpd_upcoming limit="10"]</code></td>
                    </tr>
                    <tr>
                        <td><code>[cpd_on_demand]</code></td>
                        <td><?php _e('Display on-demand CPD events', 'vet-cpd-directory'); ?></td>
                        <td><code>[cpd_on_demand category="feline"]</code></td>
                    </tr>
                </tbody>
            </table>
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
        $new_columns = [];
        foreach ($columns as $key => $value) {
            $new_columns[$key] = $value;
            if ($key === 'title') {
                $new_columns['cpd_date'] = __('Date', 'vet-cpd-directory');
                $new_columns['cpd_hours'] = __('Hours', 'vet-cpd-directory');
                $new_columns['cpd_organizer'] = __('Organizer', 'vet-cpd-directory');
            }
        }
        return $new_columns;
    }
    
    /**
     * Column content
     */
    public static function cpd_column_content($column, $post_id) {
        switch ($column) {
            case 'cpd_date':
                $date = VET_CPD_CPD::get_meta($post_id, '_cpd_date');
                if ($date) {
                    echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($date)));
                }
                break;
                
            case 'cpd_hours':
                $hours = VET_CPD_CPD::get_meta($post_id, '_cpd_hours');
                if ($hours) {
                    echo esc_html($hours) . ' ' . __('hrs', 'vet-cpd-directory');
                }
                break;
                
            case 'cpd_organizer':
                $organizer_id = VET_CPD_CPD::get_meta($post_id, '_cpd_organizer');
                if ($organizer_id) {
                    $organizer = get_post($organizer_id);
                    if ($organizer) {
                        echo '<a href="' . get_edit_post_link($organizer_id) . '">' . esc_html($organizer->post_title) . '</a>';
                    }
                }
                break;
        }
    }
    
    /**
     * Sortable columns
     */
    public static function cpd_sortable_columns($columns) {
        $columns['cpd_date'] = '_cpd_date';
        $columns['cpd_hours'] = '_cpd_hours';
        return $columns;
    }
}