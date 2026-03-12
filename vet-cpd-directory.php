<?php
/**
 * Plugin Name: Vet CPD Directory
 * Description: Veterinary CPD Directory - Manage veterinary Continuing Professional Development courses, venues, instructors, and organisers
 * Version: 1.0.6
 * Author: OrangeWidow
 * Author URI: https://orangewidow.com
 * License: GPL v2 or later
 * Text Domain: vet-cpd-directory
 * Domain Path: /languages
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define constants
define('VET_CPD_VERSION', '1.0.6');
define('VET_CPD_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('VET_CPD_PLUGIN_URL', plugin_dir_url(__FILE__));
define('VET_CPD_PLUGIN_BASENAME', plugin_basename(__FILE__));

// Autoloader
spl_autoload_register(function ($class) {
    $prefix = 'VET_CPD_';
    $base_dir = VET_CPD_PLUGIN_DIR . 'includes/';
    
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }
    
    $relative_class = substr($class, $len);
    $file = $base_dir . 'class-' . str_replace('_', '-', strtolower($relative_class)) . '.php';
    
    if (file_exists($file)) {
        require $file;
    }
});

// Initialize plugin
add_action('plugins_loaded', function() {
    // Load text domain
    load_plugin_textdomain('vet-cpd-directory', false, dirname(VET_CPD_PLUGIN_BASENAME) . '/languages');
    
    // Initialize CPTs
    VET_CPD_CPD::init();
    VET_CPD_Venue::init();
    VET_CPD_Organiser::init();
    VET_CPD_Instructor::init();
    VET_CPD_Series::init();
    VET_CPD_Taxonomies::init();
    VET_CPD_Meta_Boxes::init();
    VET_CPD_Auto_Tag::init();
    VET_CPD_Frontend::init();
    VET_CPD_Admin::init();
    
    // Load WP-CLI commands
    if (defined('WP_CLI') && WP_CLI) {
        require_once VET_CPD_PLUGIN_DIR . 'includes/class-cli-commands.php';
    }
});

// Activation hook
register_activation_hook(__FILE__, function() {
    // Register post types and flush rewrite rules
    VET_CPD_CPD::register();
    VET_CPD_Venue::register();
    VET_CPD_Organiser::register();
    VET_CPD_Instructor::register();
    VET_CPD_Series::register();
    VET_CPD_Taxonomies::register();
    flush_rewrite_rules();
});

// Deactivation hook
register_deactivation_hook(__FILE__, function() {
    flush_rewrite_rules();
});
