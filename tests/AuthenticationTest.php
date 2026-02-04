<?php

/**
 * @author    Joffrey Demetz <joffrey.demetz@gmail.com>
 * @license   MIT License; <https://opensource.org/licenses/MIT>
 */

namespace JDZ\Authentication\Tests;

use JDZ\Authentication\Authentication;
use JDZ\Authentication\AuthenticationResult;
use JDZ\Authentication\AuthStatusEnum;
use JDZ\Authentication\Connector\BasicConnector;
use PHPUnit\Framework\TestCase;

class AuthenticationTest extends TestCase
{
    public function testAddConnectorReturnsInstance(): void
    {
        $auth = new Authentication();
        $connector = new BasicConnector('test', 'test');

        $result = $auth->addConnector($connector);

        $this->assertSame($auth, $result);
    }

    public function testGetConnectors(): void
    {
        $auth = new Authentication();
        $connector = new BasicConnector('test', 'test');
        $auth->addConnector($connector);

        $connectors = $auth->getConnectors();

        $this->assertCount(1, $connectors);
        $this->assertSame($connector, $connectors[0]);
    }

    public function testAuthenticateReturnsEmptyIdentifierWhenIdentifierIsEmpty(): void
    {
        $auth = new Authentication();
        $credentials = ['identifier' => '', 'password' => 'test'];

        $result = $auth->authenticate($credentials);

        $this->assertInstanceOf(AuthenticationResult::class, $result);
        $this->assertSame(AuthStatusEnum::EMPTY_IDENTIFIER, $result->getStatus());
    }

    public function testAuthenticateReturnsEmptyPasswordWhenPasswordIsEmpty(): void
    {
        $auth = new Authentication();
        $credentials = ['identifier' => 'test', 'password' => ''];

        $result = $auth->authenticate($credentials);

        $this->assertSame(AuthStatusEnum::EMPTY_PASSWORD, $result->getStatus());
    }

    public function testAuthenticateNormalizesUsernameToIdentifier(): void
    {
        $auth = new Authentication();
        $connector = new BasicConnector('testuser', 'testpass');
        $auth->addConnector($connector);

        // Using 'username' key should work
        $result = $auth->authenticate(['username' => 'testuser', 'password' => 'testpass']);

        $this->assertTrue($result->isSuccess());
    }

    public function testAuthenticateNormalizesEmailToIdentifier(): void
    {
        $auth = new Authentication();
        $connector = new BasicConnector('test@example.com', 'testpass');
        $auth->addConnector($connector);

        // Using 'email' key should work
        $result = $auth->authenticate(['email' => 'test@example.com', 'password' => 'testpass']);

        $this->assertTrue($result->isSuccess());
    }

    public function testAuthenticateWithBasicConnectorSuccess(): void
    {
        $password = 'testpassword';

        $auth = new Authentication();
        $connector = new BasicConnector('testuser', $password);
        $auth->addConnector($connector);

        $credentials = ['identifier' => 'testuser', 'password' => $password];

        $result = $auth->authenticate($credentials);

        $this->assertTrue($result->isSuccess());
        $this->assertSame(AuthStatusEnum::SUCCESS, $result->getStatus());
        $this->assertSame('basic', $result->getType());
        $this->assertSame('testuser', $result->getIdentifier());
    }

    public function testAuthenticateWithBasicConnectorUserNotFound(): void
    {
        $password = 'testpassword';

        $auth = new Authentication();
        $connector = new BasicConnector('testuser', $password);
        $auth->addConnector($connector);

        $credentials = ['identifier' => 'wronguser', 'password' => $password];

        $result = $auth->authenticate($credentials);

        $this->assertFalse($result->isSuccess());
        $this->assertSame(AuthStatusEnum::USER_NOT_FOUND, $result->getStatus());
    }

    public function testAuthenticateWithBasicConnectorInvalidPassword(): void
    {
        $auth = new Authentication();
        $connector = new BasicConnector('testuser', 'testpassword');
        $auth->addConnector($connector);

        $credentials = ['identifier' => 'testuser', 'password' => 'wrongpassword'];

        $result = $auth->authenticate($credentials);

        $this->assertFalse($result->isSuccess());
        $this->assertSame(AuthStatusEnum::INVALID_PASSWORD, $result->getStatus());
    }

    public function testAuthenticateWithMultipleConnectors(): void
    {
        $password = 'testpassword';

        $auth = new Authentication();

        // Add first connector with different credentials
        $connector1 = new BasicConnector('user1', 'pass1');
        $auth->addConnector($connector1);

        // Add second connector with correct credentials
        $connector2 = new BasicConnector('user2', $password);
        $auth->addConnector($connector2);

        $credentials = ['identifier' => 'user2', 'password' => $password];

        $result = $auth->authenticate($credentials);

        $this->assertTrue($result->isSuccess());
        $this->assertSame(AuthStatusEnum::SUCCESS, $result->getStatus());
    }

    public function testAuthenticateWithPriority(): void
    {
        $auth = new Authentication();

        // Add connector with lower priority
        $connector1 = new BasicConnector('user', 'pass1');
        $auth->addConnector($connector1, 10);

        // Add connector with higher priority (should be tried first)
        $connector2 = new BasicConnector('user', 'pass2');
        $auth->addConnector($connector2, 20);

        // Should match the higher priority connector
        $result = $auth->authenticate(['identifier' => 'user', 'password' => 'pass2']);

        $this->assertTrue($result->isSuccess());
    }

    public function testSupportsReturnsTrueWhenConnectorSupports(): void
    {
        $auth = new Authentication();
        $connector = new BasicConnector('test', 'test');
        $auth->addConnector($connector);

        $this->assertTrue($auth->supports(['identifier' => 'test', 'password' => 'test']));
    }

    public function testSupportsReturnsFalseWhenNoConnectors(): void
    {
        $auth = new Authentication();

        $this->assertFalse($auth->supports(['identifier' => 'test', 'password' => 'test']));
    }

    public function testAuthenticateReturnsUserNotFoundWhenNoConnectorsMatch(): void
    {
        $auth = new Authentication();
        $connector = new BasicConnector('testuser', 'testpass');
        $auth->addConnector($connector);

        $result = $auth->authenticate(['identifier' => 'unknown', 'password' => 'unknown']);

        $this->assertFalse($result->isSuccess());
        $this->assertSame(AuthStatusEnum::USER_NOT_FOUND, $result->getStatus());
    }
}
