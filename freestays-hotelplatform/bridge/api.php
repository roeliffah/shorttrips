
<?php
require_once __DIR__ . '/../../vendor/autoload.php';

// .env laden
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../config');
$dotenv->load();

header('Content-Type: application/json');

// CORS headers
if (isset($_SERVER['HTTP_ORIGIN'])) {
    $allowed_origins = explode(',', $_ENV['CORS_ALLOWED_ORIGINS'] ?? '');
    if (in_array($_SERVER['HTTP_ORIGIN'], $allowed_origins)) {
        header('Access-Control-Allow-Origin: ' . $_SERVER['HTTP_ORIGIN']);
    }
}
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// API-key uit .env
$secret = $_ENV['DB_PASS'] ?? '';

// Debug
error_log('DEBUG: $_GET[key]=' . ($_GET['key'] ?? 'nvt') . ' $secret=' . $secret);

if (!isset($_GET['key']) || $_GET['key'] !== $secret) {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}


// === DATABASE CONNECTIE ===
$db_host = $_ENV['DB_HOST'] ?? 'localhost';
$db_port = $_ENV['DB_PORT'] ?? 3306;
$db_name = $_ENV['DB_NAME'] ?? '';
$db_user = $_ENV['DB_USER'] ?? '';
$db_pass = $_ENV['DB_PASS'] ?? '';
$dsn = "mysql:host=$db_host;port=$db_port;dbname=$db_name;charset=utf8mb4";

// Sunhotels API credentials uit .env
$api_user = $_ENV['API_USER'] ?? '';
$api_pass = $_ENV['API_PASS'] ?? '';
$api_url  = $_ENV['API_URL'] ?? '';

