<?php namespace App\Helpers;

// Guzzle
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\ParseException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\BadResponseException;
use GuzzleHttp\Exception\ServerException;

use App\Helpers\ApiHelper;

use App\Result;
use App\Job;

class ScraperCommandHelper
{

  /**
   * Organize the array data to be similar no mater the job
   * @param  array $data job data
   * @return array       uniform job and source data
   */
  public static function organizeArray($data)
  {
    // Compare with validation
    $validator = \Validator::make($data, [
      'id' => 'required',
      'source_id' => 'required',
      'name' => 'required',
      'is_enabled' => 'required',
      'url' => 'required',
    ]);

    // Check validation
    if ($validator->fails()) {

      // Return false
      return [
        'success' => false,
        'error' => $validator->errors()->all(),
        'data' => []
      ];
    }

    // Organize the data
    $data = [
      'job_id' => $data['id'],
      'job_name' => $data['name'],
      'job_status' => $data['is_enabled'],
      'job_url' => $data['url'],
      'last_run_at' => $data['last_run_at'],
      'source_id' => $data['source']['id'],
      'source_name' => $data['source']['name'],
      'max_requests' => $data['source']['max_requests'],
      'interval_seconds' => $data['source']['interval_seconds'],
    ];

    // Return true
    return [
      'success' => true,
      'error' => null,
      'data' => $data
    ];
  }

  /**
  * Selects the source to run
  * @param  array $jobArray uniformed job array
  * @return array           result from job
  */
  public static function runJobSource($jobArray)
  {

    // Check the source
    switch (true) {

      // Job is eventbrite
      case $jobArray['data']['source_name'] === 'eventbrite':

        // Scrape eventbrite source
        $eventbriteScrape = self::eventbriteScrape($jobArray);
        return $eventbriteScrape;

      // Job is viagogo
      case $jobArray['data']['source_name'] === 'viagogo':

        // Scrape viagogo source
        $viagogoScrape = self::viagogoScrape($jobArray);
        return $viagogoScrape;

      // If all else fails
      default:

        // End execution
        return;
    }
  }

  /**
   * Scraper logic for eventbrite
   * @param  array $jobArray uniformed job data
   * @return array
   */
  public static function eventbriteScrape($jobArray)
  {
    // Set job specifics
    $jobId = $jobArray['data']['job_id'];
    $jobName = $jobArray['data']['job_name'];
    $jobStatus = $jobArray['data']['job_status'];
    $jobUrl = $jobArray['data']['job_url'];
    $jobIterationUrl = $jobArray['data']['job_url'];
    $jobLastRunAt = $jobArray['data']['last_run_at'];
    $jobSourceId = $jobArray['data']['source_id'];
    $jobSourceName = $jobArray['data']['source_name'];
    $jobMaxRequests = $jobArray['data']['max_requests'];
    $jobIntervalSecond = $jobArray['data']['interval_seconds'];
    $token = env('API_KEY_EVENTBRITE');

    // Configure scrape url
    $jobUrl = self::configureScrapeUrl($jobUrl, [
      'token' => $token,
    ]);

    // Initial request
    $eventContent = self::guzzleContent($jobUrl . '&start_date.range_start=' . date('Y-m-d') . 'T00:00:00&start_date.range_end=' . date('Y-m-d', strtotime('+30 days')) . 'T00:00:00');

    // Check if unauthorized code was returned
    if (isset($eventContent['code'])) {

      // Error switch
      $errorSwitch = self::errorSwitch($eventContent);
    }

    // Check status
    if ($eventContent['success'] !== true) {

      // Return false
      return [
        'success' => false,
        'error' => $eventContent['error'],
        'data' => $eventContent['data'],
      ];
    }

    // Scrape data
    $eventScrape = self::scrapeIt($jobUrl, $jobId, $eventContent['data']['pagination']['page_count'], $jobMaxRequests, $jobIntervalSecond);

    // Return true
    return [
      'success' => true,
      'error' => $eventScrape['error'],
      'data' => $eventScrape['data'],
    ];
  }

