jQuery(document).ready(function($) {
    // Bravo landen laden
    if ($('#freestays_country').length) {
        $('#freestays_country').html('<option>Even laden...</option>');
        fetch('/wp-json/freestays/v1/bravo-destinations')
            .then(res => res.json())
            .then(data => {
                var options = '<option value="">Kies land</option>';
                let countries = {};
                if (Array.isArray(data.data) && data.data.length > 0) {
                    data.data.forEach(dest => {
                        if (!countries[dest.country_id]) {
                            countries[dest.country_id] = dest.country_name;
                        }
                    });
                    Object.entries(countries).forEach(([id, name]) => {
                        options += `<option value="${id}">${name}</option>`;
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
            return;
        }
        $('#freestays_city').html('<option>Even laden...</option>');
        fetch(`/wp-json/freestays/v1/bravo-destinations?country_id=${countryId}`)
            .then(res => res.json())
            .then(data => {
                var options = '<option value="">Kies stad</option>';
                if (Array.isArray(data.data) && data.data.length > 0) {
                    data.data.forEach(dest => {
                        options += `<option value="${dest.destination_id}">${dest.destination_name}</option>`;
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
    // Resort dropdown niet meer gebruikt, alles via Bravo steden

    $('#load_hotels').on('click', function() {
        var cityId = $('#freestays_city').val();
        var searchQ = $('#freestays_search').val();
        if (!cityId && !searchQ) {
            alert('Kies een stad of vul een zoekterm in.');
            return;
        }
        let url = `/wp-json/freestays/v1/bravo-destinations?`;
        let params = [];
        if (cityId) params.push(`destination_id=${cityId}`);
        if (searchQ) params.push(`q=${encodeURIComponent(searchQ)}`);
        url += params.join('&');
        fetch(url)
            .then(res => res.json())
            .then(data => {
                var resultsDiv = $('#results');
                resultsDiv.empty();
                if (Array.isArray(data.data) && data.data.length > 0) {
                    resultsDiv.html(data.data.map(dest => `
                        <div class="fs-hotel-card">
                            <h3>${dest.destination_name}</h3>
                            <p>${dest.country_name}</p>
                        </div>
                    `).join(''));
                } else {
                    resultsDiv.html('<p>Geen bestemmingen gevonden.</p>');
                }
            })
            .catch(() => {
                $('#results').html('<p>Fout bij het laden van bestemmingen.</p>');
            });
    });
});