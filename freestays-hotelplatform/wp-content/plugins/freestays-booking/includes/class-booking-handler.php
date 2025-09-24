<?php

class Booking_Handler {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
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

        // Insert booking into the database
        $stmt = $this->pdo->prepare("INSERT INTO bookings (hotel_id, checkin_date, checkout_date, adults, children) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$data['hotel_id'], $data['checkin_date'], $data['checkout_date'], $data['adults'], $data['children'] ?? 0]);

        return ['success' => true, 'booking_id' => $this->pdo->lastInsertId()];
    }

    public function getBookingDetails($booking_id) {
        $stmt = $this->pdo->prepare("SELECT * FROM bookings WHERE id = ?");
        $stmt->execute([$booking_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}