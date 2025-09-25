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

/**
 * Haal een mapping van bestemmingsnamen naar IATA-codes op via Sunhotels GetDestinations.
 * Resultaat wordt gecachet met een WordPress transient (standaard 12 uur).
 *
 * @return array naam => code
 */
function freestays_get_destination_map() {
    error_log('freestays_get_destination_map wordt aangeroepen');
    $map = get_transient('freestays_destination_map');
    if ($map !== false && is_array($map)) {
        error_log('Mapping uit cache gehaald, aantal: ' . count($map));
        return $map;
    }

    $api_url  = $_ENV['API_URL'] ?? '';
    $api_user = $_ENV['API_USER'] ?? '';
    $api_pass = $_ENV['API_PASS'] ?? '';
    $language = 'en';

    error_log('API URL: ' . $api_url);

    $endpoint = rtrim($api_url, '/') . '/GetDestinations';
    $params = [
        'userName'              => $api_user,
        'password'              => $api_pass,
        'language'              => $language,
        'destinationCode'       => '',
        'sortBy'                => 'Destination',
        'sortOrder'             => 'asc',
        'exactDestinationMatch' => 'false',
    ];
    $url = $endpoint . '?' . http_build_query($params);

    error_log('GetDestinations URL: ' . $url);

    $response = wp_remote_get($url, [
        'timeout' => 30,
        'headers' => [
            'Accept' => 'application/xml',
        ],
    ]);
    if (is_wp_error($response)) {
        error_log('Sunhotels GetDestinations error: ' . $response->get_error_message());
        return [];
    }
    $body = wp_remote_retrieve_body($response);
    error_log('Sunhotels GetDestinations response: ' . substr($body, 0, 500));
    if (empty($body)) {
        error_log('Lege response van Sunhotels GetDestinations.');
        return [];
    }

    $xml = simplexml_load_string($body, 'SimpleXMLElement', LIBXML_NOCDATA);
    if ($xml === false) {
        error_log('Ongeldige XML van Sunhotels GetDestinations.');
        return [];
    }

    // Mapping opbouwen: naam => code (en evt. alternatieven)
    $map = [];
    if (isset($xml->Destinations->Destination)) {
        foreach ($xml->Destinations->Destination as $dest) {
            $name = strtolower((string)$dest->DestinationName);
            $code = (string)$dest->DestinationCode;
            if ($name && $code) {
                $map[$name] = $code;
            }
            // Alternatieve codes toevoegen
            for ($i = 2; $i <= 4; $i++) {
                $altCode = (string)$dest->{'DestinationCode.' . $i};
                if ($altCode) {
                    $map[strtolower($altCode)] = $altCode;
                }
            }
        }
    }

    set_transient('freestays_destination_map', $map, 12 * HOUR_IN_SECONDS);
    return $map;
}

