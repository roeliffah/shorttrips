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

    require_once dirname(__DIR__) . '/api/class-sunhotels-client.php';
    $client = new Sunhotels_Client();

    try {
        $args = [
            'start' => $atts['checkin'],
            'end' => $atts['checkout'],
            'room' => $atts['rooms'],
            'adults' => $atts['adults'],
            'children' => $atts['children'],
            'destination_id' => $atts['destination_id'],
        ];
        $results = $client->searchV3($args);
    } catch (Exception $e) {
        return '<div class="error">Fout bij ophalen hotels: ' . esc_html($e->getMessage()) . '</div>';
    }

    ob_start();
    echo '<div class="freestays-hotel-list">';
    if (!empty($results['hotels'])) {
        foreach ($results['hotels'] as $hotel) {
            $hotel_data = $hotel; // Zorg dat dit overeenkomt met je hotel-card.php
            include dirname(__DIR__) . '/../templates/hotel-card.php';
        }
    } else {
        echo '<div class="no-results">Geen hotels gevonden.</div>';
    }
    echo '</div>';
    return ob_get_clean();
}
add_shortcode('freestays_hotel_list', 'freestays_hotel_list_shortcode');
?>