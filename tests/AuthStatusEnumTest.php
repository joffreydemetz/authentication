<?php

/**
 * @author    Joffrey Demetz <joffrey.demetz@gmail.com>
 * @license   MIT License; <https://opensource.org/licenses/MIT>
 */

namespace JDZ\Authentication\Tests;

use JDZ\Authentication\AuthStatusEnum;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class AuthStatusEnumTest extends TestCase
{
    /**
     * The codes leave the package (`AuthenticationResult::toArray()['status']`,
     * `AuthenticationException::getCode()`): renumbering or adding a case is a contract change.
     */
    public function testStatusCodesAreStable(): void
    {
        $codes = [];
        foreach (AuthStatusEnum::cases() as $status) {
            $codes[$status->name] = $status->value;
        }

        $this->assertSame([
            'FAILURE' => 0,
            'SUCCESS' => 1,
            'EMPTY_IDENTIFIER' => 2,
            'EMPTY_PASSWORD' => 3,
            'USER_NOT_FOUND' => 4,
            'INVALID_PASSWORD' => 5,
            'USER_BANNED' => 6,
            'USER_NOT_CONFIRMED' => 7,
            'ACCOUNT_LOCKED' => 8,
        ], $codes);
    }

    public function testOnlySuccessIsASuccess(): void
    {
        $successes = array_values(array_filter(
            AuthStatusEnum::cases(),
            static fn(AuthStatusEnum $status): bool => $status->isSuccess()
        ));

        $this->assertSame([AuthStatusEnum::SUCCESS], $successes);
    }

    #[DataProvider('messages')]
    public function testMessage(AuthStatusEnum $status, string $message): void
    {
        $this->assertSame($message, $status->message());
    }

    /**
     * The message a visitor reads must not tell whether the account exists; the two
     * statuses stay apart (the connector chain falls through on USER_NOT_FOUND only).
     */
    public function testAWrongPasswordReadsLikeAnUnknownUser(): void
    {
        $this->assertSame(AuthStatusEnum::USER_NOT_FOUND->message(), AuthStatusEnum::INVALID_PASSWORD->message());
        $this->assertNotSame(AuthStatusEnum::USER_NOT_FOUND, AuthStatusEnum::INVALID_PASSWORD);
    }

    public static function messages(): array
    {
        return [
            'failure' => [AuthStatusEnum::FAILURE, 'Authentication failed'],
            'success' => [AuthStatusEnum::SUCCESS, 'Authentication successful'],
            'empty identifier' => [AuthStatusEnum::EMPTY_IDENTIFIER, 'Please enter your email or username'],
            'empty password' => [AuthStatusEnum::EMPTY_PASSWORD, 'Please enter your password'],
            'user not found' => [AuthStatusEnum::USER_NOT_FOUND, 'Invalid credentials'],
            'invalid password: the same words as an unknown user' => [AuthStatusEnum::INVALID_PASSWORD, 'Invalid credentials'],
            'user banned' => [AuthStatusEnum::USER_BANNED, 'Your account has been suspended'],
            'user not confirmed' => [AuthStatusEnum::USER_NOT_CONFIRMED, 'Please confirm your email address'],
            'account locked' => [AuthStatusEnum::ACCOUNT_LOCKED, 'Account temporarily locked due to too many failed attempts'],
        ];
    }
}
