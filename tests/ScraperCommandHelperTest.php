<?php

use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;

use App\Helpers\ApiHelper;
use App\Helpers\ScraperCommandHelper;

class ScraperCommandHelperTest extends TestCase
{

  /**
   * Test build array from eventbrite data
   *
   * @return void
   */
  public function testOrganizeArray()
  {
    $data = [
      'id' => '18090241399',
      'name' => [
        'text' => 'These Guys at Ron DeCar Event Center',
      ],
      'start' => [
        'timezone' => 'America/Los_Angeles',
        'local' => '2015-10-16T20:00:00',
        'utc' => '2015-10-17T03:00:00Z',
      ],
      'end' => [
        'timezone' => 'America/Los_Angeles',
        'local' => '2015-10-16T21:30:00',
        'utc' => '2015-10-17T04:30:00Z',
      ],
      'category' => [
        'short_name' => 'Music',
      ],
      'last_run_at' => null,
      'source_id' => '1',
      'source' => [
        'id' => '1',
        'name' => 'eventbrite',
        'max_requests' => '900',
        'interval_seconds' => '60'
      ],
      'is_enabled' => '1',
      'url' => 'https://www.eventbriteapi.com/v3/events/search/?venue.city=vegas&expand=organizer,categories,category,organizer.description,venue,subcategory,location.address,location.latitude,location.longitude&token={token}',
    ];

    $result = ScraperCommandHelper::organizeArray($data);

    // Check result
    $this->assertEquals(true, $result['success']);
    $this->assertEquals('These Guys at Ron DeCar Event Center', $result['data']['job_name']['text']);
  }

  /**
   * Return the source to be ran
   *
   * @return void
   */
  public function testRunJobSource()
  {
    $jobArray = [
      'data' => [
        'source_name' => 'eventbrite',
        'job_id' => '1',
        'job_name' => 'Scrape vegas',
        'job_status' => null,
        'job_url' => 'https://www.eventbriteapi.com/v3/events/search/?venue.city=vegas&expand=organizer,categories,category,organizer.description,venue,subcategory,location.address,location.latitude,location.longitude&token={token}',
        'last_run_at' => null,
        'source_id' => '1',
        'max_requests' => '900',
        'interval_seconds' => '60',
      ]
    ];

    $result = ScraperCommandHelper::runJobSource($jobArray);
    dd($result);
  }

  /**
   * Scrape eventbrite datase
   *
   * @return void
   */
  public function testEventbriteScrape()
  {
    $result = ScraperCommandHelper::eventbriteScrape($jobArray);
    dd($result);
  }

  /**
   * Pull events
   *
   * @return void
   */
  public function testScrapeIt()
  {
    $result = ScraperCommandHelper::scrapeIt($jobUrl, $jobId, $totalPages, $jobMaxRequests, $jobIntervalSecond);
    dd($result);
  }

  // /**
  //  * Replace parameters in url
  //  *
  //  * @return void
  //  */
  // protected function testConfigureScrapeUrl()
  // {
  //   $result = ScraperCommandHelper::configureScrapeUrl($jobUrl, $parameters);
  //   dd($result);
  // }

  // /**
  //  * Check and display errors
  //  *
  //  * @return void
  //  */
  // public function testErrorSwitch()
  // {
  //   $result = ScraperCommandHelper::errorSwitch($eventContent);
  //   dd($result);
  // }

  // /**
  //  * Viagogo not supported currently
  //  *
  //  * @return void
  //  */
  // public function testViagogoScrape()
  // {
  //   $result = ScraperCommandHelper::viagogoScrape($jobArray);
  //   dd($result);
  // }

  // /**
  //  * Get data from URL
  //  *
  //  * @return void
  //  */
  // public function testGuzzleContent()
  // {
  //   $result = ScraperCommandHelper::guzzleContent($jobUrl);
  //   dd($result);
  // }

  // /**
  //  * If non life threatening error comes up retry
  //  *
  //  * @return void
  //  */
  // public function testErrorRetry()
  // {
  //   $result = ScraperCommandHelper::errorRetry($jobUrl);
  //   dd($result);
  // }
}
