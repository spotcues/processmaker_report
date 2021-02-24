<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Queue;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Queue\Events\JobProcessing;

use ProcessMaker\Services\Api\Reports;
use App\Jobs\ReportsEmailEvent;
use App\Jobs\ReportsDashboardEmailEvent;


class ReportsServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        
        $this->app->bindMethod(ReportsEmailEvent::class.'@handle', function ($job, $app) {
            return $job->handle($app->make(Reports::class));
        });

        $this->app->bindMethod(ReportsDashboardEmailEvent::class.'@handle', function ($job, $app) {
            return $job->handle($app->make(Reports::class));
        });

        $this->app->singleton(Reports::class, function ($app) {
            return new Reports();
        });
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Queue::failing(function (JobFailed $event) {
            $data = $event->job->payload();
            $command = (unserialize($data['data']['command']));
            $command->failed($event->exception);
        });
    }
}
