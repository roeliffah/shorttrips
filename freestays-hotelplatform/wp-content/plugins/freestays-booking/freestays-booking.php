<?php
/**
 * Plugin Name: Freestays Booking
 * Description: A custom hotel booking platform for Freestays.
 * Version: 1.0
 * Author: Your Name
 * Author URI: https://yourwebsite.com
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Define plugin constants
define( 'FREESTAYS_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'FREESTAYS_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

// Include necessary files
require_once FREESTAYS_PLUGIN_DIR . 'includes/api/class-freestays-api.php';
require_once FREESTAYS_PLUGIN_DIR . 'includes/api/class-sunhotels-client.php';
require_once FREESTAYS_PLUGIN_DIR . 'includes/class-booking-handler.php';
require_once FREESTAYS_PLUGIN_DIR . 'includes/class-shortcodes.php';
require_once FREESTAYS_PLUGIN_DIR . 'includes/class-admin-settings.php';
require_once FREESTAYS_PLUGIN_DIR . 'includes/class-toaster.php';
require_once FREESTAYS_PLUGIN_DIR . 'includes/helpers.php';

// Activation hook
function freestays_booking_activate() {
    // Create database tables
    Booking_Handler::createBookingsTable();
    
    // Set default options
    $default_options = array(
        'api_url' => '',
        'api_key' => ''
    );
    add_option('freestays_options', $default_options);
}
register_activation_hook( __FILE__, 'freestays_booking_activate' );

// Deactivation hook
function freestays_booking_deactivate() {
    // Code to run on plugin deactivation
}
register_deactivation_hook( __FILE__, 'freestays_booking_deactivate' );

// Initialize the plugin
function freestays_booking_init() {
    // Get plugin options
    $options = get_option('freestays_options', array());
    
    // Initialize classes and hooks with proper dependencies
    $api_url = isset($options['api_url']) ? $options['api_url'] : '';
    $api_key = isset($options['api_key']) ? $options['api_key'] : '';
    
    // Only initialize API classes if credentials are available
    if (!empty($api_url) && !empty($api_key)) {
        new Freestays_API($api_url, $api_key);
    }
    
    // Initialize Sunhotels client with environment variables if available
    $sunhotels_url = defined('API_URL') ? API_URL : (isset($_ENV['API_URL']) ? $_ENV['API_URL'] : '');
    $sunhotels_user = defined('API_USER') ? API_USER : (isset($_ENV['API_USER']) ? $_ENV['API_USER'] : '');
    $sunhotels_pass = defined('API_PASS') ? API_PASS : (isset($_ENV['API_PASS']) ? $_ENV['API_PASS'] : '');
    
    if (!empty($sunhotels_url) && !empty($sunhotels_user) && !empty($sunhotels_pass)) {
        new SunhotelsClient($sunhotels_url, $sunhotels_user, $sunhotels_pass);
    }
    
    // Initialize other classes
    new Booking_Handler();
    new Shortcodes();
    new Admin_Settings();
}
add_action( 'plugins_loaded', 'freestays_booking_init' );

// Enqueue scripts and styles
function freestays_enqueue_assets() {
    wp_enqueue_style( 'freestays-css', FREESTAYS_PLUGIN_URL . 'assets/css/freestays.css' );
    wp_enqueue_script( 'freestays-js', FREESTAYS_PLUGIN_URL . 'assets/js/freestays.js', array( 'jquery' ), null, true );
}
add_action( 'wp_enqueue_scripts', 'freestays_enqueue_assets' );
?>