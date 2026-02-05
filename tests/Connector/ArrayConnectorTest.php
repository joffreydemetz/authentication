<?php

/**
 * @author    Joffrey Demetz <joffrey.demetz@gmail.com>
 * @license   MIT License; <https://opensource.org/licenses/MIT>
 */

namespace JDZ\Authentication\Tests\Connector;

use JDZ\Authentication\AuthenticationResult;
use JDZ\Authentication\AuthStatusEnum;
use JDZ\Authentication\Connector\ArrayConnector;
use JDZ\Authentication\Contract\PasswordHasherInterface;
use PHPUnit\Framework\TestCase;

class ArrayConnectorTest extends TestCase
{
    private function createPasswordHasher(): PasswordHasherInterface
    {
        $hasher = $this->createMock(PasswordHasherInterface::class);

        $hasher->method('hash')->willReturnCallback(function (string $password) {
            return password_hash($password, PASSWORD_DEFAULT);
        });

        $hasher->method('verify')->willReturnCallback(function (string $plain, string $hashed) {
            return password_verify($plain, $hashed);
        });

        return $hasher;
    }

    public function testGetName(): void
    {
        $connector = new ArrayConnector($this->createPasswordHasher());

        $this->assertSame('array', $connector->getName());
    }

    public function testSupportsWithValidCredentials(): void
    {
        $connector = new ArrayConnector($this->createPasswordHasher());

        $this->assertTrue($connector->supports(['identifier' => 'test', 'password' => 'pass']));
    }

    public function testSupportsReturnsFalseWithoutIdentifier(): void
    {
        $connector = new ArrayConnector($this->createPasswordHasher());

        $this->assertFalse($connector->supports(['password' => 'pass']));
    }

    public function testSupportsReturnsFalseWithoutPassword(): void
    {
        $connector = new ArrayConnector($this->createPasswordHasher());

        $this->assertFalse($connector->supports(['identifier' => 'test']));
    }

    public function testAddUserAndAuthenticateSuccess(): void
    {
        $connector = new ArrayConnector($this->createPasswordHasher());
        $connector->addUser('admin', 'secret', 1, [
            'email' => 'admin@example.com',
            'firstname' => 'Admin',
            'lastname' => 'User',
        ]);

        $result = $connector->authenticate([
            'identifier' => 'admin',
            'password' => 'secret',
        ]);

        $this->assertTrue($result->isSuccess());
        $this->assertSame(AuthStatusEnum::SUCCESS, $result->getStatus());
        $this->assertSame('array', $result->getType());
        $this->assertSame(1, $result->getUserId());
        $this->assertSame('admin@example.com', $result->getEmail());
        $this->assertSame('Admin', $result->getFirstname());
        $this->assertSame('User', $result->getLastname());
    }

    public function testAuthenticateReturnsUserNotFound(): void
    {
        $connector = new ArrayConnector($this->createPasswordHasher());

        $result = $connector->authenticate([
            'identifier' => 'nonexistent',
            'password' => 'pass',
        ]);

        $this->assertFalse($result->isSuccess());
        $this->assertSame(AuthStatusEnum::USER_NOT_FOUND, $result->getStatus());
    }

    public function testAuthenticateReturnsInvalidPassword(): void
    {
        $connector = new ArrayConnector($this->createPasswordHasher());
        $connector->addUser('admin', 'secret', 1);

        $result = $connector->authenticate([
            'identifier' => 'admin',
            'password' => 'wrongpass',
        ]);

        $this->assertFalse($result->isSuccess());
        $this->assertSame(AuthStatusEnum::INVALID_PASSWORD, $result->getStatus());
    }

    public function testPlainPasswordsMode(): void
    {
        $hasher = $this->createPasswordHasher();
        $connector = new ArrayConnector($hasher, [], true);
        $connector->addUser('admin', 'plain123', 1);

        $result = $connector->authenticate([
            'identifier' => 'admin',
            'password' => 'plain123',
        ]);

        $this->assertTrue($result->isSuccess());
    }

    public function testPlainPasswordsModeFailsWithWrongPassword(): void
    {
        $hasher = $this->createPasswordHasher();
        $connector = new ArrayConnector($hasher, [], true);
        $connector->addUser('admin', 'plain123', 1);

        $result = $connector->authenticate([
            'identifier' => 'admin',
            'password' => 'wrong',
        ]);

        $this->assertFalse($result->isSuccess());
        $this->assertSame(AuthStatusEnum::INVALID_PASSWORD, $result->getStatus());
    }

    public function testConstructorWithPreloadedUsers(): void
    {
        $users = [
            'user1' => [
                'id' => 1,
                'password' => 'plainpass1',
            ],
            'user2' => [
                'id' => 2,
                'password' => 'plainpass2',
            ],
        ];

        $connector = new ArrayConnector($this->createPasswordHasher(), $users, true);

        $result1 = $connector->authenticate(['identifier' => 'user1', 'password' => 'plainpass1']);
        $result2 = $connector->authenticate(['identifier' => 'user2', 'password' => 'plainpass2']);

        $this->assertTrue($result1->isSuccess());
        $this->assertSame(1, $result1->getUserId());
        $this->assertTrue($result2->isSuccess());
        $this->assertSame(2, $result2->getUserId());
    }

    public function testEmailDefaultsToIdentifier(): void
    {
        $connector = new ArrayConnector($this->createPasswordHasher(), [], true);
        $connector->addUser('admin@test.com', 'pass', 1);

        $result = $connector->authenticate([
            'identifier' => 'admin@test.com',
            'password' => 'pass',
        ]);

        $this->assertTrue($result->isSuccess());
        $this->assertSame('admin@test.com', $result->getEmail());
    }
}
