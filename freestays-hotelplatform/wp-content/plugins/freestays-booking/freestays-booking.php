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
function freestays_get_countries() {
    $countries = get_transient('freestays_countries');
    if ($countries !== false && is_array($countries)) {
        return $countries;
    }

    $api_url  = $_ENV['API_URL'] ?? getenv('API_URL') ?? '';
    $api_user = $_ENV['API_USER'] ?? getenv('API_USER') ?? '';
    $api_pass = $_ENV['API_PASS'] ?? getenv('API_PASS') ?? '';
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

    $api_url  = $_ENV['API_URL'] ?? getenv('API_URL') ?? '';
    $api_user = $_ENV['API_USER'] ?? getenv('API_USER') ?? '';
    $api_pass = $_ENV['API_PASS'] ?? getenv('API_PASS') ?? '';
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

    $api_url  = $_ENV['API_URL'] ?? getenv('API_URL') ?? '';
    $api_user = $_ENV['API_USER'] ?? getenv('API_USER') ?? '';
    $api_pass = $_ENV['API_PASS'] ?? getenv('API_PASS') ?? '';
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
    foreach ($regions as $region) {
        $selected = ($region_id === $region['id']) ? ' selected' : '';
        $output .= '<option value="' . esc_attr($region['id']) . '"' . $selected . '>' . esc_html($region['name']) . '</option>';
    }
    $output .= '</select>';

    // City dropdown (afhankelijk van regio)
    $output .= '<label for="freestays_city" style="margin-left:10px;">Stad:</label>';
    $output .= '<select name="freestays_city" id="freestays_city">';
    $output .= '<option value="">Kies stad</option>';
    foreach ($cities as $city) {
        $selected = ($city_id === $city['id']) ? ' selected' : '';
        $output .= '<option value="' . esc_attr($city['id']) . '"' . $selected . '>' . esc_html($city['name']) . '</option>';
    }
    $output .= '</select>';

    $output .= '</div>';
    $output .= '<button type="submit" style="margin-top:18px;">Zoeken</button>';
    $output .= '</form>';

    // Resultaten tonen na POST
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && (!empty($search_query) || !empty($country_id) || !empty($region_id) || !empty($city_id))) {
        // Bouw de zoekterm op
        $params = [];
        if (!empty($search_query)) $params['q'] = $search_query;
        if (!empty($country_id)) $params['country_id'] = $country_id;
        if (!empty($region_id)) $params['region_id'] = $region_id;
        if (!empty($city_id)) $params['city_id'] = $city_id;

        // Bouw de bridge-url
        $bridge_url = $_ENV['BRIDGE_URL'] ?? getenv('BRIDGE_URL') ?? '';
        $bridge_key = $_ENV['BRIDGE_KEY'] ?? getenv('BRIDGE_KEY') ?? '';
        $url = $bridge_url . '?key=' . urlencode($bridge_key) . '&action=hotels';
        foreach ($params as $k => $v) {
            $url .= '&' . urlencode($k) . '=' . urlencode($v);
        }

        $response = wp_remote_get($url, ['timeout' => 20]);
        $hotels = [];
        if (!is_wp_error($response)) {
            $body = wp_remote_retrieve_body($response);
            $data = json_decode($body, true);
            if (is_array($data)) {
                $hotels = $data;
            }
        }

        $output .= '<div class="freestays-search-results" style="display:flex;flex-wrap:wrap;gap:16px;margin-top:24px;">';
        $count = 0;
        foreach ($hotels as $hotel) {
            if (isset($hotel['availability']) && !$hotel['availability']) continue;
            $output .= '<div class="freestays-hotel-card" style="border:1px solid #ccc;padding:16px;width:300px;">';
            $output .= '<strong>' . esc_html($hotel['title'] ?? $hotel['name'] ?? 'Onbekend hotel') . '</strong><br>';
            if (!empty($hotel['city'])) {
                $output .= '<span>' . esc_html($hotel['city']) . '</span><br>';
            }
            if (!empty($hotel['address'])) {
                $output .= '<small>' . esc_html($hotel['address']) . '</small><br>';
            }
            if (!empty($hotel['image']) || !empty($hotel['thumbnail'])) {
                $img = $hotel['image'] ?? $hotel['thumbnail'];
                $output .= '<img src="' . esc_url($img) . '" alt="' . esc_attr($hotel['title'] ?? $hotel['name'] ?? '') . '" style="max-width:100%;height:auto;"><br>';
            }
            if (!empty($hotel['price'])) {
                $output .= '<div style="margin-top:8px;"><strong>Vanaf: ' . esc_html($hotel['price']) . '</strong></div>';
            }
            $output .= '</div>';
            $count++;
        }
        if ($count === 0) {
            $output .= '<p>Geen beschikbare hotels gevonden voor deze zoekopdracht.</p>';
        }
        $output .= '</div>';
    }

    return $output;
}
add_shortcode('freestays_search', 'freestays_search_shortcode');
add_shortcode('freestays_search_classic', 'freestays_search_shortcode');
add_shortcode('freestays_search_form', 'freestays_search_form_shortcode');

/**
 * AJAX handlers
 */
if (!function_exists('freestays_ajax_get_cities')) {
    add_action('wp_ajax_freestays_get_cities', 'freestays_ajax_get_cities');
    add_action('wp_ajax_nopriv_freestays_get_cities', 'freestays_ajax_get_cities');
    add_action('wp_ajax_freestays_get_resorts', 'freestays_ajax_get_resorts');
    add_action('wp_ajax_nopriv_freestays_get_resorts', 'freestays_ajax_get_resorts');
    function freestays_ajax_get_cities() {
        $country_id = isset($_POST['country_id']) ? sanitize_text_field($_POST['country_id']) : '';
        if (empty($country_id)) {
            wp_send_json([]);
        }
        $cities = freestays_get_cities($country_id);
        wp_send_json($cities);
    }

    function freestays_ajax_get_resorts() {
        $city_id = isset($_POST['city_id']) ? sanitize_text_field($_POST['city_id']) : '';
        if (empty($city_id)) {
            wp_send_json([]);
        }
        $resorts = freestays_get_resorts($city_id);
        wp_send_json($resorts);
    }
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