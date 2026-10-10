<?php

declare(strict_types=1);

/**
 * @author    Joffrey Demetz <joffrey.demetz@gmail.com>
 * @license   MIT License; <https://opensource.org/licenses/MIT>
 */

namespace JDZ\Authentication;

use JDZ\Authentication\Contract\AuthenticationInterface;
use JDZ\Authentication\Contract\ConnectorInterface;

class Authentication implements AuthenticationInterface
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
        $normalizedCredentials = $this->normalize($credentials);
        $identifier = $normalizedCredentials['identifier'];

        // '' only: "0" is a valid identifier or password (empty() refused it)
        if ('' === $identifier) {
            return AuthenticationResult::failure(AuthStatusEnum::EMPTY_IDENTIFIER);
        }

        if ('' === $normalizedCredentials['password']) {
            return AuthenticationResult::failure(AuthStatusEnum::EMPTY_PASSWORD);
        }

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
        // the credentials the connectors would get from authenticate()
        $normalizedCredentials = $this->normalize($credentials);

        foreach ($this->connectors as $entry) {
            if ($entry['connector']->supports($normalizedCredentials)) {
                return true;
            }
        }

        return false;
    }

    /**
     * The identifier (identifier > email > username, trimmed) and the password as
     * strings. Visitors control these values: a number is read as text, anything
     * else (an array, a boolean) as missing — never a TypeError.
     *
     * @return array{identifier: string, password: string}
     */
    private function normalize(array $credentials): array
    {
        return [
            'identifier' => trim(self::credential($credentials['identifier'] ?? $credentials['email'] ?? $credentials['username'] ?? '')),
            'password' => self::credential($credentials['password'] ?? ''),
        ];
    }

    private static function credential(mixed $value): string
    {
        return is_string($value) || is_int($value) || is_float($value) ? (string) $value : '';
    }

    /**
     * @return ConnectorInterface[]
     */
    public function getConnectors(): array
    {
        return array_map(fn($entry) => $entry['connector'], $this->connectors);
    }
}
