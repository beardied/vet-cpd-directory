<?php
/**
 * Single CPD Event template
 */

get_header();

while (have_posts()) : the_post();
    $date = VET_CPD_CPD::get_meta(get_the_ID(), '_cpd_date');
    $all_day = VET_CPD_CPD::get_meta(get_the_ID(), '_cpd_all_day');
    $hours = VET_CPD_CPD::get_meta(get_the_ID(), '_cpd_hours');
    $provider_url = VET_CPD_CPD::get_meta(get_the_ID(), '_cpd_provider_url');
    $venues = VET_CPD_CPD::get_meta(get_the_ID(), '_cpd_venues');
    $show_map = VET_CPD_CPD::get_meta(get_the_ID(), '_cpd_show_map');
    $online_url = VET_CPD_CPD::get_meta(get_the_ID(), '_cpd_online_url');
    $organizer_id = VET_CPD_CPD::get_meta(get_the_ID(), '_cpd_organizer');
    $instructors = VET_CPD_CPD::get_meta(get_the_ID(), '_cpd_instructors');
    $series_id = VET_CPD_CPD::get_meta(get_the_ID(), '_cpd_series');
    $statuses = VET_CPD_Frontend::get_status(get_the_ID());
?>

<article class="cpd-single">
    <header class="cpd-header">
        <?php if (has_post_thumbnail()) : ?>
            <?php the_post_thumbnail('large', ['class' => 'cpd-featured-image']); ?>
        <?php endif; ?>
        
        <h1><?php the_title(); ?></h1>
        
        <div class="cpd-header-meta">
            <?php if ($date) : ?>
                <span class="cpd-date">📅 <?php echo esc_html(VET_CPD_Frontend::format_date($date, $all_day)); ?></span>
            <?php endif; ?>
            
            <?php if ($hours) : ?>
                <span class="cpd-hours">⏱ <?php echo esc_html($hours); ?> <?php _e('CPD hours', 'vet-cpd-directory'); ?></span>
            <?php endif; ?>
            
            <?php if (!empty($statuses)) : ?>
                <span class="cpd-status">
                    <?php foreach ($statuses as $status) : ?>
                        <?php
                        $slug = sanitize_title($status);
                        $class = 'cpd-tag cpd-tag-' . esc_attr($slug);
                        ?>
                        <span class="<?php echo $class; ?>"><?php echo esc_html($status); ?></span>
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
    
    <?php if (!empty($venues) || $online_url) : ?>
        <div class="cpd-section cpd-location">
            <h2><?php _e('Location', 'vet-cpd-directory'); ?></h2>
            
            <?php if (!empty($venues)) : ?>
                <?php foreach ((array)$venues as $venue_id) : 
                    $venue = get_post($venue_id);
                    if (!$venue) continue;
                ?>
                    <div class="cpd-venue">
                        <h3><?php echo esc_html($venue->post_title); ?></h3>
                        <p class="cpd-venue-address">
                            <?php echo esc_html(VET_CPD_Venue::get_full_address($venue_id)); ?>
                        </p>
                        
                        <?php if ($show_map) : ?>
                            <div class="cpd-map">
                                <?php
                                $address = urlencode(VET_CPD_Venue::get_full_address($venue_id));
                                $map_url = 'https://maps.google.com/maps?q=' . $address . '&t=m&z=15&ie=UTF8&iwloc=&output=embed';
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
    
    <?php if ($organizer_id) : 
        $organizer = get_post($organizer_id);
        if ($organizer) :
    ?>
        <div class="cpd-section cpd-organizer">
            <h2><?php _e('Organizer', 'vet-cpd-directory'); ?></h2>
            <div class="cpd-person">
                <?php if (has_post_thumbnail($organizer_id)) : ?>
                    <?php echo get_the_post_thumbnail($organizer_id, 'thumbnail', ['class' => 'cpd-person-photo']); ?>
                <?php endif; ?>
                <div class="cpd-person-info">
                    <h3><?php echo esc_html($organizer->post_title); ?></h3>
                    <div class="cpd-person-bio">
                        <?php echo wp_trim_words($organizer->post_content, 30); ?>
                    </div>
                    <div class="cpd-person-contact">
                        <?php if ($email = VET_CPD_Person::get_meta($organizer_id, '_person_email')) : ?>
                            <a href="mailto:<?php echo esc_attr($email); ?>"><?php echo esc_html($email); ?></a><br>
                        <?php endif; ?>
                        <?php if ($website = VET_CPD_Person::get_meta($organizer_id, '_person_website')) : ?>
                            <a href="<?php echo esc_url($website); ?>" target="_blank" rel="noopener"><?php _e('Website', 'vet-cpd-directory'); ?></a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; endif; ?>
    
    <?php if (!empty($instructors)) : ?>
        <div class="cpd-section cpd-instructors">
            <h2><?php _e('Instructors', 'vet-cpd-directory'); ?></h2>
            <?php foreach ((array)$instructors as $instructor_id) : 
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
            $series_cpds = VET_CPD_Series::get_cpds($series_id);
    ?>
        <div class="cpd-series-nav">
            <h3><?php printf(__('Part of: %s', 'vet-cpd-directory'), esc_html($series->post_title)); ?></h3>
            <ul>
                <?php foreach ($series_cpds as $series_cpd) : ?>
                    <li <?php if ($series_cpd->ID == get_the_ID()) echo 'class="current"'; ?>>
                        <a href="<?php echo get_permalink($series_cpd->ID); ?>">
                            <?php echo esc_html($series_cpd->post_title); ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; endif; ?>
    
    <footer class="cpd-footer">
        <?php
        $categories = get_the_term_list(get_the_ID(), VET_CPD_Taxonomies::CATEGORY, '', ', ', '');
        $tags = get_the_term_list(get_the_ID(), VET_CPD_Taxonomies::TAG, '', ', ', '');
        ?>
        
        <?php if ($categories) : ?>
            <div class="cpd-categories">
                <strong><?php _e('Categories:', 'vet-cpd-directory'); ?></strong> <?php echo $categories; ?>
            </div>
        <?php endif; ?>
        
        <?php if ($tags) : ?>
            <div class="cpd-tags">
                <strong><?php _e('Tags:', 'vet-cpd-directory'); ?></strong> <?php echo $tags; ?>
            </div>
        <?php endif; ?>
    </footer>
</article>

<?php endwhile; get_footer(); ?>