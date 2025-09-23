<?php
/**
 * Basis zoekshortcode voor Freestays hotelplatform.
 * Plaats [freestays_search] in een pagina of bericht.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class Freestays_Search_Shortcode {
    public static function register() {
        add_shortcode( 'freestays_search', [ __CLASS__, 'render' ] );
    }

    public static function render( $atts = [], $content = null ) {
        ob_start();
        ?>
        <form id="freestays-search-form" class="freestays-search-form" method="get" action="">
            <div class="fs-search-row">
                <input type="text" name="fs_query" placeholder="Hotel, regio of land" required />
            </div>
            <div class="fs-search-row">
                <label>Check-in</label>
                <input type="date" name="fs_checkin" required />
                <label>Check-out</label>
                <input type="date" name="fs_checkout" required />
            </div>
            <div class="fs-search-row">
                <label>Volwassenen</label>
                <input type="number" name="fs_adults" min="1" value="2" required />
                <label>Kinderen</label>
                <input type="number" name="fs_children" min="0" value="0" required id="fs-children-input" />
                <span id="fs-children-ages"></span>
            </div>
            <div class="fs-search-row">
                <label>Kamers</label>
                <input type="number" name="fs_rooms" min="1" value="1" required />
            </div>
            <div class="fs-search-row">
                <button type="submit" class="fs-btn fs-btn-primary">Zoeken</button>
            </div>
        </form>
        <div id="freestays-search-results"></div>
        <script>
        // Dynamisch leeftijdsvelden tonen voor kinderen
        document.addEventListener('DOMContentLoaded', function() {
            const childrenInput = document.getElementById('fs-children-input');
            const agesContainer = document.getElementById('fs-children-ages');
            function updateAges() {
                agesContainer.innerHTML = '';
                const count = parseInt(childrenInput.value, 10) || 0;
                for (let i = 1; i <= count; i++) {
                    const label = document.createElement('label');
                    label.textContent = 'Leeftijd kind ' + i;
                    const input = document.createElement('input');
                    input.type = 'number';
                    input.name = 'fs_child_age_' + i;
                    input.min = 0;
                    input.max = 17;
                    input.required = true;
                    input.style = "width:60px;margin-right:10px;";
                    agesContainer.appendChild(label);
                    agesContainer.appendChild(input);
                }
            }
            childrenInput.addEventListener('input', updateAges);
            updateAges();
        });
        </script>
        <?php
        return ob_get_clean();
    }
}