<?php

namespace ServizeHub;

class VendorClient {
    private $apiKey;

    public function __construct($config) {
        if (empty($config['apiKey'])) {
            throw new Exception("API key is required");
        }
        $this->apiKey = $config['apiKey'];
    }

    // Validate date in YYYY-MM-DD format
    private function isValidDate($date) {
        $d = \DateTime::createFromFormat('Y-m-d', $date);
        return $d && $d->format('Y-m-d') === $date;
    }

    public function sendBooking($booking) {
        $date = $booking['date'] ?? null;
        $serviceType = $booking['serviceType'] ?? null;
        $status = $booking['status'] ?? null;

        if (!$date || !$serviceType || !$status) {
            throw new Exception("All booking details are required");
        }

        if (!$this->isValidDate($date)) {
            throw new Exception("Invalid date format, expected YYYY-MM-DD");
        }

        $validStatuses = ["available", "unavailable"];
        if (!in_array($status, $validStatuses)) {
            throw new Exception("Invalid status value");
        }

        $url = "https://servizehubuserdev.notasco.in/external/capture-availability";
        $payload = json_encode([
            'date' => $date,
            'serviceType' => $serviceType,
            'status' => $status
        ]);

        $options = [
            "http" => [
                "header"  => "Content-Type: application/json\r\n" .
                             "servizhub-api-key: " . $this->apiKey . "\r\n",
                "method"  => "POST",
                "content" => $payload,
                "ignore_errors" => true // to capture error response body
            ]
        ];

        $context  = stream_context_create($options);
        $response = file_get_contents($url, false, $context);

        if ($response === false) {
            throw new Exception("Network error while sending booking");
        }

        $data = json_decode($response, true);
        $httpCode = 0;

        if (isset($http_response_header)) {
            // Get HTTP status code from first response header
            preg_match('#HTTP/\d+\.\d+\s+(\d+)#', $http_response_header[0], $matches);
            $httpCode = intval($matches[1]);
        }

        if ($httpCode < 200 || $httpCode >= 300) {
            $errorMsg = $data['message'] ?? "Booking failed";
            throw new Exception($errorMsg);
        }

        return ["message" => "Booking availability sent successfully"];
    }

    public function sendMultipleBookings($bookings) {
        if (!is_array($bookings) || count($bookings) === 0) {
            throw new Exception("Bookings array is required");
        }

        $failedBookings = [];

        foreach ($bookings as $booking) {
            try {
                $this->sendBooking($booking);
            } catch (Exception $e) {
                $failedBookings[] = array_merge($booking, ['error' => $e->getMessage()]);
            }
        }

        return empty($failedBookings)
            ? ["message" => "Booking availability sent successfully"]
            : ["message" => "Some bookings failed", "failedBookings" => $failedBookings];
    }
}


?>