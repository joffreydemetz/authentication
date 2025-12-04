<?php

/**
 * Example 2: Multiple Connectors
 * 
 * This example shows how to use multiple authentication connectors.
 * The authentication tries each connector in reverse order until one succeeds.
 */

require_once __DIR__ . '/../vendor/autoload.php';

use JDZ\Authentication\Authentication;
use JDZ\Authentication\AuthStatusEnum;
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
    ['username' => 'admin', 'password' => 'admin123'],
    ['username' => 'user', 'password' => 'user123'],
    ['username' => 'guest', 'password' => 'guest123'],
    ['username' => 'unknown', 'password' => 'test123'],
];

foreach ($testUsers as $credentials) {
    echo "Testing user: {$credentials['username']}\n";

    $response = $auth->authenticate($credentials);

    if ($response->status === AuthStatusEnum::SUCCESS) {
        echo "  ✓ Success! (Type: {$response->type})\n";
    } else {
        echo "  ✗ Failed: {$response->status->message()}\n";
    }
    echo "\n";
}

echo "=== Response Array Format ===\n";
$credentials = ['username' => 'admin', 'password' => 'admin123'];
$response = $auth->authenticate($credentials);
print_r($response->toArray());
