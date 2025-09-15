<?php

header('Content-Type: application/json');
// Alleen frontend domein toestaan ivm CORS
if (isset($_SERVER['HTTP_ORIGIN'])) {
    $allowed_origins = [
        'https://shorttrips.eu',
        'http://localhost:3000',
        'http://localhost:5173',
        'http://127.0.0.1:3000',
        'http://127.0.0.1:5173',
    ];
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
$secret = 'hlIGzfFEk5Af0dWNZO4p';
if (!isset($_GET['key']) || $_GET['key'] !== $secret) {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}
// DB connectie tijdelijk uitgeschakeld
$dsn = null;
$db_user = null;
$db_pass = null;
$pdo = null;
$api_user = "FreestaysTEST"; // Username aangepast
$api_pass = "Vision2024!@";
$api_url  = "https://xml.sunhotels.net/15/PostGet/NonStaticXMLAPI.asmx";

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
            // Voeg statische data toe, maar overschrijf geen dynamische velden
            foreach ($static[$id] as $k => $v) {
                if (!isset($hotel[$k]) || empty($hotel[$k])) {
                    $hotel[$k] = $v;
                }
            }
        }
    }
    return $dynamic;
}
function sunhotels_search($destinationId, $checkin, $checkout, $adults = 2, $children = 0) {
    global $api_user, $api_pass, $api_url;
    // Extra filters ophalen uit $_GET
    $rooms = isset($_GET['rooms']) ? intval($_GET['rooms']) : 1;
    $sortBy = isset($_GET['sortBy']) ? htmlspecialchars($_GET['sortBy']) : '';
    $minPrice = isset($_GET['minPrice']) ? floatval($_GET['minPrice']) : '';
    $maxPrice = isset($_GET['maxPrice']) ? floatval($_GET['maxPrice']) : '';
    $minStar = isset($_GET['minStarRating']) ? intval($_GET['minStarRating']) : '';
    $maxStar = isset($_GET['maxStarRating']) ? intval($_GET['maxStarRating']) : '';
    $starRating = isset($_GET['starRating']) ? $_GET['starRating'] : '';
    $accommodationTypes = isset($_GET['accommodationTypes']) ? htmlspecialchars($_GET['accommodationTypes']) : '';
    $featureIds = isset($_GET['featureIds']) ? htmlspecialchars($_GET['featureIds']) : '';
    $themeIds = isset($_GET['themeIds']) ? htmlspecialchars($_GET['themeIds']) : '';
    $mealIds = isset($_GET['mealIds']) ? htmlspecialchars($_GET['mealIds']) : '';
    $showCoordinates = isset($_GET['showCoordinates']) ? htmlspecialchars($_GET['showCoordinates']) : '';
    $showReviews = isset($_GET['showReviews']) ? htmlspecialchars($_GET['showReviews']) : '';
    $sortOrder = isset($_GET['sortOrder']) ? htmlspecialchars($_GET['sortOrder']) : '';
    $childrenAges = isset($_GET['childrenAges']) ? htmlspecialchars($_GET['childrenAges']) : '';
    $infant = isset($_GET['infant']) ? intval($_GET['infant']) : '';
    $blockSuperdeal = isset($_GET['blockSuperdeal']) ? htmlspecialchars($_GET['blockSuperdeal']) : 'ja';
    $exactDestinationMatch = isset($_GET['exactDestinationMatch']) ? htmlspecialchars($_GET['exactDestinationMatch']) : '';
    $hotelIDs = isset($_GET['hotelIDs']) ? htmlspecialchars($_GET['hotelIDs']) : '';
    $resortIDs = isset($_GET['resortIDs']) ? htmlspecialchars($_GET['resortIDs']) : '';
    $prioritizedHotelIds = isset($_GET['prioritizedHotelIds']) ? htmlspecialchars($_GET['prioritizedHotelIds']) : '';
    $paymentMethodId = isset($_GET['paymentMethodId']) ? htmlspecialchars($_GET['paymentMethodId']) : '';
    $customerCountry = isset($_GET['customerCountry']) ? htmlspecialchars($_GET['customerCountry']) : '';
    $b2c = isset($_GET['b2c']) ? htmlspecialchars($_GET['b2c']) : '';
    $showRoomTypeName = isset($_GET['showRoomTypeName']) ? htmlspecialchars($_GET['showRoomTypeName']) : '';
    $referencePointLatitude = isset($_GET['referencePointLatitude']) ? htmlspecialchars($_GET['referencePointLatitude']) : '';
    $referencePointLongitude = isset($_GET['referencePointLongitude']) ? htmlspecialchars($_GET['referencePointLongitude']) : '';
    $maxDistanceFromReferencePoint = isset($_GET['maxDistanceFromReferencePoint']) ? htmlspecialchars($_GET['maxDistanceFromReferencePoint']) : '';

    // destinationName optioneel meesturen als bekend
    $destinationName = isset($_GET['destination']) ? htmlspecialchars($_GET['destination']) : '';
    $destinationBlock = '';
    if (strlen($destinationId)) {
        $destinationBlock = '<destinationID>' . intval($destinationId) . '</destinationID>';
    } elseif (strlen($destinationName)) {
        $destinationBlock = '<destination>' . $destinationName . '</destination>';
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
            (strlen($hotelIDs) ? '<hotelIDs>' . $hotelIDs . '</hotelIDs>' : '') .
            (strlen($resortIDs) ? '<resortIDs>' . $resortIDs . '</resortIDs>' : '') .
            (strlen($accommodationTypes) ? '<accommodationTypes>' . $accommodationTypes . '</accommodationTypes>' : '') .
            (strlen($themeIds) ? '<themeIds>' . $themeIds . '</themeIds>' : '') .
            (strlen($featureIds) ? '<featureIds>' . $featureIds . '</featureIds>' : '') .
            (strlen($mealIds) ? '<mealIds>' . $mealIds . '</mealIds>' : '') .
            (strlen($minPrice) ? '<minPrice>' . $minPrice . '</minPrice>' : '') .
            (strlen($maxPrice) ? '<maxPrice>' . $maxPrice . '</maxPrice>' : '') .
            (strlen($minStar) ? '<minStarRating>' . $minStar . '</minStarRating>' : '') .
            (strlen($maxStar) ? '<maxStarRating>' . $maxStar . '</maxStarRating>' : '') .
            (strlen($sortBy) ? '<sortBy>' . $sortBy . '</sortBy>' : '') .
            (strlen($sortOrder) ? '<sortOrder>' . $sortOrder . '</sortOrder>' : '') .
            (strlen($blockSuperdeal) ? '<blockSuperdeal>' . $blockSuperdeal . '</blockSuperdeal>' : '') .
            (strlen($exactDestinationMatch) ? '<exactDestinationMatch>' . $exactDestinationMatch . '</exactDestinationMatch>' : '') .
            (strlen($showCoordinates) ? '<showCoordinates>' . $showCoordinates . '</showCoordinates>' : '') .
            (strlen($showReviews) ? '<showReviews>' . $showReviews . '</showReviews>' : '') .
            (strlen($referencePointLatitude) ? '<referencePointLatitude>' . $referencePointLatitude . '</referencePointLatitude>' : '') .
            (strlen($referencePointLongitude) ? '<referencePointLongitude>' . $referencePointLongitude . '</referencePointLongitude>' : '') .
            (strlen($maxDistanceFromReferencePoint) ? '<maxDistanceFromReferencePoint>' . $maxDistanceFromReferencePoint . '</maxDistanceFromReferencePoint>' : '') .
            (strlen($prioritizedHotelIds) ? '<prioritizedHotelIds>' . $prioritizedHotelIds . '</prioritizedHotelIds>' : '') .
            (strlen($paymentMethodId) ? '<paymentMethodId>' . $paymentMethodId . '</paymentMethodId>' : '') .
            (strlen($customerCountry) ? '<customerCountry>' . $customerCountry . '</customerCountry>' : '') .
            (strlen($b2c) ? '<b2c>' . $b2c . '</b2c>' : '') .
            (strlen($showRoomTypeName) ? '<showRoomTypeName>' . $showRoomTypeName . '</showRoomTypeName>' : '') .
            '<paxRooms>' .
                str_repeat('<paxRoom><numberOfAdults>' . intval($adults) . '</numberOfAdults><numberOfChildren>' . intval($children) . '</numberOfChildren>' . (strlen($childrenAges) ? '<childrenAges>' . $childrenAges . '</childrenAges>' : '') . (strlen($infant) ? '<infant>' . $infant . '</infant>' : '') . '</paxRoom>', $rooms) .
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
    $parsed->registerXPathNamespace('soap', 'http://schemas.xmlsoap.org/soap/envelope/');
    $rooms = $parsed->xpath('//room');
    $hotels = $parsed->xpath('//hotel');
    $hotelResults = [];
    if ($hotels && count($hotels) > 0) {
        foreach ($hotels as $hotel) {
            $hotelResults[] = [
                'hotel_id' => (string) $hotel->attributes()->id,
                'name' => (string) $hotel->name,
                'city' => (string) $hotel->city,
                'country' => (string) $hotel->country,
                'star_rating' => (string) $hotel->starRating,
                'address' => (string) $hotel->address,
                'image_url' => (string) $hotel->mainImage,
                'availability' => true,
                'price_total' => isset($hotel->rooms->room->price->total) ? (string) $hotel->rooms->room->price->total : null,
                'currency' => isset($hotel->rooms->room->price->currency) ? (string) $hotel->rooms->room->price->currency : null,
            ];
        }
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



$action = $_GET['action'] ?? '';
if ($action === 'quicksearch') {
    $checkin     = $_GET['checkin'] ?? date('Y-m-d');
    $checkout    = $_GET['checkout'] ?? date('Y-m-d', strtotime('+6 days'));
    $adults      = $_GET['adults'] ?? 2;
    $children    = $_GET['children'] ?? 0;
    $destination = $_GET['destination'] ?? null;
    $destinationId = $_GET['destination_id'] ?? $_GET['city_id'] ?? null;

    // Debug info
    $debug = [
        'input_destination' => $destination,
        'input_destination_id' => $destinationId,
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

    // Geen destination_id? Geen zoekopdracht uitvoeren!
    if (!$destinationId || strtolower($destination) === 'popular destinations') {
        $debug['error'] = 'No valid destination_id provided, skipping Sunhotels search.';
        echo json_encode([
            'results' => [],
            'debug' => $debug
        ]);
        exit;
    }

    // 3. Dynamische zoekopdracht
    $sunhotels = sunhotels_search($destinationId, $checkin, $checkout, $adults, $children);
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
    if (empty($results)) {
        foreach ($static_hotels as $hotel) {
            if (isset($hotel['destination_id']) && $hotel['destination_id'] == $destinationId) {
                $results[] = $hotel;
            }
        }
    }

    $debug['final_destination_id'] = $destinationId;
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
}
echo json_encode(['error' => 'Invalid action']);
