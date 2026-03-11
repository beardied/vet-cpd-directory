<?php
/**
 * Shortcode: CPD List template
 * 
 * Variables available:
 * - $cpds: Array of CPD posts
 * - $atts: Shortcode attributes
 */

if (empty($cpds)) : ?>
    <p><?php _e('No CPD events found.', 'vet-cpd-directory'); ?></p>
<?php else : ?>
    <div class="cpd-shortcode-list">
        <?php foreach ($cpds as $cpd) : 
            $date = VET_CPD_CPD::get_meta($cpd->ID, '_cpd_date');
            $hours = VET_CPD_CPD::get_meta($cpd->ID, '_cpd_hours');
            $statuses = VET_CPD_Frontend::get_status($cpd->ID);
        ?>
            <div class="cpd-list-item">
                <h3><a href="<?php echo get_permalink($cpd->ID); ?>"><?php echo esc_html($cpd->post_title); ?></a></h3>
                
                <div class="cpd-list-meta">
                    <?php if ($date) : ?>
                        <span class="cpd-list-date">
                            📅 <?php echo esc_html(VET_CPD_Frontend::format_date($date)); ?>
                        </span>
                    <?php endif; ?>
                    
                    <?php if ($hours) : ?>
                        <span class="cpd-list-hours">
                            ⏱ <?php echo esc_html($hours); ?> <?php _e('hrs', 'vet-cpd-directory'); ?>
                        </span>
                    <?php endif; ?>
                    
                    <?php if (!empty($statuses)) : ?>
                        <span class="cpd-list-tags">
                            <?php foreach ($statuses as $status) : ?>
                                <span class="cpd-tag cpd-tag-<?php echo esc_attr(sanitize_title($status)); ?>">
                                    <?php echo esc_html($status); ?>
                                </span>
                            <?php endforeach; ?>
                        </span>
                    <?php endif; ?>
                </div>
                
                <p class="cpd-list-excerpt">
                    <?php echo wp_trim_words($cpd->post_content, 25); ?>
                </p>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>