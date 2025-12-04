<?php

/**
 * @author    Joffrey Demetz <joffrey.demetz@gmail.com>
 * @license   MIT License; <https://opensource.org/licenses/MIT>
 */

namespace JDZ\Authentication;

enum AuthStatusEnum
{
  case FAILURE;         // 0 -- Failed request (initial status)
  case SUCCESS;         // 1 -- Successful response
  case EMPTY_USER;      // 2 -- Missing login
  case EMPTY_PASS;      // 3 -- Missing password 
  case BAD_CREDENTIALS; // 4 -- Account not found
  case BAD_PASS;        // 5 -- Invalid password

  /**
   * Get the code the status
   */
  public function code(): int
  {
    return match ($this) {
      self::FAILURE => 0,
      self::SUCCESS => 1,
      self::EMPTY_USER => 2,
      self::EMPTY_PASS => 3,
      self::BAD_CREDENTIALS => 4,
      self::BAD_PASS => 5,
    };
  }

  /**
   * Get the text message for the status
   */
  public function message(): string
  {
    return match ($this) {
      self::FAILURE => 'Authentication failed',
      self::SUCCESS => '',
      self::EMPTY_USER => 'Missing username in credentials',
      self::EMPTY_PASS => 'Missing password in credentials',
      self::BAD_CREDENTIALS => 'Bad credentials',
      self::BAD_PASS => 'Invalid password',
    };
  }
}
