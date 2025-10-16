<?php
/*
Plugin Name: Freestays Booking
Description: Maatwerk hotel booking plugin voor Freestays.
Version: 1.0
Author: Freestays Team
*/

// Shortcode en API classes includen
require_once __DIR__ . '/includes/class-searchbar-shortcode.php';
require_once __DIR__ . '/includes/class-freestays-api.php';

// Eventueel extra initialisatie
// add_action('init', ...);



add_action('wp_enqueue_scripts', function() {
    if (is_singular() && has_shortcode(get_post()->post_content, 'freestays_searchbar')) {
        wp_enqueue_script(
            'freestays-react-search',
            plugins_url('../../../../frontend/build/static/js/main.js', __FILE__),
            [],
            null,
            true
        );
        wp_enqueue_style(
            'freestays-react-search',
            plugins_url('../../../../frontend/build/static/css/main.css', __FILE__)
        );
    }
});