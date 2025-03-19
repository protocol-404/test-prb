<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\JobOfferController;
use App\Http\Controllers\ResumeController;
use App\Http\Controllers\ApplicationController;
use App\Http\Controllers\ExportController;

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

// Authentication Routes
Route::group([
    'prefix' => 'auth'
], function () {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);
    
    Route::group([
        'middleware' => 'jwt.verify'
    ], function () {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::post('refresh', [AuthController::class, 'refresh']);
        Route::get('profile', [AuthController::class, 'userProfile']);
    });
});

// User Management Routes
Route::group([
    'middleware' => 'jwt.verify',
    'prefix' => 'user'
], function () {
    Route::get('profile', [UserController::class, 'show']);
    Route::put('profile', [UserController::class, 'update']);
    Route::post('skills', [UserController::class, 'addSkills']);
    Route::delete('skills/{skill}', [UserController::class, 'removeSkill']);
    
    // Admin only routes
    Route::get('all', [UserController::class, 'index']);
});

// Job Offer Routes
Route::group([
    'middleware' => 'jwt.verify',
    'prefix' => 'job-offers'
], function () {
    Route::get('/', [JobOfferController::class, 'index']);
    Route::get('/{id}', [JobOfferController::class, 'show']);
    Route::get('/my-offers', [JobOfferController::class, 'myJobOffers']);
    
    // Recruiter only routes
    Route::post('/', [JobOfferController::class, 'store']);
    Route::put('/{id}', [JobOfferController::class, 'update']);
    Route::delete('/{id}', [JobOfferController::class, 'destroy']);
});

// Resume Routes
Route::group([
    'middleware' => 'jwt.verify',
    'prefix' => 'resumes'
], function () {
    Route::get('/', [ResumeController::class, 'index']);
    Route::post('/', [ResumeController::class, 'upload']);
    Route::get('/{id}', [ResumeController::class, 'show']);
    Route::get('/{id}/download', [ResumeController::class, 'download']);
    Route::delete('/{id}', [ResumeController::class, 'destroy']);
});

// Application Routes
Route::group([
    'middleware' => 'jwt.verify',
    'prefix' => 'applications'
], function () {
    Route::get('/', [ApplicationController::class, 'index']);
    Route::post('/', [ApplicationController::class, 'store']);
    Route::post('/batch', [ApplicationController::class, 'batchApply']);
    Route::get('/{id}', [ApplicationController::class, 'show']);
    Route::put('/{id}/status', [ApplicationController::class, 'updateStatus']);
});

// Export Routes
Route::group([
    'middleware' => 'jwt.verify',
    'prefix' => 'export'
], function () {
    Route::get('/applications/excel', [ExportController::class, 'exportExcel']);
    Route::get('/applications/csv', [ExportController::class, 'exportCsv']);
    Route::get('/weekly-report/latest', [ExportController::class, 'downloadLatestWeeklyReport']);
});
