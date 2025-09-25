<?php
// Zorg dat $client een instantie is van Sunhotels_Client
if (!isset($client) || !$client instanceof Sunhotels_Client) {
    $apiUrl  = getenv('API_URL');
    $apiUser = getenv('API_USER');
    $apiPass = getenv('API_PASS');
    $client = new Sunhotels_Client($apiUrl, $apiUser, $apiPass);
}

try {
    $destinations = $client->getDestinations();
} catch (Exception $e) {
    $destinations = [];
    echo '<div class="error">Kan landen niet laden: ' . esc_html($e->getMessage()) . '</div>';
}
?>
<form method="post" class="freestays-search-form">
    <label for="freestays_search">Zoek op hotel, regio of land:</label>
    <input type="text" name="freestays_search" id="freestays_search" value="" placeholder="Bijv. Alanya, Turkije, Hotelnaam">

    <label for="freestays_country">Land:</label>
    <select name="freestays_country" id="freestays_country">
        <option value="">Kies land</option>
        <?php foreach ($destinations as $dest): ?>
            <option value="<?php echo esc_attr($dest['id']); ?>">
                <?php echo esc_html($dest['name']); ?>
            </option>
        <?php endforeach; ?>
    </select>

    <div class="form-group">
        <label for="checkin">Check-in Date:</label>
        <input type="date" id="checkin" name="checkin" required>
    </div>
    <div class="form-group">
        <label for="checkout">Check-out Date:</label>
        <input type="date" id="checkout" name="checkout" required>
    </div>
    <div class="form-group">
        <label for="adults">Adults:</label>
        <select id="adults" name="adults" required>
            <option value="1">1</option>
            <option value="2">2</option>
            <option value="3">3</option>
            <option value="4">4</option>
            <option value="5">5</option>
        </select>
    </div>
    <div class="form-group">
        <label for="children">Children:</label>
        <select id="children" name="children">
            <option value="0">0</option>
            <option value="1">1</option>
            <option value="2">2</option>
            <option value="3">3</option>
            <option value="4">4</option>
        </select>
    </div>
    <div class="form-group">
        <label for="rooms">Rooms:</label>
        <select id="rooms" name="rooms" required>
            <option value="1">1</option>
            <option value="2">2</option>
            <option value="3">3</option>
        </select>
    </div>
    <div class="form-group">
        <label for="destination">Destination (vrij zoeken):</label>
        <input type="text" id="destination" name="destination" placeholder="Enter destination">
    </div>
    <div class="form-group">
        <label for="destination_id">Of kies uit de lijst:</label>
        <select id="destination_id" name="destination_id">
            <option value="">Kies een bestemming...</option>
            <?php foreach ($destinations as $dest): ?>
                <option value="<?php echo esc_attr($dest['id']); ?>">
                    <?php echo esc_html($dest['name']); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    <button type="submit">Search Hotels</button>
</form>