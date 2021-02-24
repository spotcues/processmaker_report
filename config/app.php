<?php

use Illuminate\Cache\CacheServiceProvider;
use Illuminate\Filesystem\FilesystemServiceProvider;
use Illuminate\View\ViewServiceProvider;
use App\Providers\ReportsServiceProvider;

return [
    'name' => env('APP_NAME', 'Groupe.io Workflow Engine'),
    'url' => env('APP_URL', 'http://localhost'),
    'env' => env('APP_ENV', 'production'),
    'debug' => env('APP_DEBUG', false),
    'cache_lifetime' => env('APP_CACHE_LIFETIME', 60),
    'pmclass_cache_lifetime' => env('PM_CLASS_CACHE_LIFETIME', 60),
    'reports_dashboard_count_query_cache_lifetime' => env('REPORTS_DASHBOARD_COUNT_QUERY_CACHE_LIFETIME', 600),
    'reports_dashboard_case_details_query_cache_lifetime' => env('REPORTS_DASHBOARD_CASE_DETAILS_QUERY_CACHE_LIFETIME', 600),
    'reports_dashboard_form_details_query_cache_lifetime' => env('REPORTS_DASHBOARD_FORM_DETAILS_QUERY_CACHE_LIFETIME', 600),
    'reports_user_details_cache_lifetime' => env('REPORTS_USER_DETAILS_CACHE_LIFETIME', 86400),
    'key' => env('APP_KEY', 'base64:rU28h/tElUn/eiLY0qC24jJq1rakvAFRoRl1DWxj/kM='),
    'cipher' => 'AES-256-CBC',
    'timezone' => 'UTC',
    'providers' => [
        FilesystemServiceProvider::class,
        CacheServiceProvider::class,
        ViewServiceProvider::class,
        Illuminate\Database\DatabaseServiceProvider::class,
        Illuminate\Foundation\Providers\ConsoleSupportServiceProvider::class,
        Illuminate\Queue\QueueServiceProvider::class,
        Illuminate\Translation\TranslationServiceProvider::class,
        Illuminate\Encryption\EncryptionServiceProvider::class,
        Laravel\Tinker\TinkerServiceProvider::class,
        Illuminate\Notifications\NotificationServiceProvider::class,
        Illuminate\Bus\BusServiceProvider::class,
        App\Providers\ReportsServiceProvider::class,
    ],

    'aliases' => [
        'Crypt' => Illuminate\Support\Facades\Crypt::class
    ],

];
