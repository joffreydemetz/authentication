<?php

/**
 * @author    Joffrey Demetz <joffrey.demetz@gmail.com>
 * @license   MIT License; <https://opensource.org/licenses/MIT>
 */

namespace JDZ\Authentication;

use JDZ\Authentication\Connector\Connector;

class Authentication
{
  protected array $connectors;

  /**
   * Constructor
   * 
   * @param   array   $config   Key/value pairs
   */
  public function __construct(array $config = [])
  {
    foreach ($config as $key => $value) {
      $this->{$key} = $value;
    }

    $this->connectors = [];
  }

  public function addConnector(Connector $connector): self
  {
    $this->connectors[] = $connector;
    return $this;
  }

  /**
   * Prepare authentication
   * 
   * @param   array  $credentials  Array holding the user credentials
   * @return   Response
   */
  public function authenticate(array $credentials)
  {
    $response = new AuthenticationResponse();

    if (empty($credentials['username'])) {
      $response->status = AuthStatusEnum::EMPTY_USER;
      return $response;
    }

    if (empty($credentials['password'])) {
      $response->status = AuthStatusEnum::EMPTY_PASS;
      return $response;
    }

    $connectors = array_reverse($this->connectors);
    foreach ($connectors as $connector) {
      if ($connector->authenticate($credentials, $response)) {
        break;
      }
    }

    return $response;
  }
}
