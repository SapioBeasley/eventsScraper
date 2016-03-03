<?php

namespace App\Commands;

use App\Result;
use App\Source;
use App\Job;

use App\Helpers\ApiHelper;
use App\Commands\Command;
use Illuminate\Contracts\Bus\SelfHandling;

/**
 * Run with:
 * Bus::dispatch($command = new App\Commands\ImportResultsCommand())
 */
class ImportResultsCommand extends Command implements SelfHandling
{
    // private $jobResults;
    public $results;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->skip = 0;
    }

    /**
     * Execute the command.
     *
     * @return void
     */
    public function handle()
    {
        // Loop through events
        do {

            // Grab events using skip
            $jobResults = Result::where('processed_status', '=', NULL)->skip($this->skip)->take(20)->get()->toArray();

            // Check if there are events available
            if (empty($jobResults)) {

                // Reset skip
                $this->skip = 0;

                // Sleep for a minute
                sleep(60);

                // Check again
                $repeat = true;

            } else {

                // Format results array
                $events = $this->eventRequired($jobResults);

                // Check error
                if ($events['success'] === false) {

                    // Reset skip
                    $this->skip = 0;

                    echo isset($events['error']) ? $events['error'] : 'No new events to import, sleeping.' . "\n";

                    // Sleep for a minute
                    sleep(60);

                    // Check again
                    $repeat = true;

                } else {

                    // For each event
                    foreach ($events['data'] as $event) {

                        // Instanciat apiHelper
                        $api = new ApiHelper;

                        $checkItendifier = $api->index('events?filter[and][][import_identifier]=' . $event['import_identifier']);

                        if ($checkItendifier['success'] === true && isset($checkItendifier['data']['events'][0])) {

                            try {

                                echo 'Found prevous event ' . $checkItendifier['data']['events'][0]['title'] . ' updating it' . "\n";

                                // Update event on api
                                $checkDuplicate = $api->update('events', $checkItendifier['data']['events'][0]['id'], $event);

                            } catch (RequestException $e) {

                              // Return empty array
                              return [];

                            } catch (ServerException $e) {

                              // Return empty array
                              return [];
                            }

                        } else {

                            try {

                                // Create event on api
                                $checkDuplicate = $api->store('events', $event);

                            } catch (RequestException $e) {

                              // Return empty array
                              return $e;

                            } catch (ServerException $e) {

                              // Return empty array
                              return $e;
                            }

                        }

                        // Instanciate carbon
                        $processedAt = \Carbon\Carbon::now();

                        // Check failed create
                        if ($checkDuplicate['success'] === false || $checkItendifier['success'] === false) {

                            // Update result with failed
                            $result = Result::find($event['jobId'])->update([
                                'processed_at' => $processedAt->toDateTimeString(),
                                'processed_status' => 'failed'
                            ]);

                            // Go to next
                            \Log::warning('Error creating, skipping : ' . $event['title']);
                            continue 1;
                        }

                        // Update result with success
                        $result = Result::find($event['jobId'])->update([
                            'processed_at' => $processedAt->toDateTimeString(),
                            'processed_status' => 'success'
                        ]);

                        // Declare event insert
                        $eventInsert = isset($checkDuplicate['data']['event']) ? $checkDuplicate['data']['event'] : $checkDuplicate['data']['resource'];

                        try {

                            // Upload image to event
                            $uploadThisPhoto = $this->uploadPhoto($event['userId'], $eventInsert['slug'], $event['logo']);

                        } catch (RequestException $e) {

                            // Return empty array
                            return [];

                        } catch (ServerException $e) {

                            // Return empty array
                            return [];
                        }

                    }

                    // Increase skip
                    $this->skip += 20;

                    echo 'Checking next ' . $this->skip . ' results' . "\n";

                    $repeat = true;
                }
            }

        // Repeat Loop
        } while ($repeat);
    }

    /**
     * Upload photo logic
     *
     * @param  integer   $userId     User Id to of creater
     * @param  string    $slug       Slug of the event
     * @param  string    $photoUrl   Url of the image to upload
     * @return bool
     */
    public function uploadPhoto($userId, $slug, $photoUrl)
    {
        // Check for fields
        if (empty($userId) || empty($slug) || empty($photoUrl)) {
            return false;
        }

        // Random hash
        $hash = time();

        // s3 upload string
        $key = "{$userId}_{$slug}_{$hash}";

        // Get contents
        $contents = @file_get_contents($photoUrl);

        // Do not execute further if we could not get data
        if ($contents === false) {
            return false;
        }

        // Declare S3
        $s3 = \AWS::get('s3');

        // Send to S3 upload bucket
        $results = $s3->putObject([
            'Bucket' => \Config::get('aws.s3.upload'),
            'Key' => $key,
            'Body' => $contents,
        ]);

        // If we get a url
        if ( ! empty($results->get('ObjectURL'))) {
            return true;
        }

        return false;
    }

    public function getUser($email)
    {
        // Insanciate ApiHelper
        $api = new ApiHelper;

        $email = urlencode($email);

        // Grab user data
        $getUser = $api->index('users?filter[and][][email]=' . $email);

        return $getUser;
    }

    /**
     * Build event create array
     *
     * @param  array   $eventData   Result data from results table
     * @return array                Properly formatted array to create events
     */
    public function eventRequired($eventData)
    {
        // Email of inload user
        $email = 'dev1+inload1@shindiig.com';

        // // Insanciate ApiHelper
        $api = new ApiHelper;

        // Grab user data
        $getUser = $this->getUser($email);

        if ($getUser['success'] === false) {

            $user = [
                'time_zone_id' => 6,
                'first_name' => 'Events',
                'last_name' => 'Inloader',
                'email' => $email,
                'password' => 'inloader',
            ];

            $getUser = $api->store('users', $user);

            // Grab user data
            $getUser = $this->getUser($email);
        }

        // Check get user response
        if (empty($getUser['data']['users'])) {

            // Return false and end execution
            return [
                'success' => false,
                'error' => 'Inload user does not exist',
                'data' => [],
            ];
        };

        // For each event result
        foreach ($eventData as $event) {

            // Use identifier to find result
            $result = Result::where('identifier', '=', $event['identifier'])->with('job')->first();

            // Use reult job ID to find job
            $job = Job::where('id', '=', $result->job->id)->with('source')->first();

            // Use job source id to find the source
            $source = Source::where('id', '=', $job->source->id)->first();

            // Decode results
            $event = json_decode($event['result'], true);

            // Grab timezone
            $timeZoneId = $api->index('collections/timezones?filter[and][][zonePhp]=' . $event['start']['timezone']);

            // Check timezone
            switch (true) {

                // Timezone found, declare it
                case isset($timeZoneId['data']['timezones'][0]['id']):
                    $timeZoneId = $timeZoneId['data']['timezones'][0]['id'];
                    break;

                // No timezone found, error it
                default:
                    $timeZoneId = null;
                    break;
            }

            // remove special characters
            $string = preg_replace("/[^a-zA-Z0-9\s]/", "", $event['name']['text'] . ' ' . $event['category']['name'] . ' ' . $event['subcategory']['name'] . ' ' . $event['description']['text']);

            // Set max possible tags
            $tags = preg_replace("/^(.{1,2000}[^\s]*).*$/s", "\\1", $string);

            // Remove spaces and comma separate each tag
            $tags = preg_replace('#\s+#',', ',trim($tags));

            $tags = $api->tagVariations(explode(', ', $tags));

            // Event categories
            $categories = $api->index('tags?filter[and][][is_category]=1');
            $categories = $categories['data']['tags'];

            foreach ($categories as $key => $value){
              $categories[$key] = $value['tag'];
            }

            // Set default category1
            $category1 = NULL;

            // Compare tags with categories, reset key values
            $category1 = array_values(array_intersect($tags, $categories));

            if (isset($category1[0])) {
                $category1 = $category1[0];
            }

            // Build meta array
            $meta = [
                'schedules' => [
                    [
                        'timeZoneId' => $timeZoneId,
                        'start' => [
                            'date' => date('Y-m-d', strtotime($event['start']['local'])),
                            'time' => date('H:i:s', strtotime($event['start']['local'])),
                        ],
                        'end' => [
                            'date' => date('Y-m-d', strtotime($event['end']['local'])),
                            'time' => date('H:i:s', strtotime($event['end']['local'])),
                        ],
                        'repeat' => []
                    ],
                ],
            ];

            // Build event data
            $events[] = [
                'jobId' => $result->id,
                'userId' => $getUser['data']['users'][0]['id'],
                'title' => $event['name']['text'],
                'address1' => $event['venue']['address']['address_1'],
                'zipcode' => $event['venue']['address']['postal_code'],
                'description' => $event['description']['text'],
                'venueName' => $event['venue']['name'],
                'city' => $event['venue']['address']['city'],
                'state' => $event['venue']['address']['region'],
                'zipcode' => $event['venue']['address']['postal_code'],
                'country' => $event['venue']['address']['country'],
                'latitude' => $event['venue']['latitude'],
                'longitude' => $event['venue']['longitude'],
                'is_published' => 1,
                'is_automatic' => 1,
                'import_source' => $source->name,
                'import_identifier' => $event['id'],
                'timezone' => $event['start']['timezone'],
                'start' => $event['start']['local'],
                'end' => $event['end']['local'],
                'logo' => $event['logo']['url'],
                'category1' => $category1,
                'tags' => implode(',', array_slice($tags, 0, 5)),
                'meta' => json_encode($meta),
            ];
        }

        // End execution with events
        return [
            'success' => true,
            'error' => null,
            'data' => isset($events) ? $events : []
        ];
    }
}
