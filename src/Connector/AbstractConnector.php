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
