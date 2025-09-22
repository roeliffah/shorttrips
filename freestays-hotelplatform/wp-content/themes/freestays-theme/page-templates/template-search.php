<?php
/*
Template Name: Hotel Search
*/

get_header(); ?>

<div class="search-container">
    <h1>Zoek Hotels</h1>
    <?php get_template_part('wp-content/plugins/freestays-booking/templates/search-form'); ?>
    
    <div id="hotel-results">
        <?php
        // Hier kunnen we de resultaten van de hotelzoekopdracht weergeven
        // Dit kan worden gedaan door een shortcode of een functie aan te roepen die de hotels ophaalt
        ?>
    </div>
</div>

<?php get_footer(); ?>