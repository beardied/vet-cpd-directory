<?php
/**
 * CPD Event Archive Template
 */

get_header();

$current_term = get_queried_object();
$archive_title = post_type_archive_title('', false);

if (is_tax('cpd_category')) {
    $archive_title = sprintf(__('Category: %s', 'vet-cpd-directory'), $current_term->name);
} elseif (is_tax('cpd_tag')) {
    $archive_title = sprintf(__('Tag: %s', 'vet-cpd-directory'), $current_term->name);
}

?>

<div class="cpd-archive">
    <div class="cpd-container">
        
        <header class="cpd-archive-header">
            <h1 class="cpd-archive-title"><?php echo esc_html(ucwords(strtolower($archive_title))); ?></h1>
            
            <?php if (is_tax() && $current_term->description) : ?>
                <div class="cpd-archive-description">
                    <?php echo esc_html($current_term->description); ?>
                </div>
            <?php endif; ?>
        </header>
        
        <?php if (have_posts()) : ?>
            
            <div class="cpd-grid">
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
                        if ($all_day === '1') {
                            $date_display = date_i18n('j M Y', strtotime($start_date));
                        } else {
                            $date_display = date_i18n('j M Y g:i a', strtotime($start_date));
                        }
                    } else {
                        $date_display = '';
                    }
                    
                    // Format cost
                    if ($cost !== '' && $cost !== '0') {
                        $symbol = $currency === 'GBP' ? '£' : ($currency === 'EUR' ? '€' : '$');
                        $cost_display = $symbol . $cost;
                    } else {
                        $cost_display = 'Free';
                    }
                    ?>
                    
                    <article class="cpd-card">
                        <a href="<?php the_permalink(); ?>" class="cpd-card-link">
                            <div class="cpd-card-image-wrap">
                                <?php if (has_post_thumbnail()) : ?>
                                    <?php the_post_thumbnail('medium_large', ['class' => 'cpd-card-img']); ?>
                                <?php else : ?>
                                    <div class="cpd-card-noimg">📚</div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="cpd-card-body">
                                <?php if ($category) : ?>
                                    <span class="cpd-card-cat"><?php echo esc_html($category->name); ?></span>
                                <?php endif; ?>
                                
                                <h2 class="cpd-card-title"><?php the_title(); ?></h2>
                                
                                <div class="cpd-card-meta">
                                    <?php if ($date_display) : ?>
                                        <span class="cpd-card-date">📅 <?php echo esc_html($date_display); ?></span>
                                    <?php endif; ?>
                                    <span class="cpd-card-price">💰 <?php echo esc_html($cost_display); ?></span>
                                </div>
                                
                                <div class="cpd-card-excerpt">
                                    <?php echo wp_trim_words(get_the_excerpt(), 15); ?>
                                </div>
                                
                                <span class="cpd-card-btn">Learn More →</span>
                            </div>
                        </a>
                    </article>
                    
                <?php endwhile; ?>
            </div>
            
            <div class="cpd-pagination">
                <?php
                the_posts_pagination([
                    'mid_size'           => 2,
                    'prev_text'          => __('← Previous', 'vet-cpd-directory'),
                    'next_text'          => __('Next →', 'vet-cpd-directory'),
                ]);
                ?>
            </div>
            
        <?php else : ?>
            
            <div class="cpd-no-results">
                <p><?php _e('No CPD events found.', 'vet-cpd-directory'); ?></p>
                <p><a href="<?php echo esc_url(get_post_type_archive_link('cpd_event')); ?>" class="cpd-btn"><?php _e('View All Events', 'vet-cpd-directory'); ?></a></p>
            </div>
            
        <?php endif; ?>
        
    </div>
</div>

<?php get_footer(); ?>
