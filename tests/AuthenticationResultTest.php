<?php

/**
 * @author    Joffrey Demetz <joffrey.demetz@gmail.com>
 * @license   MIT License; <https://opensource.org/licenses/MIT>
 */

namespace JDZ\Authentication\Tests;

use JDZ\Authentication\AuthenticationResult;
use JDZ\Authentication\AuthStatusEnum;
use PHPUnit\Framework\TestCase;

class AuthenticationResultTest extends TestCase
{
    public function testConstructorSetsDefaultStatus(): void
    {
        $result = new AuthenticationResult();

        $this->assertSame(AuthStatusEnum::FAILURE, $result->getStatus());
        $this->assertFalse($result->isSuccess());
    }

    public function testSuccessFactoryMethod(): void
    {
        $result = AuthenticationResult::success(123);

        $this->assertTrue($result->isSuccess());
        $this->assertSame(AuthStatusEnum::SUCCESS, $result->getStatus());
        $this->assertSame(123, $result->getUserId());
    }

    public function testFailureFactoryMethod(): void
    {
        $result = AuthenticationResult::failure(AuthStatusEnum::USER_NOT_FOUND, 'Custom message');

        $this->assertFalse($result->isSuccess());
        $this->assertSame(AuthStatusEnum::USER_NOT_FOUND, $result->getStatus());
        $this->assertSame('Custom message', $result->getMessage());
    }

    public function testSettersAndGetters(): void
    {
        $result = new AuthenticationResult();

        $result->setUserId(42);
        $result->setIdentifier('test@example.com');
        $result->setEmail('test@example.com');
        $result->setUsername('testuser');
        $result->setFirstname('John');
        $result->setLastname('Doe');
        $result->setType('basic');

        $this->assertSame(42, $result->getUserId());
        $this->assertSame('test@example.com', $result->getIdentifier());
        $this->assertSame('test@example.com', $result->getEmail());
        $this->assertSame('testuser', $result->getUsername());
        $this->assertSame('John', $result->getFirstname());
        $this->assertSame('Doe', $result->getLastname());
        $this->assertSame('basic', $result->getType());
    }

    public function testGetFullname(): void
    {
        $result = new AuthenticationResult();
        $result->setFirstname('John');
        $result->setLastname('Doe');

        $this->assertSame('John Doe', $result->getFullname());
    }

    public function testGetFullnameFallsBackToEmail(): void
    {
        $result = new AuthenticationResult();
        $result->setEmail('test@example.com');

        $this->assertSame('test@example.com', $result->getFullname());
    }

    public function testGetFullnameFallsBackToIdentifier(): void
    {
        $result = new AuthenticationResult();
        $result->setIdentifier('user123');

        $this->assertSame('user123', $result->getFullname());
    }

    public function testCustomData(): void
    {
        $result = new AuthenticationResult();

        // Test set() adds individual values
        $result->set('language', 'fr-FR');
        $result->set('role', 'admin');

        $this->assertSame('fr-FR', $result->get('language'));
        $this->assertSame('admin', $result->get('role'));

        // Test setData() replaces all data
        $result->setData(['extra' => 'value']);

        $this->assertSame('value', $result->get('extra'));
        $this->assertNull($result->get('language')); // was replaced
        $this->assertNull($result->get('role'));     // was replaced

        // Test default value
        $this->assertNull($result->get('nonexistent'));
        $this->assertSame('default', $result->get('nonexistent', 'default'));
    }

    public function testToArrayWithSuccess(): void
    {
        $result = AuthenticationResult::success(1);
        $result->setIdentifier('test@example.com');
        $result->setEmail('test@example.com');
        $result->setUsername('testuser');
        $result->setFirstname('John');
        $result->setLastname('Doe');
        $result->setType('basic');

        $array = $result->toArray();

        $this->assertIsArray($array);
        $this->assertEquals(1, $array['status']);
        $this->assertEquals('Authentication successful', $array['message']);
        $this->assertEquals(1, $array['user_id']);
        $this->assertEquals('test@example.com', $array['identifier']);
        $this->assertEquals('test@example.com', $array['email']);
        $this->assertEquals('testuser', $array['username']);
        $this->assertEquals('John', $array['firstname']);
        $this->assertEquals('Doe', $array['lastname']);
        $this->assertEquals('John Doe', $array['fullname']);
        $this->assertEquals('basic', $array['type']);
    }

    public function testToArrayWithFailure(): void
    {
        $result = AuthenticationResult::failure(AuthStatusEnum::INVALID_PASSWORD);

        $array = $result->toArray();

        $this->assertEquals(5, $array['status']);
        $this->assertEquals('Invalid password', $array['message']);
    }

    public function testMessageFallsBackToStatusMessage(): void
    {
        $result = AuthenticationResult::failure(AuthStatusEnum::USER_NOT_FOUND);

        $this->assertSame('Invalid credentials', $result->getMessage());
    }

    public function testFluentInterface(): void
    {
        $result = new AuthenticationResult();

        $this->assertSame($result, $result->setStatus(AuthStatusEnum::SUCCESS));
        $this->assertSame($result, $result->setUserId(1));
        $this->assertSame($result, $result->setIdentifier('test'));
        $this->assertSame($result, $result->setEmail('test@example.com'));
        $this->assertSame($result, $result->setUsername('testuser'));
        $this->assertSame($result, $result->setFirstname('John'));
        $this->assertSame($result, $result->setLastname('Doe'));
        $this->assertSame($result, $result->setType('basic'));
        $this->assertSame($result, $result->setMessage('Custom'));
        $this->assertSame($result, $result->setData([]));
        $this->assertSame($result, $result->set('key', 'value'));
    }
}
