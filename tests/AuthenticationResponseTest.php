<?php

/**
 * @author    Joffrey Demetz <joffrey.demetz@gmail.com>
 * @license   MIT License; <https://opensource.org/licenses/MIT>
 */

namespace JDZ\Authentication\Tests;

use JDZ\Authentication\AuthenticationResponse;
use JDZ\Authentication\AuthStatusEnum;
use PHPUnit\Framework\TestCase;

class AuthenticationResponseTest extends TestCase
{
    public function testConstructorSetsDefaults(): void
    {
        $response = new AuthenticationResponse();

        $this->assertSame(AuthStatusEnum::FAILURE, $response->status);
        $this->assertSame('', $response->type);
        $this->assertSame('', $response->email);
        $this->assertSame('', $response->password);
        $this->assertSame('', $response->fullname);
        $this->assertSame('', $response->firstname);
        $this->assertSame('', $response->lastname);
        $this->assertSame('', $response->username);
        $this->assertSame('fr-FR', $response->language);
    }

    public function testToArrayConvertsPropertiesToArray(): void
    {
        $response = new AuthenticationResponse();
        $response->status = AuthStatusEnum::SUCCESS;
        $response->type = 'Basic';
        $response->username = 'testuser';
        $response->email = 'test@example.com';

        $array = $response->toArray();

        $this->assertIsArray($array);
        $this->assertEquals(1, $array['status']);
        $this->assertEquals('', $array['message']);
        $this->assertEquals('Basic', $array['type']);
        $this->assertEquals('testuser', $array['username']);
        $this->assertEquals('test@example.com', $array['email']);
    }

    public function testToArrayWithFailureStatus(): void
    {
        $response = new AuthenticationResponse();
        $response->status = AuthStatusEnum::BAD_PASS;

        $array = $response->toArray();

        $this->assertEquals(5, $array['status']);
        $this->assertEquals('Invalid password', $array['message']);
    }
}
