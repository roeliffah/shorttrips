<?php
/**
 * Sunhotels SOAP API client
 */
class Sunhotels_Client {
    private $apiUser;
    private $apiPass;
    private $apiUrl;

    public function __construct() {
        $this->apiUser = $_ENV['API_USER'] ?? getenv('API_USER') ?? '';
        $this->apiPass = $_ENV['API_PASS'] ?? getenv('API_PASS') ?? '';
        $this->apiUrl  = $_ENV['API_URL'] ?? getenv('API_URL') ?? '';
    }

    public function getCountries() {
        if (empty($this->apiUrl)) {
            throw new Exception('API URL is niet ingesteld!');
        }

        $xml = $this->buildGetCountriesXml();
        $opts = [
            'http' => [
                'method' => "POST",
                'header' => "Content-Type: text/xml; charset=utf-8\r\nSOAPAction: \"http://xml.sunhotels.net/15/GetCountries\"\r\n",
                'content' => $xml,
                'timeout' => 30
            ]
        ];
        $context = stream_context_create($opts);
        $response = @file_get_contents($this->apiUrl, false, $context);
        if ($response === false) return [];

        $xmlObj = simplexml_load_string($response);
        if (!$xmlObj) return [];

        $xmlObj->registerXPathNamespace('soap', 'http://schemas.xmlsoap.org/soap/envelope/');
        $body = $xmlObj->xpath('//soap:Body')[0] ?? null;
        if (!$body) return [];

        $countries = [];
        $resultNodes = $body->xpath('.//Country');
        if ($resultNodes) {
            foreach ($resultNodes as $country) {
                $countries[] = [
                    'id' => (string)($country->CountryId ?? ''),
                    'name' => (string)($country->Name ?? ''),
                ];
            }
        }
        return $countries;
    }

    public function getCities($country_id) {
        if (empty($this->apiUrl)) {
            throw new Exception('API URL is niet ingesteld!');
        }

        $xml = $this->buildGetCitiesXml($country_id);
        $opts = [
            'http' => [
                'method' => "POST",
                'header' => "Content-Type: text/xml; charset=utf-8\r\nSOAPAction: \"http://xml.sunhotels.net/15/GetCities\"\r\n",
                'content' => $xml,
                'timeout' => 30
            ]
        ];
        $context = stream_context_create($opts);
        $response = @file_get_contents($this->apiUrl, false, $context);
        if ($response === false) return [];

        $xmlObj = simplexml_load_string($response);
        if (!$xmlObj) return [];

        $xmlObj->registerXPathNamespace('soap', 'http://schemas.xmlsoap.org/soap/envelope/');
        $body = $xmlObj->xpath('//soap:Body')[0] ?? null;
        if (!$body) return [];

        $cities = [];
        if (isset($xmlObj->Cities->City)) {
            foreach ($xmlObj->Cities->City as $city) {
                $cities[] = [
                    'id' => (string)$city->CityId,
                    'name' => (string)$city->CityName,
                    'destinationID' => (string)$city->DestinationId // <-- deze moet je toevoegen!
                ];
            }
        }
        return $cities;
    }

    public function getResorts($city_id) {
        if (empty($this->apiUrl)) {
            throw new Exception('API URL is niet ingesteld!');
        }

        $xml = $this->buildGetResortsXml($city_id);
        $opts = [
            'http' => [
                'method' => "POST",
                'header' => "Content-Type: text/xml; charset=utf-8\r\nSOAPAction: \"http://xml.sunhotels.net/15/GetResorts\"\r\n",
                'content' => $xml,
                'timeout' => 30
            ]
        ];
        $context = stream_context_create($opts);
        $response = @file_get_contents($this->apiUrl, false, $context);
        if ($response === false) return [];

        $xmlObj = simplexml_load_string($response);
        if (!$xmlObj) return [];

        $xmlObj->registerXPathNamespace('soap', 'http://schemas.xmlsoap.org/soap/envelope/');
        $body = $xmlObj->xpath('//soap:Body')[0] ?? null;
        if (!$body) return [];

        $resorts = [];
        $resultNodes = $body->xpath('.//Resort');
        if ($resultNodes) {
            foreach ($resultNodes as $resort) {
                $resorts[] = [
                    'id' => (string)($resort->ResortId ?? ''),
                    'name' => (string)($resort->Name ?? ''),
                ];
            }
        }
        return $resorts;
    }

    public function searchV3($params) {
        if (empty($this->apiUrl)) {
            throw new Exception('API URL is niet ingesteld!');
        }

        $xml = $this->buildSearchV3Xml($params);
        $opts = [
            'http' => [
                'method' => "POST",
                'header' => "Content-Type: text/xml; charset=utf-8\r\nSOAPAction: \"http://xml.sunhotels.net/15/SearchV3\"\r\n",
                'content' => $xml,
                'timeout' => 30
            ]
        ];
        $context = stream_context_create($opts);
        $response = @file_get_contents($this->apiUrl, false, $context);
        if ($response === false) return null;

        // Parse de SOAP response
        $xmlObj = simplexml_load_string($response);
        if (!$xmlObj) return null;

        $xmlObj->registerXPathNamespace('soap', 'http://schemas.xmlsoap.org/soap/envelope/');
        $body = $xmlObj->xpath('//soap:Body')[0] ?? null;
        if (!$body) return null;

        // Zoek hotels in de response (vereenvoudigd, pas aan naar jouw API response)
        $hotels = [];
        $resultNodes = $body->xpath('.//Hotel');
        if ($resultNodes) {
            foreach ($resultNodes as $hotel) {
                $hotels[] = [
                    'id' => (string)($hotel->HotelId ?? ''),
                    'name' => (string)($hotel->Name ?? ''),
                    'city' => (string)($hotel->City ?? ''),
                    'country' => (string)($hotel->Country ?? ''),
                    // Voeg meer velden toe indien gewenst
                ];
            }
        }
        return ['hotels' => $hotels];
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
        $countryID = htmlspecialchars($country_id);

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
      <countryID>{$countryID}</countryID>
    </GetCities>
  </soap:Body>
</soap:Envelope>
XML;
    }

    private function buildGetResortsXml($city_id) {
        $cityID = htmlspecialchars($city_id);

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
      <cityID>{$cityID}</cityID>
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

        // destinationID ophalen uit params (altijd meesturen!)
        $destinationID = '';
        if (!empty($params['destination_id'])) {
            $destinationID = htmlspecialchars($params['destination_id']);
        } elseif (!empty($params['q'])) {
            $destinationID = htmlspecialchars($params['q']);
        }

        $blockSuperdeal = 'ja';
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
      <blockSuperdeal>{$blockSuperdeal}</blockSuperdeal>
    </SearchV3>
  </soap:Body>
</soap:Envelope>
XML;
    }
}
