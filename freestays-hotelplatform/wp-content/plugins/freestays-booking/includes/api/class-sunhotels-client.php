<?php
$api_url  = $_ENV['API_URL'] ?? getenv('API_URL') ?? '';
$api_user = $_ENV['API_USER'] ?? getenv('API_USER') ?? '';
$api_pass = $_ENV['API_PASS'] ?? getenv('API_PASS') ?? '';

// Eventueel debuggen:
error_log('API_URL: ' . ($api_url ?: 'NIET GEZET'));

class Sunhotels_Client {
    private $api_url;
    private $api_user;
    private $api_pass;

    public function __construct($api_url, $api_user, $api_pass) {
        $this->api_url  = $api_url;
        $this->api_user = $api_user;
        $this->api_pass = $api_pass;
    }

    /**
     * Zoek hotels via Sunhotels API
     */
    public function searchHotels(
        $country_id,
        $city_id,
        $resort_id,
        $destination_id,
        $checkin,
        $checkout,
        $adults,
        $children,
        $rooms,
        $child_ages = [],
        $mealIds = '',
        $showReviews = false,
        $minStarRating = '',
        $maxStarRating = '',
        $featureIds = '',
        $minPrice = '',
        $themeIds = '',
        $totalRoomsInBatch = ''
    ) {
        // Bepaal destinationID (gebruik $destination_id als zoekterm, anders city/resort/country)
        $destinationID = $destination_id ?: ($resort_id ?: ($city_id ?: $country_id));

        $params = [
            'userName'           => $this->api_user,
            'password'           => $this->api_pass,
            'language'           => 'en',
            'currencies'         => 'EUR',
            'checkInDate'        => $checkin,         // formaat: YYYY-MM-DD
            'checkOutDate'       => $checkout,        // formaat: YYYY-MM-DD
            'numberOfRooms'      => $rooms,
            'destinationID'      => $destinationID,
            'numberOfAdults'     => $adults,
            'numberOfChildren'   => $children,
            'childrenAges'       => $children > 0 ? implode(',', $child_ages) : '',
            'resortIDs'          => $resort_id ? $resort_id : '',
            // Toegevoegde velden:
            'mealIds'            => $mealIds,
            'showReviews'        => $showReviews ? 'true' : 'false',
            'minStarRating'      => $minStarRating,
            'maxStarRating'      => $maxStarRating,
            'featureIds'         => $featureIds,
            'minPrice'           => $minPrice,
            'themeIds'           => $themeIds,
            'totalRoomsInBatch'  => $totalRoomsInBatch,
        ];

        // Filter lege waarden eruit
        $params = array_filter($params, function($v) { return $v !== null && $v !== ''; });

        // Debug: log request
        error_log('Sunhotels SearchHotels params: ' . print_r($params, true));

        $response = wp_remote_post($this->api_url, [
            'body'    => $params,
            'timeout' => 30,
        ]);

        if (is_wp_error($response)) {
            throw new Exception('API niet bereikbaar: ' . $response->get_error_message());
        }

        $body = wp_remote_retrieve_body($response);

        // Controle: is het wel XML?
        if (empty($body) || strpos(trim($body), '<') !== 0 || stripos($body, '<html') !== false) {
            throw new Exception('Ongeldige of lege response van Sunhotels: ' . substr($body, 0, 500));
        }

        $xml = simplexml_load_string($body, 'SimpleXMLElement', LIBXML_NOCDATA);
        if ($xml === false) {
            throw new Exception('Ongeldige XML van Sunhotels.');
        }

        // Mapping: alleen relevante hoteldata als array
        $hotels = [];
        if (isset($xml->hotels->hotel)) {
            foreach ($xml->hotels->hotel as $hotel) {
                $hotels[] = [
                    'id'            => (string)($hotel->{'hotel.id'} ?? ''),
                    'name'          => (string)($hotel->name ?? ''),
                    'city'          => (string)($hotel->{'hotel.addr.city'} ?? ''),
                    'address'       => (string)($hotel->hotel->address ?? $hotel->{'hotel.address'} ?? ''),
                    'country'       => (string)($hotel->{'hotel.addr.country'} ?? ''),
                    'image'         => isset($hotel->images->image[0]->smallImage['url']) ? (string)$hotel->images->image[0]->smallImage['url'] : '',
                    'price'         => isset($hotel->roomtypes->roomtype->rooms->room->meals->meal->prices->price) ? (string)$hotel->roomtypes->roomtype->rooms->room->meals->meal->prices->price : '',
                    'classification'=> (string)($hotel->classification ?? ''),
                    'themes'        => isset($hotel->themes) ? json_encode($hotel->themes) : '',
                    // Voeg meer toe indien gewenst
                ];
            }
        }

        return $hotels;
    }
}