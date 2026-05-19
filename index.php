<?php

declare(strict_types=1);

if (PHP_VERSION_ID < 80500) {
    http_response_code(503);
    echo 'The Laravel modernization runtime requires PHP 8.5 or newer.';
    exit;
}

require __DIR__.'/laravel/public/index.php';
