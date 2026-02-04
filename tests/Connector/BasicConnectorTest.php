<?php

/**
 * @author    Joffrey Demetz <joffrey.demetz@gmail.com>
 * @license   MIT License; <https://opensource.org/licenses/MIT>
 */

namespace JDZ\Authentication\Tests\Connector;

use JDZ\Authentication\AuthenticationResult;
use JDZ\Authentication\AuthStatusEnum;
use JDZ\Authentication\Connector\BasicConnector;
use PHPUnit\Framework\TestCase;

class BasicConnectorTest extends TestCase
{
    public function testConstructorThrowsExceptionForMissingIdentifier(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Identifier and password must be provided.');

        new BasicConnector('', 'test');
    }

    public function testConstructorThrowsExceptionForEmptyPassword(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Identifier and password must be provided.');

        new BasicConnector('testuser', '');
    }

    public function testGetName(): void
    {
        $connector = new BasicConnector('testuser', 'testpass');

        $this->assertSame('basic', $connector->getName());
    }

    public function testSupportsWithValidCredentials(): void
    {
        $connector = new BasicConnector('testuser', 'testpass');

        $this->assertTrue($connector->supports(['identifier' => 'test', 'password' => 'pass']));
    }

    public function testSupportsReturnsFalseWithoutIdentifier(): void
    {
        $connector = new BasicConnector('testuser', 'testpass');

        $this->assertFalse($connector->supports(['password' => 'pass']));
    }

    public function testSupportsReturnsFalseWithoutPassword(): void
    {
        $connector = new BasicConnector('testuser', 'testpass');

        $this->assertFalse($connector->supports(['identifier' => 'test']));
    }

    public function testAuthenticateReturnsUserNotFoundForWrongIdentifier(): void
    {
        $connector = new BasicConnector('testuser', 'testpass');
        $credentials = ['identifier' => 'wronguser', 'password' => 'testpass'];

        $result = $connector->authenticate($credentials);

        $this->assertInstanceOf(AuthenticationResult::class, $result);
        $this->assertFalse($result->isSuccess());
        $this->assertSame(AuthStatusEnum::USER_NOT_FOUND, $result->getStatus());
    }

    public function testAuthenticateReturnsInvalidPasswordForWrongPassword(): void
    {
        $connector = new BasicConnector('testuser', 'correctpass');
        $credentials = ['identifier' => 'testuser', 'password' => 'wrongpass'];

        $result = $connector->authenticate($credentials);

        $this->assertFalse($result->isSuccess());
        $this->assertSame(AuthStatusEnum::INVALID_PASSWORD, $result->getStatus());
    }

    public function testAuthenticateSucceedsWithCorrectCredentials(): void
    {
        $password = 'testpassword';
        $connector = new BasicConnector('testuser', $password);
        $credentials = ['identifier' => 'testuser', 'password' => $password];

        $result = $connector->authenticate($credentials);

        $this->assertTrue($result->isSuccess());
        $this->assertSame(AuthStatusEnum::SUCCESS, $result->getStatus());
        $this->assertSame('basic', $result->getType());
        $this->assertSame('testuser', $result->getUsername());
    }

    public function testAuthenticateWithPreHashedPassword(): void
    {
        $hashedPassword = password_hash('testpassword', PASSWORD_DEFAULT);
        $connector = new BasicConnector('testuser', $hashedPassword);
        $credentials = ['identifier' => 'testuser', 'password' => 'testpassword'];

        $result = $connector->authenticate($credentials);

        $this->assertTrue($result->isSuccess());
    }

    public function testAuthenticateHashesPlainTextPassword(): void
    {
        // Constructor should hash plain text passwords
        $connector = new BasicConnector('testuser', 'plaintext');
        $credentials = ['identifier' => 'testuser', 'password' => 'plaintext'];

        $result = $connector->authenticate($credentials);

        $this->assertTrue($result->isSuccess());
    }
}
