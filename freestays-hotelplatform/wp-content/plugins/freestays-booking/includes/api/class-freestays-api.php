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

// Shortcode voor hotel search formulier
add_shortcode('freestays_search', function () {
    ob_start();
    ?>
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
                    } else {
                        cb();
                    }
                });
            } else if (typeof ReactDOM === 'undefined') {
                loadScript('https://unpkg.com/react-dom@18/umd/react-dom.development.js', cb);
            } else {
                cb();
            }
        }
        ensureReact(function() {
            const API_BASE = '/wp-json/freestays/v1';

            function HotelSearchForm() {
                const [countries, setCountries] = React.useState([]);
                const [resorts, setResorts] = React.useState([]);
                const [cities, setCities] = React.useState([]);
                const [selectedCountry, setSelectedCountry] = React.useState('');
                const [selectedResort, setSelectedResort] = React.useState('');
                const [selectedCity, setSelectedCity] = React.useState('');
                const [checkin, setCheckin] = React.useState('');
                const [checkout, setCheckout] = React.useState('');
                const [adults, setAdults] = React.useState(2);
                const [children, setChildren] = React.useState(0);
                const [loading, setLoading] = React.useState(false);
                const [error, setError] = React.useState('');
                const [results, setResults] = React.useState(null);

                // Landen ophalen bij laden
                React.useEffect(() => {
                    fetch(`${API_BASE}/countries`)
                        .then(res => res.json())
                        .then(data => setCountries(Array.isArray(data) ? data : []))
                        .catch(() => setError('Kan landen niet laden.'));
                }, []);

                // Resorts ophalen na land-selectie
                React.useEffect(() => {
                    if (!selectedCountry) {
                        setResorts([]);
                        setSelectedResort('');
                        return;
                    }
                    fetch(`${API_BASE}/resorts?country_id=${selectedCountry}`)
                        .then(res => res.json())
                        .then(data => setResorts(Array.isArray(data) ? data : []))
                        .catch(() => setError('Kan resorts niet laden.'));
                }, [selectedCountry]);

                // Cities ophalen na resort-selectie
                React.useEffect(() => {
                    if (!selectedResort) {
                        setCities([]);
                        setSelectedCity('');
                        return;
                    }
                    fetch(`${API_BASE}/cities?resort_id=${selectedResort}`)
                        .then(res => res.json())
                        .then(data => setCities(Array.isArray(data) ? data : []))
                        .catch(() => setError('Kan steden niet laden.'));
                }, [selectedResort]);

                const handleSubmit = async (e) => {
                    e.preventDefault();
                    setError('');
                    setLoading(true);
                    setResults(null);

                    const cityObj = cities.find(c => String(c.id) === String(selectedCity));
                    if (!cityObj || !cityObj.destination_id) {
                        setError('Geen geldige bestemming geselecteerd.');
                        setLoading(false);
                        return;
                    }

                    const params = new URLSearchParams({
                        city: cityObj.name,
                        checkin,
                        checkout,
                        adults,
                        children,
                        rooms: 1
                    });

                    try {
                        const res = await fetch(`${API_BASE}/search-by-city?${params.toString()}`);
                        const data = await res.json();
                        if (!res.ok) throw new Error(data.message || 'Zoeken mislukt');
                        setResults(data);
                    } catch (err) {
                        setError(err.message);
                    }
                    setLoading(false);
                };

                return React.createElement("div", {},
                    React.createElement("form", { onSubmit: handleSubmit },
                        error && React.createElement("div", { style: { color: 'red' } }, error),
                        React.createElement("div", null,
                            React.createElement("label", null, "Land:"),
                            React.createElement("select", {
                                value: selectedCountry,
                                onChange: e => setSelectedCountry(e.target.value)
                            },
                                React.createElement("option", { value: "" }, "Kies een land"),
                                countries.map(c => (
                                    React.createElement("option", { key: c.id, value: c.id }, c.name)
                                ))
                            )
                        ),
                        React.createElement("div", null,
                            React.createElement("label", null, "Resort:"),
                            React.createElement("select", {
                                value: selectedResort,
                                onChange: e => setSelectedResort(e.target.value),
                                disabled: !selectedCountry
                            },
                                React.createElement("option", { value: "" }, "Kies een resort"),
                                resorts.map(r => (
                                    React.createElement("option", { key: r.id, value: r.id }, r.name)
                                ))
                            )
                        ),
                        React.createElement("div", null,
                            React.createElement("label", null, "Stad:"),
                            React.createElement("select", {
                                value: selectedCity,
                                onChange: e => setSelectedCity(e.target.value),
                                disabled: !selectedResort
                            },
                                React.createElement("option", { value: "" }, "Kies een stad"),
                                cities.map(city => (
                                    React.createElement("option", { key: city.id, value: city.id }, city.name)
                                ))
                            )
                        ),
                        React.createElement("div", null,
                            React.createElement("label", null, "Check-in:"),
                            React.createElement("input", {
                                type: "date",
                                value: checkin,
                                onChange: e => setCheckin(e.target.value),
                                required: true
                            })
                        ),
                        React.createElement("div", null,
                            React.createElement("label", null, "Check-out:"),
                            React.createElement("input", {
                                type: "date",
                                value: checkout,
                                onChange: e => setCheckout(e.target.value),
                                required: true
                            })
                        ),
                        React.createElement("div", null,
                            React.createElement("label", null, "Volwassenen:"),
                            React.createElement("input", {
                                type: "number",
                                min: "1",
                                value: adults,
                                onChange: e => setAdults(e.target.value)
                            })
                        ),
                        React.createElement("div", null,
                            React.createElement("label", null, "Kinderen:"),
                            React.createElement("input", {
                                type: "number",
                                min: "0",
                                value: children,
                                onChange: e => setChildren(e.target.value)
                            })
                        ),
                        React.createElement("button", { type: "submit", disabled: loading },
                            loading ? 'Zoeken...' : 'Zoek hotels'
                        )
                    ),
                    results && React.createElement("div", { style: { marginTop: 20 } },
                        React.createElement("h3", null, "Zoekresultaten"),
                        Array.isArray(results.hotels) && results.hotels.length > 0
                            ? React.createElement("ul", {},
                                results.hotels.map(hotel =>
                                    React.createElement("li", { key: hotel.id },
                                        hotel.name,
                                        hotel.city ? ` (${hotel.city})` : ''
                                    )
                                )
                            )
                            : React.createElement("div", null, "Geen hotels gevonden.")
                    )
                );
            }

            ReactDOM.render(
                React.createElement(HotelSearchForm, {}),
                document.getElementById('freestays-search-root')
            );
        });
    })();
    </script>
    <?php
    return ob_get_clean();
});