<?php
/**
 * Plugin Name: Freestays Booking
 * Description: Maatwerk hotel booking platform voor Freestays.
 * Version: 1.0
 * Author: Freestays
 */

// Direct access voorkomen
if ( ! defined( 'ABSPATH' ) ) exit;

// Composer autoloader
if ( file_exists( __DIR__ . '/vendor/autoload.php' ) ) {
    require_once __DIR__ . '/vendor/autoload.php';
}

// Plugin constants
define( 'FREESTAYS_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'FREESTAYS_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

// Vereiste bestanden laden
require_once FREESTAYS_PLUGIN_DIR . 'includes/api/class-freestays-api.php';
require_once FREESTAYS_PLUGIN_DIR . 'includes/api/class-sunhotels-client.php';
require_once FREESTAYS_PLUGIN_DIR . 'includes/class-booking-handler.php';
require_once FREESTAYS_PLUGIN_DIR . 'includes/class-shortcodes.php';
require_once FREESTAYS_PLUGIN_DIR . 'includes/class-admin-settings.php';
require_once FREESTAYS_PLUGIN_DIR . 'includes/helpers.php';

// .env laden indien aanwezig
if ( file_exists( dirname( __DIR__, 3 ) . '/config/.env' ) ) {
    $dotenv = Dotenv\Dotenv::createImmutable( dirname( __DIR__, 3 ) . '/config' );
    $dotenv->load();
}

// Plugin activatie
function freestays_booking_activate() {
    // Eventuele activatiecode
}
register_activation_hook( __FILE__, 'freestays_booking_activate' );

// Plugin deactivatie
function freestays_booking_deactivate() {
    // Eventuele deactivatiecode
}
register_deactivation_hook( __FILE__, 'freestays_booking_deactivate' );

// Plugin initialisatie
function freestays_booking_init() {
    new Booking_Handler();
    new Freestays_Booking_Shortcodes();
    new Freestays_Admin_Settings();
}
add_action( 'plugins_loaded', 'freestays_booking_init' );

// Assets laden
function freestays_enqueue_assets() {
    wp_enqueue_style( 'freestays-css', FREESTAYS_PLUGIN_URL . 'assets/css/freestays.css' );
    wp_enqueue_script( 'freestays-js', FREESTAYS_PLUGIN_URL . 'assets/js/freestays.js', array( 'jquery' ), null, true );
}
add_action( 'wp_enqueue_scripts', 'freestays_enqueue_assets' );