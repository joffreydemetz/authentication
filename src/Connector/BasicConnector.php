<?php
declare(strict_types=1);

/**
 * @author    Joffrey Demetz <joffrey.demetz@gmail.com>
 * @license   MIT License; <https://opensource.org/licenses/MIT>
 */

namespace JDZ\Authentication\Connector;

use JDZ\Authentication\AuthenticationResult;
use JDZ\Authentication\AuthStatusEnum;

class BasicConnector extends AbstractConnector
{
    protected string $name = 'basic';
    protected string $identifier;
    protected string $password;

    public function __construct(string $identifier, string $password)
    {
        if ($identifier === '' || $password === '') {
            throw new \InvalidArgumentException('Identifier and password must be provided.');
        }

        $this->identifier = $identifier;

        // Hash the password if not already hashed
        if (!password_get_info($password)['algo']) {
            $this->password = password_hash($password, PASSWORD_DEFAULT);
        } else {
            $this->password = $password;
        }
    }

    public function authenticate(array $credentials): AuthenticationResult
    {
        $identifier = $credentials['identifier'] ?? '';
        $password = $credentials['password'] ?? '';

        if ($identifier !== $this->identifier) {
            return $this->createFailureResult(AuthStatusEnum::USER_NOT_FOUND);
        }

        if (!$this->verifyPassword($password, $this->password)) {
            return $this->createFailureResult(AuthStatusEnum::INVALID_PASSWORD);
        }

        return $this->createSuccessResult(null, [
            'username' => $this->identifier,
        ]);
    }
}
