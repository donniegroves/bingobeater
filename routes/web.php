<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CardSongController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});
Route::get('/view-cards/{game_id}', [CardSongController::class, 'viewCards']);
Route::post('mark-song-played', [CardSongController::class, 'toggleSongPlayed']);
