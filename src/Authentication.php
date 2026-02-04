<?php
declare(strict_types=1);

/**
 * @author    Joffrey Demetz <joffrey.demetz@gmail.com>
 * @license   MIT License; <https://opensource.org/licenses/MIT>
 */

namespace JDZ\Authentication;

use JDZ\Authentication\Connector\ConnectorInterface;

class Authentication
{
    /** @var array{connector: ConnectorInterface, priority: int}[] */
    protected array $connectors = [];

    public function addConnector(ConnectorInterface $connector, int $priority = 0): static
    {
        $this->connectors[] = [
            'connector' => $connector,
            'priority' => $priority,
        ];

        usort($this->connectors, fn($a, $b) => $b['priority'] <=> $a['priority']);

        return $this;
    }

    public function authenticate(array $credentials): AuthenticationResult
    {
        $identifier = trim($credentials['identifier'] ?? $credentials['email'] ?? $credentials['username'] ?? '');
        $password = $credentials['password'] ?? '';

        if (empty($identifier)) {
            return AuthenticationResult::failure(AuthStatusEnum::EMPTY_IDENTIFIER);
        }

        if (empty($password)) {
            return AuthenticationResult::failure(AuthStatusEnum::EMPTY_PASSWORD);
        }

        $normalizedCredentials = [
            'identifier' => $identifier,
            'password' => $password,
        ];

        foreach ($this->connectors as $entry) {
            $connector = $entry['connector'];

            if (!$connector->supports($normalizedCredentials)) {
                continue;
            }

            $result = $connector->authenticate($normalizedCredentials);

            if ($result->isSuccess()) {
                $result->setIdentifier($identifier);
                return $result;
            }

            // If connector explicitly handled this (not just "not found"), stop here
            if ($result->getStatus() !== AuthStatusEnum::USER_NOT_FOUND) {
                return $result;
            }
        }

        return AuthenticationResult::failure(
            AuthStatusEnum::USER_NOT_FOUND,
            'Invalid credentials'
        );
    }

    public function supports(array $credentials): bool
    {
        foreach ($this->connectors as $entry) {
            if ($entry['connector']->supports($credentials)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return ConnectorInterface[]
     */
    public function getConnectors(): array
    {
        return array_map(fn($entry) => $entry['connector'], $this->connectors);
    }
}
