<?php
/**
 * Plugin Name: Freestays Booking
 * Description: Maatwerk hotelboekingsplugin voor Freestays, met Sunhotels API-integratie.
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
function freestays_get_countries() {
    $countries = get_transient('freestays_countries');
    if ($countries !== false && is_array($countries)) {
        return $countries;
    }

    $api_url  = $_ENV['API_URL'] ?? '';
    $api_user = $_ENV['API_USER'] ?? '';
    $api_pass = $_ENV['API_PASS'] ?? '';
    $language = 'en';

    if (empty($api_url) || empty($api_user) || empty($api_pass)) {
        error_log('API config ontbreekt!');
        return [];
    }

    $params = [
        'method'   => 'GetCountries',
        'userName' => $api_user,
        'password' => $api_pass,
        'language' => $language,
    ];

    $response = wp_remote_post($api_url, [
        'body'    => $params,
        'timeout' => 20,
    ]);
    if (is_wp_error($response)) {
        error_log('Sunhotels GetCountries error: ' . $response->get_error_message());
        return [];
    }
    $body = wp_remote_retrieve_body($response);
    if (empty($body) || strpos(trim($body), '<') !== 0) {
        error_log('Lege of ongeldige response van Sunhotels GetCountries: ' . $body);
        return [];
    }

    $xml = simplexml_load_string($body, 'SimpleXMLElement', LIBXML_NOCDATA);
    if ($xml === false) {
        error_log('Ongeldige XML van Sunhotels GetCountries.');
        return [];
    }

    $countries = [];
    if (isset($xml->Countries->Country)) {
        foreach ($xml->Countries->Country as $country) {
            $countries[] = [
                'id'   => (string)$country->CountryId,
                'name' => (string)$country->CountryName,
            ];
        }
    }

    set_transient('freestays_countries', $countries, 24 * HOUR_IN_SECONDS);
    return $countries;
}

// Steden ophalen via Sunhotels REST API
function freestays_get_cities($country_id) {
    if (empty($country_id)) return [];
    $cache_key = 'freestays_cities_' . $country_id;
    $cities = get_transient($cache_key);
    if ($cities !== false && is_array($cities)) {
        return $cities;
    }

    $api_url  = $_ENV['API_URL'] ?? '';
    $api_user = $_ENV['API_USER'] ?? '';
    $api_pass = $_ENV['API_PASS'] ?? '';
    $language = 'en';

    if (empty($api_url) || empty($api_user) || empty($api_pass)) {
        error_log('API config ontbreekt!');
        return [];
    }

    $params = [
        'method'    => 'GetCities',
        'userName'  => $api_user,
        'password'  => $api_pass,
        'language'  => $language,
        'countryId' => $country_id,
    ];

    $response = wp_remote_post($api_url, [
        'body'    => $params,
        'timeout' => 20,
    ]);
    if (is_wp_error($response)) {
        error_log('Sunhotels GetCities error: ' . $response->get_error_message());
        return [];
    }
    $body = wp_remote_retrieve_body($response);
    if (empty($body) || strpos(trim($body), '<') !== 0) {
        error_log('Lege of ongeldige response van Sunhotels GetCities: ' . $body);
        return [];
    }

    $xml = simplexml_load_string($body, 'SimpleXMLElement', LIBXML_NOCDATA);
    if ($xml === false) {
        error_log('Ongeldige XML van Sunhotels GetCities.');
        return [];
    }

    $cities = [];
    if (isset($xml->Cities->City)) {
        foreach ($xml->Cities->City as $city) {
            $cities[] = [
                'id'   => (string)$city->CityId,
                'name' => (string)$city->CityName,
            ];
        }
    }

    set_transient($cache_key, $cities, 24 * HOUR_IN_SECONDS);
    return $cities;
}

// Resorts ophalen via Sunhotels REST API
function freestays_get_resorts($city_id) {
    if (empty($city_id)) return [];
    $cache_key = 'freestays_resorts_' . $city_id;
    $resorts = get_transient($cache_key);
    if ($resorts !== false && is_array($resorts)) {
        return $resorts;
    }

    $api_url  = $_ENV['API_URL'] ?? '';
    $api_user = $_ENV['API_USER'] ?? '';
    $api_pass = $_ENV['API_PASS'] ?? '';
    $language = 'en';

    if (empty($api_url) || empty($api_user) || empty($api_pass)) {
        error_log('API config ontbreekt!');
        return [];
    }

    $params = [
        'method'   => 'GetResorts',
        'userName' => $api_user,
        'password' => $api_pass,
        'language' => $language,
        'cityId'   => $city_id,
    ];

    $response = wp_remote_post($api_url, [
        'body'    => $params,
        'timeout' => 20,
    ]);
    if (is_wp_error($response)) {
        error_log('Sunhotels GetResorts error: ' . $response->get_error_message());
        return [];
    }
    $body = wp_remote_retrieve_body($response);
    if (empty($body) || strpos(trim($body), '<') !== 0) {
        error_log('Lege of ongeldige response van Sunhotels GetResorts: ' . $body);
        return [];
    }

    $xml = simplexml_load_string($body, 'SimpleXMLElement', LIBXML_NOCDATA);
    if ($xml === false) {
        error_log('Ongeldige XML van Sunhotels GetResorts.');
        return [];
    }

    $resorts = [];
    if (isset($xml->Resorts->Resort)) {
        foreach ($xml->Resorts->Resort as $resort) {
            $resorts[] = [
                'id'   => (string)$resort->ResortId,
                'name' => (string)$resort->ResortName,
            ];
        }
    }

    set_transient($cache_key, $resorts, 24 * HOUR_IN_SECONDS);
    return $resorts;
}

// Shortcode handler (voorbeeld, alleen relevante searchHotels-aanroep)
function freestays_search_shortcode($atts) {
    $countries = freestays_get_countries();
    $country_id = isset($_POST['freestays_country']) ? sanitize_text_field($_POST['freestays_country']) : '';
    $cities = $country_id ? freestays_get_cities($country_id) : [];
    $city_id = isset($_POST['freestays_city']) ? sanitize_text_field($_POST['freestays_city']) : '';
    $resorts = $city_id ? freestays_get_resorts($city_id) : [];
    $resort_id = isset($_POST['freestays_resort']) ? sanitize_text_field($_POST['freestays_resort']) : '';

    $search_query = isset($_POST['freestays_search']) ? sanitize_text_field($_POST['freestays_search']) : '';
    $checkin      = isset($_POST['freestays_checkin']) ? sanitize_text_field($_POST['freestays_checkin']) : '';
    $checkout     = isset($_POST['freestays_checkout']) ? sanitize_text_field($_POST['freestays_checkout']) : '';
    $adults       = isset($_POST['freestays_adults']) ? intval($_POST['freestays_adults']) : 2;
    $children     = isset($_POST['freestays_children']) ? intval($_POST['freestays_children']) : 0;
    $rooms        = isset($_POST['freestays_rooms']) ? intval($_POST['freestays_rooms']) : 1;

    $child_ages = [];
    if ($children > 0) {
        for ($i = 1; $i <= $children; $i++) {
            $child_ages[] = isset($_POST["freestays_child_age_$i"]) ? intval($_POST["freestays_child_age_$i"]) : 0;
        }
    }

    $output = '<form method="post" class="freestays-search-form">';
    // Vrij zoekveld
    $output .= '<label for="freestays_search">Zoek op hotel, regio of land:</label>';
    $output .= '<input type="text" name="freestays_search" id="freestays_search" value="' . esc_attr($search_query) . '" placeholder="Bijv. Alanya, Turkije, Hotelnaam">';
    // Dropdown landen
    $output .= '<label for="freestays_country">Land:</label>';
    $output .= '<select name="freestays_country" id="freestays_country">';
    $output .= '<option value="">Kies land</option>';
    foreach ($countries as $country) {
        $selected = ($country_id === $country['id']) ? ' selected' : '';
        $output .= '<option value="' . esc_attr($country['id']) . '"' . $selected . '>' . esc_html($country['name']) . '</option>';
    }
    $output .= '</select>';
    // Dropdown steden
    $output .= '<label for="freestays_city">Stad:</label>';
    $output .= '<select name="freestays_city" id="freestays_city">';
    $output .= '<option value="">Kies stad</option>';
    foreach ($cities as $city) {
        $selected = ($city_id === $city['id']) ? ' selected' : '';
        $output .= '<option value="' . esc_attr($city['id']) . '"' . $selected . '>' . esc_html($city['name']) . '</option>';
    }
    $output .= '</select>';
    // Dropdown resorts
    $output .= '<label for="freestays_resort">Resort:</label>';
    $output .= '<select name="freestays_resort" id="freestays_resort">';
    $output .= '<option value="">Kies resort (optioneel)</option>';
    foreach ($resorts as $resort) {
        $selected = ($resort_id === $resort['id']) ? ' selected' : '';
        $output .= '<option value="' . esc_attr($resort['id']) . '"' . $selected . '>' . esc_html($resort['name']) . '</option>';
    }
    $output .= '</select>';
    // Overige velden
    $output .= '<label for="freestays_checkin">Check-in:</label>';
    $output .= '<input type="date" name="freestays_checkin" id="freestays_checkin" value="' . esc_attr($checkin) . '" required>';
    $output .= '<label for="freestays_checkout">Check-out:</label>';
    $output .= '<input type="date" name="freestays_checkout" id="freestays_checkout" value="' . esc_attr($checkout) . '" required>';
    $output .= '<label for="freestays_adults">Volwassenen:</label>';
    $output .= '<input type="number" name="freestays_adults" id="freestays_adults" value="' . esc_attr($adults) . '" min="1" required>';
    $output .= '<label for="freestays_children">Kinderen:</label>';
    $output .= '<input type="number" name="freestays_children" id="freestays_children" value="' . esc_attr($children) . '" min="0">';
    $output .= '<label for="freestays_rooms">Kamers:</label>';
    $output .= '<input type="number" name="freestays_rooms" id="freestays_rooms" value="' . esc_attr($rooms) . '" min="1" required>';
    $output .= '<button type="submit">Zoeken</button>';
    $output .= '</form>';

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && (!empty($country_id) || !empty($search_query))) {
        $client = new Sunhotels_Client($_ENV['API_URL'], $_ENV['API_USER'], $_ENV['API_PASS']);
        try {
            $hotels = $client->searchHotels(
                $country_id,
                $city_id,
                $resort_id,
                $search_query, // destinationId of zoekterm
                $checkin,
                $checkout,
                $adults,
                $children,
                $rooms,
                $child_ages
            );
        } catch (Exception $e) {
            $output .= '<div class="freestays-search-results">';
            $output .= '<p style="color:red;">Fout bij ophalen hotels: ' . esc_html($e->getMessage()) . '</p>';
            $output .= '</div>';
            return $output;
        }

        $output .= '<div class="freestays-search-results" style="display:flex;flex-wrap:wrap;gap:16px;">';
        if (is_array($hotels) && count($hotels) > 0) {
            foreach ($hotels as $hotel) {
                $output .= '<div class="freestays-hotel-card" style="border:1px solid #ccc;padding:16px;width:300px;">';
                $output .= '<strong>' . esc_html($hotel['name'] ?? 'Onbekend hotel') . '</strong><br>';
                if (!empty($hotel['city'])) {
                    $output .= '<span>' . esc_html($hotel['city']) . '</span><br>';
                }
                if (!empty($hotel['address'])) {
                    $output .= '<small>' . esc_html($hotel['address']) . '</small><br>';
                }
                if (!empty($hotel['image'])) {
                    $output .= '<img src="' . esc_url($hotel['image']) . '" alt="' . esc_attr($hotel['name'] ?? '') . '" style="max-width:100%;height:auto;"><br>';
                }
                if (!empty($hotel['price'])) {
                    $output .= '<div style="margin-top:8px;"><strong>Vanaf: ' . esc_html($hotel['price']) . '</strong></div>';
                }
                $output .= '</div>';
            }
        } else {
            $output .= '<p>Geen hotels gevonden voor deze zoekopdracht.</p>';
        }
        $output .= '</div>';
    }

    return $output;
}
add_shortcode('freestays_search', 'freestays_search_shortcode');

/**
 * AJAX handlers
 */
