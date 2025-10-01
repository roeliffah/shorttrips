<?php

class Freestays_API {
    private $api_url;
    private $api_key;

    public function __construct($api_url, $api_key) {
        $this->api_url = $api_url;
        $this->api_key = $api_key;
    }

    public function get_hotels($params) {
        $response = $this->make_request('GET', '/hotels', $params);
        return $this->process_response($response);
    }

    public function get_hotel_details($hotel_id) {
        $response = $this->make_request('GET', "/hotels/{$hotel_id}");
        return $this->process_response($response);
    }

    public function search_hotels($checkin, $checkout, $adults, $children, $destination_id) {
        $params = [
            'checkin' => $checkin,
            'checkout' => $checkout,
            'adults' => $adults,
            'children' => $children,
            'destination_id' => $destination_id,
        ];
        $response = $this->make_request('POST', '/search', $params);
        return $this->process_response($response);
    }

    private function make_request($method, $endpoint, $params = []) {
        $url = $this->api_url . $endpoint;

        $args = [
            'method' => $method,
            'headers' => [
                'Authorization' => 'Bearer ' . $this->api_key,
                'Content-Type' => 'application/json',
            ],
            'body' => json_encode($params),
        ];

        $response = wp_remote_request($url, $args);
        return $response;
    }

    private function process_response($response) {
        if (is_wp_error($response)) {
            return [
                'error' => $response->get_error_message(),
            ];
        }

        $body = wp_remote_retrieve_body($response);
        return json_decode($body, true);
    }
}

// REST API endpoints
add_action('rest_api_init', function () {
    // Zoek hotels op plaatsnaam
    register_rest_route('freestays/v1', '/search-by-city', [
        'methods'  => 'GET',
        'callback' => function (WP_REST_Request $request) {
            $city    = $request->get_param('city');
            $checkin = $request->get_param('checkin');
            $checkout = $request->get_param('checkout');
            $adults  = $request->get_param('adults') ?: 2;
            $children = $request->get_param('children') ?: 0;
            $rooms   = $request->get_param('rooms') ?: 1;

            if (!$city || !$checkin || !$checkout) {
                return new WP_Error('missing_params', 'Vereiste parameters ontbreken.', ['status' => 400]);
            }

            require_once __DIR__ . '/api/class-sunhotels-client.php';
            $client = new Sunhotels_Client(
                $_ENV['API_URL'] ?? getenv('API_URL') ?? '',
                $_ENV['API_USER'] ?? getenv('API_USER') ?? '',
                $_ENV['API_PASS'] ?? getenv('API_PASS') ?? ''
            );

            $result = $client->zoekHotelsOpPlaats($city, $checkin, $checkout, $adults, $children, $rooms);

            if (isset($result['error'])) {
                return new WP_Error('search_failed', $result['error'], ['status' => 500]);
            }

            // Template integratie: render hotel-card.php voor elk hotel
            $hotels = $result['hotels'] ?? [];
            $hotel_html = [];
            foreach ($hotels as $hotel) {
                ob_start();
                $hotel_data = $hotel; // Zorg dat $hotel_data in template beschikbaar is
                include plugin_dir_path(__FILE__) . '../../templates/hotel-card.php';
                $hotel_html[] = ob_get_clean();
            }
            $result['hotel_html'] = $hotel_html;

            return rest_ensure_response($result);
        },
        'permission_callback' => '__return_true',
    ]);

    // Landen ophalen
    register_rest_route('freestays/v1', '/countries', [
        'methods'  => 'GET',
        'callback' => function () {
            require_once __DIR__ . '/api/class-sunhotels-client.php';
            $client = new Sunhotels_Client(
                $_ENV['API_URL'] ?? getenv('API_URL') ?? '',
                $_ENV['API_USER'] ?? getenv('API_USER') ?? '',
                $_ENV['API_PASS'] ?? getenv('API_PASS') ?? ''
            );
            return rest_ensure_response($client->getCountries());
        },
        'permission_callback' => '__return_true',
    ]);

    // Resorts ophalen op basis van country_id
    register_rest_route('freestays/v1', '/resorts', [
        'methods'  => 'GET',
        'callback' => function (WP_REST_Request $request) {
            $country_id = $request->get_param('country_id');
            require_once __DIR__ . '/api/class-sunhotels-client.php';
            $client = new Sunhotels_Client(
                $_ENV['API_URL'] ?? getenv('API_URL') ?? '',
                $_ENV['API_USER'] ?? getenv('API_USER') ?? '',
                $_ENV['API_PASS'] ?? getenv('API_PASS') ?? ''
            );
            return rest_ensure_response($client->getResorts($country_id));
        },
        'permission_callback' => '__return_true',
    ]);

    // Cities ophalen op basis van resort_id
    register_rest_route('freestays/v1', '/cities', [
        'methods'  => 'GET',
        'callback' => function (WP_REST_Request $request) {
            $resort_id = $request->get_param('resort_id');
            require_once __DIR__ . '/api/class-sunhotels-client.php';
            $client = new Sunhotels_Client(
                $_ENV['API_URL'] ?? getenv('API_URL') ?? '',
                $_ENV['API_USER'] ?? getenv('API_USER') ?? '',
                $_ENV['API_PASS'] ?? getenv('API_PASS') ?? ''
            );
            return rest_ensure_response($client->getCities($resort_id));
        },
        'permission_callback' => '__return_true',
    ]);
});

