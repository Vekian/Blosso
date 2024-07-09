<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\PlantController;
use App\Http\Controllers\UserPlantController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/register', [AuthController::class, 'register']);

Route::post('/login', [AuthController::class, 'login']);

Route::post('/me', [AuthController::class, 'me'])->middleware('auth:sanctum');

Route::prefix('/plant')->name('plant.')->controller(PlantController::class)->group(function () {

    Route::get('/', 'index')->name('index');

    Route::get('/update', 'update')->name('update');

    Route::get('/{name}', 'show')->name('show');

    Route::post('/', 'create')->name('create');

    Route::delete('/{plant}', 'destroy')->name('destroy');

});


Route::prefix('/user/plant')->name('user_plant.')->controller(UserPlantController::class)->group(function () {

    Route::post('/', 'create')->name('create')->middleware('auth:sanctum');

    Route::delete('/{plant}', 'destroy')->name('destroy')->middleware('auth:sanctum');

});