<?php

/**
 * Example 3: Database Authentication with PDO
 *
 * This example demonstrates how to implement database authentication
 * by creating a custom connector that extends AbstractConnector.
 */

require_once __DIR__ . '/../vendor/autoload.php';

use JDZ\Authentication\Authentication;
use JDZ\Authentication\AuthenticationResult;
use JDZ\Authentication\AuthStatusEnum;
use JDZ\Authentication\Connector\AbstractConnector;

/**
 * Custom PDO Authenticator extending AbstractConnector
 */
class PdoConnector extends AbstractConnector
{
    protected string $name = 'pdo';
    protected \PDO $pdo;
    protected string $table;
    protected string $identifierColumn;
    protected string $passwordColumn;

    public function __construct(\PDO $pdo, string $table, string $identifierColumn, string $passwordColumn)
    {
        $this->pdo = $pdo;
        $this->table = $table;
        $this->identifierColumn = $identifierColumn;
        $this->passwordColumn = $passwordColumn;
    }

    public function authenticate(array $credentials): AuthenticationResult
    {
        $identifier = $credentials['identifier'] ?? '';
        $password = $credentials['password'] ?? '';

        $user = $this->findUser($identifier);

        if ($user === null) {
            return $this->createFailureResult(AuthStatusEnum::USER_NOT_FOUND);
        }

        $hashedPassword = $user[$this->passwordColumn] ?? '';

        if (!$this->verifyPassword($password, $hashedPassword)) {
            return $this->createFailureResult(AuthStatusEnum::INVALID_PASSWORD);
        }

        return $this->createSuccessResult(
            isset($user['id']) ? (int) $user['id'] : null,
            $user
        );
    }

    protected function findUser(string $identifier): ?array
    {
        $sql = "SELECT id, {$this->identifierColumn}, {$this->passwordColumn}, email, username, firstname, lastname
                FROM {$this->table}
                WHERE {$this->identifierColumn} = :identifier";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['identifier' => $identifier]);
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);

        return $result ?: null;
    }
}

echo "=== Database Authentication Example ===\n\n";

// Check if SQLite PDO driver is available
if (!in_array('sqlite', \PDO::getAvailableDrivers())) {
    echo "SQLite PDO driver is not available.\n";
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
            username VARCHAR(255),
            password VARCHAR(255) NOT NULL,
            firstname VARCHAR(255),
            lastname VARCHAR(255)
        )
    ");

    // Insert test users with hashed passwords
    echo "2. Inserting test users...\n";
    $stmt = $pdo->prepare("INSERT INTO users (email, username, password, firstname, lastname) VALUES (?, ?, ?, ?, ?)");

    $users = [
        ['admin@example.com', 'admin', password_hash('admin123', PASSWORD_DEFAULT), 'Admin', 'User'],
        ['john@example.com', 'john', password_hash('john123', PASSWORD_DEFAULT), 'John', 'Doe'],
        ['jane@example.com', 'jane', password_hash('jane123', PASSWORD_DEFAULT), 'Jane', 'Smith'],
    ];

    foreach ($users as $user) {
        $stmt->execute($user);
        echo "   - Created user: {$user[0]}\n";
    }

    // Create authentication with database connector
    echo "\n3. Setting up authentication...\n";
    $auth = new Authentication();

    $pdoConnector = new PdoConnector(
        $pdo,
        'users',     // table name
        'email',     // identifier column
        'password'   // password column
    );

    $auth->addConnector($pdoConnector);

    // Test authentication
    echo "\n4. Testing authentication...\n\n";

    $testCases = [
        ['identifier' => 'admin@example.com', 'password' => 'admin123', 'expected' => true],
        ['identifier' => 'john@example.com', 'password' => 'john123', 'expected' => true],
        ['identifier' => 'jane@example.com', 'password' => 'wrongpass', 'expected' => false],
        ['identifier' => 'unknown@example.com', 'password' => 'test123', 'expected' => false],
    ];

    foreach ($testCases as $test) {
        $credentials = [
            'identifier' => $test['identifier'],
            'password' => $test['password'],
        ];

        $result = $auth->authenticate($credentials);

        echo "Testing: {$test['identifier']}\n";

        if ($result->isSuccess()) {
            echo "  ✓ Authentication successful!\n";
            echo "  Type: {$result->getType()}\n";
        } else {
            echo "  ✗ Authentication failed\n";
            echo "  Status: {$result->getStatus()->message()}\n";
        }

        $expected = $test['expected'] ? 'SUCCESS' : 'FAILURE';
        $actual = $result->isSuccess() ? 'SUCCESS' : 'FAILURE';
        $match = $expected === $actual ? '✓' : '✗';
        echo "  {$match} Expected: {$expected}, Got: {$actual}\n\n";
    }

    // Show response format
    echo "=== Response Array Format ===\n";
    $credentials = ['identifier' => 'admin@example.com', 'password' => 'admin123'];
    $result = $auth->authenticate($credentials);
    print_r($result->toArray());
} catch (\PDOException $e) {
    echo "Database Error: " . $e->getMessage() . "\n";
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
