<?php
/**
 * Single CPD Event template
 */

get_header();

while (have_posts()) : the_post();
    $event_id = get_the_ID();
    
    // Get meta
    $start_date = VET_CPD_CPD::get_meta($event_id, '_cpd_start_date');
    $end_date = VET_CPD_CPD::get_meta($event_id, '_cpd_end_date');
    $all_day = VET_CPD_CPD::get_meta($event_id, '_cpd_all_day');
    $cost = VET_CPD_CPD::get_meta($event_id, '_cpd_cost');
    $currency = VET_CPD_CPD::get_meta($event_id, '_cpd_currency') ?: 'GBP';
    $provider_url = VET_CPD_CPD::get_meta($event_id, '_cpd_provider_url');
    $venue_ids = VET_CPD_CPD::get_meta($event_id, '_cpd_venues');
    $online_url = VET_CPD_CPD::get_meta($event_id, '_cpd_online_url');
    $organiser_ids = VET_CPD_CPD::get_meta($event_id, '_cpd_organisers');
    $instructor_ids = VET_CPD_CPD::get_meta($event_id, '_cpd_instructors');
    $series_id = VET_CPD_CPD::get_meta($event_id, '_cpd_series');
    
    // Format date display
    $date_display = '';
    if ($start_date) {
        if ($all_day === '1') {
            $start_formatted = date_i18n('j M Y', strtotime($start_date));
        } else {
            $start_formatted = date_i18n('j M Y g:i a', strtotime($start_date));
        }
        
        if ($end_date && $end_date !== $start_date) {
            if ($all_day === '1') {
                $end_formatted = date_i18n('j M Y', strtotime($end_date));
            } else {
                $end_formatted = date_i18n('j M Y g:i a', strtotime($end_date));
            }
            $date_display = $start_formatted . ' - ' . $end_formatted;
        } else {
            $date_display = $start_formatted;
        }
    }
    
    // Format cost
    if ($cost !== '' && $cost !== '0') {
        $symbol = $currency === 'GBP' ? '£' : ($currency === 'EUR' ? '€' : '$');
        $cost_display = $symbol . $cost;
    } else {
        $cost_display = __('Free', 'vet-cpd-directory');
    }
?>

