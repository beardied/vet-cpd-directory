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
