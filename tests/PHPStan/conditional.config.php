<?php
declare(strict_types = 1);

use Composer\InstalledVersions;
use Composer\Semver\VersionParser;

$config = [];

if (InstalledVersions::satisfies(new VersionParser(), 'symfony/messenger', '<7.1')) {
    $config['parameters']['ignoreErrors'][] = [
        'message' => '#^Dead catch - Throwable is never thrown in the try block\\.$#',
        'path' => '../../src/MessageBusDoctrine/Middleware/TransactionMiddleware.php',
        'count' => 1,
    ];
    $config['parameters']['ignoreErrors'][] = [
        'message' => '#^Dead catch - Throwable is never thrown in the try block\\.$#',
        'path' => '../../src/MessageBusDoctrine/Middleware/ClearEntityManagerMiddleware.php',
        'count' => 1,
    ];
    $config['parameters']['ignoreErrors'][] = [
        'message' => '#^Property Nepada\\\\MessageBusDoctrine\\\\Middleware\\\\ClearEntityManagerMiddleware\\:\\:\\$clearOnError is never read, only written\\.$#',
        'path' => '../../src/MessageBusDoctrine/Middleware/ClearEntityManagerMiddleware.php',
        'count' => 1,
    ];
}

return $config;
