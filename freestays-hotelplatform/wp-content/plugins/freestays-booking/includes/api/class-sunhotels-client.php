<?php
/**
 * Sunhotels SOAP API client
 */
class Sunhotels_Client {
    private $apiUrl;
    private $apiUser;
    private $apiPass;

    public function __construct() {
        $this->apiUrl  = $_ENV['API_URL'] ?? getenv('API_URL') ?? '';
        $this->apiUser = $_ENV['API_USER'] ?? getenv('API_USER') ?? '';
        $this->apiPass = $_ENV['API_PASS'] ?? getenv('API_PASS') ?? '';
    }

    public function getCountries() {
        if (empty($this->apiUrl) || empty($this->apiUser) || empty($this->apiPass)) {
            error_log('[freestays] Sunhotels credentials ontbreken!');
            return [
                'error' => 'Sunhotels API credentials ontbreken. Controleer .env en plugin installatie.'
            ];
        }
        $xml = $this->buildGetCountriesXml();
        $response = $this->post($xml, 'GetCountries');
        error_log('[freestays] Sunhotels SOAP RESPONSE BODY (GetCountries): ' . ($response ?: 'GEEN RESPONSE'));
        if (!$response) return [];
        $xmlObj = simplexml_load_string($response);
        if (!$xmlObj) return [];
        $countries = [];
        foreach ($xmlObj->Countries->Country ?? [] as $country) {
            $countries[] = [
                'id' => (string)$country->CountryId,
                'name' => (string)$country->CountryName,
                'destinationID' => (string)$country->DestinationId ?? ''
            ];
        }
        return $countries;
    }

    public function getCities($country_id) {
        if (empty($this->apiUrl) || empty($this->apiUser) || empty($this->apiPass)) {
            error_log('[freestays] Sunhotels credentials ontbreken!');
            return [
                'error' => 'Sunhotels API credentials ontbreken. Controleer .env en plugin installatie.'
            ];
        }
        $xml = $this->buildGetCitiesXml($country_id);
        $response = $this->post($xml, 'GetCities');
        error_log('[freestays] Sunhotels SOAP RESPONSE BODY (GetCities): ' . ($response ?: 'GEEN RESPONSE'));
        if (!$response) return [];
        $xmlObj = simplexml_load_string($response);
        if (!$xmlObj) return [];
        $cities = [];
        foreach ($xmlObj->Cities->City ?? [] as $city) {
            $cities[] = [
                'id' => (string)$city->CityId,
                'name' => (string)$city->CityName,
                'destinationID' => (string)$city->DestinationId ?? ''
            ];
        }
        return $cities;
    }

    public function getResorts($city_id) {
        if (empty($this->apiUrl) || empty($this->apiUser) || empty($this->apiPass)) {
            error_log('[freestays] Sunhotels credentials ontbreken!');
            return [
                'error' => 'Sunhotels API credentials ontbreken. Controleer .env en plugin installatie.'
            ];
        }
        $xml = $this->buildGetResortsXml($city_id);
        $response = $this->post($xml, 'GetResorts');
        error_log('[freestays] Sunhotels SOAP RESPONSE BODY (GetResorts): ' . ($response ?: 'GEEN RESPONSE'));
        if (!$response) return [];
        $xmlObj = simplexml_load_string($response);
        if (!$xmlObj) return [];
        $resorts = [];
        foreach ($xmlObj->Resorts->Resort ?? [] as $resort) {
            $resorts[] = [
                'id' => (string)$resort->ResortId,
                'name' => (string)$resort->ResortName,
                'destinationID' => (string)$resort->DestinationId ?? ''
            ];
        }
        return $resorts;
    }

    public function searchV3($params) {
        if (empty($this->apiUrl) || empty($this->apiUser) || empty($this->apiPass)) {
            error_log('[freestays] Sunhotels credentials ontbreken!');
            return [
                'error' => 'Sunhotels API credentials ontbreken. Controleer .env en plugin installatie.'
            ];
        }
        $xml = $this->buildSearchV3Xml($params);
        $response = $this->post($xml, 'SearchV3');
        if (!$response) return [];
        $xmlObj = simplexml_load_string($response);
        if (!$xmlObj) return [];
        $hotels = [];
        foreach ($xmlObj->Hotels->Hotel ?? [] as $hotel) {
            $hotels[] = [
                'name' => (string)$hotel->HotelName,
                'city' => (string)$hotel->CityName,
                'country' => (string)$hotel->CountryName,
                'price' => (string)$hotel->Price ?? '',
                'image' => (string)$hotel->Image ?? ''
            ];
        }
        return ['hotels' => $hotels];
    }

