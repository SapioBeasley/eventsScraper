<?php namespace App\Helpers;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\ParseException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\BadResponseException;
use GuzzleHttp\Exception\ServerException;

class ApiHelper {

  /**
   * Index - All elements of a resource
   *
   * @param  string $uri  users | user | events | event
   * @return array        data array
   */
  public function index($uri)
  {
    // Make and parse api request
    $response = self::parseResponse(self::request('get', $uri));

    // Return data
    return $response;
  }

  /**
   * Show - A specifc element of a resource
   *
   * @param  string $uri  users | user | events | event
   * @param  int    $id   1 | 2 | 3 | etc
   * @return array        data array
   */
  public function show($uri, $id)
  {
    // Make and parse api request
    $response = self::parseResponse(self::request('get', $uri.'/'.$id));

    // Return data
    return $response;
  }

  /**
   * Store - A new elment of a resource
   *
   * @param  string $uri   users | events | comments
   * @param  array  $data  attributes / fields
   * @return array         data array
   */
  public function store($uri, $data)
  {
    // Make and parse api request
    $response = self::parseResponse(self::request('post', $uri, $data));

    // Return data
    return $response;
  }

  /**
   * Update - A existing elment of a resource
   *
   * @param  string $uri   users | events | comments
   * @param  int    $id    1 | 2 | 3 | etc
   * @param  array  $data  attributes / fields
   * @return array         data array
   */
  public function update($uri, $id, $data)
  {
    // Make and parse api request
    $response = self::parseResponse(self::request('put', $uri.'/'.$id, $data));

    // Return data
    return $response;
  }

  /**
   * Destroy the requested resource
   *
   * @param  string $uri   events | users | user
   * @param  int    $id    1 | 2 | 3 | etc
   * @return array         data array
   */
  public function destroy($uri, $id)
  {
    // Make and parse api request
    $response = self::parseResponse(self::request('delete', $uri.'/'.$id));

    // Return data
    return $response;
  }

  /**
   * The API client request call to all endpoints
   *
   * @param  string  $httpVerb  GET | POST | PUT | DELETE
   * @param  string  $uri       events | users | user/1
   * @param  array   $data      data array with all variables to be urle encoded
   * @return array              array with response or error from the api
   */
  public function request($httpVerb, $uri, $data = [])
  {
    // Create full endpoint url
    $endpoint = env('API_URL').$uri;

    // Set authentication headers
    $headers = [
      'X-API-Authorization' => env('API_KEY'),
    ];

    // Set request options
    $options = [
      'exceptions' => true,
      'headers' => empty($headers) ? null : $headers,
    ];

    // Set data type
    if ($httpVerb === 'get') {
      $options['query'] = empty($data) ? null : $data;
    } else {
      $options['form_params'] = empty($data) ? null : $data;
    }

    try {

      // Make api call
      $apiClient = new GuzzleClient();
      $response = $apiClient->{$httpVerb}($endpoint, $options);

    } catch (RequestException $e) {

      // Networking error
      self::logError($e->getResponse(), $e->getRequest(), $httpVerb, $endpoint, $data);

      // Set response
      $response = $e->getResponse();

    } catch (ServerException $e) {

      // 500 errors only ServerException, (to allow 400/500 errors, set this to BadResponseException)
      self::logError($e->getResponse(), $e->getRequest(), $httpVerb, $endpoint, $data);

      // Set response
      $response = $e->getResponse();
    };

    // Return response
    return $response;
  }

  /**
   * Check that the api response
   *
   * @param  object $response the apiClient response object
   * @return array            the response object parsed into array format
   */
  public function parseResponse($response)
  {
    // Set dummy array
    $parsedResponse = [
      'success' => false,
      'error' => null,
      'data' => [],
    ];

    // Parse response
    try {

      // Get the json response in array format
      $parsedResponse = json_decode($response->getBody(), true);

    } catch (ParseException $e) {

      // Log error
      \Log::error('API Parse Error: Malformed JSON', [
        'body' => $response->getBody(),
      ]);

      // Set the array to a dummy array with an error
      $parsedResponse = [
        'success' => false,
        'error' => 'Malformed JSON',
        'debug' => $response->getBody(),
        'data' => [],
      ];
    }

    // Return response
    return $parsedResponse;
  }

