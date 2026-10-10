<?php

declare(strict_types=1);

/**
 * @author    Joffrey Demetz <joffrey.demetz@gmail.com>
 * @license   MIT License; <https://opensource.org/licenses/MIT>
 */

namespace JDZ\Authentication\Connector;

use JDZ\Authentication\AuthenticationResult;
use JDZ\Authentication\AuthStatusEnum;
use JDZ\Authentication\Contract\ConnectorInterface;

abstract class AbstractConnector implements ConnectorInterface
{
    /**
     * A bcrypt hash (cost 12, PHP 8.4's PASSWORD_DEFAULT cost) of a random string nobody
     * knows. An unknown user's password is still verified against it, so looking up a
     * missing account costs the same hashing time as a wrong password.
     * A connector whose stored hashes use another algorithm or cost may override it.
     */
    protected const DUMMY_HASH = '$2y$12$S2sSWdKEG8AhsSokh1lkNOQ7G6ORDGR6ExqbokzUIyOHbTg3gOJUa';

    protected string $name = '';

    public function getName(): string
    {
        return $this->name;
    }

    public function supports(array $credentials): bool
    {
        return isset($credentials['identifier']) && isset($credentials['password']);
    }

    protected function verifyPassword(string $password, string $hashedPassword): bool
    {
        return password_verify($password, $hashedPassword);
    }

    /**
     * The USER_NOT_FOUND result, after the same verifyPassword() a known user goes through
     * (against DUMMY_HASH, its outcome ignored): the response time does not tell whether
     * the account exists.
     */
    protected function createUserNotFoundResult(string $password): AuthenticationResult
    {
        $this->verifyPassword($password, static::DUMMY_HASH);

        return $this->createFailureResult(AuthStatusEnum::USER_NOT_FOUND);
    }

    protected function createSuccessResult(?int $userId = null, array $userData = []): AuthenticationResult
    {
        $result = AuthenticationResult::success($userId);
        $result->setType($this->name);

        if (isset($userData['email'])) {
            $result->setEmail($userData['email']);
        }
        if (isset($userData['username'])) {
            $result->setUsername($userData['username']);
        }
        if (isset($userData['firstname'])) {
            $result->setFirstname($userData['firstname']);
        }
        if (isset($userData['lastname'])) {
            $result->setLastname($userData['lastname']);
        }

        return $result;
    }

    protected function createFailureResult(AuthStatusEnum $status, string $message = ''): AuthenticationResult
    {
        $result = AuthenticationResult::failure($status, $message);
        $result->setType($this->name);
        return $result;
    }
}
