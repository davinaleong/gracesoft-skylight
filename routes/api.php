<?php

use App\Http\Controllers\Api\Bot\BoardsController;
use App\Http\Controllers\Api\Bot\CardsController;
use App\Http\Controllers\Api\V1\BoardController;
use App\Http\Controllers\Api\V1\CardController;
use App\Http\Controllers\Api\V1\ColumnController;
use App\Http\Controllers\Api\V1\CommentController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::middleware(['auth:sanctum', 'throttle:api'])->prefix('v1')->name('api.v1.')->group(function () {
    Route::apiResource('boards', BoardController::class);

    Route::get('boards/{board}/columns', [ColumnController::class, 'index'])->name('boards.columns.index');
    Route::post('boards/{board}/columns', [ColumnController::class, 'store'])->name('boards.columns.store');
    Route::patch('columns/{column}', [ColumnController::class, 'update'])->name('columns.update');
    Route::delete('columns/{column}', [ColumnController::class, 'destroy'])->name('columns.destroy');

    Route::get('columns/{column}/cards', [CardController::class, 'index'])->name('columns.cards.index');
    Route::post('columns/{column}/cards', [CardController::class, 'store'])->name('columns.cards.store');
    Route::get('cards/{card}', [CardController::class, 'show'])->name('cards.show');
    Route::patch('cards/{card}', [CardController::class, 'update'])->name('cards.update');
    Route::delete('cards/{card}', [CardController::class, 'destroy'])->name('cards.destroy');

    Route::get('cards/{card}/comments', [CommentController::class, 'index'])->name('cards.comments.index');
    Route::post('cards/{card}/comments', [CommentController::class, 'store'])->name('cards.comments.store');
    Route::delete('comments/{comment}', [CommentController::class, 'destroy'])->name('comments.destroy');
});

Route::prefix('bot')->middleware('auth:sanctum')->group(function (): void {
    Route::get('/cards/due', [CardsController::class, 'due']);
    Route::get('/boards/summary', [BoardsController::class, 'summary']);
});
