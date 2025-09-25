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
            $.each(response, function(i, city) {
                options += '<option value="' + city.id + '">' + city.name + '</option>';
            });
            $('#freestays_city').html(options);
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
            $.each(response, function(i, resort) {
                options += '<option value="' + resort.id + '">' + resort.name + '</option>';
            });
            $('#freestays_resort').html(options);
        });
    });
});