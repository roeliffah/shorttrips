<?php
/**

// Shortcode en API classes includen
require_once __DIR__ . '/includes/class-searchbar-shortcode.php';
require_once __DIR__ . '/includes/class-freestays-api.php';

// Eventueel extra initialisatie
// add_action('init', ...);


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