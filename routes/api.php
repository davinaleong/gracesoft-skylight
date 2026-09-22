<?php

use App\Http\Controllers\Api\Bot\BoardsController;
use App\Http\Controllers\Api\Bot\CardsController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::prefix('bot')->middleware('auth:sanctum')->group(function (): void {
    Route::get('/cards/due', [CardsController::class, 'due']);
    Route::get('/boards/summary', [BoardsController::class, 'summary']);
});
