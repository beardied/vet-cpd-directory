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
        
        // Register shortcodes
        add_shortcode('cpd_list', [__CLASS__, 'shortcode_list']);
        add_shortcode('cpd_upcoming', [__CLASS__, 'shortcode_upcoming']);
        add_shortcode('cpd_on_demand', [__CLASS__, 'shortcode_on_demand']);
        add_shortcode('cpd_free', [__CLASS__, 'shortcode_free']);
        add_shortcode('cpd_online', [__CLASS__, 'shortcode_online']);
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
     * Shortcode: [cpd_list]
     */
    public static function shortcode_list($atts) {
        $atts = shortcode_atts([
            'limit'      => 10,
            'category'   => '',
            'tag'        => '',
            'orderby'    => 'meta_value',
            'meta_key'   => '_cpd_date',
            'order'      => 'ASC',
        ], $atts, 'cpd_list');
        
        $args = [
            'post_type'      => VET_CPD_CPD::POST_TYPE,
            'posts_per_page' => intval($atts['limit']),
            'orderby'        => $atts['orderby'],
            'order'          => $atts['order'],
        ];
        
        if ($atts['orderby'] === 'meta_value') {
            $args['meta_key'] = $atts['meta_key'];
            $args['meta_type'] = 'DATETIME';
        }
        
        if (!empty($atts['category'])) {
            $args['tax_query'][] = [
                'taxonomy' => VET_CPD_Taxonomies::CATEGORY,
                'field'    => 'slug',
                'terms'    => explode(',', $atts['category']),
            ];
        }
        
        if (!empty($atts['tag'])) {
            $args['tax_query'][] = [
                'taxonomy' => VET_CPD_Taxonomies::TAG,
                'field'    => 'slug',
                'terms'    => explode(',', $atts['tag']),
            ];
        }
        
        $cpds = get_posts($args);
        
        ob_start();
        include self::locate_template('shortcode-cpd-list.php');
        return ob_get_clean();
    }
    
    /**
     * Shortcode: [cpd_upcoming]
     */
    public static function shortcode_upcoming($atts) {
        $atts = shortcode_atts([
            'limit'    => 10,
            'category' => '',
        ], $atts, 'cpd_upcoming');
        
        $atts['tag'] = 'upcoming';
        return self::shortcode_list($atts);
    }
    
    /**
     * Shortcode: [cpd_on_demand]
     */
    public static function shortcode_on_demand($atts) {
        $atts = shortcode_atts([
            'limit'    => 10,
            'category' => '',
        ], $atts, 'cpd_on_demand');
        
        $atts['tag'] = 'on-demand';
        return self::shortcode_list($atts);
    }
    
    /**
     * Shortcode: [cpd_free]
     */
    public static function shortcode_free($atts) {
        $atts = shortcode_atts([
            'limit'    => 10,
            'category' => '',
        ], $atts, 'cpd_free');
        
        $atts['tag'] = 'free';
        return self::shortcode_list($atts);
    }
    
    /**
     * Shortcode: [cpd_online]
     */
    public static function shortcode_online($atts) {
        $atts = shortcode_atts([
            'limit'    => 10,
            'category' => '',
        ], $atts, 'cpd_online');
        
        $atts['tag'] = 'online';
        return self::shortcode_list($atts);
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