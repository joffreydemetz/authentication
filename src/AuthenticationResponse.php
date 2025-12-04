<?php

/**
 * @author    Joffrey Demetz <joffrey.demetz@gmail.com>
 * @license   MIT License; <https://opensource.org/licenses/MIT>
 */

namespace JDZ\Authentication;

class AuthenticationResponse
{
  public AuthStatusEnum $status;
  public string $type;
  public string $email; // as specified in section 3.4.1 of [RFC2822]
  public string $password;
  public string $fullname;
  public string $firstname;
  public string $lastname;
  public string $username;
  public string $language;

  public function __construct()
  {
    $this->status    = AuthStatusEnum::FAILURE;
    $this->type      = '';
    $this->email     = '';
    $this->password  = '';
    $this->fullname  = '';
    $this->firstname = '';
    $this->lastname  = '';
    $this->username  = '';
    $this->language  = 'fr-FR';
  }

  public function toArray(): mixed
  {
    return [
      'status'    => $this->status->code(),
      'message'   => $this->status->message(),
      'type'      => $this->type,
      'email'     => $this->email,
      'password'  => $this->password,
      'fullname'  => $this->fullname,
      'firstname' => $this->firstname,
      'lastname'  => $this->lastname,
      'username'  => $this->username,
      'language'  => $this->language,
    ];
  }
}
