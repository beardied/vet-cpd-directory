<?php
/**
 * Single Organiser Template
 */

get_header();

while (have_posts()) : the_post();
    $organiser_id = get_the_ID();
?>

<div class="cpd-archive cpd-organiser-single">
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
            <?php if (has_post_thumbnail()) : ?>
                <div class="cpd-organiser-photo">
                    <?php the_post_thumbnail('medium', ['class' => '']); ?>
                </div>
            <?php endif; ?>
            <h1 class="cpd-archive-title"><?php the_title(); ?></h1>
        </header>
        
        <div class="cpd-content">
            <?php the_content(); ?>
            
            <?php
            // Organiser details
            $phone = get_post_meta($organiser_id, '_organiser_phone', true);
            $email = get_post_meta($organiser_id, '_organiser_email', true);
            $website = get_post_meta($organiser_id, '_organiser_website', true);
            
            if ($phone || $email || $website) : ?>
                <div class="cpd-organiser-contact">
                    <h2><?php _e('Contact Information', 'vet-cpd-directory'); ?></h2>
                    
                    <?php if ($phone) : ?>
                        <p><strong><?php _e('Phone:', 'vet-cpd-directory'); ?></strong> <?php echo esc_html($phone); ?></p>
                    <?php endif; ?>
                    
                    <?php if ($email) : ?>
                        <p>
                            <strong><?php _e('Email:', 'vet-cpd-directory'); ?></strong> 
                            <a href="mailto:<?php echo esc_attr($email); ?>"><?php echo esc_html($email); ?></a>
                        </p>
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
        </div>
        
        <!-- Organiser Events Shortcode -->
        <?php echo do_shortcode('[cpd_organiser_events]'); ?>
        
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
