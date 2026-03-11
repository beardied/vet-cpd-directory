<?php
/**
 * CPD Event Archive Template
 * 
 * This template can be overridden by copying it to your theme:
 * your-theme/vet-cpd-directory/archive-cpd-event.php
 */

get_header();

$current_term = get_queried_object();
$archive_title = post_type_archive_title('', false);

if (is_tax('cpd_category')) {
    $archive_title = sprintf(__('Category: %s', 'vet-cpd-directory'), $current_term->name);
} elseif (is_tax('cpd_type')) {
    $archive_title = sprintf(__('Type: %s', 'vet-cpd-directory'), $current_term->name);
}

?>

<div class="cpd-archive">
    <div class="cpd-container">
        
        <!-- Archive Header -->
        <header class="cpd-archive-header">
            <h1 class="cpd-archive-title"><?php echo esc_html($archive_title); ?></h1>
            
            <?php if (is_tax() && $current_term->description) : ?>
                <div class="cpd-archive-description">
                    <?php echo esc_html($current_term->description); ?>
                </div>
            <?php endif; ?>
        </header>
        
        <!-- Filters -->
        <div class="cpd-filters">
            <form method="get" class="cpd-filter-form">
                <div class="cpd-filter-group">
                    <label for="cpd_category"><?php _e('Category', 'vet-cpd-directory'); ?></label>
                    <?php
                    wp_dropdown_categories([
                        'taxonomy'        => 'cpd_category',
                        'name'            => 'cpd_category',
                        'id'              => 'cpd_category',
                        'value_field'     => 'slug',
                        'show_option_all' => __('All Categories', 'vet-cpd-directory'),
                        'selected'        => isset($_GET['cpd_category']) ? $_GET['cpd_category'] : '',
                    ]);
                    ?>
                </div>
                
                <div class="cpd-filter-group">
                    <label for="cpd_type"><?php _e('Type', 'vet-cpd-directory'); ?></label>
                    <?php
                    wp_dropdown_categories([
                        'taxonomy'        => 'cpd_type',
                        'name'            => 'cpd_type',
                        'id'              => 'cpd_type',
                        'value_field'     => 'slug',
                        'show_option_all' => __('All Types', 'vet-cpd-directory'),
                        'selected'        => isset($_GET['cpd_type']) ? $_GET['cpd_type'] : '',
                    ]);
                    ?>
                </div>
                
                <div class="cpd-filter-group cpd-filter-submit">
                    <button type="submit" class="cpd-button">
                        <?php _e('Filter', 'vet-cpd-directory'); ?>
                    </button>
                </div>
            </form>
        </div>
        
        <div class="cpd-archive-content">
            
            <!-- Main Content -->
            <main class="cpd-archive-main">
                
                <?php if (have_posts()) : ?>
                    
                    <div class="cpd-event-grid">
                        <?php while (have_posts()) : the_post(); 
                            $event_id = get_the_ID();
                            
                            // Get meta
                            $start_date = VET_CPD_CPD::get_meta($event_id, '_cpd_start_date');
                            $end_date = VET_CPD_CPD::get_meta($event_id, '_cpd_end_date');
                            $cost = VET_CPD_CPD::get_meta($event_id, '_cpd_cost');
                            $currency = VET_CPD_CPD::get_meta($event_id, '_cpd_currency') ?: 'GBP';
                            $all_day = VET_CPD_CPD::get_meta($event_id, '_cpd_all_day');
                            
                            // Get categories
                            $categories = get_the_terms($event_id, 'cpd_category');
                            $category = ($categories && !is_wp_error($categories)) ? $categories[0] : null;
                            
                            // Format date
                            if ($start_date) {
                                if ($end_date && $end_date !== $start_date) {
                                    $date_display = date_i18n('M j', strtotime($start_date)) . ' - ' . date_i18n('M j, Y', strtotime($end_date));
                                } else {
                                    $date_display = date_i18n('F j, Y', strtotime($start_date));
                                }
                            } else {
                                $date_display = '';
                            }
                            
                            // Format cost
                            if ($cost !== '') {
                                $symbol = $currency === 'GBP' ? '£' : ($currency === 'EUR' ? '€' : '$');
                                $cost_display = $symbol . $cost;
                            } else {
                                $cost_display = __('Free', 'vet-cpd-directory');
                            }
                            ?>
                            
                            <article class="cpd-event-card">
                                <a href="<?php the_permalink(); ?>" class="cpd-card-link">
                                    <div class="cpd-card-image">
                                        <?php if (has_post_thumbnail()) : ?>
                                            <?php the_post_thumbnail('medium_large'); ?>
                                        <?php else : ?>
                                            <div class="cpd-card-placeholder">
                                                <span class="cpd-icon">📚</span>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="cpd-card-content">
                                        <?php if ($category) : ?>
                                            <span class="cpd-card-category">
                                                <?php echo esc_html($category->name); ?>
                                            </span>
                                        <?php endif; ?>
                                        
                                        <h2 class="cpd-card-title"><?php the_title(); ?></h2>
                                        
                                        <div class="cpd-card-meta">
                                            <?php if ($date_display) : ?>
                                                <span class="cpd-card-date">
                                                    <span class="cpd-icon">📅</span>
                                                    <?php echo esc_html($date_display); ?>
                                                </span>
                                            <?php endif; ?>
                                            
                                            <span class="cpd-card-cost">
                                                <span class="cpd-icon">💰</span>
                                                <?php echo esc_html($cost_display); ?>
                                            </span>
                                        </div>
                                        
                                        <div class="cpd-card-excerpt">
                                            <?php echo wp_trim_words(get_the_excerpt(), 20); ?>
                                        </div>
                                        
                                        <span class="cpd-card-more">
                                            <?php _e('Learn More', 'vet-cpd-directory'); ?> &rarr;
                                        </span>
                                    </div>
                                </a>
                            </article>
                            
                        <?php endwhile; ?>
                    </div>
                    
                    <!-- Pagination -->
                    <div class="cpd-pagination">
                        <?php
                        the_posts_pagination([
                            'mid_size'           => 2,
                            'prev_text'          => __('&larr; Previous', 'vet-cpd-directory'),
                            'next_text'          => __('Next &rarr;', 'vet-cpd-directory'),
                            'screen_reader_text' => __('CPD Events navigation', 'vet-cpd-directory'),
                        ]);
                        ?>
                    </div>
                    
                <?php else : ?>
                    
                    <div class="cpd-no-results">
                        <p><?php _e('No CPD events found.', 'vet-cpd-directory'); ?></p>
                        <p>
                            <a href="<?php echo esc_url(get_post_type_archive_link('cpd_event')); ?>" class="cpd-button">
                                <?php _e('View All Events', 'vet-cpd-directory'); ?>
                            </a>
                        </p>
                    </div>
                    
                <?php endif; ?>
                
            </main>
            
            <!-- Sidebar -->
            <aside class="cpd-archive-sidebar">
                
                <?php if (is_active_sidebar('cpd-sidebar')) : ?>
                    <?php dynamic_sidebar('cpd-sidebar'); ?>
                <?php else : ?>
                    
                    <!-- Default Widget: Categories -->
                    <div class="cpd-widget">
                        <h3 class="cpd-widget-title"><?php _e('Categories', 'vet-cpd-directory'); ?></h3>
                        <ul class="cpd-widget-list">
                            <?php
                            $cats = get_terms([
                                'taxonomy'   => 'cpd_category',
                                'hide_empty' => true,
                                'number'     => 10,
                            ]);
                            foreach ($cats as $cat) :
                                ?>
                                <li>
                                    <a href="<?php echo esc_url(get_term_link($cat)); ?>">
                                        <?php echo esc_html($cat->name); ?>
                                        <span class="cpd-count">(<?php echo intval($cat->count); ?>)</span>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    
                    <!-- Default Widget: Types -->
                    <div class="cpd-widget">
                        <h3 class="cpd-widget-title"><?php _e('Types', 'vet-cpd-directory'); ?></h3>
                        <ul class="cpd-widget-list cpd-type-list">
                            <?php
                            $types = get_terms([
                                'taxonomy'   => 'cpd_type',
                                'hide_empty' => true,
                            ]);
                            foreach ($types as $type) :
                                ?>
                                <li>
                                    <a href="<?php echo esc_url(get_term_link($type)); ?>" class="cpd-type-badge cpd-type-<?php echo esc_attr($type->slug); ?>">
                                        <?php echo esc_html($type->name); ?>
                                        <span class="cpd-count">(<?php echo intval($type->count); ?>)</span>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    
                    <!-- Default Widget: Upcoming Events -->
                    <div class="cpd-widget">
                        <h3 class="cpd-widget-title"><?php _e('Upcoming Events', 'vet-cpd-directory'); ?></h3>
                        <ul class="cpd-widget-events">
                            <?php
                            $upcoming = new WP_Query([
                                'post_type'      => 'cpd_event',
                                'posts_per_page' => 5,
                                'meta_key'       => '_cpd_start_date',
                                'orderby'        => 'meta_value',
                                'order'          => 'ASC',
                                'meta_query'     => [
                                    [
                                        'key'     => '_cpd_start_date',
                                        'value'   => date('Y-m-d H:i:s'),
                                        'compare' => '>=',
                                        'type'    => 'DATETIME',
                                    ],
                                ],
                            ]);
                            
                            while ($upcoming->have_posts()) : $upcoming->the_post();
                                $event_id = get_the_ID();
                                $event_date = VET_CPD_CPD::get_meta($event_id, '_cpd_start_date');
                                ?>
                                <li>
                                    <a href="<?php the_permalink(); ?>">
                                        <?php the_title(); ?>
                                    </a>
                                    <?php if ($event_date) : ?>
                                        <span class="cpd-widget-date">
                                            <?php echo esc_html(date_i18n('M j, Y', strtotime($event_date))); ?>
                                        </span>
                                    <?php endif; ?>
                                </li>
                            <?php endwhile; wp_reset_postdata(); ?>
                        </ul>
                    </div>
                    
                <?php endif; ?>
                
            </aside>
            
        </div>
        
    </div>
</div>

<?php
get_footer();
