<?php

namespace ServizeHub;

class VendorClient {
    private $apiKey;

    public function __construct($config) {
        if (empty($config['apiKey'])) {
            throw new \Exception("API key is required");
        }

        $this->apiKey = $config['apiKey'];
    }

    // Validate date format YYYY-MM-DD
    private function isValidDate($date) {
        $d = \DateTime::createFromFormat('Y-m-d', $date);
        return $d && $d->format('Y-m-d') === $date;
    }

    public function sendBooking($booking) {
        try {
            $date = $booking['date'] ?? null;
            $serviceType = $booking['serviceType'] ?? null;
            $status = $booking['status'] ?? null;

            // Validation
            if (!$date || !$serviceType || !$status) {
                return "All booking details are required";
            }

            if (!$this->isValidDate($date)) {
                return "Invalid date format, expected YYYY-MM-DD";
            }

            $validStatuses = ["available", "unavailable"];
            if (!in_array($status, $validStatuses)) {
                return "Invalid status value";
            }

            $url = "https://servizehubuserdev.notasco.in/external/capture-availability";

            $payload = json_encode([
                'date' => $date,
                'serviceType' => $serviceType,
                'status' => $status
            ]);

            $options = [
                "http" => [
                    "header" =>
                        "Content-Type: application/json\r\n" .
                        "servizhub-api-key: " . $this->apiKey . "\r\n",
                    "method" => "POST",
                    "content" => $payload,
                    "ignore_errors" => true
                ]
            ];

            $context = stream_context_create($options);
            $response = file_get_contents($url, false, $context);

            if ($response === false) {
                return "Network error while sending booking";
            }

            $data = json_decode($response, true);

            // Capture HTTP status code
            $httpCode = 0;

            if (isset($http_response_header[0])) {
                preg_match(
                    '#HTTP/\d+\.\d+\s+(\d+)#',
                    $http_response_header[0],
                    $matches
                );

                $httpCode = isset($matches[1]) ? (int)$matches[1] : 0;
            }

            return [
                "statusCode" => $httpCode,
                "data" => $data
            ];
        } catch (\Exception $e) {
            return $e->getMessage();
        }
    }

    public function sendMultipleBookings($bookings) {
        if (!is_array($bookings) || count($bookings) === 0) {
            return "Bookings array is required";
        }

        $failedBookings = [];
        $successfulBookings = [];

        foreach ($bookings as $booking) {
            $result = $this->sendBooking($booking);

            // Validation / network errors
            if (is_string($result)) {
                $failedBookings[] = array_merge($booking, [
                    'error' => $result
                ]);
                continue;
            }

            // API failed response
            if (
                !isset($result['statusCode']) ||
                $result['statusCode'] < 200 ||
                $result['statusCode'] >= 300
            ) {
                $failedBookings[] = array_merge($booking, [
                    'error' => $result
                ]);
            } else {
                $successfulBookings[] = $result;
            }
        }

        return [
            "message" => empty($failedBookings)
                ? "All bookings sent successfully"
                : "Some bookings failed",
            "successfulBookings" => $successfulBookings,
            "failedBookings" => $failedBookings
        ];
    }
}

?>