<?php
// hotel-detail.php

// Zorg ervoor dat de plugin is geladen
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

// Verkrijg hotelgegevens van de API of database
$hotel_id = isset( $_GET['hotel_id'] ) ? intval( $_GET['hotel_id'] ) : 0;
$hotel_details = get_hotel_details( $hotel_id ); // Functie om hotelgegevens op te halen

if ( ! $hotel_details ) {
    echo '<p>Hotel niet gevonden.</p>';
    return;
}

// Begin HTML-output
?>

<div class="hotel-detail">
    <h1><?php echo esc_html( $hotel_details['name'] ); ?></h1>
    <div class="hotel-images">
        <?php foreach ( $hotel_details['images'] as $image ) : ?>
            <img src="<?php echo esc_url( $image ); ?>" alt="<?php echo esc_attr( $hotel_details['name'] ); ?>">
        <?php endforeach; ?>
    </div>
    <div class="hotel-description">
        <p><?php echo esc_html( $hotel_details['description'] ); ?></p>
    </div>
    <div class="hotel-availability">
        <h2>Beschikbaarheid</h2>
        <p><?php echo esc_html( $hotel_details['availability'] ? 'Beschikbaar' : 'Niet beschikbaar' ); ?></p>
    </div>
    <div class="hotel-booking">
        <h2>Boek nu</h2>
        <form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="POST">
            <input type="hidden" name="action" value="book_hotel">
            <input type="hidden" name="hotel_id" value="<?php echo esc_attr( $hotel_id ); ?>">
            <label for="checkin">Incheckdatum:</label>
            <input type="date" name="checkin" required>
            <label for="checkout">Uitcheckdatum:</label>
            <input type="date" name="checkout" required>
            <input type="submit" value="Boek">
        </form>
    </div>
</div>

<?php
// Functie om hotelgegevens op te halen (placeholder)
function get_hotel_details( $hotel_id ) {
    // Hier zou je de logica implementeren om hotelgegevens op te halen
    // Dit kan een API-aanroep zijn of een databasequery
    return [
        'name' => 'Voorbeeld Hotel',
        'images' => [
            'https://example.com/image1.jpg',
            'https://example.com/image2.jpg',
        ],
        'description' => 'Dit is een voorbeeldbeschrijving van het hotel.',
        'availability' => true,
    ];
}
?>