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

    public function sendBooking(
        string $date,
        string $serviceType,
        string $status
    ): array {
        return [
            "message" => "Booking availability sent successfully"
        ];
    }
}
