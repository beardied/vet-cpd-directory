<?php
/**
 * Single CPD Event Template
 * 
 * This template can be overridden by copying it to your theme:
 * your-theme/vet-cpd-directory/single-cpd-event.php
 */

get_header();

$event_id = get_the_ID();
$post = get_post($event_id);

// Get all meta
$meta = VET_CPD_CPD::get_all_meta($event_id);

// Get dates
$start_date = $meta['_cpd_start_date'] ?? '';
$end_date = $meta['_cpd_end_date'] ?? '';
$all_day = $meta['_cpd_all_day'] ?? '0';
$date_display = VET_CPD_Template::get_formatted_date($start_date, $end_date, $all_day === '1');

// Get cost
$cost = $meta['_cpd_cost'] ?? '';
$currency = $meta['_cpd_currency'] ?? 'GBP';
$cost_display = VET_CPD_Template::format_cost($cost, $currency);

// Get venues
$venue_ids = isset($meta['_cpd_venues']) && is_array($meta['_cpd_venues']) ? $meta['_cpd_venues'] : [];

// Get organisers
$organiser_ids = isset($meta['_cpd_organisers']) && is_array($meta['_cpd_organisers']) ? $meta['_cpd_organisers'] : [];

// Get instructors
$instructor_ids = isset($meta['_cpd_instructors']) && is_array($meta['_cpd_instructors']) ? $meta['_cpd_instructors'] : [];

// Get series
$series_id = $meta['_cpd_series'] ?? '';

// Get provider URL
$provider_url = $meta['_cpd_provider_url'] ?? '';

?>

