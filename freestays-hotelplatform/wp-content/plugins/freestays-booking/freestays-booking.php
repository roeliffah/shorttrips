<?php
/**
 * Plugin Name: Freestays Booking
 * Description: Maatwerk hotel booking plugin voor Freestays.
 * Version: 1.0
 * Author: Freestays Team
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// Composer autoloader en .env laden
if ( file_exists( __DIR__ . '/vendor/autoload.php' ) ) {
    require_once __DIR__ . '/vendor/autoload.php';
    $env_path = dirname(__DIR__, 3) . '/config';
    if (file_exists($env_path . '/.env')) {
        $dotenv = Dotenv\Dotenv::createImmutable($env_path);
        $dotenv->load();
    }
}

// Plugin constants
define( 'FREESTAYS_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'FREESTAYS_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

// Vereiste bestanden laden
require_once FREESTAYS_PLUGIN_DIR . 'includes/api/class-sunhotels-client.php';

// Landen ophalen via Sunhotels REST API
// Legacy Sunhotels array-body functies verwijderd. Alleen REST API en Sunhotels_Client actief.

// Shortcode handler (voorbeeld, alleen relevante searchHotels-aanroep)
function freestays_search_shortcode($atts) {
    // Ophalen van landen, regio's en steden op basis van selectie
    $countries = freestays_bridge_get_countries();
    $country_id = isset($_POST['freestays_country']) ? sanitize_text_field($_POST['freestays_country']) : '';
    $regions = $country_id ? freestays_bridge_get_regions($country_id) : [];
    $region_id = isset($_POST['freestays_region']) ? sanitize_text_field($_POST['freestays_region']) : '';
    $cities = $region_id ? freestays_bridge_get_cities($region_id) : [];
    $city_id = isset($_POST['freestays_city']) ? sanitize_text_field($_POST['freestays_city']) : '';
    $search_query = isset($_POST['freestays_search']) ? sanitize_text_field($_POST['freestays_search']) : '';

    $output = '<form method="post" class="freestays-search-form">';
    $output .= '<label for="freestays_search">Zoek op hotel, regio of land:</label>';
    $output .= '<input type="text" name="freestays_search" id="freestays_search" value="' . esc_attr($search_query) . '" placeholder="Bijv. Alanya, Turkije, Hotelnaam">';
    $output .= '<div style="margin-top: 18px;">';

    // Country dropdown
    $output .= '<label for="freestays_country">Land:</label>';
    $output .= '<select name="freestays_country" id="freestays_country" onchange="this.form.submit()">';
    $output .= '<option value="">Kies land</option>';
    foreach ($countries as $country) {
        $selected = ($country_id === $country['id']) ? ' selected' : '';
        $output .= '<option value="' . esc_attr($country['id']) . '"' . $selected . '>' . esc_html($country['name']) . '</option>';
    }
    $output .= '</select>';

    // Region dropdown (afhankelijk van country)
    $output .= '<label for="freestays_region" style="margin-left:10px;">Regio:</label>';
    $output .= '<select name="freestays_region" id="freestays_region" onchange="this.form.submit()">';
    $output .= '<option value="">Kies regio</option>';

require_once plugin_dir_path(__FILE__) . 'includes/shortcodes/hotel-list.php';
require_once __DIR__ . '/includes/class-searchbar-shortcode.php';
require_once __DIR__ . '/includes/shortcodes/filters.php';

// Test Sunhotels API shortcode
function freestays_test_sunhotels_api() {
    $api_url  = $_ENV['API_URL'] ?? '';
    $api_user = $_ENV['API_USER'] ?? '';
    $api_pass = $_ENV['API_PASS'] ?? '';

    if (empty($api_url) || empty($api_user) || empty($api_pass)) {
        return '<div style="color:red;">API config ontbreekt!</div>';
    }

    $params = [
        'method'   => 'GetDestinations',
        'userName' => $api_user,
        'password' => $api_pass,
        'language' => 'en',
    ];

    $response = wp_remote_post($api_url, [
        'body'    => $params,
        'timeout' => 20,
    ]);
    if (is_wp_error($response)) {
        return '<div style="color:red;">API niet bereikbaar: ' . esc_html($response->get_error_message()) . '</div>';
    }
    $body = wp_remote_retrieve_body($response);
    if (empty($body) || strpos(trim($body), '<') !== 0) {
        return '<div style="color:red;">Lege of ongeldige response van Sunhotels: ' . esc_html($body) . '</div>';
    }

    // Toon de eerste 500 tekens van de response als debug
    return '<pre>' . esc_html(substr($body, 0, 500)) . '</pre>';
}
add_shortcode('freestays_test_api', 'freestays_test_sunhotels_api');

error_log('API_URL uit .env: ' . ($_ENV['API_URL'] ?? 'NIET GEZET'));

function freestays_enqueue_react_search() {
    wp_enqueue_script(
        'freestays-react-search',
        plugins_url('assets/js/freestays-react-search.js', __FILE__),
        array(),
        filemtime(plugin_dir_path(__FILE__) . 'assets/js/freestays-react-search.js'),
        true
    );
}
add_action('wp_enqueue_scripts', 'freestays_enqueue_react_search');

// Shortcode en API classes includen
require_once __DIR__ . '/includes/class-searchbar-shortcode.php';
require_once __DIR__ . '/includes/class-freestays-api.php';

// Eventueel extra initialisatie
// add_action('init', ...);

/**
 * Haal landen op via de bridge op freestays.eu
 */
