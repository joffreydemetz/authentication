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

    /**
     * A BasicConnector whose verifyPassword() records its (password, hash) calls in
     * `$verified` and answers $verifies to every one of them.
     */
    private static function recordingConnector(string $hash, bool $verifies): BasicConnector
    {
        return new class ('testuser', $hash, $verifies) extends BasicConnector {
            /** @var list<array{string, string}> */
            public array $verified = [];

            public function __construct(string $identifier, string $password, private bool $verifies)
            {
                parent::__construct($identifier, $password);
            }

            protected function verifyPassword(string $password, string $hashedPassword): bool
            {
                $this->verified[] = [$password, $hashedPassword];
                return $this->verifies;
            }
        };
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

    /**
     * An unknown identifier costs the same password verification as a known one (against
     * one fixed bcrypt hash, never the stored one), so the response time does not tell
     * whether the account exists; the outcome of that check is thrown away.
     */
    #[DataProvider('dummyVerifications')]
    public function testAnUnknownUserStillCostsAPasswordVerification(bool $verifies): void
    {
        $stored = self::hash('testpass');
        $connector = self::recordingConnector($stored, $verifies);

        $result = $connector->authenticate(['identifier' => 'wronguser', 'password' => 'testpass']);

        $this->assertSame(AuthStatusEnum::USER_NOT_FOUND, $result->getStatus());
        $this->assertSame('basic', $result->getType());
        $this->assertCount(1, $connector->verified);
        $this->assertSame('testpass', $connector->verified[0][0]);
        $this->assertNotSame($stored, $connector->verified[0][1]);
        $this->assertSame(
            ['algo' => '2y', 'algoName' => 'bcrypt', 'options' => ['cost' => 12]],
            password_get_info($connector->verified[0][1])
        );
    }

    public static function dummyVerifications(): array
    {
        return [
            'the dummy check fails' => [false],
            'even a dummy check that passes' => [true],
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
