<?php

/**
 * @author    Joffrey Demetz <joffrey.demetz@gmail.com>
 * @license   MIT License; <https://opensource.org/licenses/MIT>
 */

namespace JDZ\Authentication\Tests;

use JDZ\Authentication\AuthStatusEnum;
use PHPUnit\Framework\TestCase;

class AuthStatusEnumTest extends TestCase
{
    public function testFailureCase(): void
    {
        $this->assertEquals(0, AuthStatusEnum::FAILURE->value);
        $this->assertEquals('Authentication failed', AuthStatusEnum::FAILURE->message());
        $this->assertFalse(AuthStatusEnum::FAILURE->isSuccess());
    }

    public function testSuccessCase(): void
    {
        $this->assertEquals(1, AuthStatusEnum::SUCCESS->value);
        $this->assertEquals('Authentication successful', AuthStatusEnum::SUCCESS->message());
        $this->assertTrue(AuthStatusEnum::SUCCESS->isSuccess());
    }

    public function testEmptyIdentifierCase(): void
    {
        $this->assertEquals(2, AuthStatusEnum::EMPTY_IDENTIFIER->value);
        $this->assertEquals('Please enter your email or username', AuthStatusEnum::EMPTY_IDENTIFIER->message());
        $this->assertFalse(AuthStatusEnum::EMPTY_IDENTIFIER->isSuccess());
    }

    public function testEmptyPasswordCase(): void
    {
        $this->assertEquals(3, AuthStatusEnum::EMPTY_PASSWORD->value);
        $this->assertEquals('Please enter your password', AuthStatusEnum::EMPTY_PASSWORD->message());
        $this->assertFalse(AuthStatusEnum::EMPTY_PASSWORD->isSuccess());
    }

    public function testUserNotFoundCase(): void
    {
        $this->assertEquals(4, AuthStatusEnum::USER_NOT_FOUND->value);
        $this->assertEquals('Invalid credentials', AuthStatusEnum::USER_NOT_FOUND->message());
        $this->assertFalse(AuthStatusEnum::USER_NOT_FOUND->isSuccess());
    }

    public function testInvalidPasswordCase(): void
    {
        $this->assertEquals(5, AuthStatusEnum::INVALID_PASSWORD->value);
        $this->assertEquals('Invalid password', AuthStatusEnum::INVALID_PASSWORD->message());
        $this->assertFalse(AuthStatusEnum::INVALID_PASSWORD->isSuccess());
    }

    public function testUserBannedCase(): void
    {
        $this->assertEquals(6, AuthStatusEnum::USER_BANNED->value);
        $this->assertEquals('Your account has been suspended', AuthStatusEnum::USER_BANNED->message());
        $this->assertFalse(AuthStatusEnum::USER_BANNED->isSuccess());
    }

    public function testUserNotConfirmedCase(): void
    {
        $this->assertEquals(7, AuthStatusEnum::USER_NOT_CONFIRMED->value);
        $this->assertEquals('Please confirm your email address', AuthStatusEnum::USER_NOT_CONFIRMED->message());
        $this->assertFalse(AuthStatusEnum::USER_NOT_CONFIRMED->isSuccess());
    }

    public function testAccountLockedCase(): void
    {
        $this->assertEquals(8, AuthStatusEnum::ACCOUNT_LOCKED->value);
        $this->assertEquals('Account temporarily locked due to too many failed attempts', AuthStatusEnum::ACCOUNT_LOCKED->message());
        $this->assertFalse(AuthStatusEnum::ACCOUNT_LOCKED->isSuccess());
    }
}