<div class="cpd-single-event">
    <div class="cpd-container">
        
        <!-- Breadcrumbs -->
        <nav class="cpd-breadcrumbs">
            <a href="<?php echo esc_url(home_url('/')); ?>"><?php _e('Home', 'vet-cpd-directory'); ?></a> &raquo;
            <a href="<?php echo esc_url(get_post_type_archive_link('cpd_event')); ?>"><?php _e('CPD Directory', 'vet-cpd-directory'); ?></a> &raquo;
            <span><?php the_title(); ?></span>
        </nav>
        
        <div class="cpd-event-header">
            <?php if (has_post_thumbnail()) : ?>
                <div class="cpd-event-image">
                    <?php the_post_thumbnail('large'); ?>
                </div>
            <?php endif; ?>
            
            <div class="cpd-event-title-wrap">
                <?php 
                $categories = get_the_terms($event_id, 'cpd_category');
                if ($categories && !is_wp_error($categories)) : 
                    $category = $categories[0];
                    ?>
                    <span class="cpd-category-badge">
                        <?php echo esc_html($category->name); ?>
                    </span>
                <?php endif; ?>
                
                <h1 class="cpd-event-title"><?php the_title(); ?></h1>
                
                <div class="cpd-event-meta">
                    <?php if ($start_date) : ?>
                        <div class="cpd-meta-item">
                            <span class="cpd-icon">📅</span>
                            <span><?php echo esc_html($date_display); ?></span>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($cost !== '') : ?>
                        <div class="cpd-meta-item">
                            <span class="cpd-icon">💰</span>
                            <span><?php echo esc_html($cost_display); ?></span>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($provider_url) : ?>
                        <div class="cpd-meta-item">
                            <span class="cpd-icon">🔗</span>
                            <a href="<?php echo esc_url($provider_url); ?>" target="_blank" rel="noopener">
                                <?php _e('Visit Website', 'vet-cpd-directory'); ?>
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <div class="cpd-event-content-wrap">
            <div class="cpd-event-main">
                
                <!-- Description -->
                <div class="cpd-event-description">
                    <h2><?php _e('Description', 'vet-cpd-directory'); ?></h2>
                    <?php the_content(); ?>
                </div>
                
                <!-- Venues -->
                <?php if (!empty($venue_ids)) : ?>
                    <div class="cpd-event-venues">
                        <h2><?php _e('Venue', 'vet-cpd-directory'); ?></h2>
                        <?php foreach ($venue_ids as $venue_id) : 
                            $venue = get_post($venue_id);
                            if (!$venue) continue;
                            
                            $venue_meta = VET_CPD_Venue::get_all_meta($venue_id);
                            $address = VET_CPD_Venue::get_address_display($venue_id);
                            
                            // Check if this is an "Online" venue
                            $is_online = strtolower($venue->post_title) === 'online';
                            ?>
                            <div class="cpd-venue <?php echo $is_online ? 'cpd-venue-online' : 'cpd-venue-physical'; ?>">
                                <h3><?php echo esc_html($venue->post_title); ?></h3>
                                
                                <?php if ($address) : ?>
                                    <p class="cpd-venue-address"><?php echo esc_html($address); ?></p>
                                <?php endif; ?>
                                
                                <?php if ($is_online && $meta['_cpd_online_url']) : ?>
                                    <p class="cpd-online-url">
                                        <a href="<?php echo esc_url($meta['_cpd_online_url']); ?>" target="_blank" rel="noopener">
                                            <?php _e('Join Online Event', 'vet-cpd-directory'); ?> &rarr;
                                        </a>
                                    </p>
                                <?php endif; ?>
                                
                                <?php 
                                // Only show map for physical venues (not Online)
                                if (!$is_online) : 
                                    $map_url = VET_CPD_Venue::get_map_url($venue_id);
                                    if ($map_url) : 
                                        ?>
                                        <div class="cpd-venue-map">
                                            <iframe 
                                                width="100%" 
                                                height="300" 
                                                frameborder="0" 
                                                style="border:0" 
                                                src="<?php echo esc_url($map_url); ?>" 
                                                allowfullscreen>
                                            </iframe>
                                        </div>
                                    <?php endif; 
                                endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                
                <!-- Organisers -->
                <?php if (!empty($organiser_ids)) : ?>
                    <div class="cpd-event-organisers">
                        <h2><?php _e('Organisers', 'vet-cpd-directory'); ?></h2>
                        <div class="cpd-people-list">
                            <?php foreach ($organiser_ids as $organiser_id) : 
                                $organiser = get_post($organiser_id);
                                if (!$organiser) continue;
                                
                                $person_meta = VET_CPD_Person::get_all_meta($organiser_id);
                                ?>
                                <div class="cpd-person-card">
                                    <div class="cpd-person-avatar">
                                        <?php if (has_post_thumbnail($organiser_id)) : ?>
                                            <?php echo get_the_post_thumbnail($organiser_id, 'thumbnail'); ?>
                                        <?php else : ?>
                                            <div class="cpd-avatar-placeholder">👤</div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="cpd-person-info">
                                        <h3 class="cpd-person-name">
                                            <a href="<?php echo esc_url(get_permalink($organiser_id)); ?>">
                                                <?php echo esc_html($organiser->post_title); ?>
                                            </a>
                                        </h3>
                                        <p class="cpd-person-role"><?php _e('Organiser', 'vet-cpd-directory'); ?></p>
                                        
                                        <?php if (!empty($person_meta['_person_phone'])) : ?>
                                            <p class="cpd-person-phone">
                                                <span class="cpd-icon">📞</span>
                                                <a href="tel:<?php echo esc_attr($person_meta['_person_phone']); ?>">
                                                    <?php echo esc_html($person_meta['_person_phone']); ?>
                                                </a>
                                            </p>
                                        <?php endif; ?>
                                        
                                        <?php if (!empty($person_meta['_person_email'])) : ?>
                                            <p class="cpd-person-email">
                                                <span class="cpd-icon">✉️</span>
                                                <a href="mailto:<?php echo esc_attr($person_meta['_person_email']); ?>">
                                                    <?php echo esc_html($person_meta['_person_email']); ?>
                                                </a>
                                            </p>
                                        <?php endif; ?>
                                        
                                        <?php if (!empty($person_meta['_person_website'])) : 
                                            $website_url = $person_meta['_person_website'];
                                            $website_display = parse_url($website_url, PHP_URL_HOST);
                                            $website_display = str_replace('www.', '', $website_display);
                                            ?>
                                            <p class="cpd-person-website">
                                                <span class="cpd-icon">🌐</span>
                                                <?php _e('Website:', 'vet-cpd-directory'); ?>
                                                <a href="<?php echo esc_url($website_url); ?>" target="_blank" rel="noopener">
                                                    <?php echo esc_html($website_display); ?>
                                                </a>
                                            </p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
                
                <!-- Instructors -->
                <?php if (!empty($instructor_ids)) : ?>
                    <div class="cpd-event-instructors">
                        <h2><?php _e('Instructors', 'vet-cpd-directory'); ?></h2>
                        <div class="cpd-people-list">
                            <?php foreach ($instructor_ids as $instructor_id) : 
                                $instructor = get_post($instructor_id);
                                if (!$instructor) continue;
                                
                                $person_meta = VET_CPD_Person::get_all_meta($instructor_id);
                                ?>
                                <div class="cpd-person-card">
                                    <div class="cpd-person-avatar">
                                        <?php if (has_post_thumbnail($instructor_id)) : ?>
                                            <?php echo get_the_post_thumbnail($instructor_id, 'thumbnail'); ?>
                                        <?php else : ?>
                                            <div class="cpd-avatar-placeholder">👤</div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="cpd-person-info">
                                        <h3 class="cpd-person-name">
                                            <a href="<?php echo esc_url(get_permalink($instructor_id)); ?>">
                                                <?php echo esc_html($instructor->post_title); ?>
                                            </a>
                                        </h3>
                                        <p class="cpd-person-role"><?php _e('Instructor', 'vet-cpd-directory'); ?></p>
                                        
                                        <?php if (!empty($person_meta['_person_email'])) : ?>
                                            <p class="cpd-person-email">
                                                <span class="cpd-icon">✉️</span>
                                                <a href="mailto:<?php echo esc_attr($person_meta['_person_email']); ?>">
                                                    <?php echo esc_html($person_meta['_person_email']); ?>
                                                </a>
                                            </p>
                                        <?php endif; ?>
                                        
                                        <?php if (!empty($person_meta['_person_website'])) : 
                                            $website_url = $person_meta['_person_website'];
                                            $website_display = parse_url($website_url, PHP_URL_HOST);
                                            $website_display = str_replace('www.', '', $website_display);
                                            ?>
                                            <p class="cpd-person-website">
                                                <span class="cpd-icon">🌐</span>
                                                <?php _e('Website:', 'vet-cpd-directory'); ?>
                                                <a href="<?php echo esc_url($website_url); ?>" target="_blank" rel="noopener">
                                                    <?php echo esc_html($website_display); ?>
                                                </a>
                                            </p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
                
                <!-- Related Events from same organiser -->
                <?php if (!empty($organiser_ids)) : 
                    $related_query = new WP_Query([
                        'post_type'      => 'cpd_event',
                        'posts_per_page' => 3,
                        'post__not_in'   => [$event_id],
                        'meta_query'     => [
                            [
                                'key'     => '_cpd_organisers',
                                'value'   => '"' . implode('" OR "', $organiser_ids) . '"',
                                'compare' => 'LIKE',
                            ],
                        ],
                    ]);
                    
                    if ($related_query->have_posts()) : 
                        ?>
                        <div class="cpd-related-events">
                            <h2><?php _e('More from this organiser', 'vet-cpd-directory'); ?></h2>
                            <div class="cpd-related-grid">
                                <?php while ($related_query->have_posts()) : $related_query->the_post(); 
                                    $rel_id = get_the_ID();
                                    $rel_date = VET_CPD_CPD::get_meta($rel_id, '_cpd_start_date');
                                    ?>
                                    <div class="cpd-related-card">
                                        <?php if (has_post_thumbnail()) : ?>
                                            <div class="cpd-related-image">
                                                <?php the_post_thumbnail('medium'); ?>
                                            </div>
                                        <?php endif; ?>
                                        <div class="cpd-related-content">
                                            <h3>
                                                <a href="<?php the_permalink(); ?>">
                                                    <?php the_title(); ?>
                                                </a>
                                            </h3>
                                            <?php if ($rel_date) : ?>
                                                <p class="cpd-related-date">
                                                    <?php echo esc_html(date_i18n(get_option('date_format'), strtotime($rel_date))); ?>
                                                </p>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endwhile; wp_reset_postdata(); ?>
                            </div>
                        </div>
                    <?php endif; 
                endif; ?>
                
                <!-- Previous/Next Navigation -->
                <nav class="cpd-event-navigation">
                    <?php
                    $prev_post = get_adjacent_post(false, '', true, 'cpd_category');
                    $next_post = get_adjacent_post(false, '', false, 'cpd_category');
                    ?>
                    <div class="cpd-nav-links">
                        <?php if ($prev_post) : ?>
                            <div class="cpd-nav-previous">
                                <a href="<?php echo esc_url(get_permalink($prev_post)); ?>">
                                    <span class="cpd-nav-label">&larr; <?php _e('Previous', 'vet-cpd-directory'); ?></span>
                                    <span class="cpd-nav-title"><?php echo esc_html(get_the_title($prev_post)); ?></span>
                                </a>
                            </div>
                        <?php else : ?>
                            <div class="cpd-nav-previous cpd-nav-empty"></div>
                        <?php endif; ?>
                        
                        <?php if ($next_post) : ?>
                            <div class="cpd-nav-next">
                                <a href="<?php echo esc_url(get_permalink($next_post)); ?>">
                                    <span class="cpd-nav-label"><?php _e('Next', 'vet-cpd-directory'); ?> &rarr;</span>
                                    <span class="cpd-nav-title"><?php echo esc_html(get_the_title($next_post)); ?></span>
                                </a>
                            </div>
                        <?php else : ?>
                            <div class="cpd-nav-next cpd-nav-empty"></div>
                        <?php endif; ?>
                    </div>
                </nav>
                
            </div>
            
            <!-- Sidebar -->
            <aside class="cpd-event-sidebar">
                
                <?php if ($provider_url) : ?>
                    <div class="cpd-sidebar-widget cpd-widget-register">
                        <h3><?php _e('Register for this event', 'vet-cpd-directory'); ?></h3>
                        <a href="<?php echo esc_url($provider_url); ?>" target="_blank" rel="noopener" class="cpd-button">
                            <?php _e('Book Now', 'vet-cpd-directory'); ?> &rarr;
                        </a>
                    </div>
                <?php endif; ?>
                
                <?php if ($series_id) : 
                    $series = get_post($series_id);
                    if ($series) : 
                        ?>
                        <div class="cpd-sidebar-widget cpd-widget-series">
                            <h3><?php _e('Part of Series', 'vet-cpd-directory'); ?></h3>
                            <div class="cpd-series-card">
                                <?php if (has_post_thumbnail($series_id)) : ?>
                                    <?php echo get_the_post_thumbnail($series_id, 'medium'); ?>
                                <?php endif; ?>
                                <h4><?php echo esc_html($series->post_title); ?></h4>
                                <a href="<?php echo esc_url(get_permalink($series_id)); ?>" class="cpd-button cpd-button-secondary">
                                    <?php _e('View Series', 'vet-cpd-directory'); ?> &rarr;
                                </a>
                            </div>
                        </div>
                    <?php endif; 
                endif; ?>
                
                <?php if ($categories && !is_wp_error($categories)) : ?>
                    <div class="cpd-sidebar-widget cpd-widget-categories">
                        <h3><?php _e('Categories', 'vet-cpd-directory'); ?></h3>
                        <ul class="cpd-taxonomy-list">
                            <?php foreach ($categories as $cat) : ?>
                                <li>
                                    <a href="<?php echo esc_url(get_term_link($cat)); ?>">
                                        <?php echo esc_html($cat->name); ?>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
                
                <?php 
                $types = get_the_terms($event_id, 'cpd_type');
                if ($types && !is_wp_error($types)) : 
                    ?>
                    <div class="cpd-sidebar-widget cpd-widget-types">
                        <h3><?php _e('Types', 'vet-cpd-directory'); ?></h3>
                        <ul class="cpd-taxonomy-list cpd-type-list">
                            <?php foreach ($types as $type) : ?>
                                <li>
                                    <span class="cpd-type-badge cpd-type-<?php echo esc_attr($type->slug); ?>">
                                        <?php echo esc_html($type->name); ?>
                                    </span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
                
            </aside>
        </div>
        
    </div>
</div>

<?php
get_footer();
