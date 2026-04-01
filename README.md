# ServizeHub SDK

## Install
Install using Composer:
composer require servizehub/servizehub-sdk

## Usage

<?php

require 'vendor/autoload.php';

use ServizeHub\VendorClient;

$client = new VendorClient("encryptedkey");

$result = $client->sendBooking(
    "2026-04-01",
    "Haircut",
    "available"
);

print_r($result);


## 📩 License

Created by Notasco Dev Team

Happy Coding! 🎯