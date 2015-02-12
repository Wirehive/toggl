<?php


/**
 * Class InvalidModeTogglException
 */
class InvalidModeTogglException extends TogglException
{
  /**
   * @param string $mode
   */
  public function __construct($mode)
  {
    parent::__construct('You supplied an invalid mode: ' . $mode, self::INVALID_MODE);
  }
}