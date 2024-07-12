<?php

namespace App\Http\Controllers;

use App\Models\Plant;
use App\Services\Api\DateService;
use App\Services\Api\WeatherService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class UserPlantController extends Controller
{
    /**
     * Show the form for creating a new resource.
     */
    #[OA\Post(path: '/api/user/plant/', summary: "Ajoute une plante à un utilisateur", tags: ["user_plant"], security: [['bearerAuth' => []]], requestBody: new OA\RequestBody(
        required: true,
        description: "Json payload",
        content: [
            'application/json' => new OA\MediaType(
                mediaType: "application/json",
                schema: new OA\Schema(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'name', type: 'string', example: 'rose'),
                        new OA\Property(property: 'city', type: 'string', example: 'Roanne'),
                        new OA\Property(property: 'country', type: 'string', example: 'France'),
                    ],
                    required: ['name', 'city', 'country']
                )
            )
                ],
    ), responses: [
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
    public function create(Request $request, WeatherService $weatherService, DateService $dateService): JsonResponse
    {
        try {
            $validatedLocalisation = $request->validate([
                'city' => 'required|string|max:255',
                'country' => 'required|string|max:255',
            ]);
            $user= $request->user();
            $user->city = $validatedLocalisation['city'];
            $user->country = $validatedLocalisation['country'];
            $user->save();
             
        } catch (\Exception $e) {
            return response()->json(['message' => 'Erreur avec les coordonnées de l\'utilisateur'], 500);
        }
        

        try {
            $validatedPlant = $request->validate([
                'name' => 'required|string|max:255',
            ]);
    
            $plant = Plant::where('common_name', 'LIKE', '%' . $validatedPlant['name'] . '%')->first();
            if (!$plant) {
                return response()->json(['message' => 'Plante non trouvée'], 404);
            }
            $notifWatering ="";
            $wateringData = [];

            try {
                $wateringData = $weatherService->calculeWhenToWater($plant, $user->city);
                $formatedDate = $dateService->formatDate($wateringData['date'], "Y-m-d", "d-m-Y");
                $notifWatering = match($wateringData['trust']){
                    true => "Il faudra l'arroser le " . $formatedDate,
                    false => "Pas besoin d'arroser jusqu'au " . $formatedDate,
                };
            } catch (\Exception $e) {
                return response()->json(['message' => 'Erreur lors du calcul de la date d\'arrosage: ' . $e], 500);
            }
            $plantUser = $user->plants()->find($plant->id);
            if (!$plantUser) {
                $user->plants()->attach($plant->id);
            }

            $currentDate = $dateService->calculateDate(0, "days");
            $user->plants()->updateExistingPivot($plant->id, ['trust' => $wateringData['trust'], 'to_water_at' => $wateringData['date'], "checked_at" => $currentDate]);
            

        } catch(\Exception $e) {
            return response()->json(['message' => "Erreur lors de l'ajout de la plante dans l'utilisateur" . $e], 500);
        }
        return response()->json(['message' => "Plante ajouté pour " . $user->name . ". " . $notifWatering], 201);
    }

    /**
     * Remove the specified resource from storage.
     */
    #[OA\Delete(path: '/api/user/plant/{id}', summary: "Supprime une plante d'utilisateur", tags: ["user_plant"], security: [['bearerAuth' => []]], parameters: [
        new OA\Parameter(
            name: 'id',
            in: 'path',
            description: "id de la plante",
            required: true,
            schema: new OA\Schema(type: 'integer'),
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
            description: 'Unauthorized',
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'message', type: 'string', example: 'Non autorisé')
                ]
            )
        ), 
    ])]
    public function destroy(Plant $plant, Request $request): JsonResponse
    {
        try {
            try {
                $user= $request->user();
            } catch(\Exception $e) {
                return response()->json(['message' => "Erreur d'authentification"], 403);
            }
            
            $user->plants()->detach($plant->id);
            return response()->json(['message' => 'Plante supprimée'], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Erreur lors de la suppression de la plante'], 500);
        }
    }
}
