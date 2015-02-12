<?php


/**
 * Class TogglAPI
 *
 * Interface to the Toggl API and Toggle Reports API
 *
 * @author  Robin Corps <robin@wirehive.net>
 * @version 0.1
 * @package toggl
 *
 * @method CreateClient
 * @method GetClient
 * @method UpdateClient
 * @method DeleteClient
 * @method GetClients
 * @method GetClientProjects
 * @method CreateProject
 * @method GetProjects
 * @method GetProject
 * @method UpdateProject
 * @method DeleteProject
 * @method GetProjectUsers
 * @method CreateProjectUser
 * @method UpdateProjectUser
 * @method DeleteProjectUser
 * @method CreateProjectUsers
 * @method UpdateProjectUsers
 * @method DeleteProjectUsers
 * @method CreateTag
 * @method UpdateTag
 * @method DeleteTag
 * @method CreateTask
 * @method GetTask
 * @method UpdateTask
 * @method DeleteTask
 * @method UpdateTasks
 * @method DeleteTasks
 * @method StartTimeEntry
 * @method StopTimeEntry
 * @method GetTimeEntry
 * @method UpdateTimeEntry
 * @method DeleteTimeEntry
 * @method GetTimeEntries
 * @method GetCurrentUser
 * @method CreateUser
 * @method GetWorkspaces
 * @method GetWorkspaceUsers
 * @method GetWorkspaceClients
 * @method GetWorkspaceProjects
 * @method GetWorkspaceTasks
 * @method UpdateWorkspaceUser
 * @method DeleteWorkspaceUser
 */
class TogglAPI
{
  const API = 1;
  const REPORTS = 2;

  const API_VERSION = 'v8';
  const REPORTS_VERSION = 'v2';

  const GET    = 'GET';
  const POST   = 'POST';
  const PUT    = 'PUT';
  const DELETE = 'DELETE';

  /**
   * @var string
   */
  protected $baseUrl = 'https://www.toggl.com/api/';

  /**
   * @var string
   */
  protected $baseReportsUrl = 'https://www.toggl.com/reports/api/';

  /**
   * @var bool
   */
  protected $returnArray = true;

  /**
   * @var string
   */
  private $apiKey;

  /**
   * @var int
   */
  private $mode;

  /**
   * @var resource
   */
  private $curl = null;

  /**
   * @var array
   */
  private $curlOpts = array();

  /**
   * @var bool
   */
  private $ipv4Fallback = true;

  /**
   * @var array
   */
  private $apiMethods = array();


  /**
   * Construct a new TogglAPI instance
   *
   * @param string   $apiKey
   * @param int|null $mode
   *
   * @throws TogglException
   */
  public function __construct($apiKey, $mode = null)
  {
    if ($apiKey === null)
    {
      throw new TogglException('You must supply an API key to construct a new TogglAPI instance');
    }

    $this->setApiKey($apiKey);

    // default to API mode
    if ($mode === null)
    {
      $mode = self::API;
    }

    $this->setMode($mode);

    $this->loadServices();
  }


