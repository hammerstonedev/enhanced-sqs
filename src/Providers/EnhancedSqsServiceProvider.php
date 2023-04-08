<?php
/**
 * @author Aaron Francis <aaron@hammerstone.dev>
 */

namespace Hammerstone\EnhancedSqs\Providers;

use App\Services\SqsConnector;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\ServiceProvider;

class EnhancedSqsServiceProvider extends ServiceProvider
{
    public function register()
    {
        //
    }

    public function boot()
    {
        Queue::extend('enhanced-sqs', function () {
            return new SqsConnector;
        });
    }
}
