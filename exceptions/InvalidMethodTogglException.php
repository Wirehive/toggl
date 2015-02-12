<?php


/**
 * Class InvalidMethodTogglException
 */
class InvalidMethodTogglException extends TogglException
{
/**
   * @param string $method
   * @param array  $valid
   */
  public function __construct($method, array $valid)
  {
    parent::__construct('You called an invalid method "' . $method . '". Valid methods are: ' . implode(', ', $valid), self::INVALID_METHOD);
  }
}