  /**
   * Scrap the given resource data set
   * @param  string    $jobUrl              Parsed url
   * @param  integer   $jobId               Id of the job to run
   * @param  integer   $totalPages          Total amount of pages to loop through
   * @param  integer   $jobMaxRequests      Max requests allowed to make
   * @param  integer   $jobIntervalSecond   Seconds in a 24 hour roll
   * @return array                          Timestamp of the finished run
   */
  public static function scrapeIt($jobUrl, $jobId, $totalPages, $jobMaxRequests, $jobIntervalSecond)
  {
    // Set default total pages
    $totalPages = $totalPages;

    // Set default days
    $days = 90;

    // Grab start date as today
    $start = date('Y-m-d');

    // Increment through pages
    for ($page=1; $page <= $totalPages; $page++) {

      // Guzzle my url
      $eventContent = self::guzzleContent($jobUrl . '&start_date.range_start=' . $start . 'T00:00:00&start_date.range_end=' . date('Y-m-d', strtotime('+' . $days . 'days')) . 'T00:00:00&page=' . $page);

      // Check if unauthorized code was returned
      if (isset($eventContent['code'])) {

        // Error switch
        $errorSwitch = self::errorSwitch($eventContent);
      }

      // Check status
      if ($eventContent['success'] !== true) {

        // Return false
        return [
          'success' => false,
          'error' => $eventContent['error'],
          'data' => $eventContent['data'],
        ];
      }

      // Loop the events and save
      foreach ($eventContent['data']['events'] as $event) {

        // Check if we have a duplicate
        $checkDuplicate = Result::where('identifier', '=', $event['id'])
          ->where('start_date', '=', $event['start']['local'])
          ->where('end_date', '=', $event['end']['local'])
          ->get()->toArray();

        // Check for a second instance of the same event
        if (isset($checkDuplicate[1])) {

          // Search for all other duplicates
          $removeDuplicates = Result::where('identifier', '=', $checkDuplicate[0]['identifier'])
            ->where('start_date', '=', $checkDuplicate[0]['start_date'])
            ->where('end_date', '=', $checkDuplicate[0]['end_date'])
            ->where('processed_at', '=', null)->get();

          // If the duplicates are not empty
          if (! $removeDuplicates->isEmpty()) {
            echo 'Deleting ' . $event['id'] . ' ' . $event['name']['text'] . "\n";

            // delete each one
            foreach ($removeDuplicates as $delete) {
              $delete->delete();
            }

          }
        }

        // If a duplicate is found
        if (isset($checkDuplicate[0])) {

          echo 'duplicate found, skipping ' . $event['id'] . ' ' . $event['name']['text'] . "\n";
          continue 1;

        }

        try {

          echo 'Creating new event ' . $event['id'] . ' ' . $event['name']['text'] . "\n";

          // Create a new instance
          Result::firstOrCreate([
            'job_id' => $jobId,
            'identifier' => $event['id'],
            'start_date' => $event['start']['local'],
            'end_date' => $event['end']['local'],
            'timezone' => $event['start']['timezone'],
            'result' => json_encode($event),
          ]);

        // Catch any errors
        } catch (Exception $e) {

          // Return false
          return [
            'success' => false,
            'error' => $e->getMessage(),
            'data' => [],
          ];
        }
      }

      // Calculate Interval seconds
      $interval = $jobIntervalSecond / $jobMaxRequests;

      // Echo page status
      echo 'Finished page ' . $page . "\r\n \r\n";

      // Sleep for $interval seconds between
      sleep($interval);

      // If total tages is equal to current page
      if ($totalPages === $page) {

        // Increment days by 30
        $days += 30;

        // Start date is now incremented
        $startDate = date('Y-m-d', strtotime('+' . $days  . 'days')) . 'T00:00:00';

        echo 'date: ' . $startDate . "\r\n \r\n";
        echo 'days ' . $days . "\r\n \r\n";

        // Set page to 0
        $page = 0;

        // Check date equals
        if ($days === 720) {

          // Echo final iteration message
          echo 'Finished iterations' . "\r\n \r\n";

          // Sleep for a bit
          sleep(3600);

          // Reset days
          $days = 0;
        }
      }
    }

    // Echo end of scrape if need be
    echo 'End of scrape';

    // Set last run at date
    $lastRunAt = \Carbon\Carbon::now();

    // Find job and update it
    $job = Job::find($jobId)->update([
      'last_run_at' => $lastRunAt->toDateTimeString()
    ]);

    // End Execution
    return [
      'success' => true,
      'error' => null,
      'data' => [
        'last_run_at' => $lastRunAt->toDateTimeString()
      ],
    ];
  }

