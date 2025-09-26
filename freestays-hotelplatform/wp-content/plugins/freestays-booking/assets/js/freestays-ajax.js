<?php
// Zorg dat deze PHP alleen in je enqueue/registratie-bestand staat, niet in je JS-bestand zelf!
wp_localize_script(
    'freestays-ajax-js',
    'freestaysAjax',
    array('ajax_url' => admin_url('admin-ajax.php'))
);
?>

// filepath: /workspaces/shorttrips/freestays-hotelplatform/wp-content/plugins/freestays-booking/assets/js/freestays-ajax.js
jQuery(document).ready(function($) {
    $('#freestays_country').on('change', function() {
        var countryId = $(this).val();
        $('#freestays_city').html('<option>Even laden...</option>');
        $('#freestays_resort').html('<option>Kies resort (optioneel)</option>');
        $.post(freestaysAjax.ajax_url, {
            action: 'freestays_get_cities',
            country_id: countryId
        }, function(response) {
            var options = '<option value="">Kies stad</option>';
            if (Array.isArray(response) && response.length > 0) {
                $.each(response, function(i, city) {
                    options += '<option value="' + city.id + '">' + city.name + '</option>';
                });
            } else {
                options += '<option value="">Geen steden gevonden</option>';
            }
            $('#freestays_city').html(options);
        }).fail(function() {
            $('#freestays_city').html('<option value="">Fout bij laden steden</option>');
        });
    });

    $('#freestays_city').on('change', function() {
        var cityId = $(this).val();
        $('#freestays_resort').html('<option>Even laden...</option>');
        $.post(freestaysAjax.ajax_url, {
            action: 'freestays_get_resorts',
            city_id: cityId
        }, function(response) {
            var options = '<option value="">Kies resort (optioneel)</option>';
            if (Array.isArray(response) && response.length > 0) {
                $.each(response, function(i, resort) {
                    options += '<option value="' + resort.id + '">' + resort.name + '</option>';
                });
            } else {
                options += '<option value="">Geen resorts gevonden</option>';
            }
            $('#freestays_resort').html(options);
        }).fail(function() {
            $('#freestays_resort').html('<option value="">Fout bij laden resorts</option>');
        });
    });
});

