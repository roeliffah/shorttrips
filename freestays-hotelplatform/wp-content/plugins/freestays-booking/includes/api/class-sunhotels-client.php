<?php
/**
 * Sunhotels SOAP API client
 */
class Sunhotels_Client {
    private $apiUser;
    private $apiPass;
    private $apiUrl;

    public function __construct() {
        $envPath = dirname(__DIR__, 3) . '/config/.env';
        if (file_exists($envPath)) {
            $env = parse_ini_file($envPath);
            $this->apiUser = $env['API_USER'] ?? '';
            $this->apiPass = $env['API_PASS'] ?? '';
            $this->apiUrl  = $env['API_URL'] ?? '';
        }
    }

    public function searchV3($params) {
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

    private function buildSearchV3Xml($params) {
        $checkIn = htmlspecialchars($params['start'] ?? date('Y-m-d'));
        $checkOut = htmlspecialchars($params['end'] ?? date('Y-m-d', strtotime('+1 day')));
        $rooms = (int)($params['room'] ?? 1);
        $adults = (int)($params['adults'] ?? 2);
        $children = (int)($params['children'] ?? 0);

        // Prioriteit: vrije zoekterm (q), anders dropdowns
        $destinationID = '';
        if (!empty($params['q'])) {
            $destinationID = htmlspecialchars($params['q']);
        } elseif (!empty($params['city_id'])) {
            $destinationID = htmlspecialchars($params['city_id']);
        } elseif (!empty($params['resort_id'])) {
            $destinationID = htmlspecialchars($params['resort_id']);
        } elseif (!empty($params['country'])) {
            $destinationID = htmlspecialchars($params['country']);
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
