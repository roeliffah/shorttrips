<?php
/**
 * REST API endpoint voor hotel zoeken
 */
class Freestays_API {
    public static function register_routes() {
        register_rest_route('freestays/v1', '/countries', [
            'methods' => 'GET',
            'callback' => [self::class, 'get_countries'],
              'permission_callback' => '__return_true',
        ]);
        register_rest_route('freestays/v1', '/cities', [
            'methods' => 'GET',
            'callback' => [self::class, 'get_cities'],
              'permission_callback' => '__return_true',
        ]);
        register_rest_route('freestays/v1', '/resorts', [
            'methods' => 'GET',
            'callback' => [self::class, 'get_resorts'],
              'permission_callback' => '__return_true',
        ]);
        register_rest_route('freestays/v1', '/search', [
            'methods' => 'POST',
            'callback' => [self::class, 'search_hotels'],
              'permission_callback' => '__return_true',
        ]);
    }

    public static function get_countries($request) {
        require_once __DIR__ . '/api/class-sunhotels-client.php';
        $client = new Sunhotels_Client();
        error_log('[freestays] get_countries aangeroepen');
        $result = $client->getCountries();
        error_log('[freestays] get_countries resultaat: ' . print_r($result, true));
        return rest_ensure_response(['data' => $result]);
    }

    public static function get_cities($request) {
        $country_id = $request->get_param('country_id');
        require_once __DIR__ . '/api/class-sunhotels-client.php';
        $client = new Sunhotels_Client();
        error_log('[freestays] get_cities aangeroepen met country_id: ' . $country_id);
        $result = $client->getCities($country_id);
        error_log('[freestays] get_cities resultaat: ' . print_r($result, true));
        return rest_ensure_response(['data' => $result]);
    }

    public static function get_resorts($request) {
        $city_id = $request->get_param('city_id');
        require_once __DIR__ . '/api/class-sunhotels-client.php';
        $client = new Sunhotels_Client();
        error_log('[freestays] get_resorts aangeroepen met city_id: ' . $city_id);
        $result = $client->getResorts($city_id);
        error_log('[freestays] get_resorts resultaat: ' . print_r($result, true));
        return rest_ensure_response(['data' => $result]);
    }

    public static function search_hotels($request) {
        $params = $request->get_json_params();
        require_once __DIR__ . '/api/class-sunhotels-client.php';
        $client = new Sunhotels_Client();
        $result = $client->searchV3($params);
        if ($result && isset($result['hotels'])) {
            return ['success' => true, 'data' => $result['hotels']];
        }
        return ['success' => false, 'data' => []];
    }
}
add_action('rest_api_init', ['Freestays_API', 'register_routes']);