function freestays_bridge_get_countries() {
    $bridge_url = $_ENV['BRIDGE_URL'] ?? getenv('BRIDGE_URL') ?? '';
    $bridge_key = $_ENV['BRIDGE_KEY'] ?? getenv('BRIDGE_KEY') ?? '';
    if (empty($bridge_url) || empty($bridge_key)) {
        error_log('Bridge config ontbreekt!');
        return [];
    }

    // Key en action als GET-parameters
    $url = $bridge_url . '?key=' . urlencode($bridge_key) . '&action=countries';

    $response = wp_remote_get($url, [
        'timeout' => 15,
    ]);
    if (is_wp_error($response)) {
        error_log('Bridge countries error: ' . $response->get_error_message());
        return [];
    }
    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);
    if (!is_array($data)) {
        error_log('Bridge countries: ongeldige JSON: ' . $body);
        return [];
    }
    return $data['results'] ?? $data;
}

/**
 * Haal regio's op via de bridge op freestays.eu
 */
function freestays_bridge_get_regions($country_id) {
    $bridge_url = $_ENV['BRIDGE_URL'] ?? getenv('BRIDGE_URL') ?? '';
    $bridge_key = $_ENV['BRIDGE_KEY'] ?? getenv('BRIDGE_KEY') ?? '';
    if (empty($bridge_url) || empty($bridge_key) || empty($country_id)) {
        error_log('Bridge config ontbreekt of country_id ontbreekt!');
        return [];
    }

    $url = $bridge_url . '?key=' . urlencode($bridge_key) . '&action=regions&country_id=' . urlencode($country_id);

    $response = wp_remote_get($url, [
        'timeout' => 15,
    ]);
    if (is_wp_error($response)) {
        error_log('Bridge regions error: ' . $response->get_error_message());
        return [];
    }
    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);
    if (!is_array($data)) {
        error_log('Bridge regions: ongeldige JSON: ' . $body);
        return [];
    }
    return $data['results'] ?? $data;
}

/**
 * Haal steden op via de bridge op freestays.eu
 */
function freestays_bridge_get_cities($region_id) {
    $bridge_url = $_ENV['BRIDGE_URL'] ?? getenv('BRIDGE_URL') ?? '';
    $bridge_key = $_ENV['BRIDGE_KEY'] ?? getenv('BRIDGE_KEY') ?? '';
    if (empty($bridge_url) || empty($bridge_key) || empty($region_id)) {
        error_log('Bridge config ontbreekt of region_id ontbreekt!');
        return [];
    }

    $url = $bridge_url . '?key=' . urlencode($bridge_key) . '&action=cities&region_id=' . urlencode($region_id);

    $response = wp_remote_get($url, [
        'timeout' => 15,
    ]);
    if (is_wp_error($response)) {
        error_log('Bridge cities error: ' . $response->get_error_message());
        return [];
    }
    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);
    if (!is_array($data)) {
        error_log('Bridge cities: ongeldige JSON: ' . $body);
        return [];
    }
    return $data['results'] ?? $data;
}

// Shortcode voor regio's (voorbeeld: [freestays_bridge_regions country_id="1"])
add_shortcode('freestays_bridge_regions', function($atts) {
    $atts = shortcode_atts(['country_id' => ''], $atts);
    if (empty($atts['country_id'])) return '<p>Geen country_id opgegeven.</p>';
    $regions = freestays_bridge_get_regions($atts['country_id']);
    if (!$regions) return '<p>Geen regio\'s gevonden.</p>';
    $out = '<ul>';
    foreach ($regions as $region) {
        $out .= '<li>' . esc_html($region['name'] ?? $region['id']) . '</li>';
    }
    $out .= '</ul>';
    return $out;
});

