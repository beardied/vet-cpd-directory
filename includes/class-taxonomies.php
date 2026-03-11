<?php
/**
 * Taxonomies: Categories and Tags
 */

class VET_CPD_Taxonomies {
    
    const CATEGORY = 'cpd_category';
    const TAG = 'cpd_tag';
    
    public static function init() {
        add_action('init', [__CLASS__, 'register']);
    }
    
    public static function register() {
        // Categories (hierarchical)
        $cat_labels = [
            'name'                       => _x('Categories', 'Taxonomy general name', 'vet-cpd-directory'),
            'singular_name'              => _x('Category', 'Taxonomy singular name', 'vet-cpd-directory'),
            'search_items'               => __('Search Categories', 'vet-cpd-directory'),
            'all_items'                  => __('All Categories', 'vet-cpd-directory'),
            'parent_item'                => __('Parent Category', 'vet-cpd-directory'),
            'parent_item_colon'          => __('Parent Category:', 'vet-cpd-directory'),
            'edit_item'                  => __('Edit Category', 'vet-cpd-directory'),
            'update_item'                => __('Update Category', 'vet-cpd-directory'),
            'add_new_item'               => __('Add New Category', 'vet-cpd-directory'),
            'new_item_name'              => __('New Category Name', 'vet-cpd-directory'),
            'menu_name'                  => __('Categories', 'vet-cpd-directory'),
        ];
        
        $cat_args = [
            'labels'            => $cat_labels,
            'hierarchical'      => true,
            'public'            => true,
            'show_ui'           => true,
            'show_admin_column' => true,
            'show_in_nav_menus' => true,
            'show_tagcloud'     => true,
            'rewrite'           => ['slug' => 'cpd-category'],
            'show_in_rest'      => true,
        ];
        
        register_taxonomy(self::CATEGORY, [VET_CPD_CPD::POST_TYPE], $cat_args);
        
        // Tags (flat)
        $tag_labels = [
            'name'                       => _x('Tags', 'Taxonomy general name', 'vet-cpd-directory'),
            'singular_name'              => _x('Tag', 'Taxonomy singular name', 'vet-cpd-directory'),
            'search_items'               => __('Search Tags', 'vet-cpd-directory'),
            'all_items'                  => __('All Tags', 'vet-cpd-directory'),
            'parent_item'                => null,
            'parent_item_colon'          => null,
            'edit_item'                  => __('Edit Tag', 'vet-cpd-directory'),
            'update_item'                => __('Update Tag', 'vet-cpd-directory'),
            'add_new_item'               => __('Add New Tag', 'vet-cpd-directory'),
            'new_item_name'              => __('New Tag Name', 'vet-cpd-directory'),
            'separate_items_with_commas' => __('Separate tags with commas', 'vet-cpd-directory'),
            'add_or_remove_items'        => __('Add or remove tags', 'vet-cpd-directory'),
            'choose_from_most_used'      => __('Choose from the most used tags', 'vet-cpd-directory'),
            'not_found'                  => __('No tags found.', 'vet-cpd-directory'),
            'menu_name'                  => __('Tags', 'vet-cpd-directory'),
        ];
        
        $tag_args = [
            'labels'            => $tag_labels,
            'hierarchical'      => false,
            'public'            => true,
            'show_ui'           => true,
            'show_admin_column' => true,
            'show_in_nav_menus' => true,
            'show_tagcloud'     => true,
            'rewrite'           => ['slug' => 'cpd-tag'],
            'show_in_rest'      => true,
        ];
        
        register_taxonomy(self::TAG, [VET_CPD_CPD::POST_TYPE], $tag_args);
        
        // Pre-populate system-managed tags
        self::create_system_tags();
    }
    
    /**
     * Create system-managed tags
     */
    private static function create_system_tags() {
        $system_tags = ['upcoming', 'on-demand', 'online', 'free'];
        
        foreach ($system_tags as $tag) {
            if (!term_exists($tag, self::TAG)) {
                wp_insert_term($tag, self::TAG, [
                    'slug' => $tag,
                ]);
            }
        }
    }
    
    /**
     * Get all categories
     */
    public static function get_categories($args = []) {
        $defaults = [
            'taxonomy'   => self::CATEGORY,
            'hide_empty' => false,
        ];
        return get_terms(array_merge($defaults, $args));
    }
    
    /**
     * Get all tags
     */
    public static function get_tags($args = []) {
        $defaults = [
            'taxonomy'   => self::TAG,
            'hide_empty' => false,
        ];
        return get_terms(array_merge($defaults, $args));
    }
}