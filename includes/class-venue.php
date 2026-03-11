<?php
/**
 * Venue Custom Post Type
 */

class VET_CPD_Venue {
    
    const POST_TYPE = 'cpd_venue';
    
    public static function init() {
        add_action('init', [__CLASS__, 'register']);
    }
    
    public static function register() {
        $labels = [
            'name'                  => _x('Venues', 'Post type general name', 'vet-cpd-directory'),
            'singular_name'         => _x('Venue', 'Post type singular name', 'vet-cpd-directory'),
            'menu_name'             => _x('Venues', 'Admin Menu text', 'vet-cpd-directory'),
            'name_admin_bar'        => _x('Venue', 'Add New on Toolbar', 'vet-cpd-directory'),
            'add_new'               => __('Add New', 'vet-cpd-directory'),
            'add_new_item'          => __('Add New Venue', 'vet-cpd-directory'),
            'new_item'              => __('New Venue', 'vet-cpd-directory'),
            'edit_item'             => __('Edit Venue', 'vet-cpd-directory'),
            'view_item'             => __('View Venue', 'vet-cpd-directory'),
            'all_items'             => __('Venues', 'vet-cpd-directory'),
            'search_items'          => __('Search Venues', 'vet-cpd-directory'),
            'not_found'             => __('No venues found.', 'vet-cpd-directory'),
            'not_found_in_trash'    => __('No venues found in Trash.', 'vet-cpd-directory'),
        ];
        
        $args = [
            'labels'                => $labels,
            'public'                => true,
            'publicly_queryable'    => true,
            'show_ui'               => true,
            'show_in_menu'          => 'edit.php?post_type=cpd_event',
            'query_var'             => true,
            'rewrite'               => ['slug' => 'venue'],
            'capability_type'       => 'post',
            'has_archive'           => false,
            'hierarchical'          => false,
            'supports'              => ['title'],
            'show_in_rest'          => true,
        ];
        
        register_post_type(self::POST_TYPE, $args);
    }
    
    /**
     * Get venue meta fields with defaults
     */
    public static function get_meta_fields() {
        return [
            '_venue_address'        => '',
            '_venue_city'           => '',
            '_venue_country'        => '',
            '_venue_state'          => '',
            '_venue_postal_code'    => '',
            '_venue_phone'          => '',
            '_venue_website'        => '',
            '_venue_show_map'       => '1',
            '_venue_show_map_link'  => '1',
            '_venue_use_coords'     => '0',
            '_venue_latitude'       => '',
            '_venue_longitude'      => '',
        ];
    }
    
    /**
     * Get single venue meta value
     */
    public static function get_meta($post_id, $key) {
        $fields = self::get_meta_fields();
        $default = isset($fields[$key]) ? $fields[$key] : '';
        return get_post_meta($post_id, $key, true) ?: $default;
    }
    
    /**
     * Get full address as string
     */
    public static function get_full_address($post_id) {
        $parts = [
            self::get_meta($post_id, '_venue_address'),
            self::get_meta($post_id, '_venue_city'),
            self::get_meta($post_id, '_venue_state'),
            self::get_meta($post_id, '_venue_postal_code'),
            self::get_meta($post_id, '_venue_country'),
        ];
        return implode(', ', array_filter($parts));
    }
    
    /**
     * Get all venues
     */
    public static function get_all($args = []) {
        $defaults = [
            'post_type'      => self::POST_TYPE,
            'posts_per_page' => -1,
            'orderby'        => 'title',
            'order'          => 'ASC',
        ];
        return get_posts(array_merge($defaults, $args));
    }
    
    /**
     * Get all venue meta
     */
    public static function get_all_meta($post_id) {
        $fields = self::get_meta_fields();
        $meta = [];
        foreach ($fields as $key => $default) {
            $meta[$key] = get_post_meta($post_id, $key, true) ?: $default;
        }
        return $meta;
    }
}
