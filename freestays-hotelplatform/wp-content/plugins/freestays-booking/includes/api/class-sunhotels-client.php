<?php

class SunhotelsClient {
    private $apiUrl;
    private $apiUser;
    private $apiPass;

    public function __construct($apiUrl, $apiUser, $apiPass) {
        $this->apiUrl = $apiUrl;
        $this->apiUser = $apiUser;
        $this->apiPass = $apiPass;
    }

    public function searchHotels($params) {
        $xml = $this->buildSearchXml($params);
        $response = $this->sendRequest($xml);
        return $this->parseResponse($response);
    }

    private function buildSearchXml($params) {
        $checkin = htmlspecialchars($params['checkin']);
        $checkout = htmlspecialchars($params['checkout']);
        $adults = intval($params['adults'] ?? 2);
        $children = intval($params['children'] ?? 0);
        $rooms = intval($params['rooms'] ?? 1);
        $destinationId = intval($params['destination_id'] ?? 0);
        $hotelId = htmlspecialchars($params['hotel_id'] ?? '');

        $destinationBlock = $hotelId ? "<hotelIDs>$hotelId</hotelIDs>" : "<destinationID>$destinationId</destinationID>";

        return '<?xml version="1.0" encoding="utf-8"?>' .
            '<SearchV3 xmlns="http://xml.sunhotels.net/15/">' .
            '<userName>' . $this->apiUser . '</userName>' .
            '<password>' . $this->apiPass . '</password>' .
            '<language>EN</language>' .
            '<currencies>EUR</currencies>' .
            '<searchV3Request>' .
            '<checkInDate>' . $checkin . '</checkInDate>' .
            '<checkOutDate>' . $checkout . '</checkOutDate>' .
            '<numberOfRooms>' . $rooms . '</numberOfRooms>' .
            $destinationBlock .
            '<blockSuperdeal>ja</blockSuperdeal>' .
            '<paxRooms>' .
            str_repeat('<paxRoom><numberOfAdults>' . $adults . '</numberOfAdults><numberOfChildren>' . $children . '</numberOfChildren></paxRoom>', $rooms) .
            '</paxRooms>' .
            '</searchV3Request>' .
            '</SearchV3>';
    }

    private function sendRequest($xml) {
        $ch = curl_init($this->apiUrl);
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
        curl_close($ch);
        return $response;
    }

    private function parseResponse($response) {
        libxml_use_internal_errors(true);
        $parsed = simplexml_load_string($response);
        if (!$parsed) {
            return ['error' => 'Invalid response from Sunhotels API'];
        }

        $hotels = [];
        foreach ($parsed->searchV3Response->hotels->hotel as $hotel) {
            $hotels[] = [
                'hotel_id' => (string)$hotel['id'],
                'name' => (string)$hotel->name,
                'city' => (string)$hotel->city,
                'country' => (string)$hotel->country,
                'star_rating' => (string)$hotel->starRating,
                'address' => (string)$hotel->address,
                'image_url' => (string)$hotel->mainImage,
                'availability' => true,
                'price_total' => (string)$hotel->rooms->room->price->total,
                'currency' => (string)$hotel->rooms->room->price->currency,
            ];
        }

        return ['hotels' => $hotels];
    }
}