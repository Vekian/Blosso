<?php

use App\Services\Api\WeatherService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schedule;
 
Schedule::call(function () {
    $apiService = new WeatherService();
    $apiService->sendReminderWatering();
})->daily();

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();
