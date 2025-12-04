<?php

/**
 * @author    Joffrey Demetz <joffrey.demetz@gmail.com>
 * @license   MIT License; <https://opensource.org/licenses/MIT>
 */

namespace JDZ\Authentication\Tests;

use JDZ\Authentication\Authentication;
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

    public function testAuthenticateReturnsEmptyUserWhenUsernameIsEmpty(): void
    {
        $auth = new Authentication();
        $credentials = ['username' => '', 'password' => 'test'];

        $response = $auth->authenticate($credentials);

        $this->assertSame(AuthStatusEnum::EMPTY_USER, $response->status);
    }

    public function testAuthenticateReturnsEmptyPassWhenPasswordIsEmpty(): void
    {
        $auth = new Authentication();
        $credentials = ['username' => 'test', 'password' => ''];

        $response = $auth->authenticate($credentials);

        $this->assertSame(AuthStatusEnum::EMPTY_PASS, $response->status);
    }

    public function testAuthenticateWithBasicConnectorSuccess(): void
    {
        $password = 'testpassword';

        $auth = new Authentication();
        $connector = new BasicConnector('testuser', $password);
        $auth->addConnector($connector);

        $credentials = ['username' => 'testuser', 'password' => $password];

        $response = $auth->authenticate($credentials);

        $this->assertSame(AuthStatusEnum::SUCCESS, $response->status);
        $this->assertSame('Basic', $response->type);
    }

    public function testAuthenticateWithBasicConnectorBadCredentials(): void
    {
        $password = 'testpassword';

        $auth = new Authentication();
        $connector = new BasicConnector('testuser', \password_hash($password, \PASSWORD_DEFAULT));
        $auth->addConnector($connector);

        $credentials = ['username' => 'wronguser', 'password' => $password];

        $response = $auth->authenticate($credentials);

        $this->assertSame(AuthStatusEnum::BAD_CREDENTIALS, $response->status);
    }

    public function testAuthenticateWithBasicConnectorBadPassword(): void
    {
        $auth = new Authentication();
        $connector = new BasicConnector('testuser', \password_hash('testpassword', \PASSWORD_DEFAULT));
        $auth->addConnector($connector);

        $credentials = ['username' => 'testuser', 'password' => 'wrongpassword'];

        $response = $auth->authenticate($credentials);

        $this->assertSame(AuthStatusEnum::BAD_PASS, $response->status);
    }

    public function testAuthenticateWithMultipleConnectors(): void
    {
        $password = 'testpassword';

        $auth = new Authentication();

        // Add first connector with different credentials
        $connector1 = new BasicConnector('user1', \password_hash('pass1', \PASSWORD_DEFAULT));
        $auth->addConnector($connector1);

        // Add second connector with correct credentials
        $connector2 = new BasicConnector('user2', \password_hash($password, \PASSWORD_DEFAULT));
        $auth->addConnector($connector2);

        $credentials = ['username' => 'user2', 'password' => $password];

        $response = $auth->authenticate($credentials);

        $this->assertSame(AuthStatusEnum::SUCCESS, $response->status);
    }
}