// === DATABASE CONNECTIE ===
try {
    $pdo = new PDO($dsn, $db_user, $db_pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database connectie mislukt']);
    exit;
}

// === Mapping en statische data helpers ===
function load_destination_mapping() {
    $file = __DIR__ . '/destinations.json';
    if (!file_exists($file)) return [];
    $json = file_get_contents($file);
    return json_decode($json, true);
}

function load_static_hotels() {
    $file = __DIR__ . '/static_hotels.json';
    if (!file_exists($file)) return [];
    $json = file_get_contents($file);
    $hotels = json_decode($json, true);
    $by_id = [];
    foreach ($hotels as $hotel) {
        if (isset($hotel['hotel_id'])) {
            $by_id[$hotel['hotel_id']] = $hotel;
        }
    }
    return $by_id;
}

function find_destination_id($name) {
    $mapping = load_destination_mapping();
    foreach ($mapping as $dest) {
        if (strcasecmp($dest['name'], $name) === 0) {
            return $dest['id'];
        }
    }
    return null;
}

function merge_hotel_data($dynamic, $static) {
    foreach ($dynamic as &$hotel) {
        $id = $hotel['hotel_id'];
        if (isset($static[$id])) {
            foreach ($static[$id] as $k => $v) {
                if (!isset($hotel[$k]) || empty($hotel[$k])) {
                    $hotel[$k] = $v;
                }
            }
        }
    }
    return $dynamic;
}

// Sunhotels SOAP-call functie met robuuste XML parsing
function sunhotels_search_v3($params) {
    global $api_user, $api_pass, $api_url;

    $checkin = $params['checkin'];
    $checkout = $params['checkout'];
    $adults = $params['adults'] ?? 2;
    $children = $params['children'] ?? 0;
    $rooms = $params['rooms'] ?? 1;

    // Bepaal of we zoeken op hotel_id of destination_id
    $hotel_id = $params['hotel_id'] ?? null;
    $destination_id = $params['destination_id'] ?? null;

    // Bouw de juiste XML
    $destinationBlock = '';
    if ($hotel_id) {
        $destinationBlock = '<hotelIDs>' . htmlspecialchars($hotel_id) . '</hotelIDs>';
    } elseif ($destination_id) {
        $destinationBlock = '<destinationID>' . intval($destination_id) . '</destinationID>';
    }

    $body = '<SearchV3 xmlns="http://xml.sunhotels.net/15/">' .
        '<userName>' . htmlspecialchars($api_user) . '</userName>' .
        '<password>' . htmlspecialchars($api_pass) . '</password>' .
        '<language>EN</language>' .
        '<currencies>EUR</currencies>' .
        '<searchV3Request>' .
            '<checkInDate>' . $checkin . '</checkInDate>' .
            '<checkOutDate>' . $checkout . '</checkOutDate>' .
            '<numberOfRooms>' . intval($rooms) . '</numberOfRooms>' .
            $destinationBlock .
            '<blockSuperdeal>ja</blockSuperdeal>' .
            '<paxRooms>' .
                str_repeat('<paxRoom><numberOfAdults>' . intval($adults) . '</numberOfAdults><numberOfChildren>' . intval($children) . '</numberOfChildren></paxRoom>', $rooms) .
            '</paxRooms>' .
        '</searchV3Request>' .
    '</SearchV3>';

    $xml = '<?xml version="1.0" encoding="utf-8"?>' .
        '<soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">' .
        '<soap:Body>' . $body . '</soap:Body></soap:Envelope>';

    $ch = curl_init($api_url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            "Content-Type: text/xml; charset=utf-8",
            "SOAPAction: http://xml.sunhotels.net/15/SearchV3",
        ],
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $xml,
        CURLOPT_TIMEOUT => 30,
    ]);
    $response = curl_exec($ch);
    $raw_length = $response !== false ? strlen($response) : 0;
    $raw_snippet = $response !== false ? substr($response, 0, 400) : '';
    if (curl_errno($ch)) {
        return [
            'error' => 'Sunhotels API error',
            'availability' => false,
            'sunhotels_raw_response_length' => $raw_length,
            'sunhotels_raw_response_snippet' => $raw_snippet,
            'debug_request_body' => str_replace($api_pass, '***', $body),
        ];
    }
    curl_close($ch);

    // Robuuste XML parsing: vind alle hotel-elementen, ongeacht namespace/diepte
    libxml_use_internal_errors(true);
    $parsed = simplexml_load_string($response);
    if (!$parsed) {
        return [
            'availability' => false,
            'note' => 'Geen kamers beschikbaar',
            'sunhotels_raw_response_length' => $raw_length,
            'sunhotels_raw_response_snippet' => $raw_snippet,
            'debug_request_body' => str_replace($api_pass, '***', $body),
        ];
    }

    // Zoek naar hotel-elementen via json_decode (werkt altijd, ook bij 1 hotel)
    $parsedArr = json_decode(json_encode($parsed), true);
    $hotels = [];
    // Zoek recursief naar alle hotel-elementen
    $findHotels = function($arr) use (&$findHotels, &$hotels) {
        if (is_array($arr)) {
            foreach ($arr as $k => $v) {
                if ($k === 'hotel') {
                    if (isset($v[0])) {
                        foreach ($v as $h) $hotels[] = $h;
                    } else {
                        $hotels[] = $v;
                    }
                } elseif (is_array($v)) {
                    $findHotels($v);
                }
            }
        }
    };
    $findHotels($parsedArr);

    $hotelResults = [];
    foreach ($hotels as $hotel) {
        $hotelResults[] = [
            'hotel_id' => $hotel['@attributes']['id'] ?? '',
            'name' => $hotel['name'] ?? '',
            'city' => $hotel['city'] ?? '',
            'country' => $hotel['country'] ?? '',
            'star_rating' => $hotel['starRating'] ?? '',
            'address' => $hotel['address'] ?? '',
            'image_url' => $hotel['mainImage'] ?? '',
            'availability' => true,
            'price_total' => isset($hotel['rooms']['room']['price']['total']) ? $hotel['rooms']['room']['price']['total'] : null,
            'currency' => isset($hotel['rooms']['room']['price']['currency']) ? $hotel['rooms']['room']['price']['currency'] : null,
        ];
    }

    if (count($hotelResults) > 0) {
        return [
            'availability' => true,
            'hotels' => $hotelResults,
            'sunhotels_raw_response_length' => $raw_length,
            'sunhotels_raw_response_snippet' => $raw_snippet,
            'debug_request_body' => str_replace($api_pass, '***', $body),
        ];
    } else {
        return [
            'availability' => false,
            'note' => 'Geen hotels beschikbaar',
            'sunhotels_raw_response_length' => $raw_length,
            'sunhotels_raw_response_snippet' => $raw_snippet,
            'debug_request_body' => str_replace($api_pass, '***', $body),
        ];
    }
}