  /**
   * Configure scrape URl
   * @param  string  $jobUrl      initial job url from jobs table
   * @param  array   $parameters  parameters to replace in url
   * @return string               full url to scrape
   */
  protected static function configureScrapeUrl($jobUrl, $parameters)
  {
    // Find and replace
    foreach ($parameters as $key => $value) {

      // Set key to template key
      $key = '{' . $key . '}';

      // Set urlencode on values
      $jobUrl = str_replace($key, urlencode($value), $jobUrl);
    }

    // Return the configured url
    return $jobUrl;
  }

  /**
   * Check and return appropriate error code and message
   * @param  array  $eventContent  eventcontent from guzzle
   * @return array                 error code or retry
   */
  public static function errorSwitch($eventContent)
  {
    switch (true) {


    // If code returned is 404
      case $eventContent['code'] === 404: // NOT_FOUND

        // Return false
        return [
          'success' => false,
          'error' => $eventContent['error'],
          'data' => []
        ];

      // If code returned is 400
      case $eventContent['code'] === 400: // INVALID_AUTH or INVALID_AUTH_HEADER or BAD_PAGE or INVALID_BATCH

        // Return false
        return [
          'success' => false,
          'error' => $eventContent['error'],
          'data' => []
        ];

      // If code returned is 401
      case $eventContent['code'] === 401: // NO_AUTH

        // Return false
        return [
          'success' => false,
          'error' => $eventContent['error'],
          'data' => []
        ];

      // If code returned is 403
      case $eventContent['code'] === 403: // NOT_AUTHORIZED

        // Return false
        return [
          'success' => false,
          'error' => $eventContent['error'],
          'data' => []
        ];

      // If code returned is 405
      case $eventContent['code'] === 405: // METHOD_NOT_ALLOWED

        // Return false
        return [
          'success' => false,
          'error' => $eventContent['error'],
          'data' => []
        ];

      // If code returned is 429
      case $eventContent['code'] === 429: // HIT_RATE_LIMIT
        $eventContent = self::errorRetry($jobUrl);
        break;

      // If code returned is 500
      case $eventContent['code'] === 500: // INTERNAL_ERROR or EXPANSION_FAILED
        $eventContent = self::errorRetry($jobUrl);
        break;

      // If all else fails
      default:

        // Return false
        return [
          'success' => false,
          'error' => 'Unknown Error',
          'data' => []
        ];
    }
  }

  /**
   * Viagog scraper logic
   * @param  array $jobArray uniformed job data
   * @return null           not yet supported
   */
  public static function viagogoScrape($jobArray)
  {
    // Return not supported message
    return [
      'success' => false,
      'error' => 'Viagogo Not yet supported',
      'data' => []
    ];
  }

  /**
   * cURL data with guzzle
   * @param  array  $jobUrl array of scrape job data
   * @return array          cURL'ed data
   */
  public static function guzzleContent($jobUrl)
  {
    try {

      // Make guzzle request
      $client = new GuzzleClient();

      // Get data and unset user agent
      $eventContent = $client->get($jobUrl, [
        'headers' => [
          'User-Agent' => '',
        ]
      ]);

    // Catch my response errors
    } catch (RequestException $e) {

      // Format error as json
      $e = $e->getResponse()->json();

      // Return false
      return [
        'success' => false,
        'error' => $e['error_description'],
        'code' => $e['status_code'],
        'data' => []
      ];

    // Catch my response errors
    } catch (ServerException $e) {

      // Format error as json
      $e = $e->getResponse()->json();

      // Return false
      return [
        'success' => false,
        'error' => $e['error_description'],
        'code' => $e['status_code'],
        'data' => []
      ];

    }

    // Parse response
    try {

      // Get the json response in array format
      $eventContent = json_decode($eventContent->getBody()->getContents(), true);

    } catch (ParseException $e) {

      // Return empty array
      return [];
    }

    // Return response
    return [
      'success' => true,
      'error' => null,
      'data' => $eventContent
    ];
  }

  /**
   * Retry guzzle if we have a 400
   * @param  string $jobUrl       Url to scrape
   * @return array                Array of events data
   */
  public static function errorRetry($jobUrl)
  {
    // Retry
    do {

      \Log::info('YAAAWWWNNN ... im sleepy. Taking an hour nap');

      // Sleep for an hour
      sleep(3600);

      // Retry guzzle content
      $eventContent = self::guzzleContent($jobUrl);

    // While response is 400
    } while (isset($eventContent['code']) && $eventContent['code'] === 400);

    // Retun data
    return [
      'success' => true,
      'error' => null,
      'data' => $eventContent
    ];
  }
}
