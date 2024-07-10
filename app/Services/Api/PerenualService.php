<?php

namespace App\Services\Api;

use App\Contracts\Services\PerenualServiceInterface;
use App\Models\Plant;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\JsonResponse;

class PerenualService implements PerenualServiceInterface
{
    const URL_LIST_PLANTS = "https://perenual.com/api/species-list";
    const URL_PLANT = "https://perenual.com/api/species/details/";
    
    public function fetchData(string $baseUrl, int|null $id = null, string|null $query = null): Response
    {
        $token = env('API_PERENUAL_KEY');
        $url = $baseUrl;

        if ($id){
;            $url .= $id;
        }
        $url .= "?key=" . $token;

        if ($query) {
            $url .= "&q=" . $query;
        }

        return Http::get($url);
    }

    public function updatePlant(array $plantData): Plant 
    {
        $plant = Plant::updateOrCreate(['id' => $plantData['id']], [
            "common_name" => $plantData['common_name'],
            'watering_general_benchmark' => json_encode($plantData['watering_general_benchmark']),
            'watering' => $plantData['watering'],
            'watering_period' => $plantData['watering_period'],
            'depth_water_requirement' => json_encode($plantData['depth_water_requirement']),
            'volume_water_requirement' => json_encode($plantData['volume_water_requirement']),
        ]);

        return $plant;
    }

    public function updatePlants(): JsonResponse
    {
        try {
            $plants = Plant::all();
        } catch (\Exception $e) {
            return response()->json(['message' => 'Erreur lors de la récupération des plantes'], 500);
        }

        foreach($plants as $plant) {
            $response = $this->fetchData($this::URL_PLANT, $plant->id);
            if ($response->successful()){
                $data = $response->json();
                $this->updatePlant($data);
            }
            else {
                return response()->json(['message' => "Impossible d'upload la plante n°" . $plant->id, 400]);
            }
        }

        return response()->json(['message' => "La base de donnée a été mise à jour avec succès", 201]);
    }

    public function fetchPlantId(string $query): int
    {
        $response = $this->fetchData($this::URL_LIST_PLANTS, null, $query);
        if ($response->successful()){
            $data = $response->json();
            return $data[0]['id'];
        }
        else return 0;
    }
}
