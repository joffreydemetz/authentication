<?php

/**
 * Example 3: Database Authentication with PDO
 * 
 * This example demonstrates how to implement database authentication
 * using PDO with the DatabaseConnector.
 */

require_once __DIR__ . '/../vendor/autoload.php';

use JDZ\Authentication\Authentication;
use JDZ\Authentication\AuthStatusEnum;
use JDZ\Authentication\Connector\DatabaseConnector;

/**
 * Custom Database Authenticator using PDO
 */
class PdoAuthenticator extends DatabaseConnector
{
    protected \PDO $pdo;

    public function __construct(string $tableName, string $usernameColumn, string $passwordColumn, \PDO $pdo)
    {
        parent::__construct($tableName, $usernameColumn, $passwordColumn);
        $this->pdo = $pdo;
    }

    protected function getHashedPassword(array $credentials): string
    {
        $sql = "SELECT {$this->tbl_pass_column} 
                FROM {$this->tbl_name} 
                WHERE {$this->tbl_username_column} = :username";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['username' => $credentials['username']]);
        $result = $stmt->fetchColumn();

        return $result ? (string)$result : '';
    }
}

echo "=== Database Authentication Example ===\n\n";

// Check if SQLite PDO driver is available
if (!in_array('sqlite', \PDO::getAvailableDrivers())) {
    echo "⚠ SQLite PDO driver is not available.\n";
    echo "This example requires PDO SQLite extension.\n\n";
    echo "Available PDO drivers: " . implode(', ', \PDO::getAvailableDrivers()) . "\n\n";
    echo "To run this example:\n";
    echo "1. Enable pdo_sqlite in php.ini, OR\n";
    echo "2. Modify this example to use MySQL/PostgreSQL\n\n";
    echo "Example MySQL connection:\n";
    echo "\$pdo = new \\PDO(\n";
    echo "    'mysql:host=localhost;dbname=testdb;charset=utf8mb4',\n";
    echo "    'username',\n";
    echo "    'password'\n";
    echo ");\n";
    exit(1);
}

// For this example, we'll use SQLite in memory
try {
    // Create in-memory SQLite database
    $pdo = new \PDO('sqlite::memory:', null, null, [
        \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
        \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
    ]);

    echo "1. Creating database table...\n";
    $pdo->exec("
        CREATE TABLE users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            email VARCHAR(255) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL,
            name VARCHAR(255) NOT NULL
        )
    ");

    // Insert test users with hashed passwords
    echo "2. Inserting test users...\n";
    $stmt = $pdo->prepare("INSERT INTO users (email, password, name) VALUES (?, ?, ?)");

    $users = [
        ['admin@example.com', password_hash('admin123', PASSWORD_DEFAULT), 'Admin User'],
        ['john@example.com', password_hash('john123', PASSWORD_DEFAULT), 'John Doe'],
        ['jane@example.com', password_hash('jane123', PASSWORD_DEFAULT), 'Jane Smith'],
    ];

    foreach ($users as $user) {
        $stmt->execute($user);
        echo "   - Created user: {$user[0]}\n";
    }

    // Create authentication with database connector
    echo "\n3. Setting up authentication...\n";
    $auth = new Authentication();

    $dbConnector = new PdoAuthenticator(
        'users',     // table name
        'email',     // username column
        'password',  // password column
        $pdo
    );

    $auth->addConnector($dbConnector);

    // Test authentication
    echo "\n4. Testing authentication...\n\n";

    $testCases = [
        ['email' => 'admin@example.com', 'password' => 'admin123', 'expected' => true],
        ['email' => 'john@example.com', 'password' => 'john123', 'expected' => true],
        ['email' => 'jane@example.com', 'password' => 'wrongpass', 'expected' => false],
        ['email' => 'unknown@example.com', 'password' => 'test123', 'expected' => false],
    ];

    foreach ($testCases as $test) {
        $credentials = [
            'username' => $test['email'],
            'password' => $test['password']
        ];

        $response = $auth->authenticate($credentials);

        echo "Testing: {$test['email']}\n";

        if ($response->status === AuthStatusEnum::SUCCESS) {
            echo "  ✓ Authentication successful!\n";
            echo "  Type: {$response->type}\n";
        } else {
            echo "  ✗ Authentication failed\n";
            echo "  Status: {$response->status->message()}\n";
        }

        $expected = $test['expected'] ? 'SUCCESS' : 'FAILURE';
        $actual = $response->status === AuthStatusEnum::SUCCESS ? 'SUCCESS' : 'FAILURE';
        $match = $expected === $actual ? '✓' : '✗';
        echo "  {$match} Expected: {$expected}, Got: {$actual}\n\n";
    }

    // Show response format
    echo "=== Response Array Format ===\n";
    $credentials = ['username' => 'admin@example.com', 'password' => 'admin123'];
    $response = $auth->authenticate($credentials);
    print_r($response->toArray());
} catch (\PDOException $e) {
    echo "Database Error: " . $e->getMessage() . "\n";
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
