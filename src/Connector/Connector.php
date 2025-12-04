<?php

/**
 * @author    Joffrey Demetz <joffrey.demetz@gmail.com>
 * @license   MIT License; <https://opensource.org/licenses/MIT>
 */

namespace JDZ\Authentication\Connector;

abstract class Connector implements ConnectorInterface
{
  protected function checkPassword(array $credentials, string $hashed_password): bool
  {
    return (\password_verify($credentials['password'], $hashed_password));
  }
}
