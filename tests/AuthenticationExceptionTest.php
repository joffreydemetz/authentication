<?php

/**
 * @author    Joffrey Demetz <joffrey.demetz@gmail.com>
 * @license   MIT License; <https://opensource.org/licenses/MIT>
 */

namespace JDZ\Authentication\Tests;

use JDZ\Authentication\AuthenticationException;
use JDZ\Authentication\AuthStatusEnum;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class AuthenticationExceptionTest extends TestCase
{
    #[DataProvider('statuses')]
    public function testMessageAndCodeDefaultToTheStatus(AuthStatusEnum $status, string $message, int $code): void
    {
        $exception = new AuthenticationException($status);

        $this->assertSame($status, $exception->getStatus());
        $this->assertSame($message, $exception->getMessage());
        $this->assertSame($code, $exception->getCode());
        $this->assertNull($exception->getPrevious());
    }

    #[DataProvider('statuses')]
    public function testFromStatusBuildsTheDefaultException(AuthStatusEnum $status, string $message, int $code): void
    {
        $exception = AuthenticationException::fromStatus($status);

        $this->assertSame($status, $exception->getStatus());
        $this->assertSame($message, $exception->getMessage());
        $this->assertSame($code, $exception->getCode());
    }

    public static function statuses(): array
    {
        return [
            'failure: code 0' => [AuthStatusEnum::FAILURE, 'Authentication failed', 0],
            'empty password' => [AuthStatusEnum::EMPTY_PASSWORD, 'Please enter your password', 3],
            'user banned' => [AuthStatusEnum::USER_BANNED, 'Your account has been suspended', 6],
            'account locked' => [AuthStatusEnum::ACCOUNT_LOCKED, 'Account temporarily locked due to too many failed attempts', 8],
        ];
    }

    public function testAnExplicitMessageCodeAndPreviousWin(): void
    {
        $previous = new \RuntimeException('lookup failed');

        $exception = new AuthenticationException(AuthStatusEnum::USER_BANNED, 'ERROR_AUTH_LOGIN_BANNED', 403, $previous);

        $this->assertSame(AuthStatusEnum::USER_BANNED, $exception->getStatus());
        $this->assertSame('ERROR_AUTH_LOGIN_BANNED', $exception->getMessage());
        $this->assertSame(403, $exception->getCode());
        $this->assertSame($previous, $exception->getPrevious());
    }

    public function testAnEmptyMessageAndAZeroCodeFallBackToTheStatus(): void
    {
        $exception = new AuthenticationException(AuthStatusEnum::USER_NOT_CONFIRMED, '', 0);

        $this->assertSame('Please confirm your email address', $exception->getMessage());
        $this->assertSame(7, $exception->getCode());
    }
}