// Shortcode handler: initialiseer Sunhotels_Client alleen bij zoekactie
//function freestays_search_shortcode($atts) {
  //  $api_url  = $_ENV['API_URL'] ?? '';
    //$api_user = $_ENV['API_USER'] ?? '';
    //$api_pass = $_ENV['API_PASS'] ?? '';

    //$client = new Sunhotels_Client($api_url, $api_user, $api_pass);

    // Mapping ophalen voor dropdown
    $destination_map = freestays_get_destination_map();

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

    // Zoekformulier tonen, dynamisch dropdown
    $output = '<form method="post" class="freestays-search-form">';
    $output .= '<label for="freestays_destination">Bestemming:</label>';
    if (empty($destination_map)) {
        $output .= '<div style="color:red;">Geen bestemmingen beschikbaar. Probeer het later opnieuw.</div>';
    } else {
        $output .= '<select name="freestays_destination" id="freestays_destination" required>';
        $output .= '<option value="">Kies bestemming</option>';
        $unique = [];
        foreach ($destination_map as $name => $code) {
            if (!in_array($code, $unique)) {
                $selected = ($destination === $code) ? ' selected' : '';
                $output .= '<option value="' . esc_attr($code) . '"' . $selected . '>' . esc_html(ucfirst($name)) . '</option>';
                $unique[] = $code;
            }
        }
        $output .= '</select>';
    }
    // Voeg overige formuliervelden toe (voorbeeld)
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

    // Resultaten tonen als er gezocht is
    if (!empty($destination) && !empty($checkin) && !empty($checkout)) {
        $destination_id = $destination; // dropdown value is altijd de code

        try {
            $hotels = $client->searchHotels(
                $destination_id,
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

/**
 * Haal landen op via Sunhotels API.
 */
function freestays_get_countries() {
    $countries = get_transient('freestays_countries');
    if ($countries !== false && is_array($countries)) {
        return $countries;
    }

    $api_url  = $_ENV['API_URL'] ?? '';
    $api_user = $_ENV['API_USER'] ?? '';
    $api_pass = $_ENV['API_PASS'] ?? '';
    $language = 'en';

    $endpoint = rtrim($api_url, '/') . '/GetCountries';
    $params = [
        'userName' => $api_user,
        'password' => $api_pass,
        'language' => $language,
    ];
    $url = $endpoint . '?' . http_build_query($params);

    $response = wp_remote_get($url, [
        'timeout' => 20,
        'headers' => [
            'Accept' => 'application/xml',
        ],
    ]);
    if (is_wp_error($response)) {
        error_log('Sunhotels GetCountries error: ' . $response->get_error_message());
        return [];
    }
    $body = wp_remote_retrieve_body($response);
    if (empty($body)) {
        error_log('Lege response van Sunhotels GetCountries.');
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

/**
 * Haal steden op voor een land via Sunhotels API.
 */
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

    $endpoint = rtrim($api_url, '/') . '/GetCities';
    $params = [
        'userName'  => $api_user,
        'password'  => $api_pass,
        'language'  => $language,
        'countryId' => $country_id,
    ];
    $url = $endpoint . '?' . http_build_query($params);

    $response = wp_remote_get($url, [
        'timeout' => 20,
        'headers' => [
            'Accept' => 'application/xml',
        ],
    ]);
    if (is_wp_error($response)) {
        error_log('Sunhotels GetCities error: ' . $response->get_error_message());
        return [];
    }
    $body = wp_remote_retrieve_body($response);
    if (empty($body)) {
        error_log('Lege response van Sunhotels GetCities.');
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

/**
 * Haal resorts op voor een stad via Sunhotels API.
 */
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

    $endpoint = rtrim($api_url, '/') . '/GetResorts';
    $params = [
        'userName' => $api_user,
        'password' => $api_pass,
        'language' => $language,
        'cityId'   => $city_id,
    ];
    $url = $endpoint . '?' . http_build_query($params);

    $response = wp_remote_get($url, [
        'timeout' => 20,
        'headers' => [
            'Accept' => 'application/xml',
        ],
    ]);
    if (is_wp_error($response)) {
        error_log('Sunhotels GetResorts error: ' . $response->get_error_message());
        return [];
    }
    $body = wp_remote_retrieve_body($response);
    if (empty($body)) {
        error_log('Lege response van Sunhotels GetResorts.');
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

/**
 * Shortcode handler: zoekformulier met vrij zoekveld en dropdowns.
 * - Werkt met POST en laadt steden/resorts opnieuw bij selectie (via form submit).
 * - Voor een vloeiendere UX kun je later AJAX toevoegen (zie uitleg onderaan).
 */
function freestays_search_shortcode($atts) {
    // Ophalen van landen, steden, resorts op basis van POST of default
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

    // Formulier opbouwen
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

    // Resultaten tonen als er gezocht is
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && (!empty($country_id) || !empty($search_query))) {
        // Hier kun je de Sunhotels API aanroepen met de gekozen parameters
        // Voorbeeld: zoekHotels($country_id, $city_id, $resort_id, $search_query, $checkin, $checkout, $adults, $children, $rooms)
        $client = new Sunhotels_Client($_ENV['API_URL'], $_ENV['API_USER'], $_ENV['API_PASS']);
        try {
            $hotels = $client->searchHotels([
                'country_id'   => $country_id,
                'city_id'      => $city_id,
                'resort_id'    => $resort_id,
                'search_query' => $search_query,
                'checkin'      => $checkin,
                'checkout'     => $checkout,
                'adults'       => $adults,
                'children'     => $children,
                'rooms'        => $rooms,
            ]);
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
 * AJAX handler voor steden
 */
add_action('wp_ajax_freestays_get_cities', 'freestays_ajax_get_cities');
add_action('wp_ajax_nopriv_freestays_get_cities', 'freestays_ajax_get_cities');
function freestays_ajax_get_cities() {
    $country_id = isset($_POST['country_id']) ? sanitize_text_field($_POST['country_id']) : '';
    $cities = freestays_get_cities($country_id);
    wp_send_json($cities);
}

/**
 * AJAX handler voor resorts
 */
add_action('wp_ajax_freestays_get_resorts', 'freestays_ajax_get_resorts');
add_action('wp_ajax_nopriv_freestays_get_resorts', 'freestays_ajax_get_resorts');
function freestays_ajax_get_resorts() {
    $city_id = isset($_POST['city_id']) ? sanitize_text_field($_POST['city_id']) : '';
    $resorts = freestays_get_resorts($city_id);
    wp_send_json($resorts);
}

/**
 * JavaScript en AJAX script toevoegen
 */
add_action('wp_enqueue_scripts', function() {
    wp_enqueue_script(
        'freestays-ajax',
        FREESTAYS_PLUGIN_URL . 'assets/js/freestays-ajax.js',
        array('jquery'),
        null,
        true
    );
    wp_localize_script('freestays-ajax', 'freestaysAjax', array(
        'ajax_url' => admin_url('admin-ajax.php'),
    ));
});