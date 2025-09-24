<?php

class Freestays_Booking_Shortcodes {

    public function __construct() {
        add_shortcode('freestays_search_form', [$this, 'render_search_form']);
        add_shortcode('freestays_hotel_list', [$this, 'render_hotel_list']);
        add_shortcode('freestays_booking_form', [$this, 'render_booking_form']);
    }

    public function render_search_form($atts = []) {
        ob_start();
        include plugin_dir_path(__FILE__) . '../templates/search-form.php';
        return ob_get_clean();
    }

    public function render_hotel_list($atts = []) {
        ob_start();
        include plugin_dir_path(__FILE__) . '../templates/hotel-list.php';
        return ob_get_clean();
    }

    public function render_booking_form($atts = []) {
        ob_start();
        include plugin_dir_path(__FILE__) . '../templates/booking-form.php';
        return ob_get_clean();
    }
}
