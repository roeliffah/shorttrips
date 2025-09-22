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