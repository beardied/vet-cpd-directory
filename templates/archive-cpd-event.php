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
                <p class="cpd-archive-desc"><?php echo esc_html($current_term->description); ?></p>
            <?php endif; ?>
        </header>
        
        <?php if (have_posts()) : ?>
            
            <div class="cpd-list">
                <?php while (have_posts()) : the_post(); 
                    $event_id = get_the_ID();
                    
                    // Get meta
                    $start_date = VET_CPD_CPD::get_meta($event_id, '_cpd_start_date');
                    $end_date = VET_CPD_CPD::get_meta($event_id, '_cpd_end_date');
                    $cost = VET_CPD_CPD::get_meta($event_id, '_cpd_cost');
                    $currency = VET_CPD_CPD::get_meta($event_id, '_cpd_currency') ?: 'GBP';
                    $all_day = VET_CPD_CPD::get_meta($event_id, '_cpd_all_day');
                    
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
                            <?php if (has_post_thumbnail()) : ?>
                                <?php the_post_thumbnail('medium_large', ['class' => 'cpd-card-image']); ?>
                            <?php endif; ?>
                            
                            <div class="cpd-card-content">
                                <h2 class="cpd-card-title"><?php the_title(); ?></h2>
                                
                                <div class="cpd-card-meta">
                                    <?php if ($date_display) : ?>
                                        <span class="cpd-card-date">📅 <?php echo esc_html($date_display); ?></span>
                                    <?php endif; ?>
                                    <span class="cpd-card-cost">💰 <?php echo esc_html($cost_display); ?></span>
                                </div>
                                
                                <?php
                                // Get categories and tags for pill badges
                                $card_cats = get_the_terms($event_id, 'cpd_category');
                                $card_tags = get_the_terms($event_id, 'cpd_tag');
                                ?>
                                
                                <?php if (!empty($card_cats) && !is_wp_error($card_cats)) : ?>
                                    <div class="cpd-card-badges cpd-categories">
                                        <?php foreach ($card_cats as $cat) : ?>
                                            <span class="cpd-badge cpd-badge-category"><?php echo esc_html($cat->name); ?></span>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if (!empty($card_tags) && !is_wp_error($card_tags)) : ?>
                                    <div class="cpd-card-badges cpd-tags">
                                        <?php foreach ($card_tags as $tag) : ?>
                                            <span class="cpd-badge cpd-badge-tag"><?php echo esc_html($tag->name); ?></span>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="cpd-card-excerpt">
                                    <?php echo wp_trim_words(get_the_excerpt(), 20); ?>
                                </div>
                                
                                <span class="cpd-card-more">Learn More →</span>
                            </div>
                        </a>
                    </article>
                    
                <?php endwhile; ?>
            </div>
            
            <div class="cpd-pagination">
                <?php
                the_posts_pagination([
                    'mid_size'  => 2,
                    'prev_text' => __('« Previous', 'vet-cpd-directory'),
                    'next_text' => __('Next »', 'vet-cpd-directory'),
                ]);
                ?>
            </div>
            
        <?php else : ?>
            <p><?php _e('No CPD events found.', 'vet-cpd-directory'); ?></p>
        <?php endif; ?>
        
    </div>
</div>

<?php get_footer(); ?>
