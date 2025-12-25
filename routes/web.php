<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\GameController;
use App\Http\Controllers\RevealController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::get('/health/bot', function () {
    $lastSeen = \Illuminate\Support\Facades\Cache::get('telegram_bot_last_seen');
    if ($lastSeen && \Carbon\Carbon::parse($lastSeen)->diffInMinutes(now()) < 5) {
        return response()->json(['status' => 'ok', 'last_seen' => $lastSeen]);
    }

    return response()->json(['status' => 'error', 'last_seen' => $lastSeen], 503);
});

Route::get('/new', [GameController::class, 'create'])->name('game.create');
Route::post('/game', [GameController::class, 'store'])->name('game.store');

Route::get('/game/{game}/constraints', [GameController::class, 'constraints'])->name('game.constraints');
Route::post('/game/{game}/constraints', [GameController::class, 'storeConstraints'])->name('game.storeConstraints');

Route::get('/game/join/{token}', [GameController::class, 'showJoin'])->name('game.join');
Route::post('/game/join/{token}', [GameController::class, 'join'])->name('game.join.post');

Route::get('/game/{game}/assign', [GameController::class, 'assign'])->name('game.assign');
Route::get('/game/{game}/edit', [GameController::class, 'edit'])->name('game.edit');
Route::patch('/game/{game}', [GameController::class, 'update'])->name('game.update');
Route::get('/game/{game}/result', [GameController::class, 'result'])->name('game.result');
Route::post('/game/{game}/start', [GameController::class, 'startGame'])->name('game.start');

Route::get('/reveal/{gameId}/{participantId}/{token}', [RevealController::class, 'show'])->name('reveal.show');
Route::post('/reveal/{gameId}/{participantId}/{token}/wishlist', [RevealController::class, 'updateWishlist'])->name('wishlist.update');
Route::post('/reveal/{gameId}/{participantId}/{token}/resend', [RevealController::class, 'resendNotification'])->name('reveal.resend');

Route::get('/login/telegram', [AuthController::class, 'telegramLogin'])->name('login.telegram');
Route::post('/login/telegram/webapp', [AuthController::class, 'webAppLogin'])->name('login.telegram.webapp');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

Route::prefix('admin')->name('admin.')->middleware(['auth', 'admin'])->group(function () {
    Route::get('/', [AdminController::class, 'index'])->name('index');
    Route::delete('/game/{game}', [AdminController::class, 'destroyGame'])->name('game.destroy');
});

Route::get('/my-games', [GameController::class, 'myGames'])->name('game.myGames')->middleware('auth');

Route::get('/locale/{lang}', function ($lang) {
    if (in_array($lang, ['en', 'uk'])) {
        session(['locale' => $lang]);
        if (auth()->check()) {
            auth()->user()->update(['language' => $lang]);
        }
        session()->save();
    }

    return redirect()->back();
})->name('locale.switch');
