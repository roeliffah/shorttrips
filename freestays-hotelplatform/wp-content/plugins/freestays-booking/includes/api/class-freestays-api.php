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

add_action('rest_api_init', function () {
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
});