// Shortcode voor steden (voorbeeld: [freestays_bridge_cities region_id="1"])
add_shortcode('freestays_bridge_cities', function($atts) {
    $atts = shortcode_atts(['region_id' => ''], $atts);
    if (empty($atts['region_id'])) return '<p>Geen region_id opgegeven.</p>';
    $cities = freestays_bridge_get_cities($atts['region_id']);
    if (!$cities) return '<p>Geen steden gevonden.</p>';
    $out = '<ul>';
    foreach ($cities as $city) {
        $out .= '<li>' . esc_html($city['name'] ?? $city['id']) . '</li>';
    }
    $out .= '</ul>';
    return $out;
});

/**
 * Haal hotels op via de bridge op freestays.eu op basis van een zoekterm
 */
function freestays_bridge_search_hotels($search_term) {
    $bridge_url = $_ENV['BRIDGE_URL'] ?? getenv('BRIDGE_URL') ?? '';
    $bridge_key = $_ENV['BRIDGE_KEY'] ?? getenv('BRIDGE_KEY') ?? '';
    if (empty($bridge_url) || empty($bridge_key) || empty($search_term)) {
        error_log('Bridge config ontbreekt of zoekterm ontbreekt!');
        return [];
    }

    $url = $bridge_url . '?key=' . urlencode($bridge_key) . '&action=hotels&q=' . urlencode($search_term);

    $response = wp_remote_get($url, [
        'timeout' => 15,
    ]);
    if (is_wp_error($response)) {
        error_log('Bridge hotels error: ' . $response->get_error_message());
        return [];
    }
    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);
    if (!is_array($data)) {
        error_log('Bridge hotels: ongeldige JSON: ' . $body);
        return [];
    }
    return $data;
}

// Shortcode voor vrije zoekopdracht hotels (voorbeeld: [freestays_bridge_hotels q="hotelnaam"])
add_shortcode('freestays_bridge_hotels', function($atts) {
    $atts = shortcode_atts(['q' => ''], $atts);
    if (empty($atts['q'])) return '<p>Geen zoekterm opgegeven.</p>';
    $hotels = freestays_bridge_search_hotels($atts['q']);
    if (!$hotels) return '<p>Geen hotels gevonden.</p>';
    $out = '<ul>';
    foreach ($hotels as $hotel) {
        $out .= '<li>' . esc_html($hotel['title'] ?? $hotel['name'] ?? $hotel['id']) . '</li>';
    }
    $out .= '</ul>';
    return $out;
});

add_action('rest_api_init', function() {
    register_rest_route('freestays/v1', '/countries', [
        'methods' => 'GET',
        'callback' => function() {
            require_once __DIR__ . '/includes/api/class-sunhotels-client.php';
            $client = new Sunhotels_Client();
            return rest_ensure_response(['data' => $client->getCountries()]);
        }
    ]);
    register_rest_route('freestays/v1', '/cities', [
        'methods' => 'GET',
        'callback' => function($request) {
            require_once __DIR__ . '/includes/api/class-sunhotels-client.php';
            $client = new Sunhotels_Client();
            return rest_ensure_response(['data' => $client->getCities($request->get_param('country_id'))]);
        }
    ]);
    register_rest_route('freestays/v1', '/resorts', [
        'methods' => 'GET',
        'callback' => function($request) {
            require_once __DIR__ . '/includes/api/class-sunhotels-client.php';
            $client = new Sunhotels_Client();
            return rest_ensure_response(['data' => $client->getResorts($request->get_param('city_id'))]);
        }
    ]);
    register_rest_route('freestays/v1', '/search', [
        'methods' => 'POST',
        'callback' => function($request) {
            require_once __DIR__ . '/includes/api/class-sunhotels-client.php';
            $client = new Sunhotels_Client();
            $params = $request->get_json_params();
            $results = $client->searchV3($params);
            return rest_ensure_response(['data' => $results['hotels'] ?? []]);
        }
    ]);
});

add_action('wp_enqueue_scripts', function() {
    if (is_singular() && has_shortcode(get_post()->post_content, 'freestays_search')) {
        wp_enqueue_script(
            'freestays-react-search',
            plugins_url('../../../../frontend/build/static/js/main.js', __FILE__),
            [],
            null,
            true
        );
        wp_enqueue_style(
            'freestays-react-search',
            plugins_url('../../../../frontend/build/static/css/main.css', __FILE__)
        );
    }
});