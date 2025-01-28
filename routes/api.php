<?php

use App\Http\Controllers\Api\Auth\UserController;
use App\Http\Controllers\Api\Admin\CategorieController;
use App\Http\Controllers\Api\Admin\ProduitController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

//test
Route::get('test', function () {
    return 'test';
});

//lien qui permettra au client (React de naviguer)
Route::prefix('admin')->name('admin.')->group(function () {
    Route::resource('categorie', CategorieController::class)->except(['show', 'create', 'store']);
    Route::resource('produit', ProduitController::class)->except(['show', 'create']);

});

Route::post('/register', [UserController::class, 'register']);
Route::post('/login', [UserController::class, 'login']);
Route::middleware('auth:sanctum')->post('/logout', [UserController::class, 'logout']);
Route::post('/validation', [UserController::class, 'validateCode']);