    private function post($xml, $action) {
        if (empty($this->apiUrl)) return false;
        $headers = [
            "Content-Type: text/xml; charset=utf-8",
            "SOAPAction: \"http://xml.sunhotels.net/15/$action\""
        ];
        $opts = [
            'http' => [
                'method' => "POST",
                'header' => implode("\r\n", $headers),
                'content' => $xml,
                'timeout' => 30
            ]
        ];
        $context = stream_context_create($opts);
        error_log('[freestays] Sunhotels SOAP REQUEST (' . $action . '): ' . $xml);
        $response = @file_get_contents($this->apiUrl, false, $context);
        error_log('[freestays] Sunhotels SOAP RESPONSE (' . $action . '): ' . ($response ?: 'GEEN RESPONSE'));
        return $response;
    }

    private function buildGetCountriesXml() {
        return <<<XML
<?xml version="1.0" encoding="utf-8"?>
<soap:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
               xmlns:xsd="http://www.w3.org/2001/XMLSchema"
               xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">
  <soap:Body>
    <GetCountries xmlns="http://xml.sunhotels.net/15/">
      <userName>{$this->apiUser}</userName>
      <password>{$this->apiPass}</password>
      <language>EN</language>
    </GetCountries>
  </soap:Body>
</soap:Envelope>
XML;
    }

    private function buildGetCitiesXml($country_id) {
        $country_id = htmlspecialchars($country_id);
        return <<<XML
<?xml version="1.0" encoding="utf-8"?>
<soap:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
               xmlns:xsd="http://www.w3.org/2001/XMLSchema"
               xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">
  <soap:Body>
    <GetCities xmlns="http://xml.sunhotels.net/15/">
      <userName>{$this->apiUser}</userName>
      <password>{$this->apiPass}</password>
      <language>EN</language>
      <countryID>{$country_id}</countryID>
    </GetCities>
  </soap:Body>
</soap:Envelope>
XML;
    }

    private function buildGetResortsXml($city_id) {
        $city_id = htmlspecialchars($city_id);
        return <<<XML
<?xml version="1.0" encoding="utf-8"?>
<soap:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
               xmlns:xsd="http://www.w3.org/2001/XMLSchema"
               xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">
  <soap:Body>
    <GetResorts xmlns="http://xml.sunhotels.net/15/">
      <userName>{$this->apiUser}</userName>
      <password>{$this->apiPass}</password>
      <language>EN</language>
      <cityID>{$city_id}</cityID>
    </GetResorts>
  </soap:Body>
</soap:Envelope>
XML;
    }

    private function buildSearchV3Xml($params) {
        $checkIn = htmlspecialchars($params['start'] ?? date('Y-m-d'));
        $checkOut = htmlspecialchars($params['end'] ?? date('Y-m-d', strtotime('+1 day')));
        $rooms = (int)($params['room'] ?? 1);
        $adults = (int)($params['adults'] ?? 2);
        $children = (int)($params['children'] ?? 0);
        $destinationID = htmlspecialchars($params['destination_id'] ?? '');
        return <<<XML
<?xml version="1.0" encoding="utf-8"?>
<soap:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
               xmlns:xsd="http://www.w3.org/2001/XMLSchema"
               xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">
  <soap:Body>
    <SearchV3 xmlns="http://xml.sunhotels.net/15/">
      <userName>{$this->apiUser}</userName>
      <password>{$this->apiPass}</password>
      <language>EN</language>
      <currencies>EUR</currencies>
      <checkInDate>{$checkIn}</checkInDate>
      <checkOutDate>{$checkOut}</checkOutDate>
      <numberOfRooms>{$rooms}</numberOfRooms>
      <destinationID>{$destinationID}</destinationID>
      <numberOfAdults>{$adults}</numberOfAdults>
    <numberOfChildren>{$children}</numberOfChildren>
    </SearchV3>
  </soap:Body>
</soap:Envelope>
XML;
    }
}

