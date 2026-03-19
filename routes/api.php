<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\OfferController;
use App\Http\Controllers\FavorisController;
use App\Http\Controllers\RequestController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\SendIdentifiantCompanyController;
use App\Http\Controllers\ApplyOfferController;
use App\Http\Controllers\SendIdentifiantForgetController;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

Route::get('/test', function () {
    return response()->json(['message' => 'OK']);
});

// tous les rôles non connecté !!!
Route::middleware(['guest'])->group(
    function () {
        Route::post('/login', [AuthController::class, 'login']); // connexion
        Route::post('/addUser', [UserController::class, 'addUser']); // inscription

        Route::get('/count', [Controller::class, 'getCount']); // affichage compteur page d'entrée
        Route::get('/allOffer', [OfferController::class, 'getOffer']);

        Route::get('/allCompany', [CompanyController::class, 'getCompany']);
        Route::get('/companyById/{id}', [CompanyController::class, 'getCompanyById']);
        Route::post('/addCompany', [CompanyController::class, 'addCompany']);

        Route::get('/allRequest', [RequestController::class, 'getRequest']);

        Route::post('/contact', [ContactController::class, 'submitContact']);
        Route::post('/send-identifiants-company', [SendIdentifiantCompanyController::class, 'sendIdentifiants']);
        Route::post('/send-identifiants', [SendIdentifiantForgetController::class, 'sendIdentifiants']);
    }
);

// Rôles : admin
Route::middleware(['auth:sanctum', 'role:admin'])->group(
    function () {
        Route::get('/allUser', [UserController::class, 'getUser']);
        Route::get('/userByRole/{role}', [UserController::class, 'getUserByRole']);
        Route::delete('/deleteUser/{id}', [UserController::class, 'deleteUser']);

        Route::patch('/toggleRequestStatus/{id}', [RequestController::class, 'toggleRequest']);
    }
);

// Rôles : company, admin
Route::middleware(['auth:sanctum', 'role:company,admin'])->group(
    function () {

        Route::get('/myOffers', [OfferController::class, 'getOffersByCompany']);
        Route::post('/addOffer', [OfferController::class, 'addOffer']);
        Route::post('/offerUpdate/{id}', [OfferController::class, 'updateOffer']);
        Route::delete('/deleteOffer/{id}', [OfferController::class, 'deleteOffer']);

        Route::post('/companyUpdate/{id}', [CompanyController::class, 'updateCompany']);
        Route::delete('/deleteCompany/{id}', [CompanyController::class, 'deleteCompany']);
    }
);

// Rôles : candidat, admin
Route::middleware(['auth:sanctum', 'role:candidat,admin'])->group(
    function () {

        // Routes Favoris - Gérées par l'utilisateur connecté (candidat)
        Route::post('/favorites/add/{offerId}', [FavorisController::class, 'addFavorite']);      // Ajouter aux favoris (utilise l'ID de l'offre)
        Route::delete('/favorites/remove/{offerId}', [FavorisController::class, 'removeFavorite']); // Retirer des favoris (utilise l'ID de l'offre)
        Route::get('/favorites', [FavorisController::class, 'getUserFavorites']);                // Afficher les offres favorites de l'utilisateur
        Route::get('/favorites/check/{offerId}', [FavorisController::class, 'checkFavorite']);   // Vérifier l'état (pour l'affichage du cœur)

        // Anciennes routes de favoris conservées par précaution si elles sont toujours dans FavorisController, mais non utilisées par le nouveau système
        Route::get('/favorisById/{id}', [FavorisController::class, 'getFavorisById']);
        Route::post('/addFavoris', [FavorisController::class, 'addFavoris']);
        Route::delete('/deleteFavoris/{id}', [FavorisController::class, 'deleteFavoris']);

        Route::post('/apply-offer', [ApplyOfferController::class, 'sendSummaryOffer']);
    }
);

// Rôles : candidat, company, admin
Route::middleware(['auth:sanctum', 'role:candidat,company,admin'])->group(function () {

    Route::get('/offerById/{id}', [OfferController::class, 'getOfferById']);

    Route::get('/userById/{id}', [UserController::class, 'getUserById']);
    Route::post('/userUpdate/{id}', [UserController::class, 'updateUser']);

    Route::post('/addRequest', [RequestController::class, 'addRequest']);
    Route::get('/requestById/{id}', [RequestController::class, 'getRequestById']);
    Route::get('/requestsByUser/{userId}', [RequestController::class, 'getRequestsByUser']);
    Route::post('/requestUpdate/{id}', [RequestController::class, 'updateRequest']);
    Route::delete('/deleteRequest/{id}', [RequestController::class, 'deleteRequest']);
});


// Test d'envoi de mail
Route::get('/test_env', function () {
    return [
        'MAIL_USERNAME' => env('MAIL_USERNAME'),
        'MAIL_PASSWORD' => env('MAIL_PASSWORD')
    ];
});
