<?php
declare(strict_types=1);

/**
 * @author    Joffrey Demetz <joffrey.demetz@gmail.com>
 * @license   MIT License; <https://opensource.org/licenses/MIT>
 */

namespace JDZ\Authentication;

class AuthenticationResult
{
    protected AuthStatusEnum $status;
    protected ?int $userId = null;
    protected string $identifier = '';
    protected string $email = '';
    protected string $firstname = '';
    protected string $lastname = '';
    protected string $username = '';
    protected string $type = '';
    protected string $message = '';
    protected array $data = [];

    public function __construct(AuthStatusEnum $status = AuthStatusEnum::FAILURE)
    {
        $this->status = $status;
    }

    public static function success(?int $userId = null): static
    {
        $result = new static(AuthStatusEnum::SUCCESS);
        $result->userId = $userId;
        return $result;
    }

    public static function failure(AuthStatusEnum $status, string $message = ''): static
    {
        $result = new static($status);
        $result->message = $message;
        return $result;
    }

    public function isSuccess(): bool
    {
        return $this->status === AuthStatusEnum::SUCCESS;
    }

    public function getStatus(): AuthStatusEnum
    {
        return $this->status;
    }

    public function setStatus(AuthStatusEnum $status): static
    {
        $this->status = $status;
        return $this;
    }

    public function getUserId(): ?int
    {
        return $this->userId;
    }

    public function setUserId(?int $userId): static
    {
        $this->userId = $userId;
        return $this;
    }

    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    public function setIdentifier(string $identifier): static
    {
        $this->identifier = $identifier;
        return $this;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;
        return $this;
    }

    public function getFirstname(): string
    {
        return $this->firstname;
    }

    public function setFirstname(string $firstname): static
    {
        $this->firstname = $firstname;
        return $this;
    }

    public function getLastname(): string
    {
        return $this->lastname;
    }

    public function setLastname(string $lastname): static
    {
        $this->lastname = $lastname;
        return $this;
    }

    public function getUsername(): string
    {
        return $this->username;
    }

    public function setUsername(string $username): static
    {
        $this->username = $username;
        return $this;
    }

    public function getFullname(): string
    {
        $parts = array_filter([$this->firstname, $this->lastname]);
        return implode(' ', $parts) ?: $this->email ?: $this->identifier;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): static
    {
        $this->type = $type;
        return $this;
    }

    public function getMessage(): string
    {
        return $this->message ?: $this->status->message();
    }

    public function setMessage(string $message): static
    {
        $this->message = $message;
        return $this;
    }

    public function getData(): array
    {
        return $this->data;
    }

    public function setData(array $data): static
    {
        $this->data = $data;
        return $this;
    }

    public function set(string $key, mixed $value): static
    {
        $this->data[$key] = $value;
        return $this;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->data[$key] ?? $default;
    }

    public function toArray(): array
    {
        return [
            'status' => $this->status->value,
            'message' => $this->getMessage(),
            'user_id' => $this->userId,
            'identifier' => $this->identifier,
            'email' => $this->email,
            'username' => $this->username,
            'firstname' => $this->firstname,
            'lastname' => $this->lastname,
            'fullname' => $this->getFullname(),
            'type' => $this->type,
            'data' => $this->data,
        ];
    }
}
