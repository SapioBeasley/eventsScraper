<?php

namespace App\Commands;

use App\Job;
use App\Result;

use App\Helpers\ScraperCommandHelper;

use App\Commands\Command;
use Illuminate\Contracts\Bus\SelfHandling;

class EventbriteScraperCommand extends Command implements SelfHandling
{
    protected $job;
    public $results;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct($id)
    {
        // Declare job as found job id with source
        $this->job = Job::with('source')->find($id);
    }

    /**
     * Execute the command.
     *
     * @return void
     */
    public function handle()
    {
        // Convert data into array
        $this->job = $this->job->toArray();

        // Build our array
        $jobArray = ScraperCommandHelper::organizeArray($this->job);

        // Check if array bulder passes
        if ($jobArray['success'] !== true) {

            // Return false
            $this->results = [
                'success' => false,
                'error' => $jobArray['error'],
                'data' => $jobArray['data'],
            ];

            // End execution
            return $this->results;
        }

        // Select the scrape source
        $jobWork = ScraperCommandHelper::runJobSource($jobArray);

        // Check if success true
        if ($jobWork['success'] !== true) {

            // Return false
            $this->results = [
                'success' => false,
                'error' => $jobWork['error'],
                'data' => $jobWork['data'],
            ];

            // End execution
            return $this->results;
        }

        // Return success
        $this->results = [
            'success' => true,
            'error' => $jobWork['error'],
            'data' => $jobWork['data'],
        ];

        // End execition
        return $this->results;
    }
}
