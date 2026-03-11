<?php
/**
 * Archive template for CPD Events
 */

get_header();
?>

<div class="cpd-archive">
    <header class="cpd-archive-header">
        <h1><?php post_type_archive_title(); ?></h1>
        
        <?php if (is_tax()) : ?>
            <?php $term = get_queried_object(); ?>
            <p><?php printf(__('Showing CPDs in: %s', 'vet-cpd-directory'), esc_html($term->name)); ?></p>
        <?php endif; ?>
    </header>
    
    <?php if (have_posts()) : ?>
        <div class="cpd-list">
            <?php while (have_posts()) : the_post(); ?>
                <?php
                $date = VET_CPD_CPD::get_meta(get_the_ID(), '_cpd_date');
                $hours = VET_CPD_CPD::get_meta(get_the_ID(), '_cpd_hours');
                $statuses = VET_CPD_Frontend::get_status(get_the_ID());
                ?>
                <article class="cpd-card">
                    <?php if (has_post_thumbnail()) : ?>
                        <?php the_post_thumbnail('medium', ['class' => 'cpd-card-image']); ?>
                    <?php endif; ?>
                    
                    <div class="cpd-card-content">
                        <h2 class="cpd-card-title">
                            <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                        </h2>
                        
                        <div class="cpd-card-meta">
                            <?php if ($date) : ?>
                                <div class="cpd-card-date">
                                    📅 <?php echo esc_html(VET_CPD_Frontend::format_date($date)); ?>
                                </div>
                            <?php endif; ?>
                            
                            <?php if ($hours) : ?>
                                <span class="cpd-card-hours">
                                    ⏱ <?php echo esc_html($hours); ?> <?php _e('hours', 'vet-cpd-directory'); ?>
                                </span>
                            <?php endif; ?>
                        </div>
                        
                        <?php if (!empty($statuses)) : ?>
                            <div class="cpd-card-tags">
                                <?php foreach ($statuses as $status) : ?>
                                    <?php
                                    $slug = sanitize_title($status);
                                    $class = 'cpd-tag cpd-tag-' . esc_attr($slug);
                                    ?>
                                    <span class="<?php echo $class; ?>"><?php echo esc_html($status); ?></span>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                        
                        <div class="cpd-card-excerpt">
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
        <p><?php _e('No CPD events found.', 'vet-cpd-directory'); ?></p>
    <?php endif; ?>
</div>

<?php get_footer(); ?>