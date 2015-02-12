<?php


/**
 * Class UnexpectedParametersTogglException
 */
class UnexpectedParametersTogglException extends TogglException
{
  /**
   * @param array $unexpected
   * @param int   $method
   * @param array $valid
   */
  public function __construct(array $unexpected, $method, array $valid = null)
  {
    $message = 'passed to the method "' . $method . '".';

    if (count($unexpected) > 1)
    {
      $message = 'Unexpected parameters (' . implode(', ', $unexpected) . ') were ' . $message;
    }
    else
    {
      $message = 'Unexpected parameter "' . implode('', $unexpected) . '" was ' . $message;
    }

    if ($valid === null)
    {
      $message .= ' The method expects no parameters.';
    }
    else
    {
      $message .= ' Valid parameters are: ' . implode(', ', $valid);
    }

    parent::__construct($message, self::UNEXPECTED_PARAMETER);
  }
}