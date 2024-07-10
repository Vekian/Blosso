<?php 

namespace App\Contracts\Services;

use App\Models\Plant;
use Illuminate\Http\Client\Response;
use Illuminate\Http\JsonResponse;

interface WeatherServiceInterface
{
    public function fetchData(string $baseUrl, string|null $query = null): Response;
    public function cacheData(string $city): array;
}