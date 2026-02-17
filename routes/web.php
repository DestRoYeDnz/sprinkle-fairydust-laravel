<?php

use App\Http\Controllers\Admin\AdminSettingsController;
use App\Http\Controllers\Admin\QuoteManagementController;
use App\Http\Controllers\Admin\TestimonialManagementController;
use App\Http\Controllers\Admin\TrackingStatsController;
use App\Http\Controllers\Admin\UserManagementController;
use App\Http\Controllers\Api\AdminUploadController;
use App\Http\Controllers\Api\EventController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Site/Home');
})->name('home');

Route::get('about', fn () => Inertia::render('Site/About'))->name('about');
Route::get('faq', fn () => Inertia::render('Site/Faq'))->name('faq');
Route::get('terms-and-conditions', fn () => Inertia::render('Site/Terms'))->name('terms');
Route::get('services', fn () => Inertia::render('Site/Services'))->name('services');
Route::get('face-painting', fn () => Inertia::render('Site/FacePainting'))->name('face-painting');
Route::get('glitter-tattoos', fn () => Inertia::render('Site/GlitterTattoos'))->name('glitter-tattoos');
Route::get('festival-face-painting', fn () => Inertia::render('Site/FestivalFacePainting'))->name('festival-face-painting');
Route::get('events', fn () => Inertia::render('Site/Events'))->name('events');
Route::get('gallery', fn () => Inertia::render('Site/Gallery'))->name('gallery');
Route::get('designs', fn () => Inertia::render('Site/Designs'))->name('designs');
Route::get('testimonials', fn () => Inertia::render('Site/Testimonials'))->name('testimonials');
Route::get('add-testimonial', fn () => Inertia::render('Site/AddTestimonial'))->name('add-testimonial');
Route::get('quote', fn () => Inertia::render('Site/Quote'))->name('quote');
Route::get('quotes/{quote}/confirm', [QuoteManagementController::class, 'confirmFromEmail'])
    ->middleware('signed')
    ->name('quotes.confirm');
Route::get('quotes/{quote}/open', [QuoteManagementController::class, 'trackEmailOpen'])
    ->middleware('signed')
    ->name('quotes.open');

Route::middleware(['auth', 'admin'])->group(function () {
    Route::get('admin', fn () => Inertia::render('Site/Admin'))->name('admin');
    Route::get('admin/events', fn () => Inertia::render('Site/AdminEvents'))->name('admin.events');
    Route::get('admin/images', fn () => Inertia::render('Site/AdminImages'))->name('admin.images');
    Route::get('admin/quotes', fn () => Inertia::render('Site/AdminQuotes'))->name('admin.quotes');
    Route::get('admin/testimonials', fn () => Inertia::render('Site/AdminTestimonials'))->name('admin.testimonials');
    Route::get('admin/users/create', fn () => Inertia::render('Site/AdminUsers'))->name('admin.users.create');
    Route::get('admin/calculator', fn () => Inertia::render('Site/AdminCalculator'))->name('admin.calculator');
    Route::get('admin/settings', fn () => Inertia::render('Site/AdminSettings'))->name('admin.settings');
    Route::get('admin/tracking', fn () => Inertia::render('Site/AdminTracking'))->name('admin.tracking');

    Route::get('admin/quotes/list', [QuoteManagementController::class, 'index'])->name('admin.quotes.index');
    Route::post('admin/quotes', [QuoteManagementController::class, 'store'])->name('admin.quotes.store');
    Route::put('admin/quotes/{quote}', [QuoteManagementController::class, 'update'])->name('admin.quotes.update');
    Route::delete('admin/quotes/{quote}', [QuoteManagementController::class, 'destroy'])->name('admin.quotes.destroy');
    Route::post('admin/quotes/{quote}/send-email', [QuoteManagementController::class, 'sendEmail'])->name('admin.quotes.send-email');

    Route::get('admin/testimonials/list', [TestimonialManagementController::class, 'index'])->name('admin.testimonials.index');
    Route::post('admin/testimonials', [TestimonialManagementController::class, 'store'])->name('admin.testimonials.store');
    Route::put('admin/testimonials/{testimonial}', [TestimonialManagementController::class, 'update'])->name('admin.testimonials.update');
    Route::delete('admin/testimonials/{testimonial}', [TestimonialManagementController::class, 'destroy'])->name('admin.testimonials.destroy');
    Route::get('admin/settings/calculator', [AdminSettingsController::class, 'showCalculator'])->name('admin.settings.calculator.show');
    Route::put('admin/settings/calculator', [AdminSettingsController::class, 'updateCalculator'])->name('admin.settings.calculator.update');
    Route::get('admin/tracking/stats', [TrackingStatsController::class, 'index'])->name('admin.tracking.stats');

    Route::post('admin/events', [EventController::class, 'store'])->name('admin.events.store');
    Route::post('admin/images/upload', [AdminUploadController::class, 'store'])->name('admin.images.upload');
    Route::post('admin/users', [UserManagementController::class, 'store'])->name('admin.users.store');
});

Route::get('dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

require __DIR__.'/settings.php';
