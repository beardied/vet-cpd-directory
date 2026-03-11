<?php
/**
 * Single CPD Series template
 */

get_header();

while (have_posts()) : the_post();
    $series_cpds = VET_CPD_Series::get_cpds(get_the_ID());
?>

<article class="cpd-series-single">
    <header class="cpd-series-header">
        <?php if (has_post_thumbnail()) : ?>
            <?php the_post_thumbnail('large'); ?>
        <?php endif; ?>
        
        <h1><?php the_title(); ?></h1>
        
        <div class="cpd-series-description">
            <?php the_content(); ?>
        </div>
    </header>
    
    <?php if (!empty($series_cpds)) : ?>
        <section class="cpd-series-events">
            <h2><?php _e('CPDs in this Series', 'vet-cpd-directory'); ?></h2>
            
            <div class="cpd-series-list">
                <?php foreach ($series_cpds as $index => $cpd) : 
                    $date = VET_CPD_CPD::get_meta($cpd->ID, '_cpd_date');
                    $hours = VET_CPD_CPD::get_meta($cpd->ID, '_cpd_hours');
                ?>
                    <div class="cpd-series-item">
                        <span class="cpd-series-number"><?php echo $index + 1; ?></span>
                        <div class="cpd-series-content">
                            <h3><a href="<?php echo get_permalink($cpd->ID); ?>"><?php echo esc_html($cpd->post_title); ?></a></h3>
                            
                            <?php if ($date) : ?>
                                <span class="cpd-series-date">
                                    📅 <?php echo esc_html(VET_CPD_Frontend::format_date($date)); ?>
                                </span>
                            <?php endif; ?>
                            
                            <?php if ($hours) : ?>
                                <span class="cpd-series-hours">
                                    ⏱ <?php echo esc_html($hours); ?> <?php _e('hours', 'vet-cpd-directory'); ?>
                                </span>
                            <?php endif; ?>
                            
                            <p><?php echo wp_trim_words($cpd->post_content, 20); ?></p>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
    <?php else : ?>
        <p><?php _e('No CPDs in this series yet.', 'vet-cpd-directory'); ?></p>
    <?php endif; ?>
</article>

<?php endwhile; get_footer(); ?>