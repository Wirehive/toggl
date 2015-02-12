<?php


/**
 * Class ServiceDescriptorNotFoundTogglException
 */
class ServiceDescriptorNotFoundTogglException extends TogglException
{
  /**
   * @param string $path
   */
  public function __construct($path)
  {
    parent::__construct('The service descriptor JSON file could not be found at: ' . $path, self::SERVICE_DESCRIPTOR_NOT_FOUND);
  }
}