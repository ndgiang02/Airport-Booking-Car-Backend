<?php

namespace App\Jobs;

use App\Models\TripBooking;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Bus\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

class ClusteringJob implements ShouldQueue
{

    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle()
    {
        $trips = TripBooking::where('trip_status', 'requested')
            ->where('trip_type', 'airport_sharing')
            ->get();

        $locations = $trips->map(function ($trip) {
            return [
                'from_lat' => $trip->from_lat,
                'from_lng' => $trip->from_lng,
                'to_lat' => $trip->to_lat,
                'to_lng' => $trip->to_lng,
                'scheduled_time' => $trip->scheduled_time->format('Y-m-d H:i'),
                'passenger_count' => $trip->passenger_count,
                'vehicle_type' => $trip->vehicle_type,
                'seating_capacity' => $trip->vehicleType->seating_capacity
            ];
        })->toArray();

        $client = new \GuzzleHttp\Client();
        $response = $client->post('http://python-api:5000/cluster', [
            'json' => ['trips' => $locations]
        ]);

        $result = json_decode($response->getBody(), true);

        if ($result['status'] == 'success') {
            foreach ($trips as $index => $trip) {
                $trip->cluster_group = $result['clusters'][$index];
                $trip->save();
            }

            $clusters = collect($result['clusters'])->unique();

            $tripsGroupedByCluster = [];
    
            foreach ($clusters as $clusterId) {
                $tripsInCluster = TripBooking::where('cluster_group', $clusterId)->get();
               // AssignDriverToClusterJob::dispatch($clusterId, $tripsInCluster->first()->vehicle_type);
                $tripsGroupedByCluster[$clusterId] = $tripsInCluster;
            }
    
            foreach ($tripsGroupedByCluster as $clusterId => $tripsInCluster) {
                if ($tripsInCluster->isNotEmpty()) {
                    if ($tripsInCluster->isNotEmpty()) {
                        $lastAssignedTime = Cache::get('cluster_' . $clusterId . '_driver_assigned_at');
                        if (!$lastAssignedTime || Carbon::parse($lastAssignedTime)->addMinutes(3)->isPast()) {
                            AssignDriverToClusterJob::dispatch($clusterId, $tripsInCluster->first()->vehicle_type);
                            Cache::put('cluster_' . $clusterId . '_driver_assigned_at', now(), now()->addMinutes(3));
                        }
                    }
                    //AssignDriverToClusterJob::dispatch($clusterId, $tripsInCluster->first()->vehicle_type);
                }
            }
/*
            foreach ($clusters as $clusterId) {
                $tripsInCluster = TripBooking::where('cluster_group', $clusterId)->get();

                $nowTime = now();

                $earliestTime = $tripsInCluster->min('scheduled_time');

                $findDriverTime = \Carbon\Carbon::parse($earliestTime)->subMinutes(30);

                AssignDriverToClusterJob::dispatch($clusterId, $tripsInCluster->first()->vehicle_type);
            }
                */
        }
    }
}
