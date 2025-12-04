<?php

/**
 * @author    Joffrey Demetz <joffrey.demetz@gmail.com>
 * @license   MIT License; <https://opensource.org/licenses/MIT>
 */

namespace JDZ\Authentication\Tests\Connector;

use JDZ\Authentication\AuthenticationResponse;
use JDZ\Authentication\AuthStatusEnum;
use JDZ\Authentication\Connector\BasicConnector;
use PHPUnit\Framework\TestCase;

class BasicConnectorTest extends TestCase
{
    public function testAuthenticateReturnsEmptyUserForEmptyUsername(): void
    {
        $connector = new BasicConnector('testuser', \password_hash('test', \PASSWORD_DEFAULT));
        $response = new AuthenticationResponse();
        $credentials = ['username' => '', 'password' => 'test'];

        $result = $connector->authenticate($credentials, $response);

        $this->assertFalse($result);
        $this->assertSame(AuthStatusEnum::EMPTY_USER, $response->status);
    }

    public function testAuthenticateReturnsBadCredentialsForWrongUsername(): void
    {
        $connector = new BasicConnector('testuser', \password_hash('test', \PASSWORD_DEFAULT));
        $response = new AuthenticationResponse();
        $credentials = ['username' => 'wronguser', 'password' => 'test'];

        $result = $connector->authenticate($credentials, $response);

        $this->assertFalse($result);
        $this->assertSame(AuthStatusEnum::BAD_CREDENTIALS, $response->status);
    }

    public function testConstructorThrowsExceptionForMissingUsername(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Username and password must be set in the configuration.');

        new BasicConnector('', 'test');
    }

    public function testConstructorThrowsExceptionForEmptyPassword(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Username and password must be set in the configuration.');

        new BasicConnector('testuser', '');
    }

    public function testAuthenticateReturnsEmptyPassForEmptyPassword(): void
    {
        $connector = new BasicConnector('testuser', 'test');
        $response = new AuthenticationResponse();
        $credentials = ['username' => 'testuser', 'password' => ''];

        $result = $connector->authenticate($credentials, $response);

        $this->assertFalse($result);
        $this->assertSame(AuthStatusEnum::EMPTY_PASS, $response->status);
    }

    public function testAuthenticateReturnsBadPassForWrongPassword(): void
    {
        $connector = new BasicConnector('testuser', 'correctpass');
        $response = new AuthenticationResponse();
        $credentials = ['username' => 'testuser', 'password' => 'wrongpass'];

        $result = $connector->authenticate($credentials, $response);

        $this->assertFalse($result);
        $this->assertSame(AuthStatusEnum::BAD_PASS, $response->status);
    }

    public function testAuthenticateSucceedsWithCorrectCredentials(): void
    {
        $password = 'testpassword';
        $connector = new BasicConnector('testuser', $password);
        $response = new AuthenticationResponse();
        $credentials = ['username' => 'testuser', 'password' => $password];

        $result = $connector->authenticate($credentials, $response);

        $this->assertTrue($result);
        $this->assertSame(AuthStatusEnum::SUCCESS, $response->status);
        $this->assertSame('Basic', $response->type);
    }
}
