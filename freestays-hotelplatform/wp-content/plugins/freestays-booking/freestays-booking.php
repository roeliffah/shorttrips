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

// Composer autoloader als eerste laden!
if ( file_exists( __DIR__ . '/vendor/autoload.php' ) ) {
    require_once __DIR__ . '/vendor/autoload.php';
}
error_log('Autoload bestaat: ' . (file_exists(__DIR__ . '/vendor/autoload.php') ? 'JA' : 'NEE'));

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

// Load environment variables from .env file if it exists
if ( file_exists( dirname( __DIR__, 3 ) . '/config/.env' ) ) {
    $dotenv = Dotenv\Dotenv::createImmutable( dirname( __DIR__, 3 ) . '/config' );
    $dotenv->load();
}

// Get API credentials from environment variables
$api_url  = $_ENV['API_URL'] ?? '';
$api_user = $_ENV['API_USER'] ?? '';
$api_pass = $_ENV['API_PASS'] ?? '';

// Initialize API class
$api = new Freestays_API( $api_user, $api_pass );

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
    // Sunhotels_Client wordt NIET meer hier geïnitialiseerd!
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

// Shortcode handler: initialiseer Sunhotels_Client alleen bij zoekactie
function freestays_search_shortcode($atts) {
    $api_url  = $_ENV['API_URL'] ?? '';
    $api_user = $_ENV['API_USER'] ?? '';
    $api_pass = $_ENV['API_PASS'] ?? '';

    $client = new Sunhotels_Client($api_url, $api_user, $api_pass);

    // Formulierwaarden ophalen of standaardwaarden instellen
    $destination = isset($_POST['freestays_destination']) ? sanitize_text_field($_POST['freestays_destination']) : '';
    $checkin     = isset($_POST['freestays_checkin']) ? sanitize_text_field($_POST['freestays_checkin']) : '';
    $checkout    = isset($_POST['freestays_checkout']) ? sanitize_text_field($_POST['freestays_checkout']) : '';
    $adults      = isset($_POST['freestays_adults']) ? intval($_POST['freestays_adults']) : 2;
    $children    = isset($_POST['freestays_children']) ? intval($_POST['freestays_children']) : 0;
    $rooms       = isset($_POST['freestays_rooms']) ? intval($_POST['freestays_rooms']) : 1;

    // Kind-leeftijden ophalen
    $child_ages = [];
    if ($children > 0) {
        for ($i = 1; $i <= $children; $i++) {
            $child_ages[] = isset($_POST["freestays_child_age_$i"]) ? intval($_POST["freestays_child_age_$i"]) : '';
        }
    }

    // Zoekformulier tonen
    $output = '<form method="post" class="freestays-search-form">';
    $output .= '<input type="text" name="freestays_destination" placeholder="Bestemming" value="' . esc_attr($destination) . '" required />';

    // Datum picker (checkin/checkout)
    $output .= '<input type="date" name="freestays_checkin" value="' . esc_attr($checkin) . '" required />';
    $output .= '<input type="date" name="freestays_checkout" value="' . esc_attr($checkout) . '" required />';

    // Aantal volwassenen
    $output .= '<select name="freestays_adults">';
    for ($i = 1; $i <= 6; $i++) {
        $selected = ($adults == $i) ? 'selected' : '';
        $output .= "<option value=\"$i\" $selected>$i volwassenen</option>";
    }
    $output .= '</select>';

    // Aantal kinderen
    $output .= '<select name="freestays_children" id="freestays_children" onchange="this.form.submit()">';
    for ($i = 0; $i <= 4; $i++) {
        $selected = ($children == $i) ? 'selected' : '';
        $output .= "<option value=\"$i\" $selected>$i kinderen</option>";
    }
    $output .= '</select>';

    // Kind-leeftijd dropdowns (alleen tonen als kinderen > 0)
    if ($children > 0) {
        for ($i = 1; $i <= $children; $i++) {
            $age = isset($child_ages[$i-1]) ? $child_ages[$i-1] : '';
            $output .= '<select name="freestays_child_age_' . $i . '" required>';
            $output .= '<option value="">Leeftijd kind ' . $i . '</option>';
            for ($a = 0; $a <= 17; $a++) {
                $sel = ($age === $a) ? 'selected' : '';
                $output .= "<option value=\"$a\" $sel>$a jaar</option>";
            }
            $output .= '</select>';
        }
    }

    // Aantal kamers
    $output .= '<select name="freestays_rooms">';
    for ($i = 1; $i <= 4; $i++) {
        $selected = ($rooms == $i) ? 'selected' : '';
        $output .= "<option value=\"$i\" $selected>$i kamer(s)</option>";
    }
    $output .= '</select>';

    $output .= '<button type="submit">Zoeken</button>';
    $output .= '</form>';

    // Resultaten tonen als er gezocht is
    if (!empty($destination) && !empty($checkin) && !empty($checkout)) {
        // Roep de Sunhotels API aan via je client
        try {
            $hotels = $client->searchHotels(
                $destination,
                $checkin,
                $checkout,
                $adults,
                $children,
                $child_ages,
                $rooms
            );
        } catch (Exception $e) {
            $output .= '<div class="freestays-search-results">';
            $output .= '<p style="color:red;">Fout bij ophalen hotels: ' . esc_html($e->getMessage()) . '</p>';
            $output .= '</div>';
            return $output;
        }

        $output .= '<div class="freestays-search-results">';
        if (is_array($hotels) && count($hotels) > 0) {
            $output .= '<ul>';
            foreach ($hotels as $hotel) {
                $output .= '<li>';
                $output .= '<strong>' . esc_html($hotel['name'] ?? 'Onbekend hotel') . '</strong>';
                if (!empty($hotel['city'])) {
                    $output .= ' - ' . esc_html($hotel['city']);
                }
                if (!empty($hotel['address'])) {
                    $output .= '<br><small>' . esc_html($hotel['address']) . '</small>';
                }
                if (!empty($hotel['image'])) {
                    $output .= '<br><img src="' . esc_url($hotel['image']) . '" alt="' . esc_attr($hotel['name'] ?? '') . '" style="max-width:200px;">';
                }
                $output .= '</li>';
            }
            $output .= '</ul>';
        } else {
            $output .= '<p>Geen hotels gevonden voor deze zoekopdracht.</p>';
        }
        $output .= '</div>';
    }

    return $output;
}
add_shortcode('freestays_search', 'freestays_search_shortcode');


// In Sunhotels_Client.php
public function searchHotels($destination, $checkin, $checkout, $adults, $children, $child_ages, $rooms) {
    // Bouw je request op basis van deze parameters en stuur naar de Sunhotels API
    // Parseer de response en geef een array van hotels terug
}