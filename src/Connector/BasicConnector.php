<?php

/**
 * @author    Joffrey Demetz <joffrey.demetz@gmail.com>
 * @license   MIT License; <https://opensource.org/licenses/MIT>
 */

namespace JDZ\Authentication\Connector;

use JDZ\Authentication\AuthStatusEnum;
use JDZ\Authentication\AuthenticationResponse;

class BasicConnector extends Connector
{
  protected string $username;
  protected string $password;

  public function __construct(string $username, string $password)
  {
    $this->username = $username;
    $this->password = $password;

    if ($this->username === '' || $this->password === '') {
      throw new \InvalidArgumentException('Username and password must be set in the configuration.');
    }

    // check if the password is hashed, if not hash it
    if (!\password_get_info($this->password)['algo']) {
      $this->password = \password_hash($this->password, \PASSWORD_DEFAULT);
    }
  }

  public function authenticate(array $credentials, AuthenticationResponse $response): bool
  {
    if (empty($credentials['username'])) {
      $response->status = AuthStatusEnum::EMPTY_USER;
      return false;
    }

    if (empty($credentials['password'])) {
      $response->status = AuthStatusEnum::EMPTY_PASS;
      return false;
    }

    if ($credentials['username'] !== $this->username) {
      $response->status = AuthStatusEnum::BAD_CREDENTIALS;
      return false;
    }

    if (!\password_verify($credentials['password'], $this->getHashedPassword($credentials))) {
      $response->status = AuthStatusEnum::BAD_PASS;
      return false;
    }

    $response->type = 'Basic';
    $response->status = AuthStatusEnum::SUCCESS;

    return true;
  }

  protected function getHashedPassword(array $credentials): string
  {
    return $this->password;
  }
}
