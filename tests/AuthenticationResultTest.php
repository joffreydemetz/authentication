<?php

/**
 * @author    Joffrey Demetz <joffrey.demetz@gmail.com>
 * @license   MIT License; <https://opensource.org/licenses/MIT>
 */

namespace JDZ\Authentication\Tests;

use JDZ\Authentication\AuthenticationResult;
use JDZ\Authentication\AuthStatusEnum;
use PHPUnit\Framework\Attributes\DataProvider;
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

    #[DataProvider('fullnames')]
    public function testFullname(string $firstname, string $lastname, string $email, string $identifier, string $expected): void
    {
        $result = (new AuthenticationResult())
            ->setFirstname($firstname)
            ->setLastname($lastname)
            ->setEmail($email)
            ->setIdentifier($identifier);

        $this->assertSame($expected, $result->getFullname());
    }

    public static function fullnames(): array
    {
        return [
            'first and last name' => ['John', 'Doe', 'john@example.com', 'jdoe', 'John Doe'],
            'first name only' => ['John', '', 'john@example.com', 'jdoe', 'John'],
            'last name only' => ['', 'Doe', 'john@example.com', 'jdoe', 'Doe'],
            'no name: the email' => ['', '', 'john@example.com', 'jdoe', 'john@example.com'],
            'no name nor email: the identifier' => ['', '', '', 'jdoe', 'jdoe'],
            'nothing at all' => ['', '', '', '', ''],
        ];
    }

    public function testCustomData(): void
    {
        $result = new AuthenticationResult();

        $result->set('language', 'fr-FR');
        $result->set('role', 'admin');

        $this->assertSame(['language' => 'fr-FR', 'role' => 'admin'], $result->getData());
        $this->assertSame('fr-FR', $result->get('language'));

        // setData() replaces everything set before
        $result->setData(['extra' => 'value']);

        $this->assertSame(['extra' => 'value'], $result->getData());
        $this->assertNull($result->get('language'));
        $this->assertSame('default', $result->get('nonexistent', 'default'));
    }

    public function testToArrayWithSuccess(): void
    {
        $result = AuthenticationResult::success(1)
            ->setIdentifier('test@example.com')
            ->setEmail('test@example.com')
            ->setUsername('testuser')
            ->setFirstname('John')
            ->setLastname('Doe')
            ->setType('basic')
            ->set('role', 'admin');

        $this->assertSame([
            'status' => 1,
            'message' => 'Authentication successful',
            'user_id' => 1,
            'identifier' => 'test@example.com',
            'email' => 'test@example.com',
            'username' => 'testuser',
            'firstname' => 'John',
            'lastname' => 'Doe',
            'fullname' => 'John Doe',
            'type' => 'basic',
            'data' => ['role' => 'admin'],
        ], $result->toArray());
    }

    public function testToArrayWithFailure(): void
    {
        $result = AuthenticationResult::failure(AuthStatusEnum::USER_BANNED);

        $this->assertSame([
            'status' => 6,
            'message' => 'Your account has been suspended',
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

    public function testMessageFallsBackToStatusMessage(): void
    {
        $result = AuthenticationResult::failure(AuthStatusEnum::USER_NOT_FOUND);

        $this->assertSame('Invalid credentials', $result->getMessage());
    }

    public function testAnEmptyMessageFallsBackToTheCurrentStatus(): void
    {
        $result = AuthenticationResult::failure(AuthStatusEnum::USER_BANNED, 'Banned until Monday');

        $result->setMessage('')->setStatus(AuthStatusEnum::SUCCESS);

        $this->assertTrue($result->isSuccess());
        $this->assertSame('Authentication successful', $result->getMessage());
    }
}
