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
        $this->assertEquals(0, AuthStatusEnum::FAILURE->code());
        $this->assertEquals('Authentication failed', AuthStatusEnum::FAILURE->message());
    }

    public function testSuccessCase(): void
    {
        $this->assertEquals(1, AuthStatusEnum::SUCCESS->code());
        $this->assertEquals('', AuthStatusEnum::SUCCESS->message());
    }

    public function testEmptyUserCase(): void
    {
        $this->assertEquals(2, AuthStatusEnum::EMPTY_USER->code());
        $this->assertEquals('Missing username in credentials', AuthStatusEnum::EMPTY_USER->message());
    }

    public function testEmptyPassCase(): void
    {
        $this->assertEquals(3, AuthStatusEnum::EMPTY_PASS->code());
        $this->assertEquals('Missing password in credentials', AuthStatusEnum::EMPTY_PASS->message());
    }

    public function testBadCredentialsCase(): void
    {
        $this->assertEquals(4, AuthStatusEnum::BAD_CREDENTIALS->code());
        $this->assertEquals('Bad credentials', AuthStatusEnum::BAD_CREDENTIALS->message());
    }

    public function testBadPassCase(): void
    {
        $this->assertEquals(5, AuthStatusEnum::BAD_PASS->code());
        $this->assertEquals('Invalid password', AuthStatusEnum::BAD_PASS->message());
    }
}
