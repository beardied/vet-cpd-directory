<?php
/**
 * Organiser Custom Post Type
 */

class VET_CPD_Organiser {
    
    const POST_TYPE = 'cpd_organiser';
    
    public static function init() {
        add_action('init', [__CLASS__, 'register']);
    }
    
    public static function register() {
        $labels = [
            'name'                  => _x('Organisers', 'Post type general name', 'vet-cpd-directory'),
            'singular_name'         => _x('Organiser', 'Post type singular name', 'vet-cpd-directory'),
            'menu_name'             => _x('Organisers', 'Admin Menu text', 'vet-cpd-directory'),
            'name_admin_bar'        => _x('Organiser', 'Add New on Toolbar', 'vet-cpd-directory'),
            'add_new'               => __('Add New', 'vet-cpd-directory'),
            'add_new_item'          => __('Add New Organiser', 'vet-cpd-directory'),
            'new_item'              => __('New Organiser', 'vet-cpd-directory'),
            'edit_item'             => __('Edit Organiser', 'vet-cpd-directory'),
            'view_item'             => __('View Organiser', 'vet-cpd-directory'),
            'all_items'             => __('Organisers', 'vet-cpd-directory'),
            'search_items'          => __('Search Organisers', 'vet-cpd-directory'),
            'not_found'             => __('No organisers found.', 'vet-cpd-directory'),
            'not_found_in_trash'    => __('No organisers found in Trash.', 'vet-cpd-directory'),
        ];
        
        $args = [
            'labels'                => $labels,
            'public'                => true,
            'publicly_queryable'    => true,
            'show_ui'               => true,
            'show_in_menu'          => 'edit.php?post_type=cpd_event',
            'query_var'             => true,
            'rewrite'               => ['slug' => 'organiser'],
            'capability_type'       => 'post',
            'has_archive'           => false,
            'hierarchical'          => false,
            'supports'              => ['title', 'editor', 'thumbnail'],
            'show_in_rest'          => true,
        ];
        
        register_post_type(self::POST_TYPE, $args);
    }
    
    /**
     * Get organiser meta fields with defaults
     */
    public static function get_meta_fields() {
        return [
            '_organiser_phone'    => '',
            '_organiser_website'  => '',
            '_organiser_email'    => '',
        ];
    }
    
    /**
     * Get single organiser meta value
     */
    public static function get_meta($post_id, $key) {
        $fields = self::get_meta_fields();
        $default = isset($fields[$key]) ? $fields[$key] : '';
        return get_post_meta($post_id, $key, true) ?: $default;
    }
    
    /**
     * Get all organisers
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
     * Get all organiser meta
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
