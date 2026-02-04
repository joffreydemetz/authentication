<?php

/**
 * @author    Joffrey Demetz <joffrey.demetz@gmail.com>
 * @license   MIT License; <https://opensource.org/licenses/MIT>
 */

namespace JDZ\Authentication\Tests\Connector;

use JDZ\Authentication\AuthenticationResult;
use JDZ\Authentication\AuthStatusEnum;
use JDZ\Authentication\Connector\DatabaseConnector;
use JDZ\Database\DatabaseInterface;
use PHPUnit\Framework\TestCase;

class DatabaseConnectorTest extends TestCase
{
    private function createMockDatabase(?array $userData = null): DatabaseInterface
    {
        $database = $this->createMock(DatabaseInterface::class);

        $database->method('loadAssoc')->willReturn($userData);

        return $database;
    }

    public function testGetName(): void
    {
        $database = $this->createMockDatabase();
        $connector = new DatabaseConnector($database);

        $this->assertSame('database', $connector->getName());
    }

    public function testConstructorWithDefaultOptions(): void
    {
        $database = $this->createMockDatabase();
        $connector = new DatabaseConnector($database);

        $this->assertInstanceOf(DatabaseConnector::class, $connector);
    }

    public function testConstructorWithCustomOptions(): void
    {
        $database = $this->createMockDatabase();
        $connector = new DatabaseConnector($database, [
            'table' => 'members',
            'identifierColumn' => 'username',
            'passwordColumn' => 'pass_hash',
        ]);

        $this->assertInstanceOf(DatabaseConnector::class, $connector);
    }

    public function testSupportsWithValidCredentials(): void
    {
        $database = $this->createMockDatabase();
        $connector = new DatabaseConnector($database);

        $this->assertTrue($connector->supports(['identifier' => 'test', 'password' => 'pass']));
    }

    public function testSupportsReturnsFalseWithoutIdentifier(): void
    {
        $database = $this->createMockDatabase();
        $connector = new DatabaseConnector($database);

        $this->assertFalse($connector->supports(['password' => 'pass']));
    }

    public function testAuthenticateReturnsUserNotFoundWhenUserDoesNotExist(): void
    {
        $database = $this->createMockDatabase(null);
        $connector = new DatabaseConnector($database);

        $credentials = ['identifier' => 'unknown@example.com', 'password' => 'testpass'];

        $result = $connector->authenticate($credentials);

        $this->assertInstanceOf(AuthenticationResult::class, $result);
        $this->assertFalse($result->isSuccess());
        $this->assertSame(AuthStatusEnum::USER_NOT_FOUND, $result->getStatus());
    }

    public function testAuthenticateReturnsInvalidPasswordForWrongPassword(): void
    {
        $userData = [
            'id' => 1,
            'email' => 'test@example.com',
            'password' => password_hash('correctpass', PASSWORD_DEFAULT),
            'username' => 'testuser',
            'firstname' => 'Test',
            'lastname' => 'User',
        ];

        $database = $this->createMockDatabase($userData);
        $connector = new DatabaseConnector($database);

        $credentials = ['identifier' => 'test@example.com', 'password' => 'wrongpass'];

        $result = $connector->authenticate($credentials);

        $this->assertFalse($result->isSuccess());
        $this->assertSame(AuthStatusEnum::INVALID_PASSWORD, $result->getStatus());
    }

    public function testAuthenticateSucceedsWithCorrectCredentials(): void
    {
        $userData = [
            'id' => 1,
            'email' => 'test@example.com',
            'password' => password_hash('correctpass', PASSWORD_DEFAULT),
            'username' => 'testuser',
            'firstname' => 'Test',
            'lastname' => 'User',
        ];

        $database = $this->createMockDatabase($userData);
        $connector = new DatabaseConnector($database);

        $credentials = ['identifier' => 'test@example.com', 'password' => 'correctpass'];

        $result = $connector->authenticate($credentials);

        $this->assertTrue($result->isSuccess());
        $this->assertSame(AuthStatusEnum::SUCCESS, $result->getStatus());
        $this->assertSame('database', $result->getType());
        $this->assertSame(1, $result->getUserId());
        $this->assertSame('test@example.com', $result->getEmail());
        $this->assertSame('testuser', $result->getUsername());
        $this->assertSame('Test', $result->getFirstname());
        $this->assertSame('User', $result->getLastname());
    }

    public function testSetTable(): void
    {
        $database = $this->createMockDatabase();
        $connector = new DatabaseConnector($database);

        $result = $connector->setTable('members');

        $this->assertSame($connector, $result);
    }

    public function testSetIdentifierColumn(): void
    {
        $database = $this->createMockDatabase();
        $connector = new DatabaseConnector($database);

        $result = $connector->setIdentifierColumn('username');

        $this->assertSame($connector, $result);
    }

    public function testSetPasswordColumn(): void
    {
        $database = $this->createMockDatabase();
        $connector = new DatabaseConnector($database);

        $result = $connector->setPasswordColumn('pass_hash');

        $this->assertSame($connector, $result);
    }

    public function testAuthenticateWithCustomPasswordColumn(): void
    {
        $userData = [
            'id' => 1,
            'email' => 'test@example.com',
            'pass_hash' => password_hash('correctpass', PASSWORD_DEFAULT),
            'username' => 'testuser',
            'firstname' => 'Test',
            'lastname' => 'User',
        ];

        $database = $this->createMockDatabase($userData);
        $connector = new DatabaseConnector($database, [
            'passwordColumn' => 'pass_hash',
        ]);

        $credentials = ['identifier' => 'test@example.com', 'password' => 'correctpass'];

        $result = $connector->authenticate($credentials);

        $this->assertTrue($result->isSuccess());
    }

    public function testAuthenticateWithUserWithoutId(): void
    {
        $userData = [
            'email' => 'test@example.com',
            'password' => password_hash('correctpass', PASSWORD_DEFAULT),
            'username' => 'testuser',
            'firstname' => 'Test',
            'lastname' => 'User',
        ];

        $database = $this->createMockDatabase($userData);
        $connector = new DatabaseConnector($database);

        $credentials = ['identifier' => 'test@example.com', 'password' => 'correctpass'];

        $result = $connector->authenticate($credentials);

        $this->assertTrue($result->isSuccess());
        $this->assertNull($result->getUserId());
    }
}
