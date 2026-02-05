<?php

declare(strict_types=1);

namespace JDZ\Authentication\Contract;

use JDZ\Authentication\AuthenticationResult;

interface AuthenticationInterface
{
    public function authenticate(array $credentials): AuthenticationResult;
    public function supports(array $credentials): bool;
}
