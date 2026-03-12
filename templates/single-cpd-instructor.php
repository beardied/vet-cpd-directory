<?php
/**
 * Single Instructor Template
 */

get_header();

while (have_posts()) : the_post();
    $instructor_id = get_the_ID();
?>

<div class="cpd-archive cpd-instructor-single">
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
                <div class="cpd-instructor-photo">
                    <?php the_post_thumbnail('medium', ['class' => '']); ?>
                </div>
            <?php endif; ?>
            <h1 class="cpd-archive-title"><?php the_title(); ?></h1>
        </header>
        
        <div class="cpd-content">
            <?php the_content(); ?>
            
            <?php
            // Instructor details
            $email = get_post_meta($instructor_id, '_instructor_email', true);
            $website = get_post_meta($instructor_id, '_instructor_website', true);
            
            if ($email || $website) : ?>
                <div class="cpd-instructor-contact">
                    <h2><?php _e('Contact Information', 'vet-cpd-directory'); ?></h2>
                    
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
        
        <!-- Instructor Events Shortcode -->
        <?php echo do_shortcode('[cpd_instructor_events]'); ?>
        
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
