<?php
// Booking Form Template for Freestays Hotel Booking Platform

// Check if the user is logged in
if ( ! is_user_logged_in() ) {
    echo '<p>Please log in to book a hotel.</p>';
    return;
}

// Get the current user information
$current_user = wp_get_current_user();

// Check if the booking data is set in the session
$booking_data = isset($_SESSION['booking_data']) ? $_SESSION['booking_data'] : [];

// Display the booking form
?>
<form id="booking-form" method="POST" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
    <input type="hidden" name="action" value="process_booking">
    
    <h2>Book Your Hotel</h2>
    
    <div class="form-group">
        <label for="hotel_id">Hotel ID</label>
        <input type="text" id="hotel_id" name="hotel_id" value="<?php echo esc_attr($booking_data['hotel_id'] ?? ''); ?>" required>
    </div>
    
    <div class="form-group">
        <label for="checkin_date">Check-in Date</label>
        <input type="date" id="checkin_date" name="checkin_date" value="<?php echo esc_attr($booking_data['checkin_date'] ?? ''); ?>" required>
    </div>
    
    <div class="form-group">
        <label for="checkout_date">Check-out Date</label>
        <input type="date" id="checkout_date" name="checkout_date" value="<?php echo esc_attr($booking_data['checkout_date'] ?? ''); ?>" required>
    </div>
    
    <div class="form-group">
        <label for="adults">Number of Adults</label>
        <input type="number" id="adults" name="adults" value="<?php echo esc_attr($booking_data['adults'] ?? 1); ?>" min="1" required>
    </div>
    
    <div class="form-group">
        <label for="children">Number of Children</label>
        <input type="number" id="children" name="children" value="<?php echo esc_attr($booking_data['children'] ?? 0); ?>" min="0">
    </div>
    
    <div class="form-group">
        <label for="special_requests">Special Requests</label>
        <textarea id="special_requests" name="special_requests"><?php echo esc_textarea($booking_data['special_requests'] ?? ''); ?></textarea>
    </div>
    
    <button type="submit">Book Now</button>
</form>

<?php
// Include necessary scripts for form validation and AJAX handling
wp_enqueue_script('freestays-booking-js', plugins_url('/assets/js/freestays.js', __FILE__), array('jquery'), null, true);
?>