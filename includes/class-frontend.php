<?php
/**
 * Frontend functionality
 */

class VET_CPD_Frontend {
    
    public static function init() {
        // Handle 301 redirects from old TEC URLs to new Vet CPD URLs
        add_action('template_redirect', [__CLASS__, 'handle_old_url_redirects']);
        
        // Order CPD events by start date on taxonomy pages
        add_action('pre_get_posts', [__CLASS__, 'order_events_by_start_date']);
        
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
        add_shortcode('cpd_upcoming', [__CLASS__, 'shortcode_upcoming_events']);
        
        // Fix page title for category index
        add_filter('wp_title', [__CLASS__, 'fix_category_index_title'], 10, 2);
        add_filter('document_title_parts', [__CLASS__, 'fix_category_index_doc_title']);
    }
    
    /**
     * Handle 301 redirects from old The Events Calendar URLs to new Vet CPD URLs
     * Maps old TEC URLs to the new plugin structure
     */
    public static function handle_old_url_redirects() {
        // Only run on frontend
        if (is_admin()) {
            return;
        }
        
        $path = $_SERVER['REQUEST_URI'];
        
        // 1. Simple Matches - exact URL redirects
        $static_redirects = [
            '/up-coming-cpd/' => '/cpd-type/upcoming/',
            '/free-cpd/'      => '/cpd-type/free/',
            '/upcomingcpd/category/on-demand/' => '/cpd-type/on-demand/',
        ];
        
        if (isset($static_redirects[$path])) {
            wp_redirect(home_url($static_redirects[$path]), 301);
            exit;
        }
        
        // 2. Catch-all for Categories (/upcomingcpd/category/on-demand/something -> /cpd-category/something)
        if (strpos($path, '/upcomingcpd/category/on-demand/') === 0) {
            $new_path = str_replace('/upcomingcpd/category/on-demand/', '/cpd-category/', $path);
            wp_redirect(home_url($new_path), 301);
            exit;
        }
        
        // 3. Catch-all for Events (/event/post-name -> /cpd/post-name)
        if (strpos($path, '/event/') === 0) {
            $new_path = str_replace('/event/', '/cpd/', $path);
            wp_redirect(home_url($new_path), 301);
            exit;
        }
    }
    
    /**
     * Order CPD events by start date (soonest first) on taxonomy pages
     */
    public static function order_events_by_start_date($query) {
        // Only modify frontend taxonomy queries for cpd_event post type
        if (is_admin() || !$query->is_main_query()) {
            return;
        }
        
        // Check if this is a CPD taxonomy page
        if ($query->is_tax(['cpd_category', 'cpd_tag']) || $query->is_post_type_archive('cpd_event')) {
            $query->set('meta_key', '_cpd_start_date');
            $query->set('orderby', 'meta_value');
            $query->set('order', 'ASC');
            $query->set('meta_type', 'DATETIME');
            
            // Only show events with future start dates (or include all if you want past events too)
            // For now, let's show all but ordered by date
        }
    }
    
    /**
     * Fix page title for CPD category index
     */
    public static function fix_category_index_title($title, $sep) {
        if (self::is_cpd_category_base()) {
            return 'CPD Categories ' . $sep . ' ' . get_bloginfo('name');
        }
        return $title;
    }
    
    /**
     * Fix document title parts for CPD category index
     */
    public static function fix_category_index_doc_title($title_parts) {
        if (self::is_cpd_category_base()) {
            $title_parts['title'] = 'CPD Categories';
            unset($title_parts['page']);
        }
        return $title_parts;
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
        } elseif (is_singular('cpd_venue')) {
            $template = self::locate_template('single-cpd-venue.php');
        } elseif (is_singular('cpd_organiser')) {
            $template = self::locate_template('single-cpd-organiser.php');
        } elseif (is_singular('cpd_instructor')) {
            $template = self::locate_template('single-cpd-instructor.php');
        } elseif (is_post_type_archive(VET_CPD_CPD::POST_TYPE)) {
            $template = self::locate_template('archive-cpd-event.php');
        } elseif (is_post_type_archive(VET_CPD_Series::POST_TYPE)) {
            $template = self::locate_template('archive-cpd-series.php');
        } elseif (is_tax(VET_CPD_Taxonomies::CATEGORY) || is_tax(VET_CPD_Taxonomies::TAG)) {
            $template = self::locate_template('taxonomy-cpd.php');
        } elseif (self::is_cpd_category_base()) {
            $template = self::locate_template('taxonomy-cpd-category-index.php');
        }
        