  /**
   * Magic method to call underlying service methods
   *
   * @param string $method
   * @param array  $params
   *
   * @throws TogglException
   * @throws InvalidMethodTogglException
   * @throws InvalidParameterTogglException
   * @throws MissingParameterTogglException
   * @throws UnexpectedParametersTogglException
   */
  public function __call($method, $params)
  {
    if ($params === null)
    {
      $params = array();
    }

    $service = $this->validateMethod($method, $params);

    $uri = $this->buildUri($service, $params);

    $this->initCurl();

    $this->setCurlOpt(CURLOPT_URL, $this->getEndpoint() . $uri);

    if (count($params))
    {
      $this->setCurlOpt(CURLOPT_POSTFIELDS, json_encode($params, JSON_NUMERIC_CHECK));
    }

    switch ($service['method'])
    {
      case self::POST:
        $this->setCurlOpt(CURLOPT_POST, true);
        break;

      case self::PUT:
        $this->setCurlOpt(CURLOPT_PUT, true);
        $this->setCurlOpt(CURLOPT_CUSTOMREQUEST, 'PUT');
        break;

      case self::DELETE:
        $this->setCurlOpt(CURLOPT_CUSTOMREQUEST, 'DELETE');
        break;
    }

    $response = curl_exec($this->curl);

    $status = curl_getinfo($this->curl, CURLINFO_HTTP_CODE);

    if (curl_errno($this->curl) == 60)
    {
      throw new TogglException('Invalid or no certificate authority found');
    }

    if ($response === false && !$this->curlOptSet(CURLOPT_IPRESOLVE))
    {
      $matches = array();
      $regex = '/Failed to connect to ([^:].*): Network is unreachable/';

      if (preg_match($regex, curl_error($this->curl), $matches))
      {
        if (strlen(@inet_pton($matches[1])) === 16)
        {
          if (!$this->ipv4Fallback)
          {
            throw new TogglException('Invalid IPv6 configuration on server, please disable or get native IPv6 on your server.', curl_errno($this->curl));
          }

          $this->setCurlOpt(CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);

          $response = curl_exec($this->curl);
        }
      }
    }

    if ($response === false)
    {
      curl_close($this->curl);

      throw new TogglException(curl_error($this->curl), curl_errno($this->curl));
    }

    curl_close($this->curl);

    $result = json_decode($response, $this->returnArray);

    if ($result === null)
    {
      if ($status == 500)
      {
        throw new TogglException('Toggl threw a 500 error...', TogglException::REQUEST_ERROR);
      }

      throw new TogglException('Error decoding result as JSON (' . $status . '): ' . $response, TogglException::JSON_ERROR);
    }

    if ($status == 404)
    {
      throw new TogglException('Method call failed with error(s): ' . implode(', ', $response), TogglException::FAILED_REQUEST);
    }

    if ($status == 403)
    {
      throw new TogglException('Failed to authenticate with Toggl', TogglException::FAILED_AUTHENTICATION);
    }

    return $result;
  }


  /**
   * Validate a service method and passed in parameters, setting defaults
   * where appropriate and return the service method
   *
   * @param string $method
   * @param array  $params
   *
   * @return array
   *
   * @throws InvalidMethodTogglException
   * @throws InvalidParameterTogglException
   * @throws MissingParameterTogglException
   * @throws UnexpectedParametersTogglException
   */
  private function validateMethod($method, &$params)
  {
    if (!array_key_exists($method, $this->apiMethods))
    {
      throw new InvalidMethodTogglException($method, array_keys($this->apiMethods));
    }

    $service = $this->apiMethods[$method];

    // if the service method has parameters then process them
    // and check we have any required ones
    if (array_key_exists('parameters', $service))
    {
      foreach ($service['parameters'] as $parameter => $details)
      {
        // check if parameter is required
        if ($details['required'] && !array_key_exists($parameter, $params))
        {
          throw new MissingParameterTogglException($method, $parameter);
        }

        // we only want to process the parameter if it was passed in
        if (array_key_exists($parameter, $params))
        {
          // check the set parameter was of the expected type
          if (gettype($params[$parameter]) != $details['type'])
          {
            throw new InvalidParameterTogglException(
              $method,
              $parameter,
              gettype($params[$parameter]),
              $details['type']
            );
          }
        }
        else
        {
          // if the parameter wasn't passed in but there is a default then set the default
          if (array_key_exists('default', $details))
          {
            $params[$parameter] = $details['default'];
          }
        }
      }

      // check for any unexpected parameters and throw an error if any found
      $unexpected = array();

      foreach ($params as $key => $value)
      {
        if (!array_key_exists($key, $service['parameters']))
        {
          $unexpected[] = $key;
        }
      }

      if (count($unexpected))
      {
        throw new UnexpectedParametersTogglException($unexpected, $method, array_keys($service['parameters']));
      }
    }
    else
    {
      if (count($params))
      {
        // check for any unexpected parameters and throw an error if any found
        throw new UnexpectedParametersTogglException(array_keys($params), $method);
      }
    }

    return $service;
  }


