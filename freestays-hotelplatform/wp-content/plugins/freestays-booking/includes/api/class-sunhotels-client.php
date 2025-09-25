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
     * @param string $country_id
     * @param string $city_id
     * @param string $resort_id
     * @param string $search_query
     * @param string $checkin (YYYY-MM-DD)
     * @param string $checkout (YYYY-MM-DD)
     * @param int $adults
     * @param int $children
     * @param int $rooms
     * @param array $child_ages
     * @param int $infant
     * @return array
     * @throws Exception
     */
    public function searchHotels($country_id, $city_id, $resort_id, $search_query, $checkin, $checkout, $adults, $children, $rooms, $child_ages = [], $infant = 0) {
        $endpoint = $this->apiUrl . '/SearchV2';

        // Sunhotels ondersteunt alleen 1 kamer per boeking
        $numberOfRooms = 1;

        if (!is_array($child_ages)) {
            $child_ages = [];
        }
        $childrenAges = ($children > 0 && !empty($child_ages)) ? implode(',', $child_ages) : '';

        $params = [
            'userName'         => $this->apiUser,
            'password'         => $this->apiPass,
            'language'         => $this->language,
            'currencies'       => $this->currency,
            'checkInDate'      => $checkin,
            'checkOutDate'     => $checkout,
            'numberOfRooms'    => $numberOfRooms,
            'destination'      => $search_query,
            'numberOfAdults'   => $adults,
            'numberOfChildren' => $children,
            'childrenAges'     => $childrenAges,
            'infant'           => $infant,
            'customerCountry'  => $this->customerCountry,
            'B2C'              => 0, // Altijd 0
        ];

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
            throw new Exception('Lege response van Sunhotels API.');
        }

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
                $hotelArr = [
                    'id'           => (string)($hotel->{'hotel.id'} ?? ''),
                    'name'         => (string)($hotel->name ?? ''),
                    'destination'  => (string)($hotel->destination ?? ''),
                    'city'         => (string)($hotel->{'hotel.addr.city'} ?? ''),
                    'address'      => (string)($hotel->{'hotel.address'} ?? ''),
                    'classification' => (string)($hotel->classification ?? ''),
                    'roomtypes'    => [],
                ];

                if (isset($hotel->roomtypes->roomtype)) {
                    foreach ($hotel->roomtypes->roomtype as $roomtype) {
                        $roomtypeArr = [
                            'id'   => (string)($roomtype->{'roomtype.ID'} ?? ''),
                            'name' => (string)($roomtype->{'roomtype.Name'} ?? ''),
                            'rooms' => [],
                        ];
                        if (isset($roomtype->rooms->room)) {
                            foreach ($roomtype->rooms->room as $room) {
                                $roomArr = [
                                    'id'        => (string)($room->id ?? ''),
                                    'beds'      => (string)($room->beds ?? ''),
                                    'extrabeds' => (string)($room->extrabeds ?? ''),
                                    'meals'     => [],
                                    'prices'    => [],
                                ];
                                if (isset($room->meals->meal)) {
                                    foreach ($room->meals->meal as $meal) {
                                        $mealArr = [
                                            'id'       => (string)($meal->id ?? ''),
                                            'labelId'  => (string)($meal->labelId ?? ''),
                                            'name'     => (string)($meal->name ?? ''),
                                            'labelText'=> (string)($meal->labelText ?? ''),
                                            'prices'   => [],
                                        ];
                                        if (isset($meal->prices->price)) {
                                            foreach ($meal->prices->price as $price) {
                                                $mealArr['prices'][] = [
                                                    'currency'        => (string)$price['currency'],
                                                    'paymentMethods'  => (string)$price['paymentMethods'],
                                                    'amount'          => (string)$price,
                                                ];
                                            }
                                        }
                                        $roomArr['meals'][] = $mealArr;
                                    }
                                }
                                if (isset($room->prices->price)) {
                                    foreach ($room->prices->price as $price) {
                                        $roomArr['prices'][] = [
                                            'currency'        => (string)$price['currency'],
                                            'paymentMethods'  => (string)$price['paymentMethods'],
                                            'amount'          => (string)$price,
                                        ];
                                    }
                                }
                                $roomtypeArr['rooms'][] = $roomArr;
                            }
                        }
                        $hotelArr['roomtypes'][] = $roomtypeArr;
                    }
                }
                $hotels[] = $hotelArr;
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
}