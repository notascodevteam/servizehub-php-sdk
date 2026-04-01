<?php

namespace ServizeHub;

class VendorClient
{
    private string $apiKey;
    private string $baseUrl = "https://servizehubuserdev.notasco.in";

    public function __construct(string $apiKey)
    {
        if (empty($apiKey)) {
            throw new \InvalidArgumentException("API key is required");
        }

        $this->apiKey = $apiKey;
    }

    // Helper function to validate YYYY-MM-DD date
    private function isValidDate(string $date): bool
    {
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            return false;
        }

        $parts = explode('-', $date);
        return checkdate((int)$parts[1], (int)$parts[2], (int)$parts[0]);
    }

    // Send a single booking
    public function sendBooking(string $date, string $serviceType, string $status): array
    {
        if (empty($date) || empty($serviceType) || empty($status)) {
            throw new \InvalidArgumentException("All booking details are required");
        }

        if (!$this->isValidDate($date)) {
            throw new \InvalidArgumentException("Invalid date format, expected YYYY-MM-DD");
        }

        $validStatuses = ["available", "unavailable"];
        if (!in_array($status, $validStatuses)) {
            throw new \InvalidArgumentException("Invalid status value");
        }

        $payload = [
            "date" => $date,
            "serviceType" => $serviceType,
            "status" => $status
        ];

        $ch = curl_init("{$this->baseUrl}/external/capture-availability");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Content-Type: application/json",
            "servizhub-api-key: {$this->apiKey}"
        ]);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if (curl_errno($ch)) {
            $error = curl_error($ch);
            curl_close($ch);
            throw new \RuntimeException("Network error: $error");
        }

        curl_close($ch);
        $data = json_decode($response, true);

        if ($httpCode < 200 || $httpCode >= 300) {
            $message = $data['message'] ?? "Booking failed";
            throw new \RuntimeException($message);
        }

        return ["message" => "Booking availability sent successfully"];
    }

    // Send multiple bookings
    public function sendMultipleBookings(array $bookings): array
    {
        if (empty($bookings)) {
            throw new \InvalidArgumentException("Bookings array is required");
        }

        $failedBookings = [];

        foreach ($bookings as $booking) {
            $date = $booking['date'] ?? null;
            $serviceType = $booking['serviceType'] ?? null;
            $status = $booking['status'] ?? null;

            try {
                $this->sendBooking($date, $serviceType, $status);
            } catch (\Exception $e) {
                $failedBookings[] = array_merge($booking, ["error" => $e->getMessage()]);
            }
        }

        if (empty($failedBookings)) {
            return ["message" => "Booking availability sent successfully"];
        } else {
            return [
                "message" => "Some bookings failed",
                "failedBookings" => $failedBookings
            ];
        }
    }
}