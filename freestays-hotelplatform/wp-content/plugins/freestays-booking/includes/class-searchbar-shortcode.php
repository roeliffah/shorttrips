<?php
/**
 * Shortcode voor het hotel zoekformulier
 */
class Searchbar_Shortcode {
    public static function render($atts = [], $content = null) {
        ob_start();
        ?>
        <form id="freestays-search-form" method="get" action="">
            <input type="text" name="q" placeholder="Bestemming, hotel of plaats" />
            <select name="country" id="country-select">
                <option value="">Kies land</option>
            </select>
            <select name="city_id" id="city-select">
                <option value="">Kies stad</option>
            </select>
            <select name="resort_id" id="resort-select">
                <option value="">Kies resort</option>
            </select>
            <input type="date" name="start" required />
            <input type="date" name="end" required />
            <input type="number" name="room" min="1" value="1" />
            <input type="number" name="adults" min="1" value="2" />
            <input type="number" name="children" min="0" value="0" />
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
        });
        document.getElementById('freestays-search-form').onsubmit = async function(e) {
            e.preventDefault();
            const form = e.target;
            const data = Object.fromEntries(new FormData(form).entries());
            const res = await fetch('/wp-json/freestays/v1/search', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify(data)
            });
            const json = await res.json();
            document.getElementById('freestays-search-results').innerHTML =
                json.success && Array.isArray(json.data) && json.data.length > 0
                    ? json.data.map(hotel => `<div>${JSON.stringify(hotel)}</div>`).join('')
                    : 'Geen resultaten gevonden.';
        };
        </script>
        <?php
        return ob_get_clean();
    }
}
add_shortcode('freestays_search', 'freestays_search_shortcode');
add_shortcode('freestays_filters', 'freestays_filters_shortcode'); // Moet je functie bestaan!
add_shortcode('freestays_searchbar', [Searchbar_Shortcode::class, 'render']); // Voor class-based shortcode