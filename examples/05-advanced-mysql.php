<?php

/**
 * Example 5: Custom Database Connector with MySQL
 *
 * This example shows how to create a more advanced database connector
 * with MySQL/MariaDB including additional user data retrieval and
 * account status checking.
 */

require_once __DIR__ . '/../vendor/autoload.php';

use JDZ\Authentication\Authentication;
use JDZ\Authentication\AuthenticationResult;
use JDZ\Authentication\AuthStatusEnum;
use JDZ\Authentication\Connector\AbstractConnector;

/**
 * Advanced MySQL Authenticator with user data loading and account status
 */
class MySQLConnector extends AbstractConnector
{
    protected string $name = 'mysql';
    protected \PDO $pdo;
    protected string $table;
    protected string $identifierColumn;
    protected string $passwordColumn;

    public function __construct(\PDO $pdo, array $config = [])
    {
        $this->pdo = $pdo;
        $this->table = $config['table'] ?? 'users';
        $this->identifierColumn = $config['identifier_column'] ?? 'email';
        $this->passwordColumn = $config['password_column'] ?? 'password';
    }

    public function authenticate(array $credentials): AuthenticationResult
    {
        $identifier = $credentials['identifier'] ?? '';
        $password = $credentials['password'] ?? '';

        $user = $this->findUser($identifier);

        if ($user === null) {
            return $this->createFailureResult(AuthStatusEnum::USER_NOT_FOUND);
        }

        // Check if user is active
        if (isset($user['active']) && !$user['active']) {
            return $this->createFailureResult(AuthStatusEnum::USER_BANNED);
        }

        $hashedPassword = $user[$this->passwordColumn] ?? '';

        if (!$this->verifyPassword($password, $hashedPassword)) {
            return $this->createFailureResult(AuthStatusEnum::INVALID_PASSWORD);
        }

        $result = $this->createSuccessResult(
            isset($user['id']) ? (int) $user['id'] : null,
            $user
        );

        // Add language preference to custom data
        if (isset($user['language'])) {
            $result->set('language', $user['language']);
        }

        return $result;
    }

    protected function findUser(string $identifier): ?array
    {
        $columns = array_unique([
            'id',
            $this->identifierColumn,
            $this->passwordColumn,
            'email',
            'username',
            'firstname',
            'lastname',
            'language',
            'active',
        ]);

        $columnList = implode(', ', $columns);

        $sql = "SELECT {$columnList}
                FROM {$this->table}
                WHERE {$this->identifierColumn} = :identifier";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['identifier' => $identifier]);
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);

        return $result ?: null;
    }
}

echo "=== Advanced MySQL Authentication Example ===\n\n";

// Check if SQLite PDO driver is available
if (!in_array('sqlite', \PDO::getAvailableDrivers())) {
    echo "SQLite PDO driver is not available.\n";
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
            username VARCHAR(255),
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
        INSERT INTO users (email, username, password, firstname, lastname, language, active)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");

    $users = [
        ['admin@example.com', 'admin', password_hash('admin123', PASSWORD_DEFAULT), 'Admin', 'User', 'en-US', 1],
        ['john@example.com', 'john', password_hash('john123', PASSWORD_DEFAULT), 'John', 'Doe', 'en-US', 1],
        ['marie@example.com', 'marie', password_hash('marie123', PASSWORD_DEFAULT), 'Marie', 'Dupont', 'fr-FR', 1],
        ['inactive@example.com', 'inactive', password_hash('test123', PASSWORD_DEFAULT), 'Inactive', 'User', 'en-US', 0],
    ];

    foreach ($users as $user) {
        $stmt->execute($user);
        echo "   - Created: {$user[3]} {$user[4]} ({$user[0]})\n";
    }

    // Setup authentication
    echo "\n3. Configuring authentication...\n";
    $auth = new Authentication();

    $dbConnector = new MySQLConnector($pdo, [
        'table' => 'users',
        'identifier_column' => 'email',
        'password_column' => 'password',
    ]);

    $auth->addConnector($dbConnector);

    // Test authentication with user data
    echo "\n4. Testing authentication...\n\n";

    $credentials = [
        'identifier' => 'marie@example.com',
        'password' => 'marie123',
    ];

    echo "Authenticating: {$credentials['identifier']}\n";
    $result = $auth->authenticate($credentials);

    if ($result->isSuccess()) {
        echo "✓ Authentication successful!\n\n";
        echo "User Details:\n";
        echo "  - User ID: {$result->getUserId()}\n";
        echo "  - Identifier: {$result->getIdentifier()}\n";
        echo "  - Email: {$result->getEmail()}\n";
        echo "  - Full Name: {$result->getFullname()}\n";
        echo "  - First Name: {$result->getFirstname()}\n";
        echo "  - Last Name: {$result->getLastname()}\n";
        echo "  - Language: {$result->get('language')}\n";
        echo "  - Type: {$result->getType()}\n";
    } else {
        echo "✗ Authentication failed\n";
        echo "  Status: {$result->getStatus()->message()}\n";
    }

    // Test inactive user
    echo "\n\n5. Testing inactive user...\n";
    $credentials = [
        'identifier' => 'inactive@example.com',
        'password' => 'test123',
    ];

    echo "Authenticating: {$credentials['identifier']}\n";
    $result = $auth->authenticate($credentials);

    if ($result->isSuccess()) {
        echo "✓ Authentication successful!\n";
    } else {
        echo "✗ Authentication failed (inactive users cannot login)\n";
        echo "  Status: {$result->getStatus()->message()}\n";
    }

    // Show full response array
    echo "\n\n6. Complete Response Array:\n";
    $credentials = ['identifier' => 'john@example.com', 'password' => 'john123'];
    $result = $auth->authenticate($credentials);
    print_r($result->toArray());
} catch (\PDOException $e) {
    echo "Database Error: " . $e->getMessage() . "\n";
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
