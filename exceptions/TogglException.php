<?php


/**
 * Class TogglException
 *
 * Has some class constants for better automated exception handling in
 * external code
 */
class TogglException extends Exception
{
  // setup errors
  const INVALID_MODE = 110;
  const SERVICE_DESCRIPTOR_NOT_FOUND = 120;

  // service call errors
  const INVALID_METHOD = 200;
  const MISSING_PARAMETER = 210;
  const INVALID_PARAMETER = 220;
  const UNEXPECTED_PARAMETER = 230;

  // service response errors
  const FAILED_AUTHENTICATION = 300;
  const FAILED_REQUEST = 310;
  const REQUEST_ERROR = 320;
  const JSON_ERROR = 330;


  /**
   * @param string $message
   * @param int    $code
   */
  public function __construct($message, $code = 0)
  {
    parent::__construct($message, $code);
  }
}