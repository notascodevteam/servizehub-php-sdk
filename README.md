# ServizeHub SDK

## Install
Install using Composer:

```bash
composer require servizehub/servizehub-sdk
```

## Usage

```php
<?php
require __DIR__ . '/vendor/autoload.php';

use ServizeHub\VendorClient;

$config = ['apiKey' => 'encryptedkey'];
$client = new VendorClient($config);

// Single booking
$booking = [
    'date' => '2026-04-05',
    'serviceType' => 'cleaning',
    'status' => 'available'
];

// Send the booking
$response = $client->sendBooking($booking);
print_r($response);

```

## 📩 License

Created by Notasco Dev Team

Happy Coding! 🎯
