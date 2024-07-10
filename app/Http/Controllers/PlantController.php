<?php

namespace App\Http\Controllers;

use App\Models\Plant;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;
use App\Services\Api\PerenualService;
use App\Services\Api\WeatherService;
use Illuminate\Http\JsonResponse;

#[OA\Info(title: "API Blossom", version: "0.1")]
#[OA\SecurityScheme(
    securityScheme: 'bearerAuth',
    type: 'http',
    scheme: 'bearer',
    bearerFormat: 'JWT'
)]
class PlantController extends Controller
{

    #[OA\Get(path: '/api/plant/', summary: "Liste les plantes", tags: ["plant"], parameters: [
        new OA\Parameter(
            name: 'Accept',
            in: 'header',
            required: true,
            schema: new OA\Schema(type: 'string'),
            example: 'application/json'
        )
    ], )]
    #[OA\Response(response: '200', description: 'The data')]
    public function index(): JsonResponse
    {
        $plants = Plant::all();

        return response()->json($plants);
    }

    #[OA\Post(path: '/api/plant/', summary: "Ajoute une plante", tags: ["plant"], parameters: [
        new OA\Parameter(
            name: 'Accept',
            in: 'header',
            required: true,
            schema: new OA\Schema(type: 'string'),
            example: 'application/json'
        ),
        new OA\Parameter(
            name: 'common_name',
            in: 'query',
            required: true,
            schema: new OA\Schema(type: 'string'),
            example: 'pissenlit'
        ),
        new OA\Parameter(
            name: 'watering_general_benchmark',
            in: 'query',
            required: true,
            schema: new OA\Schema(type: 'string'),
            example: '{"value": "5-7", "unit": "days"}'
        )
    ], )]
    #[OA\Response(response: '201', description: 'La plante a bien été ajouté')]
    public function create(Request $request, PerenualService $apiPlantService): JsonResponse
    {
        $validatedData = $request->validate([
            'common_name' => 'required|string|max:255',
            'watering_general_benchmark' => 'required',
        ]);

        $oldPlant = Plant::where('common_name', $validatedData['common_name'])
        ->where('watering_general_benchmark', $validatedData['watering_general_benchmark'])
        ->first();

        if ($oldPlant) {
            return response()->json(['message' => "La plante est déjà présente"], 400);
        }
        $plantId = $apiPlantService->fetchPlantId($validatedData['common_name']);
        if ($plantId) {
            $response = $apiPlantService->fetchData($apiPlantService::URL_PLANT, $plantId);
            if ($response->successful() || !$response->json()) {
                $plantData = $response->json();
                $plant = $apiPlantService->updatePlant($plantData);
            }
        } else {
            return response()->json(['message' => "Impossible de trouver la plante"], 404);
        }

        return response()->json([
           'plant' => $plant,
        ], 201);
    }

    #[OA\Get(path: '/api/plant/{name}', summary: "Montre une plante à partir de son nom", tags: ["plant"], parameters: [
        new OA\Parameter(
            name: 'name',
            in: 'path',
            required: true,
            schema: new OA\Schema(type: 'string'),
            example: 'rose'
        )
    ], responses: [
        new OA\Response(
            response: 200,
            description: 'Réussite',
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'message', type: 'string', example: 'Réussie')
                ]
            )
        ),
        new OA\Response(
            response: 401,
            description: 'Unauthorized',
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'message', type: 'string', example: 'Non autorisé')
                ]
            )
        ),
    ])]
    public function show(string $name, PerenualService $apiPlantService): JsonResponse
    {
        try {
            $plant = Plant::where('common_name', 'LIKE', '%' . $name . '%')->first();

            if (!$plant) {
                $response = $apiPlantService->fetchData($apiPlantService::URL_PLANT, null, $name);
                if (!$response->successful() || !$response->json()) {
                    return response()->json(['message' => 'Plante non trouvée'], 404);
                }
                $plantData = $response->json()[0];
                $plant = $apiPlantService->updatePlant($plantData);
            }
        } catch (\Exception $e) {
            return response()->json(['message' => 'Erreur lors de la récupération de la plante'], 500);
        }
        

        return response()->json($plant, 200);
    }

    #[OA\Delete(path: '/api/plant/{id}', summary: "Supprime une plante", tags: ["plant"], parameters: [
        new OA\Parameter(
            name: 'id',
            in: 'path',
            required: true,
            schema: new OA\Schema(type: 'string'),
            example: 3
        )
    ], responses: [
        new OA\Response(
            response: 200,
            description: 'Réussite',
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'message', type: 'string', example: 'Réussie')
                ]
            )
        ),
        new OA\Response(
            response: 401,
            description: 'Plante non trouvée',
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'message', type: 'string', example: 'Non trouvée')
                ]
            )
        ),
    ])]
    public function destroy(Plant $plant): JsonResponse
    {
        try {
            $plant->delete();
            return response()->json(['message' => 'Plante supprimée'], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Erreur lors de la suppression de la plante'], 500);
        }
    }

    public function update(PerenualService $apiPlantService): JsonResponse
    {
        try {
            $result = $apiPlantService->updatePlants();
            return $result;
        } catch(\Exception $e) {
            return response()->json(['message' => 'Erreur lors de la mise à jour des plantes'], 500);
        }
    }
}
