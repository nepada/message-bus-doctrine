<?php
declare(strict_types = 1);

use Composer\InstalledVersions;
use Composer\Semver\VersionParser;

$config = ['parameters' => ['ignoreErrors' => []]];

if (InstalledVersions::satisfies(new VersionParser(), 'symfony/messenger', '<7.1')) {
    $config['parameters']['ignoreErrors'][] = [
        'message' => '#^Dead catch - Throwable is never thrown in the try block\\.$#',
        'path' => __DIR__ . '/../../src/MessageBusDoctrine/Middleware/TransactionMiddleware.php',
        'count' => 1,
    ];
    $config['parameters']['ignoreErrors'][] = [
        'message' => '#^Dead catch - Throwable is never thrown in the try block\\.$#',
        'path' => __DIR__ . '/../../src/MessageBusDoctrine/Middleware/ClearEntityManagerMiddleware.php',
        'count' => 1,
    ];
    $config['parameters']['ignoreErrors'][] = [
        'message' => '#^Property Nepada\\\\MessageBusDoctrine\\\\Middleware\\\\ClearEntityManagerMiddleware\\:\\:\\$clearOnError is never read, only written\\.$#',
        'path' => __DIR__ . '/../../src/MessageBusDoctrine/Middleware/ClearEntityManagerMiddleware.php',
        'count' => 1,
    ];
}
return $config;
