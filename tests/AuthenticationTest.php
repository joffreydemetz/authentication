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
use JDZ\Authentication\Contract\ConnectorInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class AuthenticationTest extends TestCase
{
    /** @var list<array{string, array}> the connectors' authenticate() calls: name + credentials received */
    private array $calls = [];

    /**
     * A stub connector that records each authenticate() call and answers $result.
     */
    private function connector(string $name, AuthenticationResult $result, bool $supports = true): ConnectorInterface
    {
        $connector = $this->createStub(ConnectorInterface::class);
        $connector->method('getName')->willReturn($name);
        $connector->method('supports')->willReturn($supports);
        $connector->method('authenticate')->willReturnCallback(
            function (array $credentials) use ($name, $result): AuthenticationResult {
                $this->calls[] = [$name, $credentials];
                return $result;
            }
        );

        return $connector;
    }

    private function notFound(string $name): ConnectorInterface
    {
        return $this->connector(
            $name,
            AuthenticationResult::failure(AuthStatusEnum::USER_NOT_FOUND, $name . ': unknown user')->setType($name)
        );
    }

    /**
     * A bcrypt hash cheap enough for a test (BasicConnector keeps a pre-hashed password as is).
     */
    private static function hash(string $password): string
    {
        return password_hash($password, PASSWORD_BCRYPT, ['cost' => 4]);
    }

    private function assertGenericNotFound(AuthenticationResult $result): void
    {
        $this->assertSame([
            'status' => 4,
            'message' => 'Invalid credentials',
            'user_id' => null,
            'identifier' => '',
            'email' => '',
            'username' => '',
            'firstname' => '',
            'lastname' => '',
            'fullname' => '',
            'type' => '',
            'data' => [],
        ], $result->toArray());
    }

    #[DataProvider('emptyCredentials')]
    public function testEmptyCredentialsAreRefusedBeforeAnyConnector(array $credentials, AuthStatusEnum $status): void
    {
        $connector = $this->createMock(ConnectorInterface::class);
        $connector->expects($this->never())->method('supports');
        $connector->expects($this->never())->method('authenticate');

        $result = (new Authentication())->addConnector($connector)->authenticate($credentials);

        $this->assertSame($status, $result->getStatus());
        $this->assertSame('', $result->getType());
    }

    public static function emptyCredentials(): array
    {
        return [
            'no keys at all' => [[], AuthStatusEnum::EMPTY_IDENTIFIER],
            'empty identifier' => [['identifier' => '', 'password' => 'secret'], AuthStatusEnum::EMPTY_IDENTIFIER],
            'whitespace-only identifier' => [['identifier' => " \t\n ", 'password' => 'secret'], AuthStatusEnum::EMPTY_IDENTIFIER],
            'null identifier, email and username' => [['identifier' => null, 'email' => null, 'username' => null, 'password' => 'secret'], AuthStatusEnum::EMPTY_IDENTIFIER],
            'an empty identifier key hides the email key' => [['identifier' => '', 'email' => 'bob@example.com', 'password' => 'secret'], AuthStatusEnum::EMPTY_IDENTIFIER],
            'both empty: the identifier is checked first' => [['identifier' => '', 'password' => ''], AuthStatusEnum::EMPTY_IDENTIFIER],
            'no password key' => [['identifier' => 'bob'], AuthStatusEnum::EMPTY_PASSWORD],
            'empty password' => [['identifier' => 'bob', 'password' => ''], AuthStatusEnum::EMPTY_PASSWORD],
            'null password' => [['identifier' => 'bob', 'password' => null], AuthStatusEnum::EMPTY_PASSWORD],
        ];
    }

    #[DataProvider('identifierKeys')]
    public function testIdentifierKeyPrecedence(array $credentials, string $identifier): void
    {
        $auth = (new Authentication())->addConnector($this->connector('a', AuthenticationResult::success(7)));

        $result = $auth->authenticate($credentials + ['password' => 'secret']);

        $this->assertSame([['a', ['identifier' => $identifier, 'password' => 'secret']]], $this->calls);
        $this->assertSame($identifier, $result->getIdentifier());
    }

    public static function identifierKeys(): array
    {
        return [
            'identifier over email and username' => [['identifier' => 'by-identifier', 'email' => 'by-email', 'username' => 'by-username'], 'by-identifier'],
            'email over username' => [['email' => 'by-email', 'username' => 'by-username'], 'by-email'],
            'username alone' => [['username' => 'by-username'], 'by-username'],
            'a null identifier falls through to email' => [['identifier' => null, 'email' => 'by-email', 'username' => 'by-username'], 'by-email'],
            'a null email falls through to username' => [['email' => null, 'username' => 'by-username'], 'by-username'],
        ];
    }

    public function testConnectorsGetTheTrimmedIdentifierAndTheVerbatimPasswordOnly(): void
    {
        $auth = (new Authentication())->addConnector($this->connector('a', AuthenticationResult::success(7)));

        $result = $auth->authenticate([
            'identifier' => "  bob@example.com\t\n",
            'password' => '  secret  ',
            'remember' => '1',
        ]);

        $this->assertSame([['a', ['identifier' => 'bob@example.com', 'password' => '  secret  ']]], $this->calls);
        $this->assertSame('bob@example.com', $result->getIdentifier());
    }

    public function testSuccessIsTheConnectorResultStampedWithTheIdentifier(): void
    {
        $success = AuthenticationResult::success(7)->setIdentifier('set-by-connector')->setType('ldap');
        $auth = (new Authentication())->addConnector($this->connector('a', $success));

        $result = $auth->authenticate(['username' => ' bob ', 'password' => 'secret']);

        $this->assertSame($success, $result);
        $this->assertSame('bob', $result->getIdentifier());
        $this->assertSame(7, $result->getUserId());
        $this->assertSame('ldap', $result->getType());
    }

    public function testConnectorsRunByDescendingPriorityThenInsertionOrder(): void
    {
        $a = $this->notFound('a');
        $b = $this->notFound('b');
        $c = $this->notFound('c');
        $d = $this->notFound('d');
        $e = $this->notFound('e');

        $auth = (new Authentication())
            ->addConnector($a)
            ->addConnector($b, 10)
            ->addConnector($c, -5)
            ->addConnector($d, 10)
            ->addConnector($e);

        $this->assertSame([$b, $d, $a, $e, $c], $auth->getConnectors());

        $auth->authenticate(['identifier' => 'bob', 'password' => 'secret']);

        $this->assertSame(['b', 'd', 'a', 'e', 'c'], array_column($this->calls, 0));
    }

    public function testNotFoundFallsThroughToTheNextConnector(): void
    {
        $success = AuthenticationResult::success(7);
        $auth = (new Authentication())
            ->addConnector($this->notFound('a'), 10)
            ->addConnector($this->connector('b', $success));

        $result = $auth->authenticate(['identifier' => 'bob', 'password' => 'secret']);

        $this->assertSame($success, $result);
        $this->assertSame(['a', 'b'], array_column($this->calls, 0));
    }

    #[DataProvider('chainStoppers')]
    public function testAnyOtherFailureStopsTheChain(AuthStatusEnum $status): void
    {
        $failure = AuthenticationResult::failure($status, 'answered by a');
        $auth = (new Authentication())
            ->addConnector($this->connector('a', $failure), 10)
            ->addConnector($this->connector('b', AuthenticationResult::success(7)));

        $result = $auth->authenticate(['identifier' => 'bob', 'password' => 'secret']);

        $this->assertSame($failure, $result);
        $this->assertSame(['a'], array_column($this->calls, 0));
        // only a success is stamped with the identifier
        $this->assertSame('', $result->getIdentifier());
    }

    public static function chainStoppers(): array
    {
        return [
            'failure' => [AuthStatusEnum::FAILURE],
            'invalid password' => [AuthStatusEnum::INVALID_PASSWORD],
            'user banned' => [AuthStatusEnum::USER_BANNED],
            'user not confirmed' => [AuthStatusEnum::USER_NOT_CONFIRMED],
            'account locked' => [AuthStatusEnum::ACCOUNT_LOCKED],
            'empty identifier from a connector' => [AuthStatusEnum::EMPTY_IDENTIFIER],
            'empty password from a connector' => [AuthStatusEnum::EMPTY_PASSWORD],
        ];
    }

    public function testAConnectorThatDoesNotSupportTheCredentialsIsSkipped(): void
    {
        $skipped = $this->createMock(ConnectorInterface::class);
        $skipped->expects($this->once())
            ->method('supports')
            ->with(['identifier' => 'bob', 'password' => 'secret'])
            ->willReturn(false);
        $skipped->expects($this->never())->method('authenticate');

        $success = AuthenticationResult::success(7);
        $auth = (new Authentication())
            ->addConnector($skipped, 10)
            ->addConnector($this->connector('b', $success));

        $this->assertSame($success, $auth->authenticate(['email' => ' bob ', 'password' => 'secret']));
    }

    public function testWhenEveryConnectorMissesTheResultIsAGenericNotFound(): void
    {
        $auth = (new Authentication())
            ->addConnector($this->notFound('a'))
            ->addConnector($this->notFound('b'));

        $this->assertGenericNotFound($auth->authenticate(['identifier' => 'bob', 'password' => 'secret']));
        $this->assertSame(['a', 'b'], array_column($this->calls, 0));
    }

    public function testWithoutConnectorsTheResultIsAGenericNotFound(): void
    {
        $this->assertGenericNotFound((new Authentication())->authenticate(['identifier' => 'bob', 'password' => 'secret']));
    }

    public function testWhenNoConnectorSupportsTheCredentialsTheResultIsAGenericNotFound(): void
    {
        $auth = (new Authentication())
            ->addConnector($this->connector('a', AuthenticationResult::success(7), false));

        $this->assertGenericNotFound($auth->authenticate(['identifier' => 'bob', 'password' => 'secret']));
        $this->assertSame([], $this->calls);
    }

    public function testAuthenticateWithBasicConnectorSuccess(): void
    {
        $auth = new Authentication();
        $auth->addConnector(new BasicConnector('testuser', self::hash('testpassword')));

        $result = $auth->authenticate(['identifier' => 'testuser', 'password' => 'testpassword']);

        $this->assertTrue($result->isSuccess());
        $this->assertSame(AuthStatusEnum::SUCCESS, $result->getStatus());
        $this->assertSame('basic', $result->getType());
        $this->assertSame('testuser', $result->getIdentifier());
    }

    public function testAuthenticateWithBasicConnectorInvalidPassword(): void
    {
        $auth = new Authentication();
        $auth->addConnector(new BasicConnector('testuser', self::hash('testpassword')));

        $result = $auth->authenticate(['identifier' => 'testuser', 'password' => 'wrongpassword']);

        $this->assertFalse($result->isSuccess());
        $this->assertSame(AuthStatusEnum::INVALID_PASSWORD, $result->getStatus());
        $this->assertSame('basic', $result->getType());
    }

    public function testAuthenticateWithMultipleConnectors(): void
    {
        $auth = new Authentication();
        $auth->addConnector(new BasicConnector('user1', self::hash('pass1')));
        $auth->addConnector(new BasicConnector('user2', self::hash('testpassword')));

        $result = $auth->authenticate(['identifier' => 'user2', 'password' => 'testpassword']);

        $this->assertTrue($result->isSuccess());
        $this->assertSame(AuthStatusEnum::SUCCESS, $result->getStatus());
    }

    public function testAuthenticateWithPriority(): void
    {
        $auth = new Authentication();

        // same identifier, lower priority: tried first, it would answer INVALID_PASSWORD and stop the chain
        $auth->addConnector(new BasicConnector('user', self::hash('pass1')), 10);
        $auth->addConnector(new BasicConnector('user', self::hash('pass2')), 20);

        $result = $auth->authenticate(['identifier' => 'user', 'password' => 'pass2']);

        $this->assertTrue($result->isSuccess());
    }

    public function testAuthenticateReturnsUserNotFoundWhenNoConnectorsMatch(): void
    {
        $auth = new Authentication();
        $auth->addConnector(new BasicConnector('testuser', self::hash('testpass')));

        $this->assertGenericNotFound($auth->authenticate(['identifier' => 'unknown', 'password' => 'unknown']));
    }

    public function testSupportsReturnsTrueWhenConnectorSupports(): void
    {
        $auth = new Authentication();
        $auth->addConnector(new BasicConnector('test', self::hash('test')));

        $this->assertTrue($auth->supports(['identifier' => 'test', 'password' => 'test']));
    }

    public function testSupportsReturnsFalseWhenNoConnectors(): void
    {
        $auth = new Authentication();

        $this->assertFalse($auth->supports(['identifier' => 'test', 'password' => 'test']));
    }

    public function testSupportsIsTrueWhenAnyConnectorSupports(): void
    {
        $auth = (new Authentication())
            ->addConnector($this->connector('a', AuthenticationResult::success(), false))
            ->addConnector($this->connector('b', AuthenticationResult::success(), true));

        $this->assertTrue($auth->supports(['identifier' => 'test', 'password' => 'test']));
    }

    public function testSupportsIsFalseWhenNoConnectorSupports(): void
    {
        $auth = (new Authentication())
            ->addConnector($this->connector('a', AuthenticationResult::success(), false))
            ->addConnector($this->connector('b', AuthenticationResult::success(), false));

        $this->assertFalse($auth->supports(['identifier' => 'test', 'password' => 'test']));
    }
}
