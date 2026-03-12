<?php
/**
 * CPD Category Index Template
 * Shows all CPD categories at /cpd-category/
 */

get_header();

$categories = get_terms([
    'taxonomy'   => 'cpd_category',
    'hide_empty' => false,
    'orderby'    => 'name',
    'order'      => 'ASC',
    'parent'     => 0, // Only top-level categories
]);
?>

<div class="cpd-archive">
    <div class="cpd-container">
        
        <header class="cpd-archive-header">
            <h1 class="cpd-archive-title"><?php _e('CPD Categories', 'vet-cpd-directory'); ?></h1>
            <p class="cpd-archive-desc"><?php _e('Browse all CPD event categories', 'vet-cpd-directory'); ?></p>
        </header>
        
        <?php if (!empty($categories) && !is_wp_error($categories)) : ?>
            
            <div class="cpd-categories-grid">
                <?php foreach ($categories as $category) : 
                    // Get child categories
                    $children = get_terms([
                        'taxonomy'   => 'cpd_category',
                        'hide_empty' => false,
                        'orderby'    => 'name',
                        'order'      => 'ASC',
                        'parent'     => $category->term_id,
                    ]);
                    ?>
                    
                    <div class="cpd-category-card">
                        <h2 class="cpd-category-title">
                            <a href="<?php echo esc_url(get_term_link($category)); ?>">
                                <?php echo esc_html(ucwords(strtolower($category->name))); ?>
                            </a>
                        </h2>
                        
                        <?php if ($category->description) : ?>
                            <p class="cpd-category-desc"><?php echo esc_html($category->description); ?></p>
                        <?php endif; ?>
                        
                        <span class="cpd-category-count">
                            <?php 
                            printf(
                                _n('%s event', '%s events', $category->count, 'vet-cpd-directory'),
                                number_format_i18n($category->count)
                            ); 
                            ?>
                        </span>
                        
                        <?php if (!empty($children) && !is_wp_error($children)) : ?>
                            <div class="cpd-category-children">
                                <h3><?php _e('Subcategories', 'vet-cpd-directory'); ?></h3>
                                <ul>
                                    <?php foreach ($children as $child) : ?>
                                        <li>
                                            <a href="<?php echo esc_url(get_term_link($child)); ?>">
                                                <?php echo esc_html(ucwords(strtolower($child->name))); ?>
                                            </a>
                                            <span class="cpd-child-count">(<?php echo intval($child->count); ?>)</span>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                <?php endforeach; ?>
            </div>
            
        <?php else : ?>
            <p><?php _e('No categories found.', 'vet-cpd-directory'); ?></p>
        <?php endif; ?>
        
    </div>
</div>

<?php get_footer(); ?>
