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

            <label for="resort-select" style="margin-left:10px;">Resort:</label>
            <select id="resort-select" name="resort_id"></select>

            <input type="text" id="search-input" name="q" placeholder="Zoekterm (optioneel)" style="margin-left:10px;">
            <input type="date" id="checkin-input" name="start" style="margin-left:10px;">
            <input type="date" id="checkout-input" name="end">
            <input type="number" id="adults-input" name="adults" value="2" min="1" style="width:60px;margin-left:10px;">
            <input type="number" id="children-input" name="children" value="0" min="0" style="width:60px;">
            <input type="number" id="rooms-input" name="room" value="1" min="1" style="width:60px;">
            <button type="submit" style="margin-left:10px;">Zoeken</button>
        </form>
        <div id="freestays-search-results"></div>
        <script>
        async function loadCountries() {
            try {
                const res = await fetch('/wp-json/freestays/v1/countries');
                const json = await res.json();
                const select = document.getElementById('country-select');
                select.innerHTML = '<option value="">Kies land</option>' +
                    (json.data || []).map(c => `<option value="${c.destinationID}">${c.name}</option>`).join('');
            } catch(e) {
                document.getElementById('country-select').innerHTML = '<option value="">Fout bij laden landen</option>';
            }
        }
        async function loadCities(countryId) {
            try {
                const res = await fetch('/wp-json/freestays/v1/cities?country_id=' + encodeURIComponent(countryId));
                const json = await res.json();
                const select = document.getElementById('city-select');
                select.innerHTML = '<option value="">Kies stad</option>' +
                    (json.data || []).map(c => `<option value="${c.destinationID}">${c.name}</option>`).join('');
            } catch(e) {
                document.getElementById('city-select').innerHTML = '<option value="">Fout bij laden steden</option>';
            }
        }
        async function loadResorts(cityId) {
            try {
                const res = await fetch('/wp-json/freestays/v1/resorts?city_id=' + encodeURIComponent(cityId));
                const json = await res.json();
                const select = document.getElementById('resort-select');
                select.innerHTML = '<option value="">Kies resort</option>' +
                    (json.data || []).map(r => `<option value="${r.destinationID}">${r.name}</option>`).join('');
            } catch(e) {
                document.getElementById('resort-select').innerHTML = '<option value="">Fout bij laden resorts</option>';
            }
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
                let json;
                try {
                    const res = await fetch('/wp-json/freestays/v1/search', {
                        method: 'POST',
                        headers: {'Content-Type': 'application/json'},
                        body: JSON.stringify(data)
                    });
                    json = await res.json();
                } catch(e) {
                    document.getElementById('freestays-search-results').innerHTML = '<div>Fout bij zoeken: ' + e + '</div>';
                    return;
                }
                const resultsDiv = document.getElementById('freestays-search-results');
                if (json.data && json.data.length) {
                    resultsDiv.innerHTML = json.data.map(hotel =>
                        `<div class="hotel-result" style="border:1px solid #ccc;padding:12px;margin-bottom:8px;">
                            <strong>${hotel.name}</strong><br>
                            ${hotel.city ? hotel.city + '<br>' : ''}
                            ${hotel.country ? hotel.country + '<br>' : ''}
                            ${hotel.price ? 'Prijs: ' + hotel.price + '<br>' : ''}
                            ${hotel.image ? '<img src="' + hotel.image + '" style="max-width:120px;max-height:80px;" />' : ''}
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