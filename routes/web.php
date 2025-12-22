<?php

use App\Http\Controllers\GameController;
use App\Http\Controllers\RevealController;
use App\Http\Controllers\AdminController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::get('/new', [GameController::class, 'create'])->name('game.create');
Route::post('/game', [GameController::class, 'store'])->name('game.store');

Route::get('/game/{game}/constraints', [GameController::class, 'constraints'])->name('game.constraints');
Route::post('/game/{game}/constraints', [GameController::class, 'storeConstraints'])->name('game.storeConstraints');

Route::get('/game/{game}/assign', [GameController::class, 'assign'])->name('game.assign');
Route::patch('/game/{game}', [GameController::class, 'update'])->name('game.update');
Route::get('/game/{game}/result', [GameController::class, 'result'])->name('game.result');

Route::get('/reveal/{gameId}/{participantId}/{token}', [RevealController::class, 'show'])->name('reveal.show');
Route::post('/reveal/{gameId}/{participantId}/{token}', [RevealController::class, 'reveal'])->name('reveal.submit');
Route::post('/reveal/{gameId}/{participantId}/{token}/wishlist', [RevealController::class, 'updateWishlist'])->name('wishlist.update');

Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [AdminController::class, 'index'])->name('index');
    Route::delete('/game/{game}', [AdminController::class, 'destroyGame'])->name('game.destroy');
});
