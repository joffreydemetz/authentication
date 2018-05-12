<?php
/**
 * (c) Joffrey Demetz <joffrey.demetz@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace JDZ\Authentication\Connector;

use JDZ\Authentication\Authentication;
use JDZ\Authentication\AuthenticationResponse;

/**
 * Basic connector for authentication
 *
 * @author Joffrey Demetz <joffrey.demetz@gmail.com>
 */
class BasicConnector extends Connector
{
  /**
   * Expected username
   * 
   * @var   string 
   */
  protected $username;
  
  /**
   * Expected username
   * 
   * @var   string 
   */
  protected $password;
  
  /**
   * Used to authenticate user
   * 
   * @param  array                   $credentials  Key/value pairs holding the user credentials
   * @param  AuthenticationResponse  $response     Authentication response object
   * @return boolean
   */
  public function authenticate(array $credentials, AuthenticationResponse &$response)
  {
    if ( $credentials['username'] === '' ){
      $response->status = Authentication::EMPTY_USER;
      return false;
    }
    
    if ( !isset($this->username) || $this->username === '' || $credentials['username'] !== $this->username ){
      $response->status = Authentication::BAD_CREDENTIALS;
      return false;
    }
    
    $hashed_password = $this->getHashedPassword($credentials);
    
    if ( $hashed_password === '' ){
      $response->status = Authentication::BAD_CREDENTIALS;
      return false;
    }
    
    if ( !$this->checkPassword($credentials, $hashed_password) ){
      $response->status = Authentication::BAD_PASS;
      return false;
    }
    
    $response->type   = 'Basic';
    $response->status = Authentication::SUCCESS;
    return true;
  }
  
  /**
   * Get the wanted hashed password
   * 
   * @param   array       $credentials  Key/value pairs holding the user credentials
   * @return   string      The user hashed password
   */
  protected function getHashedPassword(array $credentials)
  {
    return isset($this->password) ? $this->password : '';
  }
}
