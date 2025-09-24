<?php
/**
 * Basis zoekshortcode voor Freestays hotelplatform.
 * Plaats [freestays_search] in een pagina of bericht.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class Freestays_Search_Shortcode {
    public static function register() {
        add_shortcode( 'freestays_search', [ __CLASS__, 'render' ] );
        // AJAX handlers registreren
        add_action( 'wp_ajax_freestays_search', [ __CLASS__, 'handle_search_form' ] );
        add_action( 'wp_ajax_nopriv_freestays_search', [ __CLASS__, 'handle_search_form' ] );
    }

    public static function render( $atts = [], $content = null ) {
        ob_start();
        ?>
        <form id="freestays-search-form" class="freestays-search-form" method="post" action="<?php echo admin_url('admin-ajax.php'); ?>">
            <input type="hidden" name="action" value="freestays_search" />
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
                <input type="number" name="fs_rooms" min="1" value="1" required disabled />
                <small style="color:#888;">Sunhotels ondersteunt alleen 1 kamer per boeking</small>
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

            // AJAX submit
            document.getElementById('freestays-search-form').addEventListener('submit', function(e) {
                e.preventDefault();
                const form = e.target;
                const formData = new FormData(form);
                fetch(form.action, {
                    method: 'POST',
                    body: formData,
                    credentials: 'same-origin'
                })
                .then(response => response.json())
                .then(data => {
                    const resultsDiv = document.getElementById('freestays-search-results');
                    if (data.success) {
                        // Toon resultaten als cards (voorbeeld)
                        if (Array.isArray(data.data) && data.data.length > 0) {
                            resultsDiv.innerHTML = data.data.map(hotel => `
                                <div class="fs-hotel-card">
                                    <h3>${hotel.name}</h3>
                                    <p>${hotel.address}, ${hotel.city}</p>
                                    <p>Sterren: ${hotel.classification}</p>
                                </div>
                            `).join('');
                        } else {
                            resultsDiv.innerHTML = '<p>Geen hotels gevonden.</p>';
                        }
                    } else {
                        resultsDiv.innerHTML = '<p style="color:red;">' + (data.data && data.data.message ? data.data.message : 'Er is een fout opgetreden.') + '</p>';
                    }
                })
                .catch(() => {
                    document.getElementById('freestays-search-results').innerHTML = '<p style="color:red;">Er is een fout opgetreden bij het zoeken.</p>';
                });
            });
        });
        </script>
        <style>
        .fs-hotel-card { border:1px solid #ddd; padding:15px; margin-bottom:15px; border-radius:4px; }
        </style>
        <?php
        return ob_get_clean();
    }

    public static function handle_search_form() {
        // Mapping van formulier POST naar Sunhotels parameters
        $destination    = isset($_POST['fs_query']) ? sanitize_text_field($_POST['fs_query']) : '';
        $checkin        = isset($_POST['fs_checkin']) ? sanitize_text_field($_POST['fs_checkin']) : '';
        $checkout       = isset($_POST['fs_checkout']) ? sanitize_text_field($_POST['fs_checkout']) : '';
        $adults         = isset($_POST['fs_adults']) ? intval($_POST['fs_adults']) : 2;
        $children       = isset($_POST['fs_children']) ? intval($_POST['fs_children']) : 0;
        $infant         = isset($_POST['fs_infant']) ? intval($_POST['fs_infant']) : 0;

        // Verzamel kind-leeftijden
        $child_ages = [];
        for ($i = 1; $i <= $children; $i++) {
            $key = 'fs_child_age_' . $i;
            if (isset($_POST[$key])) {
                $child_ages[] = intval($_POST[$key]);
            }
        }

        // Haal credentials uit je config/.env
        $apiUrl         = getenv('API_URL');
        $apiUser        = getenv('API_USER');
        $apiPass        = getenv('API_PASS');
        $language       = 'nl';
        $currency       = 'EUR';
        $customerCountry = 'NL';

        $client = new Sunhotels_Client($apiUrl, $apiUser, $apiPass, $language, $currency, $customerCountry);

        try {
            $hotels = $client->searchHotels(
                $destination,
                $checkin,
                $checkout,
                $adults,
                $children,
                $child_ages,
                $infant
            );
            wp_send_json_success($hotels);
        } catch (Exception $e) {
            wp_send_json_error(['message' => $e->getMessage()]);
        }
        wp_die();
    }
}