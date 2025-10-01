<?php
function freestays_filters_shortcode() {
    $countries = freestays_get_countries();

    ob_start();
    ?>
    <form id="freestays-filters-form" class="freestays-filters-form" method="post" autocomplete="off">
        <div style="margin-bottom: 12px;">
            <label for="freestays_country">Land:</label>
            <select name="freestays_country" id="freestays_country">
                <option value="">Kies land</option>
                <?php foreach ($countries as $country): ?>
                    <option value="<?php echo esc_attr($country['id']); ?>"><?php echo esc_html($country['name']); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div style="margin-bottom: 12px;">
            <label for="freestays_city">Stad:</label>
            <select name="freestays_city" id="freestays_city">
                <option value="">Kies stad</option>
            </select>
        </div>
        <div style="margin-bottom: 12px;">
            <label for="freestays_resort">Resort:</label>
            <select name="freestays_resort" id="freestays_resort">
                <option value="">Kies resort (optioneel)</option>
            </select>
        </div>
    </form>
    <?php
    return ob_get_clean();
}
add_shortcode('freestays_filters', 'freestays_filters_shortcode');