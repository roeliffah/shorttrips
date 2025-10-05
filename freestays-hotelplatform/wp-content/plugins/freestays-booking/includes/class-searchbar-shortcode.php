<?php
/**
 * Shortcode voor het hotel zoekformulier
 */
class Searchbar_Shortcode {
    public static function render($atts = [], $content = null) {
        ob_start();
        ?>
        <form id="freestays-search-form">
            <select id="country-select" name="country_id"></select>
            <select id="city-select" name="city_id"></select>
            <select id="resort-select" name="resort_id"></select>
            <input type="text" id="search-input" name="q" placeholder="Zoekterm (optioneel)">
            <input type="date" id="checkin-input" name="start">
            <input type="date" id="checkout-input" name="end">
            <input type="number" id="adults-input" name="adults" value="2" min="1">
            <input type="number" id="children-input" name="children" value="0" min="0">
            <input type="number" id="rooms-input" name="room" value="1" min="1">
            <button type="submit">Zoeken</button>
        </form>
        <div id="freestays-search-results"></div>
        <script>
        async function loadCountries() {
            const res = await fetch('/wp-json/freestays/v1/countries');
            const json = await res.json();
            const select = document.getElementById('country-select');
            select.innerHTML = '<option value="">Kies land</option>' +
                (json.data || []).map(c => `<option value="${c.id}">${c.name}</option>`).join('');
        }
        async function loadCities(countryId) {
            const res = await fetch('/wp-json/freestays/v1/cities?country_id=' + encodeURIComponent(countryId));
            const json = await res.json();
            const select = document.getElementById('city-select');
            select.innerHTML = '<option value="">Kies stad</option>' +
                (json.data || []).map(c => `<option value="${c.id}">${c.name}</option>`).join('');
        }
        async function loadResorts(cityId) {
            const res = await fetch('/wp-json/freestays/v1/resorts?city_id=' + encodeURIComponent(cityId));
            const json = await res.json();
            const select = document.getElementById('resort-select');
            select.innerHTML = '<option value="">Kies resort</option>' +
                (json.data || []).map(r => `<option value="${r.id}">${r.name}</option>`).join('');
        }
        document.addEventListener('DOMContentLoaded', function() {
            loadCountries();
            document.getElementById('country-select').onchange = function() {
                loadCities(this.value);
                document.getElementById('city-select').innerHTML = '<option value="">Kies stad</option>';
                document.getElementById('resort-select').innerHTML = '<option value="">Kies resort</option>';
            };
            document.getElementById('city-select').onchange = function() {
                loadResorts(this.value);
                document.getElementById('resort-select').innerHTML = '<option value="">Kies resort</option>';
            };
            document.getElementById('freestays-search-form').onsubmit = async function(e) {
                e.preventDefault();
                const data = {
                    country_id: document.getElementById('country-select').value,
                    city_id: document.getElementById('city-select').value,
                    resort_id: document.getElementById('resort-select').value,
                    q: document.getElementById('search-input') ? document.getElementById('search-input').value : '',
                    start: document.getElementById('checkin-input') ? document.getElementById('checkin-input').value : '',
                    end: document.getElementById('checkout-input') ? document.getElementById('checkout-input').value : '',
                    adults: document.getElementById('adults-input') ? document.getElementById('adults-input').value : 2,
                    children: document.getElementById('children-input') ? document.getElementById('children-input').value : 0,
                    room: document.getElementById('rooms-input') ? document.getElementById('rooms-input').value : 1
                };
                const res = await fetch('/wp-json/freestays/v1/search', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify(data)
                });
                const json = await res.json();
                const resultsDiv = document.getElementById('freestays-search-results');
                if (json.data && json.data.length) {
                    resultsDiv.innerHTML = json.data.map(hotel =>
                        `<div class="hotel-result">
                            <strong>${hotel.name}</strong><br>
                            ${hotel.city ? hotel.city + '<br>' : ''}
                            ${hotel.country ? hotel.country + '<br>' : ''}
                        </div>`
                    ).join('');
                } else {
                    resultsDiv.innerHTML = '<div>Geen hotels gevonden.</div>';
                }
            };
        });
        </script>
        <?php
        return ob_get_clean();
    }
}
add_shortcode('freestays_search', 'freestays_search_shortcode');
add_shortcode('freestays_filters', 'freestays_filters_shortcode'); // Moet je functie bestaan!
add_shortcode('freestays_searchbar', [Searchbar_Shortcode::class, 'render']); // Voor class-based shortcode