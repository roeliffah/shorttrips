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
    $map = get_transient('freestays_destination_map');
    if ($map !== false && is_array($map)) {
        return $map;
    }

    $api_url  = $_ENV['API_URL'] ?? '';
    $api_user = $_ENV['API_USER'] ?? '';
    $api_pass = $_ENV['API_PASS'] ?? '';
    $language = 'en';

    $endpoint = rtrim($api_url, '/') . '/GetDestinations';
    $params = [
        'userName'              => $api_user,
        'password'              => $api_pass,
        'language'              => $language,
        'destinationCode'       => '',
        'sortBy'                => 'DestinationName',
        'sortOrder'             => 'asc',
        'exactDestinationMatch' => 'false', // <-- toegevoegd!
    ];
    $url = $endpoint . '?' . http_build_query($params);

    // Vraag de bestemmingen op
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
    error_log('Sunhotels GetDestinations response: ' . substr($body, 0, 500)); // debugregel toevoegen
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
function freestays_search_shortcode($atts) {
    $api_url  = $_ENV['API_URL'] ?? '';
    $api_user = $_ENV['API_USER'] ?? '';
    $api_pass = $_ENV['API_PASS'] ?? '';

    $client = new Sunhotels_Client($api_url, $api_user, $api_pass);

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
        $output .= '<select name="freestays_destination" id="freestays_destination" disabled><option>Geen bestemmingen beschikbaar</option></select>';
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