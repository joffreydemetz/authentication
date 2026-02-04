<?php
declare(strict_types=1);

/**
 * @author    Joffrey Demetz <joffrey.demetz@gmail.com>
 * @license   MIT License; <https://opensource.org/licenses/MIT>
 */

namespace JDZ\Authentication;

class AuthenticationException extends \Exception
{
    protected AuthStatusEnum $status;

    public function __construct(
        AuthStatusEnum $status,
        string $message = '',
        int $code = 0,
        ?\Throwable $previous = null
    ) {
        $this->status = $status;
        parent::__construct($message ?: $status->message(), $code ?: $status->value, $previous);
    }

    public function getStatus(): AuthStatusEnum
    {
        return $this->status;
    }

    public static function fromStatus(AuthStatusEnum $status): static
    {
        return new static($status);
    }
}
