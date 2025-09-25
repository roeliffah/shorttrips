<?php
class Sunhotels_Client {
    private $apiUrl;
    private $apiUser;
    private $apiPass;
    private $language;
    private $currency;
    private $customerCountry;

    public function __construct($apiUrl, $apiUser, $apiPass, $language = 'en', $currency = 'EUR', $customerCountry = 'NL') {
        $this->apiUrl         = rtrim($apiUrl, '/');
        $this->apiUser        = $apiUser;
        $this->apiPass        = $apiPass;
        $this->language       = $language;
        $this->currency       = $currency;
        $this->customerCountry = $customerCountry;
    }

    /**
     * Zoek hotels via de Sunhotels API SearchV2 (NonStatic).
     */
    public function searchHotels($country_id, $city_id, $resort_id, $destination_id, $checkin, $checkout, $adults, $children, $rooms, $child_ages = [], $infant = 0) {
        if (!is_array($child_ages)) {
            $child_ages = [];
        }
        $childrenAges = ($children > 0 && !empty($child_ages)) ? implode(',', $child_ages) : '';

        $params = [
            'method'        => 'SearchHotels',
            'userName'      => $this->apiUser,
            'password'      => $this->apiPass,
            'language'      => $this->language,
            'currency'      => $this->currency,
            'destinationId' => $destination_id,
            'checkIn'       => $checkin,
            'checkOut'      => $checkout,
            'adults'        => $adults,
            'children'      => $children,
            'rooms'         => $rooms,
            'childrenAges'  => $childrenAges,
        ];

        $response = wp_remote_post($this->apiUrl, [
            'body'    => $params,
            'timeout' => 30,
        ]);

        if (is_wp_error($response)) {
            throw new Exception('API niet bereikbaar: ' . $response->get_error_message());
        }

        $body = wp_remote_retrieve_body($response);

        // Vervang niet-ondersteunde HTML-entiteiten door een spatie
        $body = preg_replace('/&[a-zA-Z0-9#]+;/', ' ', $body);

        $xml = simplexml_load_string($body, 'SimpleXMLElement', LIBXML_NOCDATA);
        if ($xml === false) {
            throw new Exception('Ongeldige XML van Sunhotels API.');
        }

        if (isset($xml->Error)) {
            $errorType = (string)($xml->Error->ErrorType ?? '');
            $errorMsg  = (string)($xml->Error->Message ?? 'Onbekende fout');
            throw new Exception("Sunhotels API error: $errorType - $errorMsg");
        }

        $hotels = [];
        if (isset($xml->hotels->hotel)) {
            foreach ($xml->hotels->hotel as $hotel) {
                $hotels[] = [
                    'id'           => (string)($hotel->{'hotel.id'} ?? ''),
                    'name'         => (string)($hotel->name ?? ''),
                    'destination'  => (string)($hotel->destination ?? ''),
                    'city'         => (string)($hotel->{'hotel.addr.city'} ?? ''),
                    'address'      => (string)($hotel->{'hotel.address'} ?? ''),
                    'classification' => (string)($hotel->classification ?? ''),
                    // ...meer velden indien gewenst...
                ];
            }
        }

        return $hotels;
    }

    /**
     * PreBook-aanvraag bij Sunhotels API.
     * @param array $prebookData Sunhotels parameters, zie documentatie
     * @return array
     * @throws Exception
     */
    public function preBook($prebookData) {
        $endpoint = $this->apiUrl . '/PreBookV2';

        // Sunhotels ondersteunt alleen 1 kamer per boeking
        $prebookData['numberOfRooms'] = 1;

        $params = array_merge([
            'userName'        => $this->apiUser,
            'password'        => $this->apiPass,
            'language'        => $this->language,
            'currencies'      => $this->currency,
            'customerCountry' => $this->customerCountry,
            'B2C'             => 0, // Altijd 0
        ], $prebookData);

        $url = $endpoint . '?' . http_build_query($params);

        $response = wp_remote_get($url, [
            'timeout' => 30,
            'headers' => [
                'Accept' => 'application/xml',
            ],
        ]);

        if (is_wp_error($response)) {
            throw new Exception('API niet bereikbaar: ' . $response->get_error_message());
        }

        $body = wp_remote_retrieve_body($response);
        if (empty($body)) {
            throw new Exception('Lege response van Sunhotels API (PreBook).');
        }

        $xml = simplexml_load_string($body, 'SimpleXMLElement', LIBXML_NOCDATA);
        if ($xml === false) {
            throw new Exception('Ongeldige XML van Sunhotels API (PreBook).');
        }

        if (isset($xml->Error)) {
            $errorType = (string)($xml->Error->ErrorType ?? '');
            $errorMsg  = (string)($xml->Error->Message ?? 'Onbekende fout');
            throw new Exception("Sunhotels API PreBook error: $errorType - $errorMsg");
        }

        return json_decode(json_encode($xml), true);
    }

