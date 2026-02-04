<?php

declare(strict_types=1);

/**
 * @author    Joffrey Demetz <joffrey.demetz@gmail.com>
 * @license   MIT License; <https://opensource.org/licenses/MIT>
 */

namespace JDZ\Authentication\Connector;

use JDZ\Authentication\AuthenticationResult;
use JDZ\Authentication\AuthStatusEnum;
use JDZ\Database\DatabaseInterface;
use JDZ\Database\Query\SelectQuery;

class DatabaseConnector extends AbstractConnector
{
    protected string $name = 'database';

    protected DatabaseInterface $database;

    protected string $table = 'user';
    protected string $identifierColumn = 'email';
    protected string $passwordColumn = 'password';

    public function __construct(DatabaseInterface $database, array $options = [])
    {
        $this->database = $database;

        foreach ($options as $key => $value) {
            if (property_exists($this, $key)) {
                $this->$key = $value;
            }
        }
    }

    public function authenticate(array $credentials): AuthenticationResult
    {
        $identifier = $credentials['identifier'] ?? '';
        $password = $credentials['password'] ?? '';

        $user = $this->findUser($identifier);

        if ($user === null) {
            return $this->createFailureResult(AuthStatusEnum::USER_NOT_FOUND);
        }

        $hashedPassword = $user[$this->passwordColumn] ?? '';

        if (!$this->verifyPassword($password, $hashedPassword)) {
            return $this->createFailureResult(AuthStatusEnum::INVALID_PASSWORD);
        }

        return $this->createSuccessResult(
            isset($user['id']) ? (int) $user['id'] : null,
            $user
        );
    }

    protected function findUser(string $identifier): ?array
    {
        $columns = [
            'id',
            $this->identifierColumn,
            $this->passwordColumn,
            'email',
            'username',
            'firstname',
            'lastname',
        ];

        $query = (new SelectQuery())
            ->select(array_unique($columns))
            ->from('#__' . $this->table)
            ->where($this->identifierColumn . ' = :identifier')
            ->bindParam(':identifier', $identifier);

        $this->database->setQuery($query);

        $result = $this->database->loadAssoc();

        return $result ?: null;
    }

    public function setTable(string $table): static
    {
        $this->table = $table;
        return $this;
    }

    public function setIdentifierColumn(string $column): static
    {
        $this->identifierColumn = $column;
        return $this;
    }

    public function setPasswordColumn(string $column): static
    {
        $this->passwordColumn = $column;
        return $this;
    }
}
