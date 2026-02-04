<?php

/**
 * Example 1: Basic Authentication
 *
 * This example demonstrates how to use the BasicConnector
 * for simple identifier/password authentication.
 */

require_once __DIR__ . '/../vendor/autoload.php';

use JDZ\Authentication\Authentication;
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
    'identifier' => 'admin',
    'password' => 'secret123',
];

$result = $auth->authenticate($credentials);

if ($result->isSuccess()) {
    echo "✓ Authentication successful!\n";
    echo "  Type: {$result->getType()}\n";
} else {
    echo "✗ Authentication failed\n";
    echo "  Status Code: {$result->getStatus()->value}\n";
    echo "  Message: {$result->getStatus()->message()}\n";
}

// Test invalid credentials
echo "\n=== Testing Invalid Credentials ===\n";
$credentials = [
    'identifier' => 'admin',
    'password' => 'wrongpassword',
];

$result = $auth->authenticate($credentials);

if ($result->isSuccess()) {
    echo "✓ Authentication successful!\n";
} else {
    echo "✗ Authentication failed\n";
    echo "  Status Code: {$result->getStatus()->value}\n";
    echo "  Message: {$result->getStatus()->message()}\n";
}

// Test missing identifier
echo "\n=== Testing Missing Identifier ===\n";
$credentials = [
    'identifier' => '',
    'password' => 'secret123',
];

$result = $auth->authenticate($credentials);
echo "✗ Authentication failed\n";
echo "  Status Code: {$result->getStatus()->value}\n";
echo "  Message: {$result->getStatus()->message()}\n";

// Test missing password
echo "\n=== Testing Missing Password ===\n";
$credentials = [
    'identifier' => 'admin',
    'password' => '',
];

$result = $auth->authenticate($credentials);
echo "✗ Authentication failed\n";
echo "  Status Code: {$result->getStatus()->value}\n";
echo "  Message: {$result->getStatus()->message()}\n";
