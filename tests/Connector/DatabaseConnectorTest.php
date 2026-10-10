<?php

/**
 * @author    Joffrey Demetz <joffrey.demetz@gmail.com>
 * @license   MIT License; <https://opensource.org/licenses/MIT>
 */

namespace JDZ\Authentication\Tests\Connector;

use JDZ\Authentication\Authentication;
use JDZ\Authentication\AuthStatusEnum;
use JDZ\Authentication\Connector\DatabaseConnector;
use JDZ\Database\Contract\DatabaseInterface;
use JDZ\Database\Contract\QueryInterface;
use JDZ\Database\ParamType;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class DatabaseConnectorTest extends TestCase
{
    /** @var list<QueryInterface> every query handed to setQuery(), in order */
    private array $queries = [];

    private static ?string $hash = null;

    /**
     * The stored hash of "correctpass" (bcrypt cost 4: password_verify() reads the cost from the hash).
     */
    private static function hash(): string
    {
        return self::$hash ??= password_hash('correctpass', PASSWORD_BCRYPT, ['cost' => 4]);
    }

    /**
     * A user row as loadAssoc() returns it, $columns replacing or adding columns.
     */
    private static function row(array $columns = []): array
    {
        return array_replace([
            'id' => 1,
            'email' => 'test@example.com',
            'password' => self::hash(),
        ], $columns);
    }

    private static function sql(string ...$lines): string
    {
        return implode(PHP_EOL, $lines);
    }

    private static function defaultSql(): string
    {
        return self::sql('SELECT id, email, password', 'FROM #__user', 'WHERE email = :identifier');
    }

    /**
     * A query-capturing database: setQuery() records each query, loadAssoc() answers the canned $row.
     */
    private function database(?array $row = null): DatabaseInterface
    {
        $this->queries = [];

        $database = $this->createStub(DatabaseInterface::class);
        $database->method('setQuery')->willReturnCallback(function (QueryInterface $query): QueryInterface {
            $this->queries[] = $query;
            return $query;
        });
        $database->method('loadAssoc')->willReturn($row);

        return $database;
    }

    private function assertSingleQuery(string $sql, string $identifier): void
    {
        $this->assertCount(1, $this->queries);
        $this->assertSame($sql, (string) $this->queries[0]);

        $bounded = $this->queries[0]->getBounded();
        $this->assertSame([':identifier'], array_keys($bounded));
        $this->assertSame($identifier, $bounded[':identifier']->value);
        $this->assertSame(ParamType::STR->value, $bounded[':identifier']->dataType);
    }

    public function testDefaultQuery(): void
    {
        $connector = new DatabaseConnector($this->database());

        $connector->authenticate(['identifier' => 'test@example.com', 'password' => 'correctpass']);

        $this->assertSingleQuery(self::defaultSql(), 'test@example.com');
    }

    #[DataProvider('setterOptions')]
    public function testSettersShapeTheQuery(\Closure $configure, string $sql): void
    {
        $connector = new DatabaseConnector($this->database());
        $configure($connector);

        $connector->authenticate(['identifier' => 'test@example.com', 'password' => 'correctpass']);

        $this->assertSingleQuery($sql, 'test@example.com');
    }

    public static function setterOptions(): array
    {
        return [
            'setTable' => [
                static fn(DatabaseConnector $c) => $c->setTable('members'),
                self::sql('SELECT id, email, password', 'FROM #__members', 'WHERE email = :identifier'),
            ],
            'setIdentifierColumn' => [
                static fn(DatabaseConnector $c) => $c->setIdentifierColumn('username'),
                self::sql('SELECT id, username, password', 'FROM #__user', 'WHERE username = :identifier'),
            ],
            'setPasswordColumn' => [
                static fn(DatabaseConnector $c) => $c->setPasswordColumn('pass_hash'),
                self::sql('SELECT id, email, pass_hash', 'FROM #__user', 'WHERE email = :identifier'),
            ],
            'setExtraColumns' => [
                static fn(DatabaseConnector $c) => $c->setExtraColumns(['username', 'firstname', 'lastname']),
                self::sql('SELECT id, email, password, username, firstname, lastname', 'FROM #__user', 'WHERE email = :identifier'),
            ],
            'setExtraColumns: a column already selected is selected once' => [
                static fn(DatabaseConnector $c) => $c->setExtraColumns(['email', 'id', 'firstname', 'password']),
                self::sql('SELECT id, email, password, firstname', 'FROM #__user', 'WHERE email = :identifier'),
            ],
            'setExtraColumns: the last call replaces the list' => [
                static fn(DatabaseConnector $c) => $c->setExtraColumns(['username'])->setExtraColumns(['firstname']),
                self::sql('SELECT id, email, password, firstname', 'FROM #__user', 'WHERE email = :identifier'),
            ],
            'setCheckBanned selects the banned column' => [
                static fn(DatabaseConnector $c) => $c->setCheckBanned(true),
                self::sql('SELECT id, email, password, banned', 'FROM #__user', 'WHERE email = :identifier'),
            ],
            'setCheckConfirmed selects the confirmed column' => [
                static fn(DatabaseConnector $c) => $c->setCheckConfirmed(true),
                self::sql('SELECT id, email, password, confirmed', 'FROM #__user', 'WHERE email = :identifier'),
            ],
            'both checks: banned before confirmed' => [
                static fn(DatabaseConnector $c) => $c->setCheckConfirmed(true)->setCheckBanned(true),
                self::sql('SELECT id, email, password, banned, confirmed', 'FROM #__user', 'WHERE email = :identifier'),
            ],
            'checks switched back off' => [
                static fn(DatabaseConnector $c) => $c->setCheckBanned(true)->setCheckConfirmed(true)->setCheckBanned(false)->setCheckConfirmed(false),
                self::defaultSql(),
            ],
            'every setter chained' => [
                static fn(DatabaseConnector $c) => $c
                    ->setTable('members')
                    ->setIdentifierColumn('login')
                    ->setPasswordColumn('hash')
                    ->setExtraColumns(['firstname', 'lastname'])
                    ->setCheckBanned(true)
                    ->setCheckConfirmed(true),
                self::sql('SELECT id, login, hash, firstname, lastname, banned, confirmed', 'FROM #__members', 'WHERE login = :identifier'),
            ],
        ];
    }

    #[DataProvider('constructorOptions')]
    public function testConstructorOptionsShapeTheQuery(array $options, string $sql): void
    {
        $connector = new DatabaseConnector($this->database(), $options);

        $connector->authenticate(['identifier' => 'test@example.com', 'password' => 'correctpass']);

        $this->assertSingleQuery($sql, 'test@example.com');
    }

    public static function constructorOptions(): array
    {
        return [
            'table' => [
                ['table' => 'members'],
                self::sql('SELECT id, email, password', 'FROM #__members', 'WHERE email = :identifier'),
            ],
            'identifierColumn' => [
                ['identifierColumn' => 'username'],
                self::sql('SELECT id, username, password', 'FROM #__user', 'WHERE username = :identifier'),
            ],
            'passwordColumn' => [
                ['passwordColumn' => 'pass_hash'],
                self::sql('SELECT id, email, pass_hash', 'FROM #__user', 'WHERE email = :identifier'),
            ],
            'extraColumns' => [
                ['extraColumns' => ['username', 'firstname']],
                self::sql('SELECT id, email, password, username, firstname', 'FROM #__user', 'WHERE email = :identifier'),
            ],
            'checkBanned with bannedColumn' => [
                ['checkBanned' => true, 'bannedColumn' => 'is_blocked'],
                self::sql('SELECT id, email, password, is_blocked', 'FROM #__user', 'WHERE email = :identifier'),
            ],
            'checkConfirmed with confirmedColumn' => [
                ['checkConfirmed' => true, 'confirmedColumn' => 'email_verified'],
                self::sql('SELECT id, email, password, email_verified', 'FROM #__user', 'WHERE email = :identifier'),
            ],
            'banned/confirmed columns without their check are not selected' => [
                ['bannedColumn' => 'is_blocked', 'confirmedColumn' => 'email_verified'],
                self::defaultSql(),
            ],
            'unknown keys are ignored' => [
                ['tbl_name' => 'members', 'tbl_username_column' => 'username'],
                self::defaultSql(),
            ],
        ];
    }

    public function testTheIdentifierIsBoundVerbatimNeverConcatenated(): void
    {
        $identifier = " O'Brien@Example.com' OR '1'='1 ";
        $connector = new DatabaseConnector($this->database());

        $connector->authenticate(['identifier' => $identifier, 'password' => 'correctpass']);

        $this->assertSingleQuery(self::defaultSql(), $identifier);
    }

    public function testThroughAuthenticationTheTrimmedIdentifierIsBound(): void
    {
        $auth = (new Authentication())->addConnector(new DatabaseConnector($this->database()));

        $auth->authenticate(['email' => "  test@example.com\n", 'password' => 'correctpass']);

        $this->assertSingleQuery(self::defaultSql(), 'test@example.com');
    }

    #[DataProvider('outcomes')]
    public function testOutcome(array $options, ?array $row, string $password, AuthStatusEnum $status): void
    {
        $connector = new DatabaseConnector($this->database($row), $options);

        $result = $connector->authenticate(['identifier' => 'test@example.com', 'password' => $password]);

        $this->assertSame($status, $result->getStatus());
        $this->assertSame('database', $result->getType());
    }

    public static function outcomes(): array
    {
        $banned = ['checkBanned' => true];
        $confirmed = ['checkConfirmed' => true];

        return [
            'unknown user' => [[], null, 'correctpass', AuthStatusEnum::USER_NOT_FOUND],
            'empty row' => [[], [], 'correctpass', AuthStatusEnum::USER_NOT_FOUND],
            'correct password' => [[], self::row(), 'correctpass', AuthStatusEnum::SUCCESS],
            'wrong password' => [[], self::row(), 'wrongpass', AuthStatusEnum::INVALID_PASSWORD],
            'a plaintext stored password never matches' => [[], self::row(['password' => 'correctpass']), 'correctpass', AuthStatusEnum::INVALID_PASSWORD],
            'null stored password' => [[], self::row(['password' => null]), 'correctpass', AuthStatusEnum::INVALID_PASSWORD],
            'custom password column' => [['passwordColumn' => 'pass_hash'], self::row(['password' => null, 'pass_hash' => self::hash()]), 'correctpass', AuthStatusEnum::SUCCESS],
            'custom password column missing from the row' => [['passwordColumn' => 'pass_hash'], self::row(), 'correctpass', AuthStatusEnum::INVALID_PASSWORD],

            'banned: 1' => [$banned, self::row(['banned' => 1]), 'correctpass', AuthStatusEnum::USER_BANNED],
            'banned: "1"' => [$banned, self::row(['banned' => '1']), 'correctpass', AuthStatusEnum::USER_BANNED],
            'not banned: 0' => [$banned, self::row(['banned' => 0]), 'correctpass', AuthStatusEnum::SUCCESS],
            'not banned: "0"' => [$banned, self::row(['banned' => '0']), 'correctpass', AuthStatusEnum::SUCCESS],
            'not banned: null' => [$banned, self::row(['banned' => null]), 'correctpass', AuthStatusEnum::SUCCESS],
            'banned with a wrong password: the password is reported' => [$banned, self::row(['banned' => 1]), 'wrongpass', AuthStatusEnum::INVALID_PASSWORD],
            'banned is ignored when the check is off' => [[], self::row(['banned' => 1]), 'correctpass', AuthStatusEnum::SUCCESS],
            'banned is read from bannedColumn' => [$banned + ['bannedColumn' => 'is_blocked'], self::row(['is_blocked' => 1, 'banned' => 0]), 'correctpass', AuthStatusEnum::USER_BANNED],
            'bannedColumn replaces the default column' => [$banned + ['bannedColumn' => 'is_blocked'], self::row(['is_blocked' => 0, 'banned' => 1]), 'correctpass', AuthStatusEnum::SUCCESS],

            'unconfirmed: 0' => [$confirmed, self::row(['confirmed' => 0]), 'correctpass', AuthStatusEnum::USER_NOT_CONFIRMED],
            'unconfirmed: "0"' => [$confirmed, self::row(['confirmed' => '0']), 'correctpass', AuthStatusEnum::USER_NOT_CONFIRMED],
            'unconfirmed: null' => [$confirmed, self::row(['confirmed' => null]), 'correctpass', AuthStatusEnum::USER_NOT_CONFIRMED],
            'confirmed: 1' => [$confirmed, self::row(['confirmed' => 1]), 'correctpass', AuthStatusEnum::SUCCESS],
            'unconfirmed with a wrong password: the password is reported' => [$confirmed, self::row(['confirmed' => 0]), 'wrongpass', AuthStatusEnum::INVALID_PASSWORD],
            'unconfirmed is ignored when the check is off' => [[], self::row(['confirmed' => 0]), 'correctpass', AuthStatusEnum::SUCCESS],
            'confirmed is read from confirmedColumn' => [$confirmed + ['confirmedColumn' => 'email_verified'], self::row(['email_verified' => 0, 'confirmed' => 1]), 'correctpass', AuthStatusEnum::USER_NOT_CONFIRMED],

            'banned and unconfirmed: banned is reported' => [$banned + $confirmed, self::row(['banned' => 1, 'confirmed' => 0]), 'correctpass', AuthStatusEnum::USER_BANNED],
            'neither banned nor unconfirmed' => [$banned + $confirmed, self::row(['banned' => 0, 'confirmed' => 1]), 'correctpass', AuthStatusEnum::SUCCESS],
        ];
    }

    public function testSuccessResult(): void
    {
        $row = self::row([
            'id' => '42',
            'username' => 'testuser',
            'firstname' => 'Test',
            'lastname' => 'User',
            'banned' => '0',
            'confirmed' => '1',
        ]);
        $connector = (new DatabaseConnector($this->database($row)))
            ->setExtraColumns(['username', 'firstname', 'lastname'])
            ->setCheckBanned(true)
            ->setCheckConfirmed(true);

        $result = $connector->authenticate(['identifier' => 'test@example.com', 'password' => 'correctpass']);

        // the id is cast to int; the hash and the flags stay out of the result
        $this->assertSame([
            'status' => 1,
            'message' => 'Authentication successful',
            'user_id' => 42,
            'identifier' => '',
            'email' => 'test@example.com',
            'username' => 'testuser',
            'firstname' => 'Test',
            'lastname' => 'User',
            'fullname' => 'Test User',
            'type' => 'database',
            'data' => [],
        ], $result->toArray());
    }

    public function testAuthenticateWithUserWithoutId(): void
    {
        $row = self::row();
        unset($row['id']);
        $connector = new DatabaseConnector($this->database($row));

        $result = $connector->authenticate(['identifier' => 'test@example.com', 'password' => 'correctpass']);

        $this->assertTrue($result->isSuccess());
        $this->assertNull($result->getUserId());
    }
}
