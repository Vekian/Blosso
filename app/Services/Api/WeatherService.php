<?php

namespace App\Services\Api;

use App\Contracts\Services\WeatherServiceInterface;
use App\Models\Plant;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class WeatherService implements WeatherServiceInterface
{
    const URL_CURRENT_WEATHER = "http://api.weatherapi.com/v1/current.json";
    const URL_FORECAST_WEATHER = "http://api.weatherapi.com/v1//forecast.json";
    const MAX_DAYS = 7;
    const CHANCE_OF_RAIN = 30;

    public function fetchData(string $baseUrl, ?string $query = null, int $days = null, string $filter = null): Response
    {
        $token = env('API_WEATHER_KEY');
        $url = $baseUrl;

        $url .= "?key=" . $token;

        if ($query) {
            $url .= "&q=" . $query;
        }

        if ($days) {
            $url .= "&days=" . $days;
        }

        if ($filter) {
            $url .= $filter;
        }

        return Http::get($url);
    }

    public function cacheData(string $city, int|null $days = null, string|null $filter = null): array
    {
        $seconds = 7200;
        $idCache = $city . $days;
        $value = Cache::remember($idCache, $seconds, function () use ($city, $days, $filter) {
            $response = $this->fetchData($this::URL_FORECAST_WEATHER, $city, $days, $filter);
            if ($response->successful() && $response->json()) {
                return $response->json();
            }
            else return [];
        });

        return $value;
    }

    public function calculeWhenToWater(Plant $plant, $city){
        $wateringData = json_decode($plant->watering_general_benchmark);
        $parts = explode('-', $wateringData->value);
        $daysWithoutNeedToWater = (intval($parts[0]) + intval($parts[1])) / 2;
        
        if ($wateringData->unit === "days"){
            $filter = "&hour=12";
            $weatherData = $this->cacheData($city, $this::MAX_DAYS, $filter);
            if ($weatherData !== []){
                $forecasts = $weatherData['forecast']['forecastday'];
                $dateToWater = "";
                $willRain = false;
                $trust = true;
                $indexMax = $daysWithoutNeedToWater > $this::MAX_DAYS ? $this::MAX_DAYS : $daysWithoutNeedToWater;
                for ($i = 0; $i < $indexMax; $i++) {
                    $rain = $forecasts[$i]['day']['daily_will_it_rain'];
                    $chanceOfRain = $forecasts[$i]['day']['daily_chance_of_rain'];
                    if ($rain && $chanceOfRain > $this::CHANCE_OF_RAIN) {
                        $willRain = true;
                        if ($daysWithoutNeedToWater + ($i +1) > $this::MAX_DAYS){
                            $trust = false;
                        } else {
                            $dateToWater = $this->calculateDate($daysWithoutNeedToWater + ($i + 1), $wateringData->unit);
                        }
                        $indexMax++;
                        if ($indexMax > $this::MAX_DAYS){
                            break;
                        }
                    }
                }
                if (!$willRain){
                    $dateToWater = $this->calculateDate($daysWithoutNeedToWater, $wateringData->unit);
                }
                if (!$trust) {
                    $dateToWater = $this->calculateDate($this::MAX_DAYS, $wateringData->unit);
                    
                }
                $result = [
                        "trust" => $trust,
                        "date" => $dateToWater,
                ];

                return $result;
            }
        }
        else {
            $dateToWater = $this->calculateDate($daysWithoutNeedToWater, $wateringData->unit);
            $result = [
                "trust" => false,
                "date" => $dateToWater,
            ];
            return $result;
        }
    }

    public function calculateDate($value, $unit){
        $currentDate = Carbon::now();
        $futureDate = match($unit) {
            "days" => $currentDate->addDays($value)->format('d-m-Y'),
            "weeks" => $currentDate->addWeeks($value)->format('d-m-Y'),
        };
        return $futureDate;
    }
}