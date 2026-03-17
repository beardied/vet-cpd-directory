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
            <div class="cpd-header-content">
                <h1 class="cpd-archive-title"><?php echo esc_html(ucwords(strtolower($archive_title))); ?></h1>
                
                <?php if (is_tax() && $current_term->description) : ?>
                    <p class="cpd-archive-desc"><?php echo esc_html($current_term->description); ?></p>
                <?php endif; ?>
            </div>
            
            <div class="cpd-search-box">
                <input type="text" id="cpd-live-search" class="cpd-search-input" placeholder="<?php _e('Search events...', 'vet-cpd-directory'); ?>" autocomplete="off">
                <span class="cpd-search-icon">🔍</span>
            </div>
        </header>
        
        <?php if (have_posts()) : ?>
            
            <div class="cpd-list" id="cpd-events-list">
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
                    
                    // Format cost (show "Past Event" for past physical events)
                    if (VET_CPD_Frontend::is_past_physical_event($event_id)) {
                        $cost_display = __('Past Event', 'vet-cpd-directory');
                    } elseif ($cost !== '' && $cost !== '0') {
                        $symbol = $currency === 'GBP' ? '£' : ($currency === 'EUR' ? '€' : '$');
                        $cost_display = $symbol . $cost;
                    } else {
                        $cost_display = 'Free';
                    }
                    
                    // Get categories for search
                    $card_cats = get_the_terms($event_id, 'cpd_category');
                    
                    // Prepare search data (title + categories)
                    $search_terms = [strtolower(get_the_title())];
                    if (!empty($card_cats) && !is_wp_error($card_cats)) {
                        foreach ($card_cats as $cat) {
                            $search_terms[] = strtolower($cat->name);
                        }
                    }
                    $search_data = implode(' ', $search_terms);
                    ?>
                    
                    <article class="cpd-card" data-title="<?php echo esc_attr(strtolower(get_the_title())); ?>" data-search="<?php echo esc_attr($search_data); ?>">
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
                                // Get tags for pill badges (categories already fetched above)
                                $card_tags = get_the_terms($event_id, 'cpd_tag');
                                
                                // Split categories into visible (max 4) and hidden
                                $max_visible = 4;
                                $visible_cats = [];
                                $hidden_cats = [];
                                if (!empty($card_cats) && !is_wp_error($card_cats)) {
                                    $visible_cats = array_slice($card_cats, 0, $max_visible);
                                    $hidden_cats = array_slice($card_cats, $max_visible);
                                }
                                $hidden_count = count($hidden_cats);
                                ?>
                                
                                <?php if (!empty($visible_cats)) : ?>
                                    <div class="cpd-card-badges cpd-categories" data-event-id="<?php echo esc_attr($event_id); ?>">
                                        <?php foreach ($visible_cats as $cat) : ?>
                                            <span class="cpd-badge cpd-badge-category"><?php echo esc_html($cat->name); ?></span>
                                        <?php endforeach; ?>
                                        <?php if ($hidden_count > 0) : ?>
                                            <span class="cpd-more-categories" data-event-id="<?php echo esc_attr($event_id); ?>" data-hidden-cats='<?php echo esc_attr(json_encode(wp_list_pluck($hidden_cats, 'name'))); ?>'>
                                                + <?php echo $hidden_count; ?> more
                                            </span>
                                        <?php endif; ?>
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
            
            <div class="cpd-pagination" id="cpd-pagination">
                <?php
                the_posts_pagination([
                    'mid_size'  => 2,
                    'prev_text' => __('« Previous', 'vet-cpd-directory'),
                    'next_text' => __('Next »', 'vet-cpd-directory'),
                ]);
                ?>
            </div>
            
            <p class="cpd-no-results" id="cpd-no-results" style="display: none;">
                <?php _e('No events match your search.', 'vet-cpd-directory'); ?>
            </p>
            
        <?php else : ?>
            <p><?php _e('No CPD events found.', 'vet-cpd-directory'); ?></p>
        <?php endif; ?>
        
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('cpd-live-search');
    const eventsList = document.getElementById('cpd-events-list');
    const pagination = document.getElementById('cpd-pagination');
    const noResults = document.getElementById('cpd-no-results');
    
    if (!searchInput || !eventsList) return;
    
    const cards = eventsList.querySelectorAll('.cpd-card');
    
    searchInput.addEventListener('input', function() {
        const searchTerm = this.value.toLowerCase().trim();
        let visibleCount = 0;
        
        cards.forEach(function(card) {
            // Search in both title and categories
            const searchData = card.getAttribute('data-search') || card.getAttribute('data-title');
            
            if (searchData.includes(searchTerm)) {
                card.style.display = '';
                visibleCount++;
            } else {
                card.style.display = 'none';
            }
        });
        
        // Show/hide pagination based on search
        if (pagination) {
            pagination.style.display = searchTerm ? 'none' : '';
        }
        
        // Show no results message
        if (noResults) {
            noResults.style.display = visibleCount === 0 ? 'block' : 'none';
        }
    });
    
    // Handle "+ X more" category expansion
    const moreLinks = document.querySelectorAll('.cpd-more-categories');
    moreLinks.forEach(function(link) {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation(); // Prevent card click
            
            const eventId = this.getAttribute('data-event-id');
            const hiddenCats = JSON.parse(this.getAttribute('data-hidden-cats'));
            const badgesContainer = document.querySelector('.cpd-categories[data-event-id="' + eventId + '"]');
            
            if (badgesContainer && hiddenCats.length > 0) {
                // Create and append hidden category badges
                hiddenCats.forEach(function(catName) {
                    const badge = document.createElement('span');
                    badge.className = 'cpd-badge cpd-badge-category cpd-badge-hidden';
                    badge.textContent = catName;
                    badgesContainer.insertBefore(badge, link);
                });
                
                // Remove the "+ X more" link
                link.remove();
            }
        });
    });
});
</script>

<?php get_footer(); ?>