<article class="cpd-single">
    <div class="cpd-container">
        
        <header class="cpd-header">
            <?php if (has_post_thumbnail()) : ?>
                <?php the_post_thumbnail('large', ['class' => 'cpd-featured-image']); ?>
            <?php endif; ?>
            
            <h1><?php the_title(); ?></h1>
            
            <div class="cpd-header-meta">
                <?php if ($date_display) : ?>
                    <span class="cpd-date">📅 <?php echo esc_html($date_display); ?></span>
                <?php endif; ?>
                
                <span class="cpd-cost">💰 <?php echo esc_html($cost_display); ?></span>
                
                <?php
                $tags = get_the_terms($event_id, 'cpd_tag');
                if ($tags && !is_wp_error($tags)) :
                ?>
                    <span class="cpd-status">
                        <?php foreach ($tags as $tag) : ?>
                            <span class="cpd-tag cpd-tag-<?php echo esc_attr($tag->slug); ?>"><?php echo esc_html($tag->name); ?></span>
                        <?php endforeach; ?>
                    </span>
                <?php endif; ?>
            </div>
        </header>
        
        <div class="cpd-content">
            <?php the_content(); ?>
        </div>
        
        <?php if ($provider_url) : ?>
            <div class="cpd-section cpd-provider">
                <h2><?php _e('Register for this CPD', 'vet-cpd-directory'); ?></h2>
                <a href="<?php echo esc_url($provider_url); ?>" class="cpd-provider-button" target="_blank" rel="noopener">
                    <?php _e('Go to Provider Website', 'vet-cpd-directory'); ?> &rarr;
                </a>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($venue_ids) || $online_url) : ?>
            <div class="cpd-section cpd-location">
                <h2><?php _e('Location', 'vet-cpd-directory'); ?></h2>
                
                <?php if (!empty($venue_ids)) : ?>
                    <?php foreach ((array)$venue_ids as $venue_id) : 
                        $venue = get_post($venue_id);
                        if (!$venue) continue;
                        
                        $is_online = strtolower($venue->post_title) === 'online';
                        $venue_address = VET_CPD_Venue::get_full_address($venue_id);
                    ?>
                        <div class="cpd-venue">
                            <h3><?php echo esc_html($venue->post_title); ?></h3>
                            
                            <?php if (!$is_online && $venue_address) : ?>
                                <p class="cpd-venue-address">
                                    <?php echo esc_html($venue_address); ?>
                                </p>
                                
                                <!-- Show map for physical venues -->
                                <div class="cpd-map">
                                    <?php
                                    $map_address = urlencode($venue_address);
                                    $map_url = 'https://maps.google.com/maps?q=' . $map_address . '&t=m&z=15&ie=UTF8&iwloc=&output=embed';
                                    ?>
                                    <iframe width="100%" height="300" frameborder="0" scrolling="no" 
                                            marginheight="0" marginwidth="0" 
                                            src="<?php echo esc_url($map_url); ?>">
                                    </iframe>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
                
                <?php if ($online_url) : ?>
                    <div class="cpd-online">
                        <h3><?php _e('Online Access', 'vet-cpd-directory'); ?></h3>
                        <a href="<?php echo esc_url($online_url); ?>" target="_blank" rel="noopener">
                            <?php _e('Access Online', 'vet-cpd-directory'); ?> &rarr;
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($organiser_ids)) : ?>
            <div class="cpd-section cpd-organisers">
                <h2><?php _e('Organisers', 'vet-cpd-directory'); ?></h2>
                <?php foreach ((array)$organiser_ids as $organiser_id) : 
                    $organiser = get_post($organiser_id);
                    if (!$organiser) continue;
                ?>
                    <div class="cpd-person">
                        <?php if (has_post_thumbnail($organiser_id)) : ?>
                            <?php echo get_the_post_thumbnail($organiser_id, 'thumbnail', ['class' => 'cpd-person-photo']); ?>
                        <?php endif; ?>
                        <div class="cpd-person-info">
                            <h3><?php echo esc_html($organiser->post_title); ?></h3>
                            <div class="cpd-person-bio">
                                <?php echo wp_trim_words($organiser->post_content, 30); ?>
                            </div>
                            <div class="cpd-person-contact">
                                <?php if ($email = get_post_meta($organiser_id, '_organiser_email', true)) : ?>
                                    <a href="mailto:<?php echo esc_attr($email); ?>"><?php echo esc_html($email); ?></a><br>
                                <?php endif; ?>
                                <?php if ($website = get_post_meta($organiser_id, '_organiser_website', true)) : ?>
                                    <a href="<?php echo esc_url($website); ?>" target="_blank" rel="noopener"><?php _e('Website', 'vet-cpd-directory'); ?></a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($instructor_ids)) : ?>
            <div class="cpd-section cpd-instructors">
                <h2><?php _e('Instructors', 'vet-cpd-directory'); ?></h2>
                <?php foreach ((array)$instructor_ids as $instructor_id) : 
                    $instructor = get_post($instructor_id);
                    if (!$instructor) continue;
                ?>
                    <div class="cpd-person">
                        <?php if (has_post_thumbnail($instructor_id)) : ?>
                            <?php echo get_the_post_thumbnail($instructor_id, 'thumbnail', ['class' => 'cpd-person-photo']); ?>
                        <?php endif; ?>
                        <div class="cpd-person-info">
                            <h3><?php echo esc_html($instructor->post_title); ?></h3>
                            <div class="cpd-person-bio">
                                <?php echo wp_trim_words($instructor->post_content, 30); ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
        <?php if ($series_id) : 
            $series = get_post($series_id);
            if ($series) :
                $series_events = get_posts([
                    'post_type' => 'cpd_event',
                    'posts_per_page' => -1,
                    'meta_key' => '_cpd_series_order',
                    'orderby' => 'meta_value_num',
                    'order' => 'ASC',
                    'meta_query' => [
                        ['key' => '_cpd_series', 'value' => $series_id],
                    ],
                ]);
                if (!empty($series_events)) :
        ?>
            <div class="cpd-series-nav">
                <h3><?php printf(__('Part of: %s', 'vet-cpd-directory'), esc_html($series->post_title)); ?></h3>
                <ul>
                    <?php foreach ($series_events as $series_event) : ?>
                        <li <?php if ($series_event->ID == $event_id) echo 'class="current"'; ?>>
                            <a href="<?php echo get_permalink($series_event->ID); ?>">
                                <?php echo esc_html($series_event->post_title); ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; endif; endif; ?>
        
        <!-- Related Events from same organiser -->
        <?php if (!empty($organiser_ids)) : 
            $related_events = get_posts([
                'post_type'      => 'cpd_event',
                'posts_per_page' => 3,
                'post__not_in'   => [$event_id],
                'meta_query'     => [
                    [
                        'key'     => '_cpd_organisers',
                        'value'   => $organiser_ids[0],
                        'compare' => 'LIKE',
                    ],
                ],
            ]);
            
            if (!empty($related_events)) : 
        ?>
            <div class="cpd-section cpd-related">
                <h2><?php _e('Related Events', 'vet-cpd-directory'); ?></h2>
                <div class="cpd-related-grid">
                    <?php foreach ($related_events as $related) : 
                        $rel_date = VET_CPD_CPD::get_meta($related->ID, '_cpd_start_date');
                        $rel_end = VET_CPD_CPD::get_meta($related->ID, '_cpd_end_date');
                        $rel_all_day = VET_CPD_CPD::get_meta($related->ID, '_cpd_all_day');
                        
                        // Format date
                        if ($rel_date) {
                            if ($rel_all_day === '1') {
                                $rel_date_display = date_i18n('j M Y', strtotime($rel_date));
                            } else {
                                $rel_date_display = date_i18n('j M Y @ g:i a', strtotime($rel_date));
                            }
                            
                            if ($rel_end && $rel_end !== $rel_date) {
                                if ($rel_all_day === '1') {
                                    $rel_date_display .= ' - ' . date_i18n('j M Y', strtotime($rel_end));
                                } else {
                                    $rel_date_display .= ' - ' . date_i18n('j M Y @ g:i a', strtotime($rel_end));
                                }
                            }
                        } else {
                            $rel_date_display = '';
                        }
                    ?>
                        <div class="cpd-related-item">
                            <a href="<?php echo get_permalink($related->ID); ?>">
                                <?php if (has_post_thumbnail($related->ID)) : ?>
                                    <?php echo get_the_post_thumbnail($related->ID, 'medium', ['class' => 'cpd-related-img']); ?>
                                <?php endif; ?>
                                <h4><?php echo esc_html($related->post_title); ?></h4>
                                <?php if ($rel_date_display) : ?>
                                    <span class="cpd-related-date"><?php echo esc_html($rel_date_display); ?></span>
                                <?php endif; ?>
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; endif; ?>
        
        <!-- Previous/Next Navigation -->
        <nav class="cpd-post-nav">
            <?php
            $prev_post = get_adjacent_post(false, '', true);
            $next_post = get_adjacent_post(false, '', false);
            ?>
            <div class="cpd-nav-links">
                <?php if ($prev_post) : ?>
                    <div class="cpd-nav-prev">
                        <a href="<?php echo get_permalink($prev_post->ID); ?>">
                            <span class="cpd-nav-label">← <?php _e('Previous', 'vet-cpd-directory'); ?></span>
                            <span class="cpd-nav-title"><?php echo esc_html($prev_post->post_title); ?></span>
                        </a>
                    </div>
                <?php else : ?>
                    <div class="cpd-nav-prev cpd-nav-empty"></div>
                <?php endif; ?>
                
                <?php if ($next_post) : ?>
                    <div class="cpd-nav-next">
                        <a href="<?php echo get_permalink($next_post->ID); ?>">
                            <span class="cpd-nav-label"><?php _e('Next', 'vet-cpd-directory'); ?> →</span>
                            <span class="cpd-nav-title"><?php echo esc_html($next_post->post_title); ?></span>
                        </a>
                    </div>
                <?php else : ?>
                    <div class="cpd-nav-next cpd-nav-empty"></div>
                <?php endif; ?>
            </div>
        </nav>
        
        <footer class="cpd-footer">
            <?php
            $categories = get_the_term_list($event_id, 'cpd_category', '', ', ', '');
            $tags_list = get_the_term_list($event_id, 'cpd_tag', '', ', ', '');
            ?>
            
            <?php if ($categories) : ?>
                <div class="cpd-categories">
                    <strong><?php _e('Categories:', 'vet-cpd-directory'); ?></strong> <?php echo $categories; ?>
                </div>
            <?php endif; ?>
            
            <?php if ($tags_list) : ?>
                <div class="cpd-tags">
                    <strong><?php _e('Tags:', 'vet-cpd-directory'); ?></strong> <?php echo $tags_list; ?>
                </div>
            <?php endif; ?>
        </footer>
        
    </div>
</article>

<?php endwhile; get_footer(); ?>
