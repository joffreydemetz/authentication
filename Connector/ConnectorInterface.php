<?php
/**
 * (c) Joffrey Demetz <joffrey.demetz@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace JDZ\Authentication\Connector;

use JDZ\Authentication\AuthenticationResponse;

/**
 * Abstract connector for authentication
 *
 * @author Joffrey Demetz <joffrey.demetz@gmail.com>
 */
interface ConnectorInterface 
{
	/**
   * Used to authenticate user
   * 
	 * @param 	array	                    $credentials  Key/value pairs holding the user credentials
	 * @param 	AuthenticationResponse	  $response     Authentication response object
	 * @return 	boolean
	 */
  public function authenticate(array $credentials, AuthenticationResponse &$response);
}
