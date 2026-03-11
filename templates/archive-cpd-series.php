<?php
/**
 * Archive template for CPD Series
 */

get_header();
?>

<div class="cpd-archive cpd-series-archive">
    <header class="cpd-archive-header">
        <h1><?php post_type_archive_title(); ?></h1>
    </header>
    
    <?php if (have_posts()) : ?>
        <div class="cpd-series-grid">
            <?php while (have_posts()) : the_post(); 
                $series_cpds = VET_CPD_Series::get_cpds(get_the_ID());
            ?>
                <article class="cpd-series-card">
                    <?php if (has_post_thumbnail()) : ?>
                        <?php the_post_thumbnail('medium', ['class' => 'cpd-series-image']); ?>
                    <?php endif; ?>
                    
                    <div class="cpd-series-content">
                        <h2><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
                        
                        <p class="cpd-series-count">
                            <?php printf(_n('%d CPD', '%d CPDs', count($series_cpds), 'vet-cpd-directory'), count($series_cpds)); ?>
                        </p>
                        
                        <div class="cpd-series-excerpt">
                            <?php echo wp_trim_words(get_the_excerpt(), 20); ?>
                        </div>
                    </div>
                </article>
            <?php endwhile; ?>
        </div>
        
        <div class="cpd-pagination">
            <?php
            the_posts_pagination([
                'mid_size'  => 2,
                'prev_text' => __('&laquo; Previous', 'vet-cpd-directory'),
                'next_text' => __('Next &raquo;', 'vet-cpd-directory'),
            ]);
            ?>
        </div>
        
    <?php else : ?>
        <p><?php _e('No series found.', 'vet-cpd-directory'); ?></p>
    <?php endif; ?>
</div>

<?php get_footer(); ?>