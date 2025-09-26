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
        // Dynamisch destinationID bepalen
        $destinationID = $destination_id ?: ($resort_id ?: ($city_id ?: $country_id));
        if (empty($destinationID) || !is_numeric($destinationID)) {
            // Zoek op de gebruikersinvoer (bijv. stad, resort of land)
            $zoekterm = $resort_id ?: ($city_id ?: ($country_id ?: 'Turkije'));
            // Als resort/city/country geen ID is, gebruik de naam als zoekterm
            if (!is_numeric($zoekterm)) {
                $destinationID = $this->getDestinationIdByName($zoekterm);
            }
            if (empty($destinationID)) {
                throw new Exception('Geen geldige destinationID gevonden voor zoeknaam "' . $zoekterm . '".');
            }
        }

        // Bouw de SOAP XML body
        $soap_body = '<?xml version="1.0" encoding="utf-8"?>'
            . '<soap:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" '
            . 'xmlns:xsd="http://www.w3.org/2001/XMLSchema" '
            . 'xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">'
            . '<soap:Body>'
            . '<SearchHotels xmlns="http://xml.sunhotels.net/15/">'
            . '<userName>' . esc_html($this->api_user) . '</userName>'
            . '<password>' . esc_html($this->api_pass) . '</password>'
            . '<language>en</language>'
            . '<currencies>EUR</currencies>'
            . '<checkInDate>' . esc_html($checkin) . '</checkInDate>'
            . '<checkOutDate>' . esc_html($checkout) . '</checkOutDate>'
            . '<numberOfRooms>' . intval($rooms) . '</numberOfRooms>'
            . '<destinationID>' . esc_html($destinationID) . '</destinationID>'
            . '<numberOfAdults>' . intval($adults) . '</numberOfAdults>'
            . '<numberOfChildren>' . intval($children) . '</numberOfChildren>';

        // Optionele velden
        if (!empty($child_ages) && $children > 0) {
            $soap_body .= '<childrenAges>' . esc_html(implode(',', $child_ages)) . '</childrenAges>';
        }
        if (!empty($resort_id)) {
            $soap_body .= '<resortIDs>' . esc_html($resort_id) . '</resortIDs>';
        }
        if (!empty($mealIds)) {
            $soap_body .= '<mealIds>' . esc_html($mealIds) . '</mealIds>';
        }
        if ($showReviews) {
            $soap_body .= '<showReviews>true</showReviews>';
        }
        if ($minStarRating !== '') {
            $soap_body .= '<minStarRating>' . intval($minStarRating) . '</minStarRating>';
        }
        if ($maxStarRating !== '') {
            $soap_body .= '<maxStarRating>' . intval($maxStarRating) . '</maxStarRating>';
        }
        if (!empty($featureIds)) {
            $soap_body .= '<featureIds>' . esc_html($featureIds) . '</featureIds>';
        }
        if ($minPrice !== '') {
            $soap_body .= '<minPrice>' . floatval($minPrice) . '</minPrice>';
        }
        if (!empty($themeIds)) {
            $soap_body .= '<themeIds>' . esc_html($themeIds) . '</themeIds>';
        }
        if ($totalRoomsInBatch !== '') {
            $soap_body .= '<totalRoomsInBatch>' . intval($totalRoomsInBatch) . '</totalRoomsInBatch>';
        }

        $soap_body .= '</SearchHotels></soap:Body></soap:Envelope>';

        // Debug: log request body
        error_log('Sunhotels SOAP SearchHotels body: ' . $soap_body);

        $response = wp_remote_post($this->api_url, [
            'body'    => $soap_body,
            'headers' => [
                'Content-Type' => 'text/xml; charset=utf-8',
                'SOAPAction'   => 'http://xml.sunhotels.net/15/SearchHotels'
            ],
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

        // Parse de SOAP response om bij het echte resultaat te komen
        $xml = simplexml_load_string($body, 'SimpleXMLElement', LIBXML_NOCDATA);
        if ($xml === false) {
            throw new Exception('Ongeldige XML van Sunhotels.');
        }

        // Zoek het SearchHotelsResult element in de SOAP response
        $result = $xml->xpath('//SearchHotelsResult');
        if (!$result || !isset($result[0])) {
            throw new Exception('Geen SearchHotelsResult gevonden in response.');
        }

        $hotels_xml = simplexml_load_string($result[0], 'SimpleXMLElement', LIBXML_NOCDATA);
        if ($hotels_xml === false) {
            throw new Exception('Ongeldige hoteldata XML van Sunhotels.');
        }

        // Mapping: alleen relevante hoteldata als array
        $hotels = [];
        if (isset($hotels_xml->hotels->hotel)) {
            foreach ($hotels_xml->hotels->hotel as $hotel) {
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

    /**
     * Haal een geldige destinationID op aan de hand van een naam (bijv. "Turkije" of "Antalya")
     * Retourneert de eerste matchende destinationID of null.
     */
    public function getDestinationIdByName($searchName) {
        $soap_body = '<?xml version="1.0" encoding="utf-8"?>'
            . '<soap:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" '
            . 'xmlns:xsd="http://www.w3.org/2001/XMLSchema" '
            . 'xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">'
            . '<soap:Body>'
            . '<GetDestinations xmlns="http://xml.sunhotels.net/15/">'
            . '<userName>' . esc_html($this->api_user) . '</userName>'
            . '<password>' . esc_html($this->api_pass) . '</password>'
            . '<language>en</language>'
            . '</GetDestinations>'
            . '</soap:Body>'
            . '</soap:Envelope>';

        $response = wp_remote_post($this->api_url, [
            'body'    => $soap_body,
            'headers' => [
                'Content-Type' => 'text/xml; charset=utf-8',
                'SOAPAction'   => 'http://xml.sunhotels.net/15/GetDestinations'
            ],
            'timeout' => 30,
        ]);

        if (is_wp_error($response)) {
            return null;
        }

        $body = wp_remote_retrieve_body($response);
        if (empty($body) || strpos(trim($body), '<') !== 0 || stripos($body, '<html') !== false) {
            return null;
        }

        $xml = simplexml_load_string($body, 'SimpleXMLElement', LIBXML_NOCDATA);
        if ($xml === false) {
            return null;
        }

        $result = $xml->xpath('//GetDestinationsResult');
        if (!$result || !isset($result[0])) {
            return null;
        }

        $destinations_xml = simplexml_load_string($result[0], 'SimpleXMLElement', LIBXML_NOCDATA);
        if ($destinations_xml === false) {
            return null;
        }

        // Zoek naar een destination met een naam die overeenkomt met $searchName (case-insensitive)
        foreach ($destinations_xml->destinations->destination as $destination) {
            if (stripos((string)$destination->name, $searchName) !== false) {
                return (int)$destination->id;
            }
        }

        return null;
    }

    // ...bestaande code...
}