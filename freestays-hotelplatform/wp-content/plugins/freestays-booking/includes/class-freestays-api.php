<?php
<?php
/**
 * REST API endpoint voor hotel zoeken
 */
class Freestays_API {
    public static function register_routes() {
        register_rest_route('freestays/v1', '/search', [
            'methods' => 'POST',
            'callback' => [self::class, 'search_hotels'],
            'permission_callback' => '__return_true',
        ]);
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