add_action('wp_ajax_freestays_get_cities', 'freestays_ajax_get_cities');
add_action('wp_ajax_nopriv_freestays_get_cities', 'freestays_ajax_get_cities');
function freestays_ajax_get_cities() {
    $country_id = isset($_POST['country_id']) ? sanitize_text_field($_POST['country_id']) : '';
    if (empty($country_id)) {
        wp_send_json([]);
    }
    $cities = freestays_get_cities($country_id);
    wp_send_json($cities);
}

add_action('wp_ajax_freestays_get_resorts', 'freestays_ajax_get_resorts');
add_action('wp_ajax_nopriv_freestays_get_resorts', 'freestays_ajax_get_resorts');
function freestays_ajax_get_resorts() {
    $city_id = isset($_POST['city_id']) ? sanitize_text_field($_POST['city_id']) : '';
    if (empty($city_id)) {
        wp_send_json([]);
    }
    $resorts = freestays_get_resorts($city_id);
    wp_send_json($resorts);
}

/**
 * CSS en JS inladen
 */
function freestays_enqueue_assets() {
    wp_enqueue_style(
        'freestays-css',
        plugins_url('assets/css/freestays.css', __FILE__),
        array(),
        filemtime(plugin_dir_path(__FILE__) . 'assets/css/freestays.css')
    );
    wp_enqueue_script(
        'freestays-ajax-js',
        plugins_url('assets/js/freestays-ajax.js', __FILE__),
        array('jquery'),
        filemtime(plugin_dir_path(__FILE__) . 'assets/js/freestays-ajax.js'),
        true
    );
    wp_localize_script(
        'freestays-ajax-js',
        'freestaysAjax',
        array('ajax_url' => admin_url('admin-ajax.php'))
    );
    wp_enqueue_script(
        'freestays-js',
        plugins_url('assets/js/freestays.js', __FILE__),
        array(),
        filemtime(plugin_dir_path(__FILE__) . 'assets/js/freestays.js'),
        true
    );
}
add_action('wp_enqueue_scripts', 'freestays_enqueue_assets');

require_once plugin_dir_path(__FILE__) . 'includes/shortcodes/hotel-list.php';

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

<?php
error_log('API_URL uit .env: ' . ($_ENV['API_URL'] ?? 'NIET GEZET'));