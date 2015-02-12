<?php


/**
 * Class MissingParameterTogglException
 */
class MissingParameterTogglException extends TogglException
{
  /**
   * @param string $method
   * @param string $parameter
   */
  public function __construct($method, $parameter)
  {
    parent::__construct('The method "' . $method . '" requires parameter "' . $parameter . '" to be set', self::MISSING_PARAMETER);
  }
}