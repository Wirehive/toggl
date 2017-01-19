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
  const MISSING_USER_AGENT = 240;

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


  /**
   * Get the text name for a given error code.
   *
   * @param int $code
   *
   * @return string
   */
  public static function getErrorName($code)
  {
    switch ($code)
    {
      case self::INVALID_MODE:
        return 'INVALID_MODE';

      case self::SERVICE_DESCRIPTOR_NOT_FOUND:
        return 'SERVICE_DESCRIPTOR_NOT_FOUND';

      case self::INVALID_METHOD:
        return 'INVALID_METHOD';

      case self::MISSING_PARAMETER:
        return 'MISSING_PARAMETER';

      case self::INVALID_PARAMETER:
        return 'INVALID_PARAMETER';

      case self::UNEXPECTED_PARAMETER:
        return 'UNEXPECTED_PARAMETER';

      case self::MISSING_USER_AGENT:
        return 'MISSING_USER_AGENT';

      case self::FAILED_AUTHENTICATION:
        return 'FAILED_AUTHENTICATION';

      case self::FAILED_REQUEST:
        return 'FAILED_REQUEST';

      case self::REQUEST_ERROR:
        return 'REQUEST_ERROR';

      case self::JSON_ERROR:
        return 'JSON_ERROR';

      default:
        return 'UNKNOWN';
    }
  }
}