// === API actions ===
$action = $_GET['action'] ?? '';
if ($action === 'quicksearch') {
    $checkin     = $_GET['checkin'] ?? date('Y-m-d');
    $checkout    = $_GET['checkout'] ?? date('Y-m-d', strtotime('+6 days'));
    $adults      = $_GET['adults'] ?? 2;
    $children    = $_GET['children'] ?? 0;
    $rooms       = $_GET['rooms'] ?? 1;
    $destination = $_GET['destination'] ?? null;
    $destinationId = $_GET['destination_id'] ?? $_GET['city_id'] ?? null;
    $hotel_id    = $_GET['hotel_id'] ?? null;

    // Debug info
    $debug = [
        'input_destination' => $destination,
        'input_destination_id' => $destinationId,
        'input_hotel_id' => $hotel_id,
        'mapping_used' => false,
        'mapping_result' => null,
        'error' => null,
        'received_params' => $_GET,
        'sunhotels_call_executed' => false,
        'sunhotels_request_body' => null,
        'sunhotels_response_length' => null,
        'sunhotels_response_snippet' => null
    ];

    // 1. Lookup destination_id als alleen naam is opgegeven
    if (!$destinationId && $destination && strtolower($destination) !== 'popular destinations') {
        $destinationId = find_destination_id($destination);
        $debug['mapping_used'] = true;
        $debug['mapping_result'] = $destinationId;
    }

    // 2. Laad statische data alvast
    $static_hotels = load_static_hotels();

    // Geen hotel_id en geen destination_id? Geen zoekopdracht uitvoeren!
    if (!$hotel_id && (!$destinationId || strtolower($destination) === 'popular destinations')) {
        $debug['error'] = 'No valid hotel_id or destination_id provided, skipping Sunhotels search.';
        echo json_encode([
            'results' => [],
            'debug' => $debug
        ]);
        exit;
    }

    // 3. Dynamische zoekopdracht
    $params = [
        'checkin' => $checkin,
        'checkout' => $checkout,
        'adults' => $adults,
        'children' => $children,
        'rooms' => $rooms,
        'destination_id' => $destinationId,
        'hotel_id' => $hotel_id
    ];
    $sunhotels = sunhotels_search_v3($params);
    $debug['sunhotels_call_executed'] = true;
    $debug['sunhotels_request_body'] = isset($sunhotels['debug_request_body']) ? $sunhotels['debug_request_body'] : null;
    $debug['sunhotels_response_length'] = $sunhotels['sunhotels_raw_response_length'] ?? null;
    $debug['sunhotels_response_snippet'] = $sunhotels['sunhotels_raw_response_snippet'] ?? null;

    // 4. Merge per hotel_id
    $results = [];
    if (isset($sunhotels['hotels']) && is_array($sunhotels['hotels']) && count($sunhotels['hotels']) > 0) {
        $results = merge_hotel_data($sunhotels['hotels'], $static_hotels);
    }

    // 5. Fallback: als geen dynamische resultaten, toon statische hotels voor deze bestemming
    if (empty($results) && $destinationId) {
        foreach ($static_hotels as $hotel) {
            if (isset($hotel['destination_id']) && $hotel['destination_id'] == $destinationId) {
                $results[] = $hotel;
            }
        }
    }

    $debug['final_destination_id'] = $destinationId;
    $debug['final_hotel_id'] = $hotel_id;
    $debug['results_count'] = count($results);

    echo json_encode([
        'results' => $results,
        'sunhotels_raw_response_length' => $sunhotels['sunhotels_raw_response_length'] ?? null,
        'sunhotels_raw_response_snippet' => $sunhotels['sunhotels_raw_response_snippet'] ?? null,
        'availability' => $sunhotels['availability'] ?? null,
        'note' => $sunhotels['note'] ?? null,
        'debug' => $debug
    ]);
    exit;
} elseif ($action === 'countries') {
    $stmt = $pdo->query("SELECT id, name FROM bravo_locations WHERE location_type = 'country' ORDER BY name");
    $countries = $stmt->fetchAll();
    echo json_encode(['results' => $countries]);
    exit;
} elseif ($action === 'regions' && isset($_GET['country_id'])) {
    $country_id = $_GET['country_id'];
    $stmt = $pdo->prepare("SELECT id, name FROM bravo_destinations WHERE country_id = ? ORDER BY name");
    $stmt->execute([$country_id]);
    $regions = $stmt->fetchAll();
    echo json_encode(['results' => $regions]);
    exit;
} elseif ($action === 'cities' && isset($_GET['region_id'])) {
    $region_id = $_GET['region_id'];
    $stmt = $pdo->prepare("SELECT DISTINCT city AS id, city AS name FROM bravo_hotels WHERE destination_id = ? AND city IS NOT NULL AND city != '' ORDER BY city");
    $stmt->execute([$region_id]);
    $cities = $stmt->fetchAll();
    echo json_encode(['results' => $cities]);
    exit;
} elseif ($action === 'destinations' && isset($_GET['query'])) {
    $query = '%' . strtolower($_GET['query']) . '%';
    $stmt = $pdo->prepare("
        SELECT DISTINCT 
            h.city AS id, 
            h.city AS name, 
            COALESCE(d.destination_id, 0) AS destination_id
        FROM bravo_hotels h
        LEFT JOIN bravo_locations l ON h.location_id = l.id
        LEFT JOIN bravo_destinations d ON l.destination_id = d.destination_id
        WHERE 
            LOWER(h.city) LIKE ?
            AND h.city IS NOT NULL AND h.city != ''
        ORDER BY h.city
        LIMIT 20
    ");
    $stmt->execute([$query]);
    $results = $stmt->fetchAll();

    foreach ($results as &$row) {
        $row['destination_id'] = (int)$row['destination_id'];
    }
    echo json_encode(['results' => $results]);
    exit;
} elseif ($action === 'landen') {
    $stmt = $pdo->query("SELECT DISTINCT country_name AS name FROM bravo_destinations WHERE country_name IS NOT NULL AND country_name != '' ORDER BY country_name");
    $landen = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo json_encode(['results' => $landen]);
    exit;
} elseif ($action === 'steden' && isset($_GET['land'])) {
    $land = $_GET['land'];
    $stmt = $pdo->prepare("SELECT DISTINCT destination_name AS name FROM bravo_destinations WHERE country_name = ? AND destination_name IS NOT NULL AND destination_name != '' ORDER BY destination_name");
    $stmt->execute([$land]);
    $steden = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo json_encode(['results' => $steden]);
    exit;
} elseif ($action === 'destination-id' && isset($_GET['city'])) {
    $city = $_GET['city'];
    $stmt = $pdo->prepare("SELECT destination_id FROM bravo_hotels WHERE city = ? AND destination_id IS NOT NULL AND destination_id != 0 LIMIT 1");
    $stmt->execute([$city]);
    $row = $stmt->fetch();
    echo json_encode(['destination_id' => $row ? $row['destination_id'] : null]);
    exit;
}
echo json_encode(['error' => 'Invalid action']);
