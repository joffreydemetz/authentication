<?php
declare(strict_types=1);

/**
 * @author    Joffrey Demetz <joffrey.demetz@gmail.com>
 * @license   MIT License; <https://opensource.org/licenses/MIT>
 */

namespace JDZ\Authentication\Connector;

use JDZ\Authentication\AuthenticationResult;

interface ConnectorInterface
{
    public function authenticate(array $credentials): AuthenticationResult;

    public function supports(array $credentials): bool;

    public function getName(): string;
}
