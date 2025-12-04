<?php

/**
 * @author    Joffrey Demetz <joffrey.demetz@gmail.com>
 * @license   MIT License; <https://opensource.org/licenses/MIT>
 */

namespace JDZ\Authentication\Tests\Connector;

use JDZ\Authentication\AuthenticationResponse;
use JDZ\Authentication\AuthStatusEnum;
use JDZ\Authentication\Connector\DatabaseConnector;
use PHPUnit\Framework\TestCase;

class DatabaseConnectorTest extends TestCase
{
    public function testConstructorThrowsExceptionForMissingTableName(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Table name, username column and password column must be set in the configuration.');

        new class('', 'email', 'password') extends DatabaseConnector {};
    }

    public function testConstructorThrowsExceptionForMissingUsernameColumn(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Table name, username column and password column must be set in the configuration.');

        new class('users', '', 'password') extends DatabaseConnector {};
    }

    public function testConstructorThrowsExceptionForMissingPasswordColumn(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Table name, username column and password column must be set in the configuration.');

        new class('users', 'email', '') extends DatabaseConnector {};
    }

    public function testConstructorWithValidParameters(): void
    {
        $connector = new class('users', 'email', 'password') extends DatabaseConnector {};

        $this->assertInstanceOf(DatabaseConnector::class, $connector);
    }

    public function testAuthenticateReturnsBadCredentialsForEmptyHashedPassword(): void
    {
        $connector = new class('users', 'email', 'password') extends DatabaseConnector {
            protected function getHashedPassword(array $credentials): string
            {
                return '';
            }
        };

        $response = new AuthenticationResponse();
        $credentials = ['username' => 'test@example.com', 'password' => 'testpass'];

        $result = $connector->authenticate($credentials, $response);

        $this->assertFalse($result);
        $this->assertSame(AuthStatusEnum::BAD_CREDENTIALS, $response->status);
    }

    public function testAuthenticateReturnsBadPassForWrongPassword(): void
    {
        $connector = new class('users', 'email', 'password') extends DatabaseConnector {
            protected function getHashedPassword(array $credentials): string
            {
                return \password_hash('correctpass', \PASSWORD_DEFAULT);
            }
        };

        $response = new AuthenticationResponse();
        $credentials = ['username' => 'test@example.com', 'password' => 'wrongpass'];

        $result = $connector->authenticate($credentials, $response);

        $this->assertFalse($result);
        $this->assertSame(AuthStatusEnum::BAD_PASS, $response->status);
    }

    public function testAuthenticateSucceedsWithCorrectCredentials(): void
    {
        $connector = new class('users', 'email', 'password') extends DatabaseConnector {
            protected function getHashedPassword(array $credentials): string
            {
                return \password_hash('correctpass', \PASSWORD_DEFAULT);
            }
        };

        $response = new AuthenticationResponse();
        $credentials = ['username' => 'test@example.com', 'password' => 'correctpass'];

        $result = $connector->authenticate($credentials, $response);

        $this->assertTrue($result);
        $this->assertSame(AuthStatusEnum::SUCCESS, $response->status);
    }
}
