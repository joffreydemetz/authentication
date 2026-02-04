<?php

/**
 * Example 2: Multiple Connectors
 *
 * This example shows how to use multiple authentication connectors.
 * The authentication tries each connector in priority order until one succeeds.
 */

require_once __DIR__ . '/../vendor/autoload.php';

use JDZ\Authentication\Authentication;
use JDZ\Authentication\Connector\BasicConnector;

// Create authentication instance
$auth = new Authentication();

// Add multiple connectors
// Each connector represents a different user or authentication source
$connector1 = new BasicConnector('admin', 'admin123');
$auth->addConnector($connector1);

$connector2 = new BasicConnector('user', 'user123');
$auth->addConnector($connector2);

$connector3 = new BasicConnector('guest', 'guest123');
$auth->addConnector($connector3);

echo "=== Multiple Connector Authentication ===\n";
echo "Available users: admin, user, guest\n\n";

// Test different users
$testUsers = [
    ['identifier' => 'admin', 'password' => 'admin123'],
    ['identifier' => 'user', 'password' => 'user123'],
    ['identifier' => 'guest', 'password' => 'guest123'],
    ['identifier' => 'unknown', 'password' => 'test123'],
];

foreach ($testUsers as $credentials) {
    echo "Testing user: {$credentials['identifier']}\n";

    $result = $auth->authenticate($credentials);

    if ($result->isSuccess()) {
        echo "  ✓ Success! (Type: {$result->getType()})\n";
    } else {
        echo "  ✗ Failed: {$result->getStatus()->message()}\n";
    }
    echo "\n";
}

echo "=== Response Array Format ===\n";
$credentials = ['identifier' => 'admin', 'password' => 'admin123'];
$result = $auth->authenticate($credentials);
print_r($result->toArray());
