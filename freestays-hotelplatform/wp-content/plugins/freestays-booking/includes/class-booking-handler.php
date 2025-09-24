<?php

class Booking_Handler {

    public function __construct() {
        // Geen PDO nodig voor basisfunctionaliteit, kan later uitgebreid worden
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
        $errors = $this->validateBookingData($data);
        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }

        // Hier zou je de boeking verwerken, bijvoorbeeld opslaan in de database of doorsturen naar Sunhotels
        // Voor nu alleen een dummy response
        return [
            'success' => true,
            'message' => 'Booking processed (dummy handler).',
            'data'    => $data
        ];
    }

    public function getBookingDetails($booking_id) {
        // Dummy data, in productie haal je dit uit de database
        return [
            'id' => $booking_id,
            'hotel_id' => 123,
            'checkin_date' => '2025-10-01',
            'checkout_date' => '2025-10-07',
            'adults' => 2,
            'children' => 0
        ];
    }
}