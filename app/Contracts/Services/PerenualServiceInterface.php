<?php 

namespace App\Contracts\Services;

use App\Models\Plant;
use Illuminate\Http\Client\Response;
use Illuminate\Http\JsonResponse;

interface PerenualServiceInterface
{
    public function fetchData(string $baseUrl, int|null $id = null, string|null $query = null): Response;
    public function updatePlant(array $plantData): Plant;
    public function updatePlants(): JsonResponse;
    public function fetchPlantId(string $query): int;
    public function translatePlantName(string $plantName, string $langage): string;
}