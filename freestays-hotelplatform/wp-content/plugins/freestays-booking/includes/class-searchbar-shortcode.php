<?php
/**
 * Shortcode voor het hotel zoekformulier
 */
class Searchbar_Shortcode {
    public static function render($atts = [], $content = null) {
        ob_start();
        ?>
        <form id="freestays-search-form" autocomplete="off">
            <label for="country-select">Land:</label>
            <select id="country-select" name="country_id"></select>

            <label for="city-select" style="margin-left:10px;">Stad:</label>
            <select id="city-select" name="city_id"></select>

            <input type="text" id="search-input" name="q" placeholder="Zoekterm (optioneel)" style="margin-left:10px;">
            <button type="submit" style="margin-left:10px;">Zoeken</button>
        </form>
        <div id="freestays-search-results"></div>
        <script>
        async function loadCountries() {
            const res = await fetch('/wp-json/freestays/v1/bravo-destinations');
            const json = await res.json();
            const select = document.getElementById('country-select');
            let countries = {};
            if (Array.isArray(json.data)) {
                json.data.forEach(dest => {
                    if (!countries[dest.country_id]) {
                        countries[dest.country_id] = dest.country_name;
                    }
                });
            }
            select.innerHTML = '<option value="">Kies land</option>' +
                Object.entries(countries).map(([id, name]) => `<option value="${id}">${name}</option>`).join('');
        }
        async function loadCities(countryId) {
            const res = await fetch('/wp-json/freestays/v1/bravo-destinations?country_id=' + encodeURIComponent(countryId));
            const json = await res.json();
            const select = document.getElementById('city-select');
            select.innerHTML = '<option value="">Kies stad</option>' +
                (json.data || []).map(dest => `<option value="${dest.destination_id}">${dest.destination_name}</option>`).join('');
        }
        async function loadResorts(cityId) {
            const res = await fetch('/wp-json/freestays/v1/resorts?city_id=' + encodeURIComponent(cityId));
            const json = await res.json();
            const select = document.getElementById('resort-select');
            select.innerHTML = '<option value="">Kies resort</option>' +
                (json.data || []).map(r => `<option value="${r.destinationID}">${r.name}</option>`).join('');
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
                    destination_id: document.getElementById('resort-select').value
                        || document.getElementById('city-select').value
                        || document.getElementById('country-select').value,
                    start: document.getElementById('checkin-input').value,
                    end: document.getElementById('checkout-input').value,
                    adults: document.getElementById('adults-input').value,
                    children: document.getElementById('children-input').value,
                    room: document.getElementById('rooms-input').value
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
                        `<div class="hotel-result" style="border:1px solid #ccc;padding:12px;margin-bottom:8px;">
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
add_shortcode('freestays_search', 'freestays_react_search_shortcode');
add_shortcode('freestays_filters', 'freestays_filters_shortcode'); // Moet je functie bestaan!
add_shortcode('freestays_searchbar', [Searchbar_Shortcode::class, 'render']); // Voor class-based shortcode