<?php 

namespace App\Contracts\Services;

use App\Models\Plant;
use Illuminate\Http\Client\Response;

interface WeatherServiceInterface
{
    public function fetchData(string $baseUrl, string|null $query = null): Response;
    public function cacheData(string $city, int|null $days = null, string|null $filter = null): array;
    public function calculeWhenToWater(Plant $plant, $city): array;
}