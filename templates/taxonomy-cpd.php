<?php
/**
 * Taxonomy template for CPD Categories and Tags
 */

get_header();

$term = get_queried_object();
$is_category = is_tax(VET_CPD_Taxonomies::CATEGORY);
?>

<div class="cpd-archive">
    <div class="cpd-container">
        
        <header class="cpd-archive-header">
            <h1 class="cpd-archive-title"><?php echo esc_html($term->name); ?></h1>
            
            <?php if ($term->description) : ?>
                <div class="cpd-archive-description">
                    <?php echo esc_html($term->description); ?>
                </div>
            <?php endif; ?>
        </header>
        
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
                    
                    // Format date with time
                    if ($start_date) {
                        $date_format = get_option('date_format');
                        $time_format = get_option('time_format');
                        
                        if ($all_day === '1') {
                            $date_display = date_i18n($date_format, strtotime($start_date));
                        } else {
                            $date_display = date_i18n($date_format . ' ' . $time_format, strtotime($start_date));
                        }
                        
                        if ($end_date && $end_date !== $start_date) {
                            $date_display .= ' - ';
                            if ($all_day === '1') {
                                $date_display .= date_i18n($date_format, strtotime($end_date));
                            } else {
                                $date_display .= date_i18n($date_format . ' ' . $time_format, strtotime($end_date));
                            }
                        }
                    } else {
                        $date_display = '';
                    }
                    
                    // Format cost
                    if ($cost !== '' && $cost !== '0') {
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
        
    </div>
</div>

<?php get_footer(); ?>
