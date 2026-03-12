<?php
/**
 * Single Venue Template
 */

get_header();

while (have_posts()) : the_post();
    $venue_id = get_the_ID();
?>

<div class="cpd-archive cpd-venue-single">
    <div class="cpd-container">
        
        <!-- Breadcrumbs -->
        <nav class="cpd-breadcrumbs">
            <a href="<?php echo esc_url(home_url()); ?>"><?php _e('Home', 'vet-cpd-directory'); ?></a>
            <span class="cpd-breadcrumb-sep">&rsaquo;</span>
            <a href="<?php echo esc_url(get_post_type_archive_link('cpd_event')); ?>"><?php _e('CPD Events', 'vet-cpd-directory'); ?></a>
            <span class="cpd-breadcrumb-sep">&rsaquo;</span>
            <span class="cpd-breadcrumb-current"><?php the_title(); ?></span>
        </nav>
        
        <header class="cpd-archive-header">
            <h1 class="cpd-archive-title"><?php the_title(); ?></h1>
        </header>
        
        <div class="cpd-content">
            <?php the_content(); ?>
            
            <?php
            // Venue details
            $address = get_post_meta($venue_id, '_venue_address', true);
            $city = get_post_meta($venue_id, '_venue_city', true);
            $state = get_post_meta($venue_id, '_venue_state', true);
            $postal = get_post_meta($venue_id, '_venue_postal_code', true);
            $country = get_post_meta($venue_id, '_venue_country', true);
            $phone = get_post_meta($venue_id, '_venue_phone', true);
            $website = get_post_meta($venue_id, '_venue_website', true);
            
            if ($address || $city || $state || $postal || $country || $phone || $website) : ?>
                <div class="cpd-venue-details">
                    <h2><?php _e('Venue Details', 'vet-cpd-directory'); ?></h2>
                    
                    <?php if ($address) : ?>
                        <p><strong><?php _e('Address:', 'vet-cpd-directory'); ?></strong> <?php echo esc_html($address); ?></p>
                    <?php endif; ?>
                    
                    <?php if ($city || $state || $postal) : ?>
                        <p>
                            <strong><?php _e('Location:', 'vet-cpd-directory'); ?></strong> 
                            <?php 
                            $location_parts = array_filter([$city, $state, $postal]);
                            echo esc_html(implode(', ', $location_parts));
                            ?>
                        </p>
                    <?php endif; ?>
                    
                    <?php if ($country) : ?>
                        <p><strong><?php _e('Country:', 'vet-cpd-directory'); ?></strong> <?php echo esc_html($country); ?></p>
                    <?php endif; ?>
                    
                    <?php if ($phone) : ?>
                        <p><strong><?php _e('Phone:', 'vet-cpd-directory'); ?></strong> <?php echo esc_html($phone); ?></p>
                    <?php endif; ?>
                    
                    <?php if ($website) : ?>
                        <p>
                            <strong><?php _e('Website:', 'vet-cpd-directory'); ?></strong> 
                            <a href="<?php echo esc_url($website); ?>" target="_blank" rel="noopener">
                                <?php echo esc_html($website); ?>
                            </a>
                        </p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            
            <?php
            // Build full address for map
            $map_address = implode(', ', array_filter([$address, $city, $state, $postal, $country]));
            if ($map_address) :
            ?>
                <div class="cpd-venue-map">
                    <h2><?php _e('Map', 'vet-cpd-directory'); ?></h2>
                    <iframe
                        width="100%"
                        height="400"
                        frameborder="0"
                        scrolling="no"
                        marginheight="0"
                        marginwidth="0"
                        src="https://maps.google.com/maps?q=<?php echo urlencode($map_address); ?>&t=m&z=15&output=embed&iwloc=near"
                        allowfullscreen
                    ></iframe>
                    <p class="cpd-map-link">
                        <a href="https://maps.google.com/maps?q=<?php echo urlencode($map_address); ?>" target="_blank" rel="noopener">
                            <?php _e('View larger map', 'vet-cpd-directory'); ?> &rarr;
                        </a>
                    </p>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Venue Events Shortcode -->
        <?php echo do_shortcode('[cpd_venue_events]'); ?>
        
        <!-- Post Navigation -->
        <nav class="cpd-post-nav">
            <div class="cpd-nav-links">
                <div class="cpd-nav-prev">
                    <?php previous_post_link('%link', '<span class="cpd-nav-arrow">&larr;</span> <span class="cpd-nav-title">%title</span>'); ?>
                </div>
                <div class="cpd-nav-next">
                    <?php next_post_link('%link', '<span class="cpd-nav-title">%title</span> <span class="cpd-nav-arrow">&rarr;</span>'); ?>
                </div>
            </div>
        </nav>
        
    </div>
</div>

<?php 
endwhile;
get_footer(); 
?>
