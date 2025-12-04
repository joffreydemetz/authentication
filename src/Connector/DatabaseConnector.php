<?php

/**
 * @author    Joffrey Demetz <joffrey.demetz@gmail.com>
 * @license   MIT License; <https://opensource.org/licenses/MIT>
 */

namespace JDZ\Authentication\Connector;

use JDZ\Authentication\AuthStatusEnum;
use JDZ\Authentication\AuthenticationResponse;

abstract class DatabaseConnector extends Connector
{
  protected string $tbl_name;
  protected string $tbl_username_column;
  protected string $tbl_pass_column;

  public function __construct(string $tbl_name, string $tbl_username_column, string $tbl_pass_column)
  {
    $this->tbl_name = $tbl_name;
    $this->tbl_username_column = $tbl_username_column;
    $this->tbl_pass_column = $tbl_pass_column;

    if ($this->tbl_name === '' || $this->tbl_username_column === '' || $this->tbl_pass_column === '') {
      throw new \InvalidArgumentException('Table name, username column and password column must be set in the configuration.');
    }
  }

  public function authenticate(array $credentials, AuthenticationResponse $response): bool
  {
    $hashed_password = $this->getHashedPassword($credentials);

    if (empty($hashed_password)) {
      $response->status = AuthStatusEnum::BAD_CREDENTIALS;
      return false;
    }

    if (!$this->checkPassword($credentials, $hashed_password)) {
      $response->status = AuthStatusEnum::BAD_PASS;
      return false;
    }

    $response->type = 'Database';
    $response->status = AuthStatusEnum::SUCCESS;

    return true;
  }

  protected function getHashedPassword(array $credentials): string
  {
    // This method should be implemented to retrieve the hashed password from the database
    throw new \RuntimeException('Method getHashedPassword() must be implemented in the subclass.');
  }
}
