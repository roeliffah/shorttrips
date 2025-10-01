<?php
function freestays_hotel_list_shortcode($atts) {
    $atts = shortcode_atts([
        'destination_id' => '',
        'checkin' => date('Y-m-d'),
        'checkout' => date('Y-m-d', strtotime('+1 day')),
        'adults' => 2,
        'children' => 0,
        'rooms' => 1,
    ], $atts);

    $api_url  = $_ENV['API_URL'] ?? getenv('API_URL') ?? '';
    $api_user = $_ENV['API_USER'] ?? getenv('API_USER') ?? '';
    $api_pass = $_ENV['API_PASS'] ?? getenv('API_PASS') ?? '';

    require_once dirname(__DIR__) . '/api/class-sunhotels-client.php';
    $client = new Sunhotels_Client($api_url, $api_user, $api_pass);

    try {
        $hotels = $client->searchHotels(
            '', '', '', $atts['destination_id'],
            $atts['checkin'], $atts['checkout'],
            $atts['adults'], $atts['children'], $atts['rooms']
        );
    } catch (Exception $e) {
        return '<div class="error">Fout bij ophalen hotels: ' . esc_html($e->getMessage()) . '</div>';
    }

    ob_start();
    echo '<div class="freestays-hotel-list">';
    foreach ($hotels as $hotel) {
        $hotel_data = $hotel; // Zorg dat dit overeenkomt met je hotel-card.php
        include dirname(__DIR__) . '/../templates/hotel-card.php';
    }
    echo '</div>';
    return ob_get_clean();
}
add_shortcode('freestays_hotel_list', 'freestays_hotel_list_shortcode');