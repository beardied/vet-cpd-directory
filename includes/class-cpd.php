<?php
/**
 * CPD Event Custom Post Type
 */

class VET_CPD_CPD {
    
    const POST_TYPE = 'cpd_event';
    
    public static function init() {
        add_action('init', [__CLASS__, 'register']);
    }
    
    public static function register() {
        $labels = [
            'name'                  => _x('CPD Events', 'Post type general name', 'vet-cpd-directory'),
            'singular_name'         => _x('CPD Event', 'Post type singular name', 'vet-cpd-directory'),
            'menu_name'             => _x('CPD Directory', 'Admin Menu text', 'vet-cpd-directory'),
            'name_admin_bar'        => _x('CPD Event', 'Add New on Toolbar', 'vet-cpd-directory'),
            'add_new'               => __('Add New', 'vet-cpd-directory'),
            'add_new_item'          => __('Add New CPD Event', 'vet-cpd-directory'),
            'new_item'              => __('New CPD Event', 'vet-cpd-directory'),
            'edit_item'             => __('Edit CPD Event', 'vet-cpd-directory'),
            'view_item'             => __('View CPD Event', 'vet-cpd-directory'),
            'all_items'             => __('All CPD Events', 'vet-cpd-directory'),
            'search_items'          => __('Search CPD Events', 'vet-cpd-directory'),
            'parent_item_colon'     => __('Parent CPD Events:', 'vet-cpd-directory'),
            'not_found'             => __('No CPD events found.', 'vet-cpd-directory'),
            'not_found_in_trash'    => __('No CPD events found in Trash.', 'vet-cpd-directory'),
            'featured_image'        => _x('CPD Event Image', 'Overrides the "Featured Image" phrase', 'vet-cpd-directory'),
            'set_featured_image'    => _x('Set CPD image', 'Overrides the "Set featured image" phrase', 'vet-cpd-directory'),
            'remove_featured_image' => _x('Remove CPD image', 'Overrides the "Remove featured image" phrase', 'vet-cpd-directory'),
            'use_featured_image'    => _x('Use as CPD image', 'Overrides the "Use as featured image" phrase', 'vet-cpd-directory'),
            'archives'              => _x('CPD Event archives', 'The post type archive label', 'vet-cpd-directory'),
            'insert_into_item'      => _x('Insert into CPD event', 'Overrides the "Insert into post" phrase', 'vet-cpd-directory'),
            'uploaded_to_this_item' => _x('Uploaded to this CPD event', 'Overrides the "Uploaded to this post" phrase', 'vet-cpd-directory'),
            'filter_items_list'     => _x('Filter CPD events list', 'Screen reader text', 'vet-cpd-directory'),
            'items_list_navigation' => _x('CPD events list navigation', 'Screen reader text', 'vet-cpd-directory'),
            'items_list'            => _x('CPD events list', 'Screen reader text', 'vet-cpd-directory'),
        ];
        
        $args = [
            'labels'                => $labels,
            'public'                => true,
            'publicly_queryable'    => true,
            'show_ui'               => true,
            'show_in_menu'          => true,
            'query_var'             => true,
            'rewrite'               => ['slug' => 'cpd'],
            'capability_type'       => 'post',
            'has_archive'           => true,
            'hierarchical'          => false,
            'menu_position'         => 5,
            'menu_icon'             => 'dashicons-welcome-learn-more',
            'supports'              => ['title', 'editor', 'thumbnail', 'revisions'],
            'taxonomies'            => ['cpd_category', 'cpd_tag'],
            'show_in_rest'          => true,
        ];
        
        register_post_type(self::POST_TYPE, $args);
    }
    
    /**
     * Get CPD meta fields with defaults
     */
    public static function get_meta_fields() {
        return [
            '_cpd_provider_url'     => '',
            '_cpd_date'             => '',
            '_cpd_all_day'          => '0',
            '_cpd_hours'            => '',
            '_cpd_venues'           => [],
            '_cpd_show_map'         => '1',
            '_cpd_show_map_link'    => '1',
            '_cpd_online_url'       => '',
            '_cpd_organizer'        => '',
            '_cpd_instructors'      => [],
            '_cpd_series'           => '',
            '_cpd_series_order'     => '1',
        ];
    }
    
    /**
     * Get single CPD meta value
     */
    public static function get_meta($post_id, $key) {
        $fields = self::get_meta_fields();
        $default = isset($fields[$key]) ? $fields[$key] : '';
        return get_post_meta($post_id, $key, true) ?: $default;
    }
}