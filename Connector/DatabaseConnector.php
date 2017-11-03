<?php
/**
 * THIS SOFTWARE IS PRIVATE
 * CONTACT US FOR MORE INFORMATION
 * Joffrey Demetz <joffrey.demetz@gmail.com>
 * <http://callisto.izisawebsite.com>
 */
namespace JDZ\Authentication\Connector;

use JDZ\Authentication\Authentication;
use JDZ\Authentication\AuthenticationResponse;

/**
 * Database connector for authentication
 *
 * @author Joffrey Demetz <joffrey.demetz@gmail.com>
 */
abstract class DatabaseConnector extends Connector
{
	/**
   * Table name
   * 
   * @var 	string 
   */
  protected $tbl_name;
  
	/**
   * Table username column
   * 
   * @var 	string 
   */
  protected $tbl_username_column;
  
	/**
   * Table password column
   * 
   * @var 	string 
   */
  protected $tbl_pass_column;
  
	/**
   * Used to authenticate user
   * 
	 * @param 	array	                    $credentials  Key/value pairs holding the user credentials
	 * @param 	AuthenticationResponse	  $response     Authentication response object
	 * @return 	boolean
	 */
  public function authenticate(array $credentials, AuthenticationResponse &$response)
	{
    $hashed_password = $this->getHashedPassword($credentials);
    
		if ( $hashed_password === '' ){
      $response->status = Authentication::BAD_CREDENTIALS;
      return false;
    }
    
    if ( !$this->checkPassword($credentials, $hashed_password) ){
      $response->status = Authentication::BAD_PASS;
      return false;
    }
    
		$response->type   = 'Database';
    $response->status = Authentication::SUCCESS;
    return true;
	}
}
