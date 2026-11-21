<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\StudioController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Admin;
use App\Http\Controllers\PaymentController;

Route::get('/dashboard', DashboardController::class)
    ->middleware('auth')
    ->name('dashboard');

Route::get('/', [StudioController::class, 'index'])->name('studios.index');
Route::get('/studios/{studio}', [StudioController::class, 'show'])->name('studios.show');
Route::get('/studios/{studio}/slots', [BookingController::class, 'slots'])->name('studios.slots');

Route::middleware('auth')->group(function () {
    // Profile (bawaan Breeze)
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Booking
    Route::get('/bookings', [BookingController::class, 'index'])->name('bookings.index');
    Route::post('/bookings', [BookingController::class, 'store'])->name('bookings.store');
    Route::get('/bookings/{booking}', [BookingController::class, 'show'])->name('bookings.show');

    Route::post('/bookings/{booking}/pay',  [PaymentController::class, 'token'])->name('bookings.pay');
    Route::post('/bookings/{booking}/sync', [PaymentController::class, 'sync'])->name('bookings.sync');

    Route::get('/bookings/{booking}/reschedule',   [BookingController::class, 'rescheduleForm'])->name('bookings.reschedule.edit');
    Route::patch('/bookings/{booking}/reschedule', [BookingController::class, 'reschedule'])->name('bookings.reschedule.update');
    Route::patch('/bookings/{booking}/cancel',     [BookingController::class, 'cancel'])->name('bookings.cancel');
    Route::get('/bookings/{booking}/calendar.ics', [BookingController::class, 'ics'])->name('bookings.ics');
});

Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', fn () => redirect()->route('dashboard'))->name('home');

    Route::resource('studios', Admin\StudioController::class)->except('show');
    Route::delete('studios/{studio}/photos/{photo}', [Admin\StudioPhotoController::class, 'destroy'])
        ->name('studios.photos.destroy');

    Route::get('bookings', [Admin\BookingController::class, 'index'])->name('bookings.index');
    Route::patch('bookings/{booking}/status', [Admin\BookingController::class, 'updateStatus'])->name('bookings.status');

    Route::get('calendar', [Admin\BookingController::class, 'calendar'])->name('calendar');
    Route::get('calendar/events', [Admin\BookingController::class, 'events'])->name('calendar.events');
});

Route::post('/midtrans/notification', [PaymentController::class, 'notification'])->name('midtrans.notification');

require __DIR__.'/auth.php';
