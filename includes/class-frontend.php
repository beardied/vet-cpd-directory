<?php
/**
 * Frontend functionality
 */

class VET_CPD_Frontend {
    
    public static function init() {
        // Template loading
        add_filter('template_include', [__CLASS__, 'template_loader']);
        
        // Enqueue assets
        add_action('wp_enqueue_scripts', [__CLASS__, 'enqueue_assets']);
        
        // Register widget area
        add_action('widgets_init', [__CLASS__, 'register_widget_area']);
        
        // Register shortcodes
        add_shortcode('cpd_venue_events', [__CLASS__, 'shortcode_venue_events']);
        add_shortcode('cpd_instructor_events', [__CLASS__, 'shortcode_instructor_events']);
        add_shortcode('cpd_organiser_events', [__CLASS__, 'shortcode_organiser_events']);
    }
    
    /**
     * Register CPD sidebar widget area
     */
    public static function register_widget_area() {
        register_sidebar([
            'name'          => __('CPD Sidebar', 'vet-cpd-directory'),
            'id'            => 'cpd-sidebar',
            'description'   => __('Widgets displayed in the CPD archive and event pages sidebar.', 'vet-cpd-directory'),
            'before_widget' => '<div id="%1$s" class="cpd-widget %2$s">',
            'after_widget'  => '</div>',
            'before_title'  => '<h3 class="cpd-widget-title">',
            'after_title'   => '</h3>',
        ]);
    }
    
    /**
     * Template loader
     */
    public static function template_loader($template) {
        if (is_singular(VET_CPD_CPD::POST_TYPE)) {
            $template = self::locate_template('single-cpd-event.php');
        } elseif (is_singular(VET_CPD_Series::POST_TYPE)) {
            $template = self::locate_template('single-cpd-series.php');
        } elseif (is_post_type_archive(VET_CPD_CPD::POST_TYPE)) {
            $template = self::locate_template('archive-cpd-event.php');
        } elseif (is_post_type_archive(VET_CPD_Series::POST_TYPE)) {
            $template = self::locate_template('archive-cpd-series.php');
        } elseif (is_tax(VET_CPD_Taxonomies::CATEGORY) || is_tax(VET_CPD_Taxonomies::TAG)) {
            $template = self::locate_template('taxonomy-cpd.php');
        }
        
        return $template;
    }
    
    /**
     * Locate template
     */
    private static function locate_template($template_name) {
        $template_path = locate_template('vet-cpd-directory/' . $template_name);
        
        if (!$template_path) {
            $template_path = VET_CPD_PLUGIN_DIR . 'templates/' . $template_name;
        }
        
        return file_exists($template_path) ? $template_path : false;
    }
    
    /**
     * Enqueue frontend assets
     */
    public static function enqueue_assets() {
        if (!is_singular(VET_CPD_CPD::POST_TYPE) && 
            !is_singular(VET_CPD_Series::POST_TYPE) && 
            !is_post_type_archive(VET_CPD_CPD::POST_TYPE) &&
            !is_post_type_archive(VET_CPD_Series::POST_TYPE) &&
            !is_tax([VET_CPD_Taxonomies::CATEGORY, VET_CPD_Taxonomies::TAG])) {
            return;
        }
        
        wp_enqueue_style('vet-cpd-frontend', VET_CPD_PLUGIN_URL . 'assets/css/frontend.css', [], VET_CPD_VERSION);
    }
    
