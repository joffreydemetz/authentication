<?php
/**
 * (c) Joffrey Demetz <joffrey.demetz@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace JDZ\Authentication\Connector;

/**
 * Abstract connector for authentication
 *
 * @author Joffrey Demetz <joffrey.demetz@gmail.com>
 */
abstract class Connector implements ConnectorInterface
{
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
  }
  
  /**
   * Get the user hashed password from the username
   * 
   * @param   array       $credentials  Key/value pairs holding the user credentials
   * @return   string      The user hashed password
   */
  abstract protected function getHashedPassword(array $credentials);
  
  /**
   * Check the user password
   * 
   * @param   array       $credentials        Key/value pairs holding the user credentials
   * @param   string      $hashed_password    The user hashed password
   * @return   bool  True if the passwords are a match.
   */
  protected function checkPassword(array $credentials, $hashed_password)
  {
    return ( password_verify($credentials['password'], $hashed_password) );
  }
}