  /**
   * Build the URI for the call, removing any URI params from the params
   *
   * @param array $service
   * @param array $params
   *
   * @return string
   */
  private function buildUri(array $service, array &$params)
  {
    $uri = $service['uri'];

    if (array_key_exists('parameters', $service))
    {
      foreach ($service['parameters'] as $parameter => $details)
      {
        if ($details['location'] == 'uri')
        {
          $uri = str_replace('{' . $parameter . '}', $params[$parameter], $uri);

          unset($params[$parameter]);
        }
      }
    }

    return $uri;
  }


  /**
   * Get the service methods from the relevant JSON descriptor file
   */
  private function loadServices()
  {
    $path = __DIR__ . DIRECTORY_SEPARATOR;

    switch ($this->getMode())
    {
      case self::API:
        $path .= 'api_' . self::API_VERSION . '.json';
        break;

      case self::REPORTS:
        $path .= 'reports_' . self::REPORTS_VERSION . '.json';
        break;
    }

    if (!file_exists($path) || is_dir($path))
    {
      throw new ServiceDescriptorNotFoundTogglException($path);
    }

    $this->apiMethods = json_decode(file_get_contents($path), true);
  }


  /**
   * Get the endpoint URL based on the mode set
   *
   * @return string
   */
  private function getEndpoint()
  {
    switch ($this->getMode())
    {
      default:
      case self::API:
        return $this->baseUrl . self::API_VERSION . '/';

      case self::REPORTS:
        return $this->baseReportsUrl . self::REPORTS_VERSION . '/';
    }
  }


  /**
   * Get the mode of the API
   *
   * @return int
   */
  public function getMode()
  {
    return $this->mode;
  }


  /**
   * Set the mode of the API
   *
   * @param int $mode
   *
   * @throws InvalidModeTogglException
   */
  public function setMode($mode)
  {
    switch ($mode)
    {
      case self::API:
      case self::REPORTS:
        break;

      default:
        throw new InvalidModeTogglException($mode);
    }

    $this->mode = $mode;
  }


  /**
   * Get the API key in use
   *
   * @return string
   */
  public function getApiKey()
  {
    return $this->apiKey;
  }


  /**
   * Set the API key
   *
   * @param string $apiKey
   */
  private function setApiKey($apiKey)
  {
    $this->apiKey = $apiKey;
  }


  /**
   * Initialize cURL
   */
  private function initCurl()
  {
    $this->curl = curl_init();
    $this->setCurlOpts(array(
      CURLOPT_HTTPHEADER     => array(
        'Content-Type: application/json'
      ),
      CURLOPT_CONNECTTIMEOUT => 10,
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_TIMEOUT        => 60,
      CURLOPT_USERAGENT      => 'php-toggl-api'
    ));
  }


  /**
   * Return the value of a set cURL option
   *
   * @param $opt
   *
   * @return mixed
   */
  public function getCurlOpt($opt)
  {
    if (array_key_exists($opt, $this->curlOpts))
    {
      return $this->curlOpts[$opt];
    }

    return null;
  }


  /**
   * Allow setting of a cURL option
   *
   * @param int   $opt
   * @param mixed $value
   */
  public function setCurlOpt($opt, $value)
  {
    $this->curlOpts[$opt] = $value;

    curl_setopt($this->curl, $opt, $value);
  }


  /**
   * Return whether or not a cURL option has been set
   *
   * @param int $opt
   *
   * @return bool
   */
  public function curlOptSet($opt)
  {
    return array_key_exists($opt, $this->getCurlOpts());
  }


  /**
   * Get an array of the set cURL options (can be useful for debugging)
   *
   * @return array
   */
  public function getCurlOpts()
  {
    return $this->curlOpts;
  }


  /**
   * Allow setting of an array of cURL options
   *
   * @param array $options
   */
  public function setCurlOpts($options)
  {
    $this->curlOpts = $options + $this->curlOpts;

    curl_setopt_array($this->curl, $options);
  }


  /**
   * Get the URL of the API call
   *
   * @return string
   */
  public function getUrl()
  {
    return $this->getCurlOpt(CURLOPT_URL);
  }
}