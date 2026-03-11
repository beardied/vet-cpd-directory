<?php
/**
 * Series Custom Post Type
 */

class VET_CPD_Series {
    
    const POST_TYPE = 'cpd_series';
    
    public static function init() {
        add_action('init', [__CLASS__, 'register']);
    }
    
    public static function register() {
        $labels = [
            'name'                  => _x('Series', 'Post type general name', 'vet-cpd-directory'),
            'singular_name'         => _x('Series', 'Post type singular name', 'vet-cpd-directory'),
            'menu_name'             => _x('Series', 'Admin Menu text', 'vet-cpd-directory'),
            'name_admin_bar'        => _x('Series', 'Add New on Toolbar', 'vet-cpd-directory'),
            'add_new'               => __('Add New', 'vet-cpd-directory'),
            'add_new_item'          => __('Add New Series', 'vet-cpd-directory'),
            'new_item'              => __('New Series', 'vet-cpd-directory'),
            'edit_item'             => __('Edit Series', 'vet-cpd-directory'),
            'view_item'             => __('View Series', 'vet-cpd-directory'),
            'all_items'             => __('Series', 'vet-cpd-directory'),
            'search_items'          => __('Search Series', 'vet-cpd-directory'),
            'not_found'             => __('No series found.', 'vet-cpd-directory'),
            'not_found_in_trash'    => __('No series found in Trash.', 'vet-cpd-directory'),
        ];
        
        $args = [
            'labels'                => $labels,
            'public'                => true,
            'publicly_queryable'    => true,
            'show_ui'               => true,
            'show_in_menu'          => 'edit.php?post_type=cpd_event',
            'query_var'             => true,
            'rewrite'               => ['slug' => 'series'],
            'capability_type'       => 'post',
            'has_archive'           => true,
            'hierarchical'          => false,
            'supports'              => ['title', 'editor', 'thumbnail'],
            'show_in_rest'          => true,
        ];
        
        register_post_type(self::POST_TYPE, $args);
    }
    
    /**
     * Get CPDs in this series
     */
    public static function get_cpds($series_id) {
        return get_posts([
            'post_type'      => VET_CPD_CPD::POST_TYPE,
            'posts_per_page' => -1,
            'orderby'        => 'meta_value_num',
            'meta_key'       => '_cpd_series_order',
            'order'          => 'ASC',
            'meta_query'     => [
                [
                    'key'   => '_cpd_series',
                    'value' => $series_id,
                ],
            ],
        ]);
    }
}