  /**
   * Log the api request that resulted in an error
   *
   * @param  object $response the apiClient response object
   * @param  object $request  the apiClient request object
   * @param  string $httpVerb GET | POST | PUT
   * @param  string $endpoint full url to endpoint
   * @param  array  $data     the data that was passed to the request
   * @return void
   */
  public function logError($response, $request, $httpVerb, $endpoint, $data)
  {
    // Info variables
    $code = null;
    $body = null;

    // Set info variables if we have a response from the api
    if ($response) {
      $code = $response->getStatusCode();
      $body = $response->getBody();
    }

    // Set message based on code error
    $message = 'API Response Error: ';

    switch ($code) {

      // Networking error
      case null:
        $message .= 'Could Not Make A Connection';
        break 1;

      // Server error
      case 500:
        $message .= 'Internal Server Error';
        break 1;

      // Server error
      case 503:
        $message .= 'Service Unavailable';
        break 1;
    }

    // Log this error
    \Log::error($message, [
      'code' => $code,
      'body' => $body,
      'httpVerb' => $httpVerb,
      'endpoint' => $endpoint,
      'data' => $data,
    ]);

    // 10.5.3 502 Bad Gateway
    // Translate api 500 error responses as 502's from the website
    if($code === 500) {
      abort(502);
    }

    // "10.5.5 504 Gateway Timeout"
    // Show api communication error page
    // only for networking errors "null" to the api
    if ($code === null || $code === 503) {
      abort(504);
    }
  }

  /**
   * Will camelize all keys found in a array or multi dimensional array
   *
   * @param  array $originalArray   the source
   * @param  string $morphTo        camel | snake
   * @return array                  the final array with the camielize keys
   */
  public function morphArrayKeys($originalArray, $morphTo = 'camel')
  {
    // New array with the morphed keys
    $newArray = [];

    // Iterate through each items
    foreach ($originalArray as $key => $value) {

      // If $value is an array in itself, re-run through this function
      if (is_array($value)) {
        $value = self::morphArrayKeys($value, $morphTo);
      }

      // Set new key
      $newKey = '';
      switch ($morphTo) {

        // Camel case
        case 'camel':
          $newKey = camel_case($key);
        break 1;

        // Snake case
        case 'snake':
          $newKey = snake_case($key);
        break 1;
      }

      // Set array line
      $newArray[$newKey] = $value;
    }

    // Return new array
    return $newArray;
  }

  /**
   * Strips fields from $data variable not found in $fields array
   *
   * @param  array   $data             multidimensional array
   * @param  array   $fields           one level array containing all the keys that must be kept (you can use dot notation)
   * @param  boolean $keepArrayStructure  will keep the same array structure or bring it to the root level uf false
   * @return array                     the final formatted array
   */
  public static function stripFields(array $data, array $fields, $keepArrayStructure = true)
  {
    // If fields array is empty
    if (empty($fields)) {

      // return original array
      return $data;
    }

    // The new data array that will hold the stripped fields
    $newData = [];

    // For every field to be stripped
    foreach ($fields as $field) {

      // Get the specific index
      $value = array_get($data, $field);

      // If we have something else besides a null
      if ($value !== null) {

        // Should we keep our array structure?
        if ($keepArrayStructure) {

          // Add to our new array under the same array structure
          $newData = array_add($newData, $field, $value);

        // Remove structure and bring field to root of the array
        } else {

          // Remove dot notation prefix
          // example: names.joe = joe
          if (false !== ($start = strrpos($field, '.'))) {
            $field = substr($field, $start + 1);
          }

          // Add to our new array
          $newData[$field] = $value;
        }
      }
    }

    // Return the new data array
    return $newData;
  }

  /**
   * Strips fields from $data collection array not found in $fields array
   *
   * @param  array   $data                multidimensional array
   * @param  array   $fields              one level array containing all the keys that must be kept (you can use dot notation)
   * @param  boolean $keepArrayStructure  will keep the same array structure or bring it to the root level uf false
   * @return array                        the final formatted array
   */
  public static function stripFieldsCollection(array $data, array $fields, $keepArrayStructure = true)
  {
    // If fields array is empty
    if (empty($fields)) {

      // return original array
      return $data;
    }

    // The new data array that will hold the stripped fields
    $newData = [];

    // Treat data as collection
    foreach ($data as $record) {

      // Strip fields
      $newData[] = static::stripFields($record, $fields, $keepArrayStructure);
    }

    // Return the new data array
    return $newData;
  }

  /**
   * Supply an array of tags, append tag variations of singular & plural
   * note: useful when you need to search for events with party tag, which will include parties to search for as well
   *
   * @param  Array  $tags the original tags which need variations
   * @return Array        the original plus the variations in array format
   */
  public static function tagVariations(Array $tags)
  {
    // New tag variations
    $variations = [];

    // For every tag supplied
    foreach ($tags as $tag) {

      // Get singular
      $variations[] = str_singular($tag);

      // Get plural
      $variations[] = str_plural($tag);
    }

    // Add variations to $tags
    foreach ($variations as $variation) {

      // If this variation of the tag is not in the tags array
      if ( ! in_array($variation, $tags)) {

        // Add it
        $tags[] = $variation;
      }
    }

    // Return modified tag array
    return $tags;
  }

}