    /**
     * Shortcode: [cpd_venue_events]
     * Display events for a specific venue
     * Usage: [cpd_venue_events venue_id="123" limit="5"]
     */
    public static function shortcode_venue_events($atts) {
        $atts = shortcode_atts([
            'venue_id' => 0,
            'limit'    => 5,
        ], $atts, 'cpd_venue_events');
        
        $venue_id = intval($atts['venue_id']);
        if (!$venue_id) {
            return '<p>' . __('Please specify a venue ID.', 'vet-cpd-directory') . '</p>';
        }
        
        $events = get_posts([
            'post_type'      => 'cpd_event',
            'posts_per_page' => intval($atts['limit']),
            'meta_query'     => [
                [
                    'key'     => '_cpd_venues',
                    'value'   => $venue_id,
                    'compare' => 'LIKE',
                ],
            ],
        ]);
        
        if (empty($events)) {
            return '<p>' . __('No events found for this venue.', 'vet-cpd-directory') . '</p>';
        }
        
        ob_start();
        ?>
        <div class="cpd-shortcode-events cpd-venue-events">
            <h3><?php _e('Events at this venue', 'vet-cpd-directory'); ?></h3>
            <div class="cpd-events-list">
                <?php foreach ($events as $event) : 
                    $event_id = $event->ID;
                    $start_date = VET_CPD_CPD::get_meta($event_id, '_cpd_start_date');
                    $cost = VET_CPD_CPD::get_meta($event_id, '_cpd_cost');
                    $currency = VET_CPD_CPD::get_meta($event_id, '_cpd_currency') ?: 'GBP';
                    
                    // Format cost
                    if ($cost !== '' && $cost !== '0') {
                        $symbol = $currency === 'GBP' ? '£' : ($currency === 'EUR' ? '€' : '$');
                        $cost_display = $symbol . $cost;
                    } else {
                        $cost_display = __('Free', 'vet-cpd-directory');
                    }
                    ?>
                    <div class="cpd-event-item">
                        <?php if (has_post_thumbnail($event_id)) : ?>
                            <div class="cpd-event-thumb">
                                <?php echo get_the_post_thumbnail($event_id, 'thumbnail'); ?>
                            </div>
                        <?php endif; ?>
                        <div class="cpd-event-info">
                            <h4><a href="<?php echo esc_url(get_permalink($event_id)); ?>"><?php echo esc_html($event->post_title); ?></a></h4>
                            <?php if ($start_date) : ?>
                                <span class="cpd-event-date">📅 <?php echo esc_html(date_i18n(get_option('date_format'), strtotime($start_date))); ?></span>
                            <?php endif; ?>
                            <span class="cpd-event-cost">💰 <?php echo esc_html($cost_display); ?></span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Shortcode: [cpd_instructor_events]
     * Display events for a specific instructor
     * Usage: [cpd_instructor_events instructor_id="123" limit="5"]
     */
    public static function shortcode_instructor_events($atts) {
        $atts = shortcode_atts([
            'instructor_id' => 0,
            'limit'         => 5,
        ], $atts, 'cpd_instructor_events');
        
        $instructor_id = intval($atts['instructor_id']);
        if (!$instructor_id) {
            return '<p>' . __('Please specify an instructor ID.', 'vet-cpd-directory') . '</p>';
        }
        
        $events = get_posts([
            'post_type'      => 'cpd_event',
            'posts_per_page' => intval($atts['limit']),
            'meta_query'     => [
                [
                    'key'     => '_cpd_instructors',
                    'value'   => $instructor_id,
                    'compare' => 'LIKE',
                ],
            ],
        ]);
        
        if (empty($events)) {
            return '<p>' . __('No events found for this instructor.', 'vet-cpd-directory') . '</p>';
        }
        
        ob_start();
        ?>
        <div class="cpd-shortcode-events cpd-instructor-events">
            <h3><?php _e('Events by this instructor', 'vet-cpd-directory'); ?></h3>
            <div class="cpd-events-list">
                <?php foreach ($events as $event) : 
                    $event_id = $event->ID;
                    $start_date = VET_CPD_CPD::get_meta($event_id, '_cpd_start_date');
                    $cost = VET_CPD_CPD::get_meta($event_id, '_cpd_cost');
                    $currency = VET_CPD_CPD::get_meta($event_id, '_cpd_currency') ?: 'GBP';
                    
                    // Format cost
                    if ($cost !== '' && $cost !== '0') {
                        $symbol = $currency === 'GBP' ? '£' : ($currency === 'EUR' ? '€' : '$');
                        $cost_display = $symbol . $cost;
                    } else {
                        $cost_display = __('Free', 'vet-cpd-directory');
                    }
                    ?>
                    <div class="cpd-event-item">
                        <?php if (has_post_thumbnail($event_id)) : ?>
                            <div class="cpd-event-thumb">
                                <?php echo get_the_post_thumbnail($event_id, 'thumbnail'); ?>
                            </div>
                        <?php endif; ?>
                        <div class="cpd-event-info">
                            <h4><a href="<?php echo esc_url(get_permalink($event_id)); ?>"><?php echo esc_html($event->post_title); ?></a></h4>
                            <?php if ($start_date) : ?>
                                <span class="cpd-event-date">📅 <?php echo esc_html(date_i18n(get_option('date_format'), strtotime($start_date))); ?></span>
                            <?php endif; ?>
                            <span class="cpd-event-cost">💰 <?php echo esc_html($cost_display); ?></span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Shortcode: [cpd_organiser_events]
     * Display events for a specific organiser
     * Usage: [cpd_organiser_events organiser_id="123" limit="5"]
     */
    public static function shortcode_organiser_events($atts) {
        $atts = shortcode_atts([
            'organiser_id' => 0,
            'limit'        => 5,
        ], $atts, 'cpd_organiser_events');
        
        $organiser_id = intval($atts['organiser_id']);
        if (!$organiser_id) {
            return '<p>' . __('Please specify an organiser ID.', 'vet-cpd-directory') . '</p>';
        }
        
        $events = get_posts([
            'post_type'      => 'cpd_event',
            'posts_per_page' => intval($atts['limit']),
            'meta_query'     => [
                [
                    'key'     => '_cpd_organisers',
                    'value'   => $organiser_id,
                    'compare' => 'LIKE',
                ],
            ],
        ]);
        
        if (empty($events)) {
            return '<p>' . __('No events found for this organiser.', 'vet-cpd-directory') . '</p>';
        }
        
        ob_start();
        ?>
        <div class="cpd-shortcode-events cpd-organiser-events">
            <h3><?php _e('Events by this organiser', 'vet-cpd-directory'); ?></h3>
            <div class="cpd-events-list">
                <?php foreach ($events as $event) : 
                    $event_id = $event->ID;
                    $start_date = VET_CPD_CPD::get_meta($event_id, '_cpd_start_date');
                    $cost = VET_CPD_CPD::get_meta($event_id, '_cpd_cost');
                    $currency = VET_CPD_CPD::get_meta($event_id, '_cpd_currency') ?: 'GBP';
                    
                    // Format cost
                    if ($cost !== '' && $cost !== '0') {
                        $symbol = $currency === 'GBP' ? '£' : ($currency === 'EUR' ? '€' : '$');
                        $cost_display = $symbol . $cost;
                    } else {
                        $cost_display = __('Free', 'vet-cpd-directory');
                    }
                    ?>
                    <div class="cpd-event-item">
                        <?php if (has_post_thumbnail($event_id)) : ?>
                            <div class="cpd-event-thumb">
                                <?php echo get_the_post_thumbnail($event_id, 'thumbnail'); ?>
                            </div>
                        <?php endif; ?>
                        <div class="cpd-event-info">
                            <h4><a href="<?php echo esc_url(get_permalink($event_id)); ?>"><?php echo esc_html($event->post_title); ?></a></h4>
                            <?php if ($start_date) : ?>
                                <span class="cpd-event-date">📅 <?php echo esc_html(date_i18n(get_option('date_format'), strtotime($start_date))); ?></span>
                            <?php endif; ?>
                            <span class="cpd-event-cost">💰 <?php echo esc_html($cost_display); ?></span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Helper: Format date
     */
    public static function format_date($date_string, $all_day = false) {
        if (empty($date_string)) {
            return '';
        }
        
        $format = $all_day ? get_option('date_format') : get_option('date_format') . ' ' . get_option('time_format');
        return date_i18n($format, strtotime($date_string));
    }
    
    /**
     * Helper: Get CPD status
     */
    public static function get_status($post_id) {
        $terms = get_the_terms($post_id, VET_CPD_Taxonomies::TAG);
        if (empty($terms) || is_wp_error($terms)) {
            return [];
        }
        
        $statuses = [];
        foreach ($terms as $term) {
            if (in_array($term->slug, ['upcoming', 'on-demand', 'online', 'free'])) {
                $statuses[] = $term->name;
            }
        }
        return $statuses;
    }
}
