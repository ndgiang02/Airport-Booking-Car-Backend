<?php

namespace App\Providers;

use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\ServiceProvider;

class BroadcastServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Broadcast::routes();

        Broadcast::routes(['middleware' => ['auth:api']]);

        Broadcast::channel('driver-location.{driverId}', function ($user, $driverId) {
            return true  ;//$user->isCustomerOfDriver($driverId);
        });
        

        require base_path('routes/channels.php');
    }
}
