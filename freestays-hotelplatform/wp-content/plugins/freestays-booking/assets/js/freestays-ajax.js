jQuery(document).ready(function($) {
    // Landen laden via REST API
    if ($('#freestays_country').length) {
        $('#freestays_country').html('<option>Even laden...</option>');
        fetch('/wp-json/freestays/v1/countries')
            .then(res => res.json())
            .then(data => {
                var options = '<option value="">Kies land</option>';
                if (Array.isArray(data.data) && data.data.length > 0) {
                    data.data.forEach(country => {
                        options += `<option value="${country.id}">${country.name}</option>`;
                    });
                } else {
                    options += '<option value="">Geen landen gevonden</option>';
                }
                $('#freestays_country').html(options);
            })
            .catch(() => {
                $('#freestays_country').html('<option value="">Fout bij laden landen</option>');
            });
    }
    console.log('freestays-ajax.js geladen');
    $('#freestays_country').on('change', function() {
        var countryId = $(this).val();
        if (!countryId) {
            $('#freestays_city').html('<option value="">Kies eerst een land</option>');
            $('#freestays_resort').html('<option>Kies resort (optioneel)</option>');
            return;
        }
        $('#freestays_city').html('<option>Even laden...</option>');
        $('#freestays_resort').html('<option>Kies resort (optioneel)</option>');
        fetch(`/wp-json/freestays/v1/cities?country_id=${countryId}`)
            .then(res => res.json())
            .then(data => {
                var options = '<option value="">Kies stad</option>';
                if (Array.isArray(data.data) && data.data.length > 0) {
                    data.data.forEach(city => {
                        options += `<option value="${city.id}">${city.name}</option>`;
                    });
                } else {
                    options += '<option value="">Geen steden gevonden</option>';
                }
                $('#freestays_city').html(options);
            })
            .catch(() => {
                $('#freestays_city').html('<option value="">Fout bij laden steden</option>');
            });
    });
    $('#freestays_city').on('change', function() {
        var cityId = $(this).val();
        if (!cityId) {
            $('#freestays_resort').html('<option value="">Kies eerst een stad</option>');
            return;
        }
        $('#freestays_resort').html('<option>Even laden...</option>');
        fetch(`/wp-json/freestays/v1/resorts?city_id=${cityId}`)
            .then(res => res.json())
            .then(data => {
                var options = '<option value="">Kies resort (optioneel)</option>';
                if (Array.isArray(data.data) && data.data.length > 0) {
                    data.data.forEach(resort => {
                        options += `<option value="${resort.id}">${resort.name}</option>`;
                    });
                } else {
                    options += '<option value="">Geen resorts gevonden</option>';
                }
                $('#freestays_resort').html(options);
            })
            .catch(() => {
                $('#freestays_resort').html('<option value="">Fout bij laden resorts</option>');
            });
    });

    $('#load_hotels').on('click', function() {
        var resortId = $('#freestays_resort').val();
        if (!resortId) {
            alert('Kies eerst een resort.');
            return;
        }
        // Voor hotels zoeken: resortId is destination_id
        fetch('/wp-json/freestays/v1/search', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ destination_id: resortId })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                var resultsDiv = $('#results');
                resultsDiv.empty();
                resultsDiv.html(data.data.map(hotel => `
                    <div class="fs-hotel-card">
                        <h3>${hotel.name}</h3>
                        <p>${hotel.city}, ${hotel.country}</p>
                        <p>Prijs: ${hotel.price}</p>
                    </div>
                `).join(''));
            } else {
                $('#results').html('<p>Geen hotels gevonden.</p>');
            }
        })
        .catch(() => {
            $('#results').html('<p>Fout bij het laden van hotels.</p>');
        });
    });
});