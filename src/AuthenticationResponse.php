<?php
/**
 * (c) Joffrey Demetz <joffrey.demetz@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace JDZ\Authentication;

/**
 * Authentication response class, provides an object for storing user and error details
 *
 * @author Joffrey Demetz <joffrey.demetz@gmail.com>
 */
class AuthenticationResponse 
{
  /**
   * Response status (see status codes)
   * 
   * @var   string 
   */
  public $status;

  /**
   * The type of authentication that was successful
   * 
   * @var   string 
   */
  public $type;

  /**
   * End User email as specified in section 3.4.1 of [RFC2822]
   * 
   * @var   string 
   */
  public $email;

  /**
   * End User password
   * 
   * @var   string 
   */
  public $password;
  
  /**
   * End User full name
   * 
   * @var   string 
   */
  public $fullname;
  
  /**
   * End User firstname
   * 
   * @var   string 
   */
  public $firstname;
  
  /**
   * End User lastname
   * 
   * @var   string 
   */
  public $lastname;

  /**
   * End User username
   * 
   * @var   string 
   */
  public $username;
  
  /**
   * End User preferred language as specified by ISO639
   * 
   * @var   string 
   */
  public $language;
  
  /**
   * Constructor
   */
  public function __construct()
  {
    $this->status    = Authentication::FAILURE;
    $this->type      = '';
    $this->email     = '';
    $this->password  = '';
    $this->fullname  = '';
    $this->firstname = '';
    $this->lastname  = '';
    $this->username  = '';
    $this->language  = 'fr-FR';
  }
}
