<?php

/**
 * Example 5: Custom Database Connector with MySQL
 * 
 * This example shows how to create a more advanced database connector
 * with MySQL/MariaDB including additional user data retrieval.
 */

require_once __DIR__ . '/../vendor/autoload.php';

use JDZ\Authentication\Authentication;
use JDZ\Authentication\AuthenticationResponse;
use JDZ\Authentication\AuthStatusEnum;
use JDZ\Authentication\Connector\DatabaseConnector;

/**
 * Advanced MySQL Authenticator with user data loading
 */
class MySQLAuthenticator extends DatabaseConnector
{
    protected \PDO $pdo;

    public function __construct(array $config, \PDO $pdo)
    {
        // Config should contain: tbl_name, tbl_username_column, tbl_pass_column
        parent::__construct(
            $config['tbl_name'],
            $config['tbl_username_column'],
            $config['tbl_pass_column']
        );

        $this->pdo = $pdo;
    }

    protected function getHashedPassword(array $credentials): string
    {
        $sql = "SELECT {$this->tbl_pass_column} 
                FROM {$this->tbl_name} 
                WHERE {$this->tbl_username_column} = :username 
                AND active = 1";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['username' => $credentials['username']]);
        $result = $stmt->fetchColumn();

        return $result ? (string)$result : '';
    }

    /**
     * Override authenticate to load additional user data
     */
    public function authenticate(array $credentials, AuthenticationResponse $response): bool
    {
        // Call parent authentication
        $authenticated = parent::authenticate($credentials, $response);

        // If successful, load user data
        if ($authenticated) {
            $this->loadUserData($credentials['username'], $response);
        }

        return $authenticated;
    }

    /**
     * Load additional user data into the response
     */
    protected function loadUserData(string $username, AuthenticationResponse $response): void
    {
        $sql = "SELECT email, firstname, lastname, language 
                FROM {$this->tbl_name} 
                WHERE {$this->tbl_username_column} = :username";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['username' => $username]);
        $user = $stmt->fetch();

        if ($user) {
            $response->username = $username;
            $response->email = $user['email'] ?? '';
            $response->firstname = $user['firstname'] ?? '';
            $response->lastname = $user['lastname'] ?? '';
            $response->fullname = trim(($user['firstname'] ?? '') . ' ' . ($user['lastname'] ?? ''));
            $response->language = $user['language'] ?? 'en-US';
        }
    }
}

echo "=== Advanced MySQL Authentication Example ===\n\n";

// Check if SQLite PDO driver is available
if (!in_array('sqlite', \PDO::getAvailableDrivers())) {
    echo "⚠ SQLite PDO driver is not available.\n";
    echo "This example requires PDO SQLite extension.\n\n";
    echo "Available PDO drivers: " . implode(', ', \PDO::getAvailableDrivers()) . "\n\n";
    echo "To run this example:\n";
    echo "1. Enable pdo_sqlite in php.ini, OR\n";
    echo "2. Modify this example to use MySQL/PostgreSQL\n\n";
    echo "See examples/README.md for MySQL setup instructions.\n";
    exit(1);
}

try {
    // Create in-memory SQLite database (simulating MySQL)
    echo "1. Setting up database...\n";
    $pdo = new \PDO('sqlite::memory:', null, null, [
        \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
        \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
    ]);

    // Create users table with additional fields
    $pdo->exec("
        CREATE TABLE users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            email VARCHAR(255) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL,
            firstname VARCHAR(100) NOT NULL,
            lastname VARCHAR(100) NOT NULL,
            language VARCHAR(10) DEFAULT 'en-US',
            active INTEGER DEFAULT 1
        )
    ");

    // Insert test users
    echo "2. Creating test users...\n";
    $stmt = $pdo->prepare("
        INSERT INTO users (email, password, firstname, lastname, language, active) 
        VALUES (?, ?, ?, ?, ?, ?)
    ");

    $users = [
        ['admin@example.com', password_hash('admin123', PASSWORD_DEFAULT), 'Admin', 'User', 'en-US', 1],
        ['john@example.com', password_hash('john123', PASSWORD_DEFAULT), 'John', 'Doe', 'en-US', 1],
        ['marie@example.com', password_hash('marie123', PASSWORD_DEFAULT), 'Marie', 'Dupont', 'fr-FR', 1],
        ['inactive@example.com', password_hash('test123', PASSWORD_DEFAULT), 'Inactive', 'User', 'en-US', 0],
    ];

    foreach ($users as $user) {
        $stmt->execute($user);
        echo "   - Created: {$user[2]} {$user[3]} ({$user[0]})\n";
    }

    // Setup authentication
    echo "\n3. Configuring authentication...\n";
    $auth = new Authentication();

    $dbConnector = new MySQLAuthenticator([
        'tbl_name' => 'users',
        'tbl_username_column' => 'email',
        'tbl_pass_column' => 'password',
    ], $pdo);

    $auth->addConnector($dbConnector);

    // Test authentication with user data
    echo "\n4. Testing authentication...\n\n";

    $credentials = [
        'username' => 'marie@example.com',
        'password' => 'marie123'
    ];

    echo "Authenticating: {$credentials['username']}\n";
    $response = $auth->authenticate($credentials);

    if ($response->status === AuthStatusEnum::SUCCESS) {
        echo "✓ Authentication successful!\n\n";
        echo "User Details:\n";
        echo "  - Username: {$response->username}\n";
        echo "  - Email: {$response->email}\n";
        echo "  - Full Name: {$response->fullname}\n";
        echo "  - First Name: {$response->firstname}\n";
        echo "  - Last Name: {$response->lastname}\n";
        echo "  - Language: {$response->language}\n";
        echo "  - Type: {$response->type}\n";
    } else {
        echo "✗ Authentication failed\n";
        echo "  Status: {$response->status->message()}\n";
    }

    // Test inactive user
    echo "\n\n5. Testing inactive user...\n";
    $credentials = [
        'username' => 'inactive@example.com',
        'password' => 'test123'
    ];

    echo "Authenticating: {$credentials['username']}\n";
    $response = $auth->authenticate($credentials);

    if ($response->status === AuthStatusEnum::SUCCESS) {
        echo "✓ Authentication successful!\n";
    } else {
        echo "✗ Authentication failed (inactive users cannot login)\n";
        echo "  Status: {$response->status->message()}\n";
    }

    // Show full response array
    echo "\n\n6. Complete Response Array:\n";
    $credentials = ['username' => 'john@example.com', 'password' => 'john123'];
    $response = $auth->authenticate($credentials);
    print_r($response->toArray());
} catch (\PDOException $e) {
    echo "Database Error: " . $e->getMessage() . "\n";
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