    /**
     * Book-aanvraag bij Sunhotels API.
     * @param array $bookData Sunhotels parameters, zie documentatie
     * @param bool $paymentCompleted (vereist: true als betaling is afgerond)
     * @return array
     * @throws Exception
     */
    public function book($bookData, $paymentCompleted = false) {
        // Alleen boeken als betaling is afgerond
        if (!$paymentCompleted) {
            throw new Exception('Boeking mag pas naar Sunhotels na succesvolle betaling.');
        }

        // Alleen echte boekingen toestaan als userName NIET FreestaysTEST is
        if ($this->apiUser === 'FreestaysTEST') {
            throw new Exception('Boeken is niet toegestaan in testmodus (FreestaysTEST).');
        }

        $endpoint = $this->apiUrl . '/BookV2';

        // Sunhotels ondersteunt alleen 1 kamer per boeking
        $bookData['numberOfRooms'] = 1;

        $params = array_merge([
            'userName'        => $this->apiUser,
            'password'        => $this->apiPass,
            'language'        => $this->language,
            'currencies'      => $this->currency,
            'customerCountry' => $this->customerCountry,
            'B2C'             => 0, // Altijd 0
        ], $bookData);

        $url = $endpoint . '?' . http_build_query($params);

        $response = wp_remote_get($url, [
            'timeout' => 30,
            'headers' => [
                'Accept' => 'application/xml',
            ],
        ]);

        if (is_wp_error($response)) {
            throw new Exception('API niet bereikbaar: ' . $response->get_error_message());
        }

        $body = wp_remote_retrieve_body($response);
        if (empty($body)) {
            throw new Exception('Lege response van Sunhotels API (Book).');
        }

        $xml = simplexml_load_string($body, 'SimpleXMLElement', LIBXML_NOCDATA);
        if ($xml === false) {
            throw new Exception('Ongeldige XML van Sunhotels API (Book).');
        }

        if (isset($xml->Error)) {
            $errorType = (string)($xml->Error->ErrorType ?? '');
            $errorMsg  = (string)($xml->Error->Message ?? 'Onbekende fout');
            throw new Exception("Sunhotels API Book error: $errorType - $errorMsg");
        }

        return json_decode(json_encode($xml), true);
    }

    /**
     * Haal bestemmingen (landen) op uit de Sunhotels API.
     * @return array
     */
    public function getDestinations() {
        $params = [
            'method'   => 'GetDestinations',
            'userName' => $this->apiUser,
            'password' => $this->apiPass,
            'language' => $this->language,
        ];

        $response = wp_remote_post($this->apiUrl, [
            'body'    => $params,
            'timeout' => 30,
        ]);
        if (is_wp_error($response)) {
            throw new Exception('API niet bereikbaar: ' . $response->get_error_message());
        }
        $body = wp_remote_retrieve_body($response);
        $xml = simplexml_load_string($body, 'SimpleXMLElement', LIBXML_NOCDATA);
        if ($xml === false) {
            throw new Exception('Ongeldige XML van Sunhotels API (GetDestinations).');
        }
        if (isset($xml->Error)) {
            throw new Exception('Sunhotels API error: ' . (string)$xml->Error->Message);
        }
        $destinations = [];
        if (isset($xml->destinations->destination)) {
            foreach ($xml->destinations->destination as $dest) {
                $destinations[] = [
                    'destinationId'   => (string)($dest->destinationId ?? ''),
                    'destinationName' => (string)($dest->destinationName ?? ''),
                ];
            }
        }
        return $destinations;
    }

    /**
     * Haal steden op voor een land (destinationId) uit de Sunhotels API.
     * @param string $countryId
     * @return array
     */
    public function getCitiesByCountry($countryId) {
        $params = [
            'method'       => 'GetCities',
            'userName'     => $this->apiUser,
            'password'     => $this->apiPass,
            'language'     => $this->language,
            'destinationId'=> $countryId,
        ];

        $response = wp_remote_post($this->apiUrl, [
            'body'    => $params,
            'timeout' => 30,
        ]);
        if (is_wp_error($response)) {
            throw new Exception('API niet bereikbaar: ' . $response->get_error_message());
        }
        $body = wp_remote_retrieve_body($response);
        $xml = simplexml_load_string($body, 'SimpleXMLElement', LIBXML_NOCDATA);
        if ($xml === false) {
            throw new Exception('Ongeldige XML van Sunhotels API (GetCities).');
        }
        if (isset($xml->Error)) {
            throw new Exception('Sunhotels API error: ' . (string)$xml->Error->Message);
        }
        $cities = [];
        if (isset($xml->cities->city)) {
            foreach ($xml->cities->city as $city) {
                $cities[] = [
                    'id'   => (string)($city->cityId ?? ''),
                    'name' => (string)($city->cityName ?? ''),
                ];
            }
        }
        return $cities;
    }

    /**
     * Haal resorts op voor een stad (cityId) uit de Sunhotels API.
     * @param string $cityId
     * @return array
     */
    public function getResortsByCity($cityId) {
        $params = [
            'method'   => 'GetResorts',
            'userName' => $this->apiUser,
            'password' => $this->apiPass,
            'language' => $this->language,
            'cityId'   => $cityId,
        ];

        $response = wp_remote_post($this->apiUrl, [
            'body'    => $params,
            'timeout' => 30,
        ]);
        if (is_wp_error($response)) {
            throw new Exception('API niet bereikbaar: ' . $response->get_error_message());
        }
        $body = wp_remote_retrieve_body($response);
        $xml = simplexml_load_string($body, 'SimpleXMLElement', LIBXML_NOCDATA);
        if ($xml === false) {
            throw new Exception('Ongeldige XML van Sunhotels API (GetResorts).');
        }
        if (isset($xml->Error)) {
            throw new Exception('Sunhotels API error: ' . (string)$xml->Error->Message);
        }
        $resorts = [];
        if (isset($xml->resorts->resort)) {
            foreach ($xml->resorts->resort as $resort) {
                $resorts[] = [
                    'id'   => (string)($resort->resortId ?? ''),
                    'name' => (string)($resort->resortName ?? ''),
                ];
            }
        }
        return $resorts;
    }
}