<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BookController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\StatisticsController;
use Illuminate\Support\Facades\Route;

// Routes publiques
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);
    
    Route::get('/categories', [CategoryController::class, 'index']);
    Route::get('/categories/{slug}', [CategoryController::class, 'show']);
    Route::get('/categories/{categorySlug}/books', [CategoryController::class, 'books']);
    Route::get('/categories/{categorySlug}/books/{bookSlug}', [BookController::class, 'show']);
    
    Route::get('/statistics/popular-books', [StatisticsController::class, 'popularBooks']);
    
    // Routes admin
    Route::get('/statistics', [StatisticsController::class, 'index']);
    Route::get('/statistics/degraded-books', [StatisticsController::class, 'degradedBooks']);
    Route::get('/statistics/by-category', [StatisticsController::class, 'byCategory']);
    Route::get('/statistics/collection-health', [StatisticsController::class, 'collectionHealth']);
    Route::get('/statistics/book/{bookId}', [StatisticsController::class, 'bookStatistics']);
    
    Route::post('/categories', [CategoryController::class, 'store']);
    Route::put('/categories/{category}', [CategoryController::class, 'update']);
    Route::delete('/categories/{category}', [CategoryController::class, 'destroy']);
    
    Route::get('/books', [BookController::class, 'index']);
    Route::post('/books', [BookController::class, 'store']);
    Route::put('/books/{book}', [BookController::class, 'update']);
    Route::delete('/books/{book}', [BookController::class, 'destroy']);
    Route::patch('/books/{book}/damaged', [BookController::class, 'markAsDamaged']);
});