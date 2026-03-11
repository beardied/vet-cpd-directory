<?php
/**
 * Person Custom Post Type (Organizers + Instructors)
 */

class VET_CPD_Person {
    
    const POST_TYPE = 'cpd_person';
    
    public static function init() {
        add_action('init', [__CLASS__, 'register']);
    }
    
    public static function register() {
        $labels = [
            'name'                  => _x('People', 'Post type general name', 'vet-cpd-directory'),
            'singular_name'         => _x('Person', 'Post type singular name', 'vet-cpd-directory'),
            'menu_name'             => _x('People', 'Admin Menu text', 'vet-cpd-directory'),
            'name_admin_bar'        => _x('Person', 'Add New on Toolbar', 'vet-cpd-directory'),
            'add_new'               => __('Add New', 'vet-cpd-directory'),
            'add_new_item'          => __('Add New Person', 'vet-cpd-directory'),
            'new_item'              => __('New Person', 'vet-cpd-directory'),
            'edit_item'             => __('Edit Person', 'vet-cpd-directory'),
            'view_item'             => __('View Person', 'vet-cpd-directory'),
            'all_items'             => __('All People', 'vet-cpd-directory'),
            'search_items'          => __('Search People', 'vet-cpd-directory'),
            'not_found'             => __('No people found.', 'vet-cpd-directory'),
            'not_found_in_trash'    => __('No people found in Trash.', 'vet-cpd-directory'),
        ];
        
        $args = [
            'labels'                => $labels,
            'public'                => true,
            'publicly_queryable'    => true,
            'show_ui'               => true,
            'show_in_menu'          => 'edit.php?post_type=cpd_event',
            'query_var'             => true,
            'rewrite'               => ['slug' => 'person'],
            'capability_type'       => 'post',
            'has_archive'           => false,
            'hierarchical'          => false,
            'supports'              => ['title', 'editor', 'thumbnail'],
            'show_in_rest'          => true,
        ];
        
        register_post_type(self::POST_TYPE, $args);
    }
    
    /**
     * Get person meta fields with defaults
     */
    public static function get_meta_fields() {
        return [
            '_person_role_organizer'    => '0',
            '_person_role_instructor'   => '0',
            '_person_phone'             => '',
            '_person_website'           => '',
            '_person_email'             => '',
        ];
    }
    
    /**
     * Get single person meta value
     */
    public static function get_meta($post_id, $key) {
        $fields = self::get_meta_fields();
        $default = isset($fields[$key]) ? $fields[$key] : '';
        return get_post_meta($post_id, $key, true) ?: $default;
    }
    
    /**
     * Get all people by role
     */
    public static function get_by_role($role) {
        $meta_key = '_person_role_' . $role;
        
        return get_posts([
            'post_type'      => self::POST_TYPE,
            'posts_per_page' => -1,
            'orderby'        => 'title',
            'order'          => 'ASC',
            'meta_query'     => [
                [
                    'key'   => $meta_key,
                    'value' => '1',
                ],
            ],
        ]);
    }
    
    /**
     * Check if person has role
     */
    public static function has_role($post_id, $role) {
        return self::get_meta($post_id, '_person_role_' . $role) === '1';
    }
}