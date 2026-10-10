<?php

declare(strict_types=1);

namespace JDZ\Authentication\Connector;

use JDZ\Authentication\AuthenticationResult;
use JDZ\Authentication\AuthStatusEnum;
use JDZ\Authentication\Connector\AbstractConnector;
use JDZ\Authentication\Contract\PasswordHasherInterface;

class ArrayConnector extends AbstractConnector
{
    protected string $name = 'array';

    /** @var array<string, array{password: string, id: int, email?: string, firstname?: string, lastname?: string}> */
    protected array $users = [];

    protected PasswordHasherInterface $passwordHasher;
    protected bool $plainPasswords = false;

    public function __construct(PasswordHasherInterface $passwordHasher, array $users = [], bool $plainPasswords = false)
    {
        $this->passwordHasher = $passwordHasher;
        $this->users = $users;
        $this->plainPasswords = $plainPasswords;
    }

    public function addUser(string $identifier, string $password, int $id, array $data = []): void
    {
        $this->users[$identifier] = array_merge($data, [
            'id' => $id,
            'password' => $this->plainPasswords ? $password : $this->passwordHasher->hash($password),
        ]);
    }

    public function authenticate(array $credentials): AuthenticationResult
    {
        $identifier = $credentials['identifier'];
        $password = $credentials['password'];

        if (!isset($this->users[$identifier])) {
            return $this->createUserNotFoundResult($password);
        }

        $user = $this->users[$identifier];

        if (!$this->verifyPassword($password, $user['password'])) {
            return $this->createFailureResult(AuthStatusEnum::INVALID_PASSWORD);
        }

        return $this->createSuccessResult($user['id'], [
            'email' => $user['email'] ?? $identifier,
            'firstname' => $user['firstname'] ?? '',
            'lastname' => $user['lastname'] ?? '',
        ]);
    }

    /**
     * Through the password hasher; in plain-password mode, $hashedPassword is the stored
     * plain password and the two are compared.
     */
    protected function verifyPassword(string $password, string $hashedPassword): bool
    {
        return $this->plainPasswords
            ? \hash_equals($hashedPassword, $password)
            : $this->passwordHasher->verify($password, $hashedPassword);
    }
}
