<?php

/**
 * Example 4: Error Handling with Exceptions
 *
 * This example shows how to properly handle authentication errors
 * using exceptions and the AuthStatusEnum.
 */

require_once __DIR__ . '/../vendor/autoload.php';

use JDZ\Authentication\Authentication;
use JDZ\Authentication\AuthenticationException;
use JDZ\Authentication\AuthStatusEnum;
use JDZ\Authentication\Connector\BasicConnector;

/**
 * Authentication helper function with exception handling
 */
function authenticateUser(array $credentials, Authentication $auth, bool $silent = false): bool
{
    $result = $auth->authenticate($credentials);

    if (!$result->isSuccess()) {
        if ($silent) {
            return false;
        }

        throw new AuthenticationException(
            $result->getStatus(),
            $result->getMessage()
        );
    }

    return true;
}

echo "=== Error Handling Example ===\n\n";

// Setup authentication
$auth = new Authentication();
$connector = new BasicConnector('admin', 'secret123');
$auth->addConnector($connector);

// Test cases
$testCases = [
    [
        'name' => 'Valid credentials',
        'credentials' => ['identifier' => 'admin', 'password' => 'secret123'],
    ],
    [
        'name' => 'Invalid password',
        'credentials' => ['identifier' => 'admin', 'password' => 'wrongpass'],
    ],
    [
        'name' => 'Invalid identifier',
        'credentials' => ['identifier' => 'hacker', 'password' => 'secret123'],
    ],
    [
        'name' => 'Empty identifier',
        'credentials' => ['identifier' => '', 'password' => 'secret123'],
    ],
    [
        'name' => 'Empty password',
        'credentials' => ['identifier' => 'admin', 'password' => ''],
    ],
];

foreach ($testCases as $test) {
    echo "Test: {$test['name']}\n";

    try {
        $result = authenticateUser($test['credentials'], $auth);
        echo "  ✓ Authentication successful!\n";
    } catch (AuthenticationException $e) {
        echo "  ✗ Authentication failed!\n";
        echo "  Error Code: {$e->getCode()}\n";
        echo "  Error Message: {$e->getMessage()}\n";
        echo "  Auth Status: {$e->getStatus()->name}\n";
    }
    echo "\n";
}

// Example with silent mode
echo "=== Silent Mode (No Exceptions) ===\n";
$credentials = ['identifier' => 'admin', 'password' => 'wrongpass'];

try {
    $result = authenticateUser($credentials, $auth, silent: true);
    if ($result) {
        echo "✓ Login successful\n";
    } else {
        echo "✗ Login failed (silent mode, no exception thrown)\n";
    }
} catch (AuthenticationException $e) {
    echo "This should not be reached in silent mode\n";
}

echo "\n=== Custom Error Handler ===\n";

function handleAuthenticationError(AuthStatusEnum $status): void
{
    $errorMessages = [
        AuthStatusEnum::EMPTY_IDENTIFIER->name => "Please enter your email or username.",
        AuthStatusEnum::EMPTY_PASSWORD->name => "Please enter your password.",
        AuthStatusEnum::USER_NOT_FOUND->name => "User not found.",
        AuthStatusEnum::INVALID_PASSWORD->name => "Incorrect password. Please try again.",
        AuthStatusEnum::FAILURE->name => "Authentication failed. Please try again.",
    ];

    echo "Custom Error: " . ($errorMessages[$status->name] ?? "Unknown error") . "\n";
}

$result = $auth->authenticate(['identifier' => '', 'password' => 'test']);
if (!$result->isSuccess()) {
    handleAuthenticationError($result->getStatus());
}

$result = $auth->authenticate(['identifier' => 'admin', 'password' => 'wrong']);
if (!$result->isSuccess()) {
    handleAuthenticationError($result->getStatus());
}
