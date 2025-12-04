<?php

/**
 * Example 1: Basic Authentication
 * 
 * This example demonstrates how to use the BasicConnector
 * for simple username/password authentication.
 */

require_once __DIR__ . '/../vendor/autoload.php';

use JDZ\Authentication\Authentication;
use JDZ\Authentication\AuthStatusEnum;
use JDZ\Authentication\Connector\BasicConnector;

// Create authentication instance
$auth = new Authentication();

// Add a basic connector with credentials
// In production, these would typically come from configuration
$connector = new BasicConnector('admin', 'secret123');

$auth->addConnector($connector);

// Test valid credentials
echo "=== Testing Valid Credentials ===\n";
$credentials = [
    'username' => 'admin',
    'password' => 'secret123'
];

$response = $auth->authenticate($credentials);

if ($response->status === AuthStatusEnum::SUCCESS) {
    echo "✓ Authentication successful!\n";
    echo "  Type: {$response->type}\n";
} else {
    echo "✗ Authentication failed\n";
    echo "  Status Code: {$response->status->code()}\n";
    echo "  Message: {$response->status->message()}\n";
}

// Test invalid credentials
echo "\n=== Testing Invalid Credentials ===\n";
$credentials = [
    'username' => 'admin',
    'password' => 'wrongpassword'
];

$response = $auth->authenticate($credentials);

if ($response->status === AuthStatusEnum::SUCCESS) {
    echo "✓ Authentication successful!\n";
} else {
    echo "✗ Authentication failed\n";
    echo "  Status Code: {$response->status->code()}\n";
    echo "  Message: {$response->status->message()}\n";
}

// Test missing username
echo "\n=== Testing Missing Username ===\n";
$credentials = [
    'username' => '',
    'password' => 'secret123'
];

$response = $auth->authenticate($credentials);
echo "✗ Authentication failed\n";
echo "  Status Code: {$response->status->code()}\n";
echo "  Message: {$response->status->message()}\n";

// Test missing password
echo "\n=== Testing Missing Password ===\n";
$credentials = [
    'username' => 'admin',
    'password' => ''
];

$response = $auth->authenticate($credentials);
echo "✗ Authentication failed\n";
echo "  Status Code: {$response->status->code()}\n";
echo "  Message: {$response->status->message()}\n";