        return $template;
    }
    
    /**
     * Check if we're on the cpd-category base page (/cpd-category/)
     */
    public static function is_cpd_category_base() {
        $uri = trim($_SERVER['REQUEST_URI'], '/');
        return $uri === 'cpd-category' || $uri === 'cpd-category/';
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
        // Check if we're on a CPD page
        $is_cpd_page = is_singular(VET_CPD_CPD::POST_TYPE) || 
            is_singular(VET_CPD_Series::POST_TYPE) || 
            is_singular('cpd_venue') ||
            is_singular('cpd_organiser') ||
            is_singular('cpd_instructor') ||
            is_post_type_archive(VET_CPD_CPD::POST_TYPE) ||
            is_post_type_archive(VET_CPD_Series::POST_TYPE) ||
            is_tax([VET_CPD_Taxonomies::CATEGORY, VET_CPD_Taxonomies::TAG]) ||
            self::is_cpd_category_base();
        
        // Check if any CPD shortcodes are present in the content
        $has_shortcode = false;
        if (is_singular()) {
            $post = get_post();
            if ($post && $post->post_content) {
                $shortcodes = ['cpd_upcoming', 'cpd_venue_events', 'cpd_instructor_events', 'cpd_organiser_events'];
                foreach ($shortcodes as $shortcode) {
                    if (has_shortcode($post->post_content, $shortcode)) {
                        $has_shortcode = true;
                        break;
                    }
                }
            }
        }
        
        if (!$is_cpd_page && !$has_shortcode) {
            return;
        }
        
        wp_enqueue_style('vet-cpd-frontend', VET_CPD_PLUGIN_URL . 'assets/css/frontend.css', [], VET_CPD_VERSION);
    }
    
    /**
     * Shortcode: [cpd_venue_events]
     * Display events for a specific venue
     * Usage: [cpd_venue_events venue_id="123"] or auto-detect on venue page
     */
    public static function shortcode_venue_events($atts) {
        $atts = shortcode_atts([
            'venue_id' => 0,
        ], $atts, 'cpd_venue_events');
        
        $venue_id = intval($atts['venue_id']);
        
        // Auto-detect venue ID if on a venue page
        if (!$venue_id && is_singular('cpd_venue')) {
            $venue_id = get_the_ID();
        }
        
        if (!$venue_id) {
            return '<p>' . __('Please specify a venue ID or use on a venue page.', 'vet-cpd-directory') . '</p>';
        }
        
        $events = get_posts([
            'post_type'      => 'cpd_event',
            'posts_per_page' => -1, // No limit - show all
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
            <div class="cpd-list">
                <?php foreach ($events as $event) : 
                    echo self::render_event_card($event);
                endforeach; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Shortcode: [cpd_instructor_events]
     * Display events for a specific instructor
     * Usage: [cpd_instructor_events instructor_id="123"] or auto-detect on instructor page
     */
    public static function shortcode_instructor_events($atts) {
        $atts = shortcode_atts([
            'instructor_id' => 0,
        ], $atts, 'cpd_instructor_events');
        
        $instructor_id = intval($atts['instructor_id']);
        
        // Auto-detect instructor ID if on an instructor page
        if (!$instructor_id && is_singular('cpd_instructor')) {
            $instructor_id = get_the_ID();
        }
        
        if (!$instructor_id) {
            return '<p>' . __('Please specify an instructor ID or use on an instructor page.', 'vet-cpd-directory') . '</p>';
        }
        
        $events = get_posts([
            'post_type'      => 'cpd_event',
            'posts_per_page' => -1, // No limit - show all
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
            <div class="cpd-list">
                <?php foreach ($events as $event) : 
                    echo self::render_event_card($event);
                endforeach; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Shortcode: [cpd_organiser_events]
     * Display events for a specific organiser
     * Usage: [cpd_organiser_events organiser_id="123"] or auto-detect on organiser page
     */
    public static function shortcode_organiser_events($atts) {
        $atts = shortcode_atts([
            'organiser_id' => 0,
        ], $atts, 'cpd_organiser_events');
        
        $organiser_id = intval($atts['organiser_id']);
        
        // Auto-detect organiser ID if on an organiser page
        if (!$organiser_id && is_singular('cpd_organiser')) {
            $organiser_id = get_the_ID();
        }
        
        if (!$organiser_id) {
            return '<p>' . __('Please specify an organiser ID or use on an organiser page.', 'vet-cpd-directory') . '</p>';
        }
        
        $events = get_posts([
            'post_type'      => 'cpd_event',
            'posts_per_page' => -1, // No limit - show all
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
            <div class="cpd-list">
                <?php foreach ($events as $event) : 
                    echo self::render_event_card($event);
                endforeach; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Shortcode: [cpd_upcoming]
     * Display the 3 newest upcoming CPD events as tiles
     * Usage: [cpd_upcoming]
     */
    public static function shortcode_upcoming_events($atts) {
        $atts = shortcode_atts([
            'count' => 3,
        ], $atts, 'cpd_upcoming');
        
        $count = intval($atts['count']);
        
        // Get upcoming events
        $events = get_posts([
            'post_type'      => 'cpd_event',
            'posts_per_page' => $count,
            'orderby'        => 'meta_value',
            'meta_key'       => '_cpd_start_date',
            'order'          => 'ASC',
            'meta_query'     => [
                [
                    'key'     => '_cpd_start_date',
                    'value'   => date('Y-m-d H:i:s'),
                    'compare' => '>=',
                    'type'    => 'DATETIME',
                ],
            ],
        ]);
        
        if (empty($events)) {
            return '<p>' . __('No upcoming events found.', 'vet-cpd-directory') . '</p>';
        }
        
        ob_start();
        ?>
        <div class="cpd-upcoming-shortcode">
            <div class="cpd-upcoming-grid">
                <?php foreach ($events as $event) : 
                    echo self::render_compact_event_card($event);
                endforeach; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Helper: Render a compact event card for shortcodes
     */
    public static function render_compact_event_card($event) {
        $event_id = $event->ID;
        $start_date = VET_CPD_CPD::get_meta($event_id, '_cpd_start_date');
        $cost = VET_CPD_CPD::get_meta($event_id, '_cpd_cost');
        $currency = VET_CPD_CPD::get_meta($event_id, '_cpd_currency') ?: 'GBP';
        $all_day = VET_CPD_CPD::get_meta($event_id, '_cpd_all_day');
        
        // Format date
        if ($start_date) {
            if ($all_day === '1') {
                $date_display = date_i18n('j M Y', strtotime($start_date));
            } else {
                $date_display = date_i18n('j M Y', strtotime($start_date));
            }
        } else {
            $date_display = '';
        }
        
        // Format cost
        if (self::is_past_physical_event($event_id)) {
            $cost_display = __('Past Event', 'vet-cpd-directory');
        } elseif ($cost !== '' && $cost !== '0') {
            $symbol = $currency === 'GBP' ? '£' : ($currency === 'EUR' ? '€' : '$');
            $cost_display = $symbol . $cost;
        } else {
            $cost_display = __('Free', 'vet-cpd-directory');
        }
        
        ob_start();
        ?>
        <article class="cpd-compact-card">
            <a href="<?php echo esc_url(get_permalink($event_id)); ?>" class="cpd-compact-link">
                <?php if (has_post_thumbnail($event_id)) : ?>
                    <div class="cpd-compact-image">
                        <?php echo get_the_post_thumbnail($event_id, 'medium', ['class' => 'cpd-compact-img']); ?>
                    </div>
                <?php endif; ?>
                <div class="cpd-compact-content">
                    <h3 class="cpd-compact-title"><?php echo esc_html($event->post_title); ?></h3>
                    <div class="cpd-compact-meta">
                        <?php if ($date_display) : ?>
                            <span class="cpd-compact-date"><?php echo esc_html($date_display); ?></span>
                        <?php endif; ?>
                        <span class="cpd-compact-cost"><?php echo esc_html($cost_display); ?></span>
                    </div>
                </div>
            </a>
        </article>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Helper: Render an event card with pill badges
     */
    public static function render_event_card($event) {
        $event_id = $event->ID;
        $start_date = VET_CPD_CPD::get_meta($event_id, '_cpd_start_date');
        $end_date = VET_CPD_CPD::get_meta($event_id, '_cpd_end_date');
        $cost = VET_CPD_CPD::get_meta($event_id, '_cpd_cost');
        $currency = VET_CPD_CPD::get_meta($event_id, '_cpd_currency') ?: 'GBP';
        $all_day = VET_CPD_CPD::get_meta($event_id, '_cpd_all_day');
        
        // Format date
        if ($start_date) {
            if ($all_day === '1') {
                $date_display = date_i18n('j M Y', strtotime($start_date));
            } else {
                $date_display = date_i18n('j M Y g:i a', strtotime($start_date));
            }
        } else {
            $date_display = '';
        }
        
        // Format cost (show "Past Event" for past physical events)
        if (self::is_past_physical_event($event_id)) {
            $cost_display = __('Past Event', 'vet-cpd-directory');
        } elseif ($cost !== '' && $cost !== '0') {
            $symbol = $currency === 'GBP' ? '£' : ($currency === 'EUR' ? '€' : '$');
            $cost_display = $symbol . $cost;
        } else {
            $cost_display = __('Free', 'vet-cpd-directory');
        }
        
        // Get categories and tags
        $categories = get_the_terms($event_id, VET_CPD_Taxonomies::CATEGORY);
        $tags = get_the_terms($event_id, VET_CPD_Taxonomies::TAG);
        
        ob_start();
        ?>
        <article class="cpd-card cpd-shortcode-card">
            <a href="<?php echo esc_url(get_permalink($event_id)); ?>" class="cpd-card-link">
                <?php if (has_post_thumbnail($event_id)) : ?>
                    <?php echo get_the_post_thumbnail($event_id, 'medium_large', ['class' => 'cpd-card-image']); ?>
                <?php endif; ?>
                
                <div class="cpd-card-content">
                    <h2 class="cpd-card-title"><?php echo esc_html($event->post_title); ?></h2>
                    
                    <div class="cpd-card-meta">
                        <?php if ($date_display) : ?>
                            <span class="cpd-card-date">📅 <?php echo esc_html($date_display); ?></span>
                        <?php endif; ?>
                        <span class="cpd-card-cost">💰 <?php echo esc_html($cost_display); ?></span>
                    </div>
                    
                    <?php if (!empty($categories) && !is_wp_error($categories)) : ?>
                        <div class="cpd-card-badges cpd-categories">
                            <?php foreach ($categories as $cat) : ?>
                                <span class="cpd-badge cpd-badge-category"><?php echo esc_html($cat->name); ?></span>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($tags) && !is_wp_error($tags)) : ?>
                        <div class="cpd-card-badges cpd-tags">
                            <?php foreach ($tags as $tag) : ?>
                                <span class="cpd-badge cpd-badge-tag"><?php echo esc_html($tag->name); ?></span>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                    
                    <div class="cpd-card-excerpt">
                        <?php echo wp_trim_words($event->post_excerpt ?: $event->post_content, 20); ?>
                    </div>
                    
                    <span class="cpd-card-more">Learn More →</span>
                </div>
            </a>
        </article>
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
            if (in_array($term->slug, ['upcoming', 'on-demand', 'online', 'free', 'physical-event'])) {
                $statuses[] = $term->name;
            }
        }
        return $statuses;
    }
    
    /**
     * Helper: Check if event is a past physical event
     * Past physical events show "Past Event" instead of price
     */
    public static function is_past_physical_event($post_id) {
        // Check if it has physical-event tag
        if (!has_term('physical-event', VET_CPD_Taxonomies::TAG, $post_id)) {
            return false;
        }
        
        // Check if event date is in the past
        $start_date = VET_CPD_CPD::get_meta($post_id, '_cpd_start_date');
        if (empty($start_date)) {
            return false;
        }
        
        $event_timestamp = strtotime($start_date);
        $now = current_time('timestamp');
        
        return $event_timestamp < $now;
    }
}
