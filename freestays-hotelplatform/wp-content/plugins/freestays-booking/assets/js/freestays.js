// JavaScript functionality for the Freestays hotel booking platform

document.addEventListener('DOMContentLoaded', function() {
    // Initialize the booking form
    const bookingForm = document.getElementById('booking-form');
    if (bookingForm) {
        bookingForm.addEventListener('submit', function(event) {
            event.preventDefault();
            handleBookingSubmit();
        });
    }

    // Function to handle booking form submission
    function handleBookingSubmit() {
        const formData = new FormData(bookingForm);
        const requestData = {};
        formData.forEach((value, key) => {
            requestData[key] = value;
        });

        fetch(bookingForm.action, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(requestData),
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Booking successful!');
                // Optionally redirect or update the UI
            } else {
                alert('Booking failed: ' + data.error);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while processing your booking.');
        });
    }

    // Function to handle hotel search
    const searchForm = document.getElementById('search-form');
    if (searchForm) {
        searchForm.addEventListener('submit', function(event) {
            event.preventDefault();
            handleSearch();
        });
    }

    function handleSearch() {
        const searchData = new FormData(searchForm);
        const requestData = {};
        searchData.forEach((value, key) => {
            requestData[key] = value;
        });

        fetch(searchForm.action, {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(requestData),
        })
        .then(response => response.json())
        .then(data => {
            if (data.results) {
                displayHotels(data.results);
            } else {
                alert('No hotels found.');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while searching for hotels.');
        });
    }

    function displayHotels(hotels) {
        const hotelList = document.getElementById('hotel-list');
        if (!hotelList) return;
        hotelList.innerHTML = ''; // Clear previous results

        hotels.forEach(hotel => {
            const hotelItem = document.createElement('div');
            hotelItem.className = 'hotel-item';
            hotelItem.innerHTML = `
                <h3>${hotel.name}</h3>
                <p>${hotel.city}, ${hotel.country}</p>
                <p>Price: ${hotel.price_total} ${hotel.currency}</p>
                <a href="/hotel/${hotel.hotel_id}" class="btn">View Details</a>
            `;
            hotelList.appendChild(hotelItem);
        });
    }

    // Dynamisch laden van steden en resorts op basis van land en stad
    if (typeof jQuery !== 'undefined') {
        jQuery(function($) {
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
    }
});