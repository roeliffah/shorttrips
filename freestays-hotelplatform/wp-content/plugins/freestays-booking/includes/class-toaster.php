<?php
class Freestays_Toaster {
    public function __construct() {
        add_shortcode('freestays_toaster', array($this, 'render_toaster'));
        add_action('wp_footer', array($this, 'toaster_js'));
    }

    public function render_toaster($atts = array()) {
        // HTML voor de toaster container
        return '<div id="freestays-toaster" style="display:none;position:fixed;top:30px;right:30px;z-index:9999;min-width:200px;padding:16px;background:#222;color:#fff;border-radius:8px;box-shadow:0 2px 8px rgba(0,0,0,0.2);font-size:16px;"></div>';
    }

    public function toaster_js() {
        ?>
        <script>
        window.freestaysToast = function(msg, timeout) {
            var el = document.getElementById('freestays-toaster');
            if (!el) return;
            el.textContent = msg;
            el.style.display = 'block';
            setTimeout(function() {
                el.style.display = 'none';
            }, timeout || 3000);
        };
        </script>
        <?php
    }
}
new Freestays_Toaster();
?>