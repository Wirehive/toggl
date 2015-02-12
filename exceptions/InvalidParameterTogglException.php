<?php


/**
 * Class InvalidParameterTogglException
 */
class InvalidParameterTogglException extends TogglException
{
  /**
   * @param string $method
   * @param string $parameter
   * @param string $given
   * @param string $expected
   */
  public function __construct($method, $parameter, $given, $expected)
  {
    parent::__construct('The parameter "' . $parameter . '" for method "' . $method . '" expects type "' . $expected . '" but was given "' . $given . '"', self::INVALID_PARAMETER);
  }
}