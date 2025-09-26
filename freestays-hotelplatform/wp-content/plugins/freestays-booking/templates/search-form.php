<?php
// Haal landen op via de Sunhotels API
$api_url  = $_ENV['API_URL'] ?? getenv('API_URL') ?? '';
$api_user = $_ENV['API_USER'] ?? getenv('API_USER') ?? '';
$api_pass = $_ENV['API_PASS'] ?? getenv('API_PASS') ?? '';

// Eventueel debuggen:
error_log('API_URL: ' . ($api_url ?: 'NIET GEZET'));

require_once plugin_dir_path(__FILE__) . '../includes/api/class-sunhotels-client.php';
$client = new Sunhotels_Client($api_url, $api_user, $api_pass);

try {
    $countries = $client->getDestinations(); // Controleer de key-namen hieronder!
} catch (Exception $e) {
    $countries = [];
    echo '<div class="error">Kan landen niet laden: ' . esc_html($e->getMessage()) . '</div>';
}
?>
<?php // var_dump($countries); ?>
<form method="post" class="freestays-search-form">
    <div class="fs-search-row">
        <div class="form-group" style="flex:2">
            <label for="freestays_search">Bestemming of hotel</label>
            <input type="text" name="freestays_search" id="freestays_search" value="<?php echo esc_attr($search_query ?? ''); ?>" placeholder="Bijv. Amsterdam, Spanje, Hotelnaam">
        </div>
        <div class="form-group">
            <label for="freestays_country">Land</label>
            <select name="freestays_country" id="freestays_country">
                <option value="">Kies land</option>
                <?php foreach ($countries as $country): ?>
                    <option value="<?php echo esc_attr($country['destinationId'] ?? $country['id']); ?>">
                        <?php echo esc_html($country['destinationName'] ?? $country['name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label for="freestays_city">Stad</label>
            <select name="freestays_city" id="freestays_city">
                <option value="">Kies stad</option>
                <?php foreach ($cities ?? [] as $city): ?>
                    <option value="<?php echo esc_attr($city['id']); ?>"<?php if (($city_id ?? '') === $city['id']) echo ' selected'; ?>>
                        <?php echo esc_html($city['name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label for="freestays_resort">Resort</label>
            <select name="freestays_resort" id="freestays_resort">
                <option value="">Kies resort (optioneel)</option>
                <?php foreach ($resorts ?? [] as $resort): ?>
                    <option value="<?php echo esc_attr($resort['id']); ?>"<?php if (($resort_id ?? '') === $resort['id']) echo ' selected'; ?>>
                        <?php echo esc_html($resort['name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>
    <div class="fs-search-row">
        <div class="form-group">
            <label for="freestays_checkin">Check-in</label>
            <input type="date" name="freestays_checkin" id="freestays_checkin" value="<?php echo esc_attr($checkin ?? ''); ?>" required>
        </div>
        <div class="form-group">
            <label for="freestays_checkout">Check-out</label>
            <input type="date" name="freestays_checkout" id="freestays_checkout" value="<?php echo esc_attr($checkout ?? ''); ?>" required>
        </div>
        <div class="form-group">
            <label for="freestays_adults">Volwassenen</label>
            <input type="number" name="freestays_adults" id="freestays_adults" value="<?php echo esc_attr($adults ?? 2); ?>" min="1" required>
        </div>
        <div class="form-group">
            <label for="freestays_children">Kinderen</label>
            <input type="number" name="freestays_children" id="freestays_children" value="<?php echo esc_attr($children ?? 0); ?>" min="0">
        </div>
        <div class="form-group">
            <label for="freestays_rooms">Kamers</label>
            <input type="number" name="freestays_rooms" id="freestays_rooms" value="<?php echo esc_attr($rooms ?? 1); ?>" min="1" required>
        </div>
        <div class="form-group" style="align-self: flex-end;">
            <button type="submit" class="fs-btn fs-btn-primary">Zoeken</button>
        </div>
    </div>
</form>