// Shortcode: volledig zoekformulier (vrij veld + dropdowns)
add_shortcode('freestays_search', function () {
    ob_start(); ?>
    <div id="freestays-search-root"></div>
    <script>
    (function() {
        function loadScript(src, cb) {
            var s = document.createElement('script');
            s.src = src;
            s.onload = cb;
            document.head.appendChild(s);
        }
        function ensureReact(cb) {
            if (typeof React === 'undefined') {
                loadScript('https://unpkg.com/react@18/umd/react.development.js', function() {
                    if (typeof ReactDOM === 'undefined') {
                        loadScript('https://unpkg.com/react-dom@18/umd/react-dom.development.js', cb);
                    } else { cb(); }
                });
            } else if (typeof ReactDOM === 'undefined') {
                loadScript('https://unpkg.com/react-dom@18/umd/react-dom.development.js', cb);
            } else { cb(); }
        }
        ensureReact(function() {
            // Zie stap 2 voor de JS-componenten
            window.FreestaysRenderSearchForm && window.FreestaysRenderSearchForm('freestays-search-root');
        });
    })();
    </script>
    <?php
    return ob_get_clean();
});

// Shortcode: alleen filters (dropdowns)
add_shortcode('freestays_filters', function () {
    ob_start(); ?>
    <div id="freestays-filters-root"></div>
    <script>
    (function() {
        function loadScript(src, cb) {
            var s = document.createElement('script');
            s.src = src;
            s.onload = cb;
            document.head.appendChild(s);
        }
        function ensureReact(cb) {
            if (typeof React === 'undefined') {
                loadScript('https://unpkg.com/react@18/umd/react.development.js', function() {
                    if (typeof ReactDOM === 'undefined') {
                        loadScript('https://unpkg.com/react-dom@18/umd/react-dom.development.js', cb);
                    } else { cb(); }
                });
            } else if (typeof ReactDOM === 'undefined') {
                loadScript('https://unpkg.com/react-dom@18/umd/react-dom.development.js', cb);
            } else { cb(); }
        }
        ensureReact(function() {
            window.FreestaysRenderFilters && window.FreestaysRenderFilters('freestays-filters-root');
        });
    })();
    </script>
    <?php
    return ob_get_clean();
});

// Shortcode: alleen zoekveld
add_shortcode('freestays_searchbar', function () {
    ob_start(); ?>
    <div id="freestays-searchbar-root"></div>
    <script>
    (function() {
        function loadScript(src, cb) {
            var s = document.createElement('script');
            s.src = src;
            s.onload = cb;
            document.head.appendChild(s);
        }
        function ensureReact(cb) {
            if (typeof React === 'undefined') {
                loadScript('https://unpkg.com/react@18/umd/react.development.js', function() {
                    if (typeof ReactDOM === 'undefined') {
                        loadScript('https://unpkg.com/react-dom@18/umd/react-dom.development.js', cb);
                    } else { cb(); }
                });
            } else if (typeof ReactDOM === 'undefined') {
                loadScript('https://unpkg.com/react-dom@18/umd/react-dom.development.js', cb);
            } else { cb(); }
        }
        ensureReact(function() {
            window.FreestaysRenderSearchBar && window.FreestaysRenderSearchBar('freestays-searchbar-root');
        });
    })();
    </script>
    <?php
    return ob_get_clean();
});

// class-sunhotels-client.php
class Sunhotels_Client {
    private $apiUser;
    private $apiPass;
    private $apiUrl;

    public function __construct() {
        $env = parse_ini_file(__DIR__ . '/../../../config/.env');
        $this->apiUser = $env['API_USER'];
        $this->apiPass = $env['API_PASS'];
        $this->apiUrl  = $env['API_URL'];
    }

    public function searchV3($params) {
        $xml = $this->buildSearchV3Xml($params);
        $opts = [
            'http' => [
                'method' => "POST",
                'header' => "Content-Type: text/xml; charset=utf-8\r\n",
                'content' => $xml,
                'timeout' => 30
            ]
        ];
        $context = stream_context_create($opts);
        $response = file_get_contents($this->apiUrl, false, $context);
        if ($response === false) return null;
        $parsed = simplexml_load_string($response, null, 0, 'http://xml.sunhotels.net/15/');
        // Parse hotels uit de response (vereenvoudigd)
        $hotels = []; // Vul dit aan met echte parsing
        return ['hotels' => $hotels];
    }

    private function buildSearchV3Xml($params) {
        $checkIn = htmlspecialchars($params['start'] ?? date('Y-m-d'));
        $checkOut = htmlspecialchars($params['end'] ?? date('Y-m-d', strtotime('+1 day')));
        $rooms = (int)($params['room'] ?? 1);
        $adults = (int)($params['adults'] ?? 2);
        $children = (int)($params['children'] ?? 0);
        $destinationID = htmlspecialchars($params['city_id'] ?? $params['resort_id'] ?? $params['country'] ?? $params['q'] ?? '');
        $blockSuperdeal = 'ja';
        return <<<XML
<?xml version="1.0" encoding="utf-8"?>
<soap:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
               xmlns:xsd="http://www.w3.org/2001/XMLSchema"
               xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">
  <soap:Body>
    <SearchV3 xmlns="http://xml.sunhotels.net/15/">
      <userName>{$this->apiUser}</userName>
      <password>{$this->apiPass}</password>
      <language>EN</language>
      <currencies>EUR</currencies>
      <checkInDate>{$checkIn}</checkInDate>
      <checkOutDate>{$checkOut}</checkOutDate>
      <numberOfRooms>{$rooms}</numberOfRooms>
      <destinationID>{$destinationID}</destinationID>
      <numberOfAdults>{$adults}</numberOfAdults>
      <numberOfChildren>{$children}</numberOfChildren>
      <blockSuperdeal>{$blockSuperdeal}</blockSuperdeal>
    </SearchV3>
  </soap:Body>
</soap:Envelope>
XML;
    }
}