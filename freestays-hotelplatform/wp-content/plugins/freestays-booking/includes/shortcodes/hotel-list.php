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

    $apiUrl  = getenv('API_URL');
    $apiUser = getenv('API_USER');
    $apiPass = getenv('API_PASS');
    require_once plugin_dir_path(__DIR__) . '../api/class-sunhotels-client.php';
    $client = new Sunhotels_Client($apiUrl, $apiUser, $apiPass);

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
        echo '<div class="hotel-item">';
        echo '<h3>' . esc_html($hotel['name']) . '</h3>';
        echo '<p>' . esc_html($hotel['city']) . '</p>';
        echo '</div>';
    }
    echo '</div>';
    return ob_get_clean();
}
add_shortcode('freestays_hotel_list', 'freestays_hotel_list_shortcode');