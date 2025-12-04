<?php

/**
 * Example 4: Error Handling with Exceptions
 * 
 * This example shows how to properly handle authentication errors
 * using exceptions and the AuthStatusEnum.
 */

require_once __DIR__ . '/../vendor/autoload.php';

use JDZ\Authentication\Authentication;
use JDZ\Authentication\AuthStatusEnum;
use JDZ\Authentication\Connector\BasicConnector;

/**
 * Custom Authentication Exception
 */
class AuthenticationException extends \Exception
{
    private AuthStatusEnum $authStatus;

    public function __construct(string $message, AuthStatusEnum $status)
    {
        parent::__construct($message, $status->code());
        $this->authStatus = $status;
    }

    public function getAuthStatus(): AuthStatusEnum
    {
        return $this->authStatus;
    }
}

/**
 * Authentication helper function with exception handling
 */
function authenticateUser(array $credentials, Authentication $auth, bool $silent = false): bool
{
    $response = $auth->authenticate($credentials);

    if ($response->status !== AuthStatusEnum::SUCCESS) {
        if ($silent) {
            return false;
        }

        throw new AuthenticationException(
            $response->status->message(),
            $response->status
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
        'credentials' => ['username' => 'admin', 'password' => 'secret123'],
    ],
    [
        'name' => 'Invalid password',
        'credentials' => ['username' => 'admin', 'password' => 'wrongpass'],
    ],
    [
        'name' => 'Invalid username',
        'credentials' => ['username' => 'hacker', 'password' => 'secret123'],
    ],
    [
        'name' => 'Empty username',
        'credentials' => ['username' => '', 'password' => 'secret123'],
    ],
    [
        'name' => 'Empty password',
        'credentials' => ['username' => 'admin', 'password' => ''],
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
        echo "  Auth Status: {$e->getAuthStatus()->name}\n";
    }
    echo "\n";
}

// Example with silent mode
echo "=== Silent Mode (No Exceptions) ===\n";
$credentials = ['username' => 'admin', 'password' => 'wrongpass'];

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
        AuthStatusEnum::EMPTY_USER->name => "Please enter your username.",
        AuthStatusEnum::EMPTY_PASS->name => "Please enter your password.",
        AuthStatusEnum::BAD_CREDENTIALS->name => "User not found.",
        AuthStatusEnum::BAD_PASS->name => "Incorrect password. Please try again.",
        AuthStatusEnum::FAILURE->name => "Authentication failed. Please try again.",
    ];

    echo "Custom Error: " . ($errorMessages[$status->name] ?? "Unknown error") . "\n";
}

$response = $auth->authenticate(['username' => '', 'password' => 'test']);
if ($response->status !== AuthStatusEnum::SUCCESS) {
    handleAuthenticationError($response->status);
}

$response = $auth->authenticate(['username' => 'admin', 'password' => 'wrong']);
if ($response->status !== AuthStatusEnum::SUCCESS) {
    handleAuthenticationError($response->status);
}
