<?php
/**
 * (c) Joffrey Demetz <joffrey.demetz@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace JDZ\Authentication;

use JDZ\Authentication\Connector\Connector;

/**
 * Authentication Base Object
 *
 * @author Joffrey Demetz <joffrey.demetz@gmail.com>
 */
class Authentication 
{
  /**
   * Failed request (initial status)
   * 
   * @var   constant 
   */
  const FAILURE = 0;

  /**
   * Successful response
   * 
   * @var   constant 
   */
  const SUCCESS = 1;

  /**
   * Missing login 
   * 
   * @var   constant 
   */
  const EMPTY_USER = 2;

  /**
   * Missing password 
   * 
   * @var   constant 
   */
  const EMPTY_PASS = 3;
  
  /**
   * Account not found
   * 
   * @var   constant 
   */
  const BAD_CREDENTIALS = 4;

  /**
   * Invalid password
   * 
   * @var   constant 
   */
  const BAD_PASS = 5;
  
  /**
   * List of connectors (local|..)
   * 
   * @var   array 
   */
  protected $connectors;
  
  /**
   * Constructor
   * 
   * @param   array   $config   Key/value pairs
   */
  public function __construct(array $config=[])
  {
    foreach($config as $key => $value){
      $this->{$key} = $value;
    }
    
    $this->connectors = [];
  }
  
  /**
   * Add a connector to the stack
   * 
   * @param   Connector  $connector   Authentication connector instance
   * @return   void
   */
  public function addConnector(Connector $connector)
  {
    $this->connectors[] = $connector;
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
    
    if ( empty($credentials['username']) ){
      $response->status = Authentication::EMPTY_USER;
      return $response;
    }
    
    if ( empty($credentials['password']) ){
      $response->status = Authentication::EMPTY_PASS;
      return $response;
    }
    
    $connectors = array_reverse($this->connectors);
    foreach($connectors as $connector){
      if ( $connector->authenticate($credentials, $response) ){
        break;
      }
    }
    
    return $response;
  }
}
