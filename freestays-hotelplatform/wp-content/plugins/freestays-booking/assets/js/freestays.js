// JavaScript functionality for the Freestays hotel booking platform

console.log('freestays.js geladen');
console.log('freestays-ajax.js geladen');

document.addEventListener('DOMContentLoaded', function() {
    // Booking form submit
    const bookingForm = document.getElementById('booking-form');
    if (bookingForm) {
        bookingForm.addEventListener('submit', function(event) {
            event.preventDefault();
            handleBookingSubmit();
        });
    }

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
            } else {
                alert('Booking failed: ' + data.error);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while processing your booking.');
        });
    }

    // Hotel search form submit
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
        hotelList.innerHTML = '';

        hotelList.innerHTML = hotels.map(hotel => `
            <div class="fs-hotel-card">
                <h3>${hotel.name}</h3>
                <p>${hotel.address}, ${hotel.city}</p>
                <p>Sterren: ${hotel.classification}</p>
                <p>Thema's: ${hotel.themes}</p>
                <p>Prijs: ${hotel.price}</p>
            </div>
        `).join('');
    }
});