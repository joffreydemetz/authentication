# authentication
Simple authentication

## Usage
Exemple for a database authentication

1/ Create a DatabaseConnector
```
use JDZ\Authentication\Connector\DatabaseConnector;

/**
 * Database connector for authentication
 */
class DboAuthenticator extends DatabaseConnector
{
  /**
   * Get the user hashed password from the username
   * 
   * @param 	array       $credentials  Key/value pairs holding the user credentials
   * @return 	string      The user hashed password
   */
  protected function getHashedPassword(array $credentials)
  {
    $dbo = Dbo();
    
	$query = $dbo->getQuery(true);

	$query->select($this->tbl_pass_column);
	$query->from($this->tbl_name);
	$query->where($this->tbl_username_column.'='.$dbo->q($credentials['username']));
	$dbo->setQuery($query);
	$result = $dbo->loadResult();
    return (string)$result;
  }
}
```

2/ Authenticate

```
use JDZ\Authentication\Authentication;
use JDZ\Authentication\AuthenticationException;

function authenticate($credentials)
{
  $authenticationConnector = new DboAuthenticator([
    'tbl_name' => 'users',
    'tbl_username_column' => 'email',
    'tbl_pass_column' => 'password',
  ]);
  
  $auth = new Authentication();
  $auth->addConnector($authenticationConnector);

  $authResponse = $auth->authenticate($credentials, $options);

  if ( $authResponse->status !== Authentication::SUCCESS ){
    if ( isset($options['silent']) && $options['silent'] ){
    return false;
    }
    
    switch($authResponse->status){
    case Authentication::EMPTY_USER:
      $message = 'Missing username in credentials';
      break;
    
    case Authentication::EMPTY_PASS:
      $message = 'Missing password in credentials';
      break;
    
    case Authentication::BAD_PASS:
      $message = 'Invalid password';
      break;
    
    case Authentication::BAD_CREDENTIALS:
      $message = 'Bad credentials';
      break;
    }
    
    throw new AuthenticationException($message);
  }
}

$credentials = [
  'username' => 'user',
  'password' => 'paswword',
];

try {
  authenticate($credentials);
}
catch(AuthenticationException $e){
  die($e->getMessage();
}
```
