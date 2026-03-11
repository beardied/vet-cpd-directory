<?php
/**
 * Instructor Custom Post Type
 */

class VET_CPD_Instructor {
    
    const POST_TYPE = 'cpd_instructor';
    
    public static function init() {
        add_action('init', [__CLASS__, 'register']);
    }
    
    public static function register() {
        $labels = [
            'name'                  => _x('Instructors', 'Post type general name', 'vet-cpd-directory'),
            'singular_name'         => _x('Instructor', 'Post type singular name', 'vet-cpd-directory'),
            'menu_name'             => _x('Instructors', 'Admin Menu text', 'vet-cpd-directory'),
            'name_admin_bar'        => _x('Instructor', 'Add New on Toolbar', 'vet-cpd-directory'),
            'add_new'               => __('Add New', 'vet-cpd-directory'),
            'add_new_item'          => __('Add New Instructor', 'vet-cpd-directory'),
            'new_item'              => __('New Instructor', 'vet-cpd-directory'),
            'edit_item'             => __('Edit Instructor', 'vet-cpd-directory'),
            'view_item'             => __('View Instructor', 'vet-cpd-directory'),
            'all_items'             => __('Instructors', 'vet-cpd-directory'),
            'search_items'          => __('Search Instructors', 'vet-cpd-directory'),
            'not_found'             => __('No instructors found.', 'vet-cpd-directory'),
            'not_found_in_trash'    => __('No instructors found in Trash.', 'vet-cpd-directory'),
        ];
        
        $args = [
            'labels'                => $labels,
            'public'                => true,
            'publicly_queryable'    => true,
            'show_ui'               => true,
            'show_in_menu'          => 'edit.php?post_type=cpd_event',
            'query_var'             => true,
            'rewrite'               => ['slug' => 'instructor'],
            'capability_type'       => 'post',
            'has_archive'           => false,
            'hierarchical'          => false,
            'supports'              => ['title', 'editor', 'thumbnail'],
            'show_in_rest'          => true,
        ];
        
        register_post_type(self::POST_TYPE, $args);
    }
    
    /**
     * Get instructor meta fields with defaults
     */
    public static function get_meta_fields() {
        return [
            '_instructor_phone'    => '',
            '_instructor_website'  => '',
            '_instructor_email'    => '',
        ];
    }
    
    /**
     * Get single instructor meta value
     */
    public static function get_meta($post_id, $key) {
        $fields = self::get_meta_fields();
        $default = isset($fields[$key]) ? $fields[$key] : '';
        return get_post_meta($post_id, $key, true) ?: $default;
    }
    
    /**
     * Get all instructors
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
     * Get all instructor meta
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
