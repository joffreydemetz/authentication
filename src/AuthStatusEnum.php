<?php
declare(strict_types=1);

/**
 * @author    Joffrey Demetz <joffrey.demetz@gmail.com>
 * @license   MIT License; <https://opensource.org/licenses/MIT>
 */

namespace JDZ\Authentication;

enum AuthStatusEnum: int
{
    case FAILURE = 0;
    case SUCCESS = 1;
    case EMPTY_IDENTIFIER = 2;
    case EMPTY_PASSWORD = 3;
    case USER_NOT_FOUND = 4;
    case INVALID_PASSWORD = 5;
    case USER_BANNED = 6;
    case USER_NOT_CONFIRMED = 7;
    case ACCOUNT_LOCKED = 8;

    public function message(): string
    {
        return match ($this) {
            self::FAILURE => 'Authentication failed',
            self::SUCCESS => 'Authentication successful',
            self::EMPTY_IDENTIFIER => 'Please enter your email or username',
            self::EMPTY_PASSWORD => 'Please enter your password',
            self::USER_NOT_FOUND => 'Invalid credentials',
            self::INVALID_PASSWORD => 'Invalid password',
            self::USER_BANNED => 'Your account has been suspended',
            self::USER_NOT_CONFIRMED => 'Please confirm your email address',
            self::ACCOUNT_LOCKED => 'Account temporarily locked due to too many failed attempts',
        };
    }

    public function isSuccess(): bool
    {
        return $this === self::SUCCESS;
    }
}
