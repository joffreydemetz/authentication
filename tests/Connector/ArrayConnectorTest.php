<?php

/**
 * @author    Joffrey Demetz <joffrey.demetz@gmail.com>
 * @license   MIT License; <https://opensource.org/licenses/MIT>
 */

namespace JDZ\Authentication\Tests\Connector;

use JDZ\Authentication\AuthStatusEnum;
use JDZ\Authentication\Connector\ArrayConnector;
use JDZ\Authentication\Contract\PasswordHasherInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class ArrayConnectorTest extends TestCase
{
    /**
     * A fake hasher: hash(p) is "hashed:p", and verify() only accepts the (plain, hashed) argument order.
     */
    private function createPasswordHasher(): PasswordHasherInterface
    {
        $hasher = $this->createStub(PasswordHasherInterface::class);
        $hasher->method('hash')->willReturnCallback(static fn(string $plain): string => 'hashed:' . $plain);
        $hasher->method('verify')->willReturnCallback(
            static fn(string $plain, string $hashed): bool => $hashed === 'hashed:' . $plain
        );

        return $hasher;
    }

    #[DataProvider('supportedCredentials')]
    public function testSupports(array $credentials, bool $supported): void
    {
        $connector = new ArrayConnector($this->createPasswordHasher());

        $this->assertSame($supported, $connector->supports($credentials));
    }

    public static function supportedCredentials(): array
    {
        return [
            'identifier and password' => [['identifier' => 'test', 'password' => 'pass'], true],
            'empty strings still count' => [['identifier' => '', 'password' => ''], true],
            'no identifier' => [['password' => 'pass'], false],
            'no password' => [['identifier' => 'test'], false],
            'null identifier' => [['identifier' => null, 'password' => 'pass'], false],
            'null password' => [['identifier' => 'test', 'password' => null], false],
            'an email key is not an identifier' => [['email' => 'test', 'password' => 'pass'], false],
        ];
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

        $this->assertSame([
            'status' => 1,
            'message' => 'Authentication successful',
            'user_id' => 1,
            'identifier' => '',
            'email' => 'admin@example.com',
            'username' => '',
            'firstname' => 'Admin',
            'lastname' => 'User',
            'fullname' => 'Admin User',
            'type' => 'array',
            'data' => [],
        ], $result->toArray());
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
        $this->assertSame('array', $result->getType());
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
        $this->assertSame('array', $result->getType());
    }

    public function testAddUserStoresTheHashAndVerifiesAgainstIt(): void
    {
        $connector = new ArrayConnector($this->createPasswordHasher());
        $connector->addUser('admin', 'secret', 1);

        // the stored value is "hashed:secret": sending it back is not the password
        $replayed = $connector->authenticate(['identifier' => 'admin', 'password' => 'hashed:secret']);
        $genuine = $connector->authenticate(['identifier' => 'admin', 'password' => 'secret']);

        $this->assertSame(AuthStatusEnum::INVALID_PASSWORD, $replayed->getStatus());
        $this->assertTrue($genuine->isSuccess());
    }

    public function testPreloadedUsersAreVerifiedThroughTheHasher(): void
    {
        $connector = new ArrayConnector($this->createPasswordHasher(), [
            'admin' => ['id' => 3, 'password' => 'hashed:secret'],
        ]);

        $genuine = $connector->authenticate(['identifier' => 'admin', 'password' => 'secret']);
        $replayed = $connector->authenticate(['identifier' => 'admin', 'password' => 'hashed:secret']);

        $this->assertTrue($genuine->isSuccess());
        $this->assertSame(3, $genuine->getUserId());
        $this->assertSame(AuthStatusEnum::INVALID_PASSWORD, $replayed->getStatus());
    }

    public function testPlainPasswordsModeNeverCallsTheHasher(): void
    {
        $hasher = $this->createMock(PasswordHasherInterface::class);
        $hasher->expects($this->never())->method('hash');
        $hasher->expects($this->never())->method('verify');

        $connector = new ArrayConnector($hasher, [], true);
        $connector->addUser('admin', 'plain123', 1);

        $this->assertTrue($connector->authenticate(['identifier' => 'admin', 'password' => 'plain123'])->isSuccess());
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

    public function testAddUserDataCannotOverrideTheIdOrThePassword(): void
    {
        $connector = new ArrayConnector($this->createPasswordHasher());
        $connector->addUser('admin', 'secret', 1, [
            'id' => 99,
            'password' => 'hashed:other',
            'email' => 'admin@example.com',
        ]);

        $genuine = $connector->authenticate(['identifier' => 'admin', 'password' => 'secret']);
        $fromData = $connector->authenticate(['identifier' => 'admin', 'password' => 'other']);

        $this->assertSame(1, $genuine->getUserId());
        $this->assertSame('admin@example.com', $genuine->getEmail());
        $this->assertSame(AuthStatusEnum::INVALID_PASSWORD, $fromData->getStatus());
    }

    public function testAddUserReplacesAnExistingIdentifier(): void
    {
        $connector = new ArrayConnector($this->createPasswordHasher());
        $connector->addUser('admin', 'old', 1);
        $connector->addUser('admin', 'new', 2);

        $old = $connector->authenticate(['identifier' => 'admin', 'password' => 'old']);
        $new = $connector->authenticate(['identifier' => 'admin', 'password' => 'new']);

        $this->assertSame(AuthStatusEnum::INVALID_PASSWORD, $old->getStatus());
        $this->assertSame(2, $new->getUserId());
    }
}
