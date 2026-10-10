<?php

/**
 * @author    Joffrey Demetz <joffrey.demetz@gmail.com>
 * @license   MIT License; <https://opensource.org/licenses/MIT>
 */

namespace JDZ\Authentication\Tests\Connector;

use JDZ\Authentication\AuthStatusEnum;
use JDZ\Authentication\Connector\BasicConnector;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class BasicConnectorTest extends TestCase
{
    /**
     * A bcrypt hash cheap enough for a test; the constructor keeps a pre-hashed password as is.
     */
    private static function hash(string $password): string
    {
        return password_hash($password, PASSWORD_BCRYPT, ['cost' => 4]);
    }

    #[DataProvider('missingConstructorArguments')]
    public function testConstructorRefusesAnEmptyIdentifierOrPassword(string $identifier, string $password): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Identifier and password must be provided.');

        new BasicConnector($identifier, $password);
    }

    public static function missingConstructorArguments(): array
    {
        return [
            'empty identifier' => ['', 'test'],
            'empty password' => ['testuser', ''],
            'both empty' => ['', ''],
        ];
    }

    #[DataProvider('otherIdentifiers')]
    public function testOnlyTheExactIdentifierIsKnown(array $credentials): void
    {
        $connector = new BasicConnector('testuser', self::hash('testpass'));

        $result = $connector->authenticate($credentials);

        $this->assertSame(AuthStatusEnum::USER_NOT_FOUND, $result->getStatus());
        $this->assertSame('basic', $result->getType());
    }

    public static function otherIdentifiers(): array
    {
        return [
            'another identifier' => [['identifier' => 'wronguser', 'password' => 'testpass']],
            'another case' => [['identifier' => 'TestUser', 'password' => 'testpass']],
            'surrounding spaces (trimming is Authentication\'s job)' => [['identifier' => ' testuser ', 'password' => 'testpass']],
            'no identifier key' => [['password' => 'testpass']],
        ];
    }

    public function testAuthenticateReturnsInvalidPasswordForWrongPassword(): void
    {
        $connector = new BasicConnector('testuser', self::hash('correctpass'));
        $credentials = ['identifier' => 'testuser', 'password' => 'wrongpass'];

        $result = $connector->authenticate($credentials);

        $this->assertFalse($result->isSuccess());
        $this->assertSame(AuthStatusEnum::INVALID_PASSWORD, $result->getStatus());
        $this->assertSame('basic', $result->getType());
    }

    public function testAuthenticateSucceedsWithCorrectCredentials(): void
    {
        // a plain password: the constructor hashes it
        $connector = new BasicConnector('testuser', 'testpassword');

        $result = $connector->authenticate(['identifier' => 'testuser', 'password' => 'testpassword']);

        $this->assertSame([
            'status' => 1,
            'message' => 'Authentication successful',
            'user_id' => null,
            'identifier' => '',
            'email' => '',
            'username' => 'testuser',
            'firstname' => '',
            'lastname' => '',
            'fullname' => '',
            'type' => 'basic',
            'data' => [],
        ], $result->toArray());
    }

    public function testAuthenticateWithPreHashedPassword(): void
    {
        $connector = new BasicConnector('testuser', self::hash('testpassword'));
        $credentials = ['identifier' => 'testuser', 'password' => 'testpassword'];

        $result = $connector->authenticate($credentials);

        $this->assertTrue($result->isSuccess());
    }

    public function testAPreHashedPasswordIsNotItselfThePassword(): void
    {
        $hash = self::hash('testpassword');
        $connector = new BasicConnector('testuser', $hash);

        $result = $connector->authenticate(['identifier' => 'testuser', 'password' => $hash]);

        $this->assertSame(AuthStatusEnum::INVALID_PASSWORD, $result->getStatus());
    }
}
