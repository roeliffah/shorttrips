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
                    register_rest_route('freestays/v1', '/bravo-destinations', [
                            'methods' => 'GET',
                            'callback' => [self::class, 'get_bravo_destinations'],
                            'permission_callback' => '__return_true',
                    ]);
    }
    /**
     * Haal Bravo bestemmingen (steden) op uit de database
     * Optioneel filteren op country_id, destination_id, of zoekterm q
     */
    public static function get_bravo_destinations($request) {
        global $wpdb;
        $table = $wpdb->prefix . 'bravo_destinations';
        $country_id = $request->get_param('country_id');
        $destination_id = $request->get_param('destination_id');
        $q = $request->get_param('q');

        $where = [];
        $params = [];
        if (!empty($country_id)) {
            $where[] = 'country_id = %d';
            $params[] = $country_id;
        }
        if (!empty($destination_id)) {
            $where[] = 'destination_id = %d';
            $params[] = $destination_id;
        }
        if (!empty($q)) {
            $where[] = '(destination_name LIKE %s OR country_name LIKE %s)';
            $params[] = '%' . $wpdb->esc_like($q) . '%';
            $params[] = '%' . $wpdb->esc_like($q) . '%';
        }
        $sql = "SELECT id, destination_code, destination_id, destination_name, country_id, country_name FROM $table";
        if ($where) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }
        $sql .= ' ORDER BY destination_name ASC LIMIT 100';
        $results = $wpdb->get_results($wpdb->prepare($sql, ...$params), ARRAY_A);
        return rest_ensure_response(['data' => $results]);
    }

    public static function get_countries($request) {
        require_once __DIR__ . '/api/class-sunhotels-client.php';
        $client = new Sunhotels_Client();
        error_log('[freestays] get_countries aangeroepen');
        $result = $client->getCountries();
        error_log('[freestays] get_countries resultaat: ' . print_r($result, true));
        if (isset($result['error'])) {
            return new WP_Error('sunhotels_credentials', $result['error'], ['status' => 500]);
        }
        return rest_ensure_response(['data' => $result]);
    }

    public static function get_cities($request) {
        $country_id = $request->get_param('country_id');
        require_once __DIR__ . '/api/class-sunhotels-client.php';
        $client = new Sunhotels_Client();
        error_log('[freestays] get_cities aangeroepen met country_id: ' . $country_id);
        $result = $client->getCities($country_id);
        error_log('[freestays] get_cities resultaat: ' . print_r($result, true));
        if (isset($result['error'])) {
            return new WP_Error('sunhotels_credentials', $result['error'], ['status' => 500]);
        }
        return rest_ensure_response(['data' => $result]);
    }

    public static function get_resorts($request) {
        $city_id = $request->get_param('city_id');
        require_once __DIR__ . '/api/class-sunhotels-client.php';
        $client = new Sunhotels_Client();
        error_log('[freestays] get_resorts aangeroepen met city_id: ' . $city_id);
        $result = $client->getResorts($city_id);
        error_log('[freestays] get_resorts resultaat: ' . print_r($result, true));
        if (isset($result['error'])) {
            return new WP_Error('sunhotels_credentials', $result['error'], ['status' => 500]);
        }
        return rest_ensure_response(['data' => $result]);
    }

    public static function search_hotels($request) {
        $params = $request->get_json_params();
        if (empty($params['destination_id'])) {
            error_log('[freestays] search_hotels: destination_id ontbreekt of leeg');
            return new WP_Error('missing_destination_id', 'Geen geldige bestemming geselecteerd.', ['status' => 400]);
        }
        require_once __DIR__ . '/api/class-sunhotels-client.php';
        $client = new Sunhotels_Client();
        $result = $client->searchV3($params);
        if (isset($result['error'])) {
            return new WP_Error('sunhotels_credentials', $result['error'], ['status' => 500]);
        }
        if ($result && isset($result['hotels'])) {
            return ['success' => true, 'data' => $result['hotels']];
        }
        return ['success' => false, 'data' => []];
    }
}
add_action('rest_api_init', ['Freestays_API', 'register_routes']);