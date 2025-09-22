/**
 * Freestays Theme JavaScript
 * 
 * Main JavaScript file for the Freestays theme functionality
 */

(function($) {
    'use strict';

    // Wait for document ready
    $(document).ready(function() {
        
        // Initialize theme functionality
        FreestaysTheme.init();
        
    });

    // Main theme object
    var FreestaysTheme = {
        
        init: function() {
            this.setupNavigation();
            this.setupSearch();
            this.setupBooking();
        },
        
        setupNavigation: function() {
            // Handle mobile menu toggle
            $('.menu-toggle').on('click', function(e) {
                e.preventDefault();
                $('.main-navigation').toggleClass('toggled');
            });
        },
        
        setupSearch: function() {
            // Handle hotel search form
            $('.hotel-search-form').on('submit', function(e) {
                // Basic validation
                var checkin = $(this).find('input[name="checkin"]').val();
                var checkout = $(this).find('input[name="checkout"]').val();
                
                if (checkin && checkout && new Date(checkin) >= new Date(checkout)) {
                    e.preventDefault();
                    alert('Check-out date must be after check-in date.');
                    return false;
                }
            });
        },
        
        setupBooking: function() {
            // Handle booking form
            $('.booking-form').on('submit', function(e) {
                // Basic validation
                var adults = parseInt($(this).find('input[name="adults"]').val()) || 0;
                
                if (adults < 1) {
                    e.preventDefault();
                    alert('At least one adult is required for booking.');
                    return false;
                }
            });
        }
        
    };

    // Make FreestaysTheme available globally
    window.FreestaysTheme = FreestaysTheme;

})(jQuery);