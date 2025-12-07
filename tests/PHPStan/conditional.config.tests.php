<?php
declare(strict_types = 1);

use Composer\InstalledVersions;
use Composer\Semver\VersionParser;

$config = ['parameters' => ['ignoreErrors' => []]];

if (! InstalledVersions::satisfies(new VersionParser(), 'doctrine/orm', '<3.4')) {
    $config['parameters']['ignoreErrors'][] = [
        'message' => '#^Call to function method_exists\\(\\) with Doctrine\\\\ORM\\\\Configuration and \'enableNativeLazy.*\' will always evaluate to true\\.$#',
        'path' => __DIR__ . '/../../tests/MessageBusDoctrine/Middleware/ClearEntityManagerMiddlewareTest.phpt',
        'count' => 1,
    ];
    $config['parameters']['ignoreErrors'][] = [
        'message' => '#^Call to function method_exists\\(\\) with Doctrine\\\\ORM\\\\Configuration and \'enableNativeLazy.*\' will always evaluate to true\\.$#',
        'path' => __DIR__ . '/../../tests/MessageBusDoctrine/Middleware/PreventOuterTransactionMiddlewareTest.phpt',
        'count' => 1,
    ];
    $config['parameters']['ignoreErrors'][] = [
        'message' => '#^Call to function method_exists\\(\\) with Doctrine\\\\ORM\\\\Configuration and \'enableNativeLazy.*\' will always evaluate to true\\.$#',
        'path' => __DIR__ . '/../../tests/MessageBusDoctrine/Middleware/TransactionMiddlewareTest.phpt',
        'count' => 1,
    ];
}

return $config;
