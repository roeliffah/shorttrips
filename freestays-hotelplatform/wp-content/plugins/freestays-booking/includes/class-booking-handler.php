<?php

class Booking_Handler {
    
    public function __construct() {
        // WordPress uses wpdb, not PDO
        // Class is ready to use WordPress database functions
        add_action('wp_ajax_process_booking', array($this, 'processBookingAjax'));
        add_action('wp_ajax_nopriv_process_booking', array($this, 'processBookingAjax'));
        add_action('admin_post_process_booking', array($this, 'processBookingPost'));
        add_action('admin_post_nopriv_process_booking', array($this, 'processBookingPost'));
    }

    public function validateBookingData($data) {
        $errors = [];

        if (empty($data['hotel_id'])) {
            $errors[] = 'Hotel ID is required.';
        }
        if (empty($data['checkin_date'])) {
            $errors[] = 'Check-in date is required.';
        }
        if (empty($data['checkout_date'])) {
            $errors[] = 'Check-out date is required.';
        }
        if (empty($data['adults']) || $data['adults'] <= 0) {
            $errors[] = 'At least one adult is required.';
        }

        return $errors;
    }

    public function processBooking($data) {
        global $wpdb;
        
        $errors = $this->validateBookingData($data);
        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }

        // Insert booking into WordPress database
        $table_name = $wpdb->prefix . 'freestays_bookings';
        
        $result = $wpdb->insert(
            $table_name,
            array(
                'hotel_id' => sanitize_text_field($data['hotel_id']),
                'checkin_date' => sanitize_text_field($data['checkin_date']),
                'checkout_date' => sanitize_text_field($data['checkout_date']),
                'adults' => intval($data['adults']),
                'children' => intval($data['children'] ?? 0),
                'booking_date' => current_time('mysql'),
                'status' => 'pending'
            ),
            array('%s', '%s', '%s', '%d', '%d', '%s', '%s')
        );

        if ($result !== false) {
            return ['success' => true, 'booking_id' => $wpdb->insert_id];
        } else {
            return ['success' => false, 'errors' => ['Database error occurred.']];
        }
    }

    public function getBookingDetails($booking_id) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'freestays_bookings';
        
        $booking = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $booking_id),
            ARRAY_A
        );
        
        return $booking;
    }
    
    public function processBookingAjax() {
        // Handle AJAX booking processing
        if (!wp_verify_nonce($_POST['nonce'], 'freestays_booking_nonce')) {
            wp_die('Security check failed');
        }
        
        $result = $this->processBooking($_POST);
        wp_send_json($result);
    }
    
    public function processBookingPost() {
        // Handle form post booking processing
        $result = $this->processBooking($_POST);
        
        if ($result['success']) {
            wp_redirect(add_query_arg('booking_success', '1', wp_get_referer()));
        } else {
            wp_redirect(add_query_arg('booking_error', '1', wp_get_referer()));
        }
        exit;
    }
    
    /**
     * Create the bookings table when plugin is activated
     */
    public static function createBookingsTable() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'freestays_bookings';
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            hotel_id varchar(255) NOT NULL,
            checkin_date date NOT NULL,
            checkout_date date NOT NULL,
            adults int(11) NOT NULL,
            children int(11) DEFAULT 0,
            booking_date datetime DEFAULT CURRENT_TIMESTAMP,
            status varchar(50) DEFAULT 'pending',
            user_id bigint(20) DEFAULT NULL,
            PRIMARY KEY (id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
}