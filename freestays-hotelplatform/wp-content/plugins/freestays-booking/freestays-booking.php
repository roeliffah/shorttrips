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
require_once FREESTAYS_PLUGIN_DIR . 'includes/helpers.php';
require_once plugin_dir_path(__FILE__) . 'includes/shortcodes/class-freestays-search-shortcode.php';

// Activation hook
function freestays_booking_activate() {
    // Code to run on plugin activation
}
register_activation_hook( __FILE__, 'freestays_booking_activate' );

// Deactivation hook
function freestays_booking_deactivate() {
    // Code to run on plugin deactivation
}
register_deactivation_hook( __FILE__, 'freestays_booking_deactivate' );

// Initialize the plugin
function freestays_booking_init() {
    // Initialize classes and hooks
    new Freestays_API();
    new Sunhotels_Client();
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