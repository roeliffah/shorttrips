<?php
function freestays_search_form_shortcode() {
    $id = 'freestays-search-form-' . uniqid();
    ob_start();
    ?>
    <div id="<?php echo esc_attr($id); ?>"></div>
    <script>
        if (window.FreestaysRenderSearchForm) {
            window.FreestaysRenderSearchForm('<?php echo esc_js($id); ?>');
        } else {
            document.addEventListener('DOMContentLoaded', function() {
                if (window.FreestaysRenderSearchForm) {
                    window.FreestaysRenderSearchForm('<?php echo esc_js($id); ?>');
                }
            });
        }
    </script>
    <?php
    return ob_get_clean();
}
add_shortcode('freestays_search', 'freestays_search_form_shortcode');