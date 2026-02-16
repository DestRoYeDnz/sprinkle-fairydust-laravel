<?php

use App\Http\Controllers\Api\EventController;
use App\Http\Controllers\Api\GalleryImageController;
use App\Http\Controllers\Api\QuoteController;
use App\Http\Controllers\Api\TestimonialController;
use Illuminate\Support\Facades\Route;

Route::get('events', [EventController::class, 'index']);
Route::get('gallery-images', [GalleryImageController::class, 'index']);

Route::get('testimonials', [TestimonialController::class, 'index']);
Route::post('testimonials', [TestimonialController::class, 'store']);
Route::post('testimonials/upload-image', [TestimonialController::class, 'uploadImage']);

Route::post('quotes', [QuoteController::class, 'store']);
