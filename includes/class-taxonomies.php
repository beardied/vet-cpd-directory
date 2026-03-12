<?php
/**
 * Taxonomies: Categories and Tags
 */

class VET_CPD_Taxonomies {
    
    const CATEGORY = 'cpd_category';
    const TAG = 'cpd_tag';
    
    public static function init() {
        add_action('init', [__CLASS__, 'register']);
        add_action('admin_init', [__CLASS__, 'add_nav_menu_meta_box']);
    }
    
    /**
     * Add CPD Categories meta box to nav menus
     */
    public static function add_nav_menu_meta_box() {
        add_meta_box(
            'cpd_categories_nav_box',
            __('CPD Categories', 'vet-cpd-directory'),
            [__CLASS__, 'nav_menu_meta_box_callback'],
            'nav-menus',
            'side',
            'default'
        );
        
        add_meta_box(
            'cpd_tags_nav_box',
            __('CPD Tags', 'vet-cpd-directory'),
            [__CLASS__, 'nav_menu_tags_meta_box_callback'],
            'nav-menus',
            'side',
            'default'
        );
    }
    
    /**
     * Render CPD Categories nav menu meta box
     */
    public static function nav_menu_meta_box_callback() {
        $categories = self::get_categories(['hide_empty' => false]);
        
        if (empty($categories)) {
            echo '<p>' . __('No categories found.', 'vet-cpd-directory') . '</p>';
            return;
        }
        ?>
        <div id="cpd-category-tabs" class="posttypediv">
            <ul class="cpd-category-tabs add-menu-item-tabs">
                <li class="tabs">
                    <a href="#cpd-category-all" class="nav-tab-link"><?php _e('View All', 'vet-cpd-directory'); ?></a>
                </li>
            </ul>
            
            <div id="cpd-category-all" class="tabs-panel tabs-panel-active">
                <ul class="categorychecklist form-no-clear">
                    <?php 
                    $i = -1;
                    foreach ($categories as $category) : 
                        $i++;
                    ?>
                        <li>
                            <label class="menu-item-title">
                                <input type="checkbox" class="menu-item-checkbox" name="menu-item[<?php echo esc_attr($i); ?>][menu-item-object-id]" value="<?php echo esc_attr($category->term_id); ?>">
                                <input type="hidden" name="menu-item[<?php echo esc_attr($i); ?>][menu-item-type]" value="taxonomy">
                                <input type="hidden" name="menu-item[<?php echo esc_attr($i); ?>][menu-item-object]" value="cpd_category">
                                <input type="hidden" name="menu-item[<?php echo esc_attr($i); ?>][menu-item-url]" value="<?php echo esc_url(get_term_link($category)); ?>">
                                <?php echo esc_html($category->name); ?>
                            </label>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
            
            <p class="button-controls wp-clearfix">
                <span class="add-to-menu">
                    <input type="submit" class="button-secondary submit-add-to-menu right" value="<?php esc_attr_e('Add to Menu', 'vet-cpd-directory'); ?>" name="add-cpd-category-menu-item">
                </span>
            </p>
        </div>
        <?php
    }
    
    /**
     * Render CPD Tags nav menu meta box
     */
    public static function nav_menu_tags_meta_box_callback() {
        $tags = self::get_tags(['hide_empty' => false]);
        
        if (empty($tags)) {
            echo '<p>' . __('No tags found.', 'vet-cpd-directory') . '</p>';
            return;
        }
        ?>
        <div id="cpd-tag-tabs" class="posttypediv">
            <ul class="cpd-tag-tabs add-menu-item-tabs">
                <li class="tabs">
                    <a href="#cpd-tag-all" class="nav-tab-link"><?php _e('View All', 'vet-cpd-directory'); ?></a>
                </li>
            </ul>
            
            <div id="cpd-tag-all" class="tabs-panel tabs-panel-active">
                <ul class="categorychecklist form-no-clear">
                    <?php 
                    $i = -1000; // Use different offset to avoid conflicts
                    foreach ($tags as $tag) : 
                        $i++;
                    ?>
                        <li>
                            <label class="menu-item-title">
                                <input type="checkbox" class="menu-item-checkbox" name="menu-item[<?php echo esc_attr($i); ?>][menu-item-object-id]" value="<?php echo esc_attr($tag->term_id); ?>">
                                <input type="hidden" name="menu-item[<?php echo esc_attr($i); ?>][menu-item-type]" value="taxonomy">
                                <input type="hidden" name="menu-item[<?php echo esc_attr($i); ?>][menu-item-object]" value="cpd_tag">
                                <input type="hidden" name="menu-item[<?php echo esc_attr($i); ?>][menu-item-url]" value="<?php echo esc_url(get_term_link($tag)); ?>">
                                <?php echo esc_html($tag->name); ?>
                            </label>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
            
            <p class="button-controls wp-clearfix">
                <span class="add-to-menu">
                    <input type="submit" class="button-secondary submit-add-to-menu right" value="<?php esc_attr_e('Add to Menu', 'vet-cpd-directory'); ?>" name="add-cpd-tag-menu-item">
                </span>
            </p>
        </div>
        <?php
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
        
        // Tags (flat) - Status tags like upcoming, on-demand, online, free
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
            'rewrite'           => ['slug' => 'cpd-type'],
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
