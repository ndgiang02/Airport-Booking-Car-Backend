<?php

namespace App\Jobs;

use App\Models\TripBooking;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class CheckDriverResponseJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $tripId;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($tripId)
    {
        $this->tripId = $tripId;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $trip = TripBooking::find($this->tripId);

        if ($trip) {
            if ($trip->trip_status !== 'accepted') {
                Log::info('Driver did not confirm for trip ID: ' . $this->tripId . '. Searching for another driver...');
                FindNearestDriverJob::dispatch($trip);
            } else {
                Log::info('Driver has confirmed the trip ID: ' . $this->tripId);
            }
        } else {
            Log::error('Trip not found for trip ID: ' . $this->tripId);
        }
    }
}
