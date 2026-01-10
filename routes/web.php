<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\InputDataController;
use App\Http\Controllers\AuthController;

// Protected Routes
Route::middleware(['auth'])->group(function () {
    // dashboard pages
    Route::get('/', function () {
        return view('pages.dashboard.ecommerce', ['title' => 'E-commerce Dashboard']);
    })->name('dashboard');

    // Input Data Routes (consolidated)
    Route::controller(InputDataController::class)->group(function () {
        // Logs
        Route::get('/logs/data', 'getLogs')->name('logs.data');

        // Dosen
        Route::get('/dosen/data', 'getDosenData')->name('dosen.data');
        Route::get('/dosen', 'indexDosen')->name('dosen.index');
        Route::post('/dosen', 'storeDosen')->name('dosen.store');
        Route::get('/dosen/{id}', 'showDosen')->name('dosen.show');
        Route::put('/dosen/{id}', 'updateDosen')->name('dosen.update');
        Route::delete('/dosen/{id}', 'destroyDosen')->name('dosen.destroy');

        // Mata Kuliah
        Route::get('/mata-kuliah/data', 'getMataKuliahData')->name('mata-kuliah.data');
        Route::get('/mata-kuliah', 'indexMataKuliah')->name('mata-kuliah.index');
        Route::post('/mata-kuliah', 'storeMataKuliah')->name('mata-kuliah.store');
        Route::get('/mata-kuliah/{id}', 'showMataKuliah')->name('mata-kuliah.show');
        Route::put('/mata-kuliah/{id}', 'updateMataKuliah')->name('mata-kuliah.update');
        Route::delete('/mata-kuliah/{id}', 'destroyMataKuliah')->name('mata-kuliah.destroy');

        // Hari
        Route::get('/hari/data', 'getHariData')->name('hari.data');
        Route::get('/hari', 'indexHari')->name('hari.index');
        Route::post('/hari', 'storeHari')->name('hari.store');
        Route::get('/hari/{id}', 'showHari')->name('hari.show');
        Route::put('/hari/{id}', 'updateHari')->name('hari.update');
        Route::delete('/hari/{id}', 'destroyHari')->name('hari.destroy');

        // Jam
        Route::get('/jam/data', 'getJamData')->name('jam.data');
        Route::get('/jam', 'indexJam')->name('jam.index');
        Route::post('/jam', 'storeJam')->name('jam.store');
        Route::get('/jam/{id}', 'showJam')->name('jam.show');
        Route::put('/jam/{id}', 'updateJam')->name('jam.update');
        Route::delete('/jam/{id}', 'destroyJam')->name('jam.destroy');

        // Ruangan
        Route::get('/ruangan/data', 'getRuanganData')->name('ruangan.data');
        Route::get('/ruangan', 'indexRuangan')->name('ruangan.index');
        Route::post('/ruangan', 'storeRuangan')->name('ruangan.store');
        Route::get('/ruangan/{id}', 'showRuangan')->name('ruangan.show');
        Route::put('/ruangan/{id}', 'updateRuangan')->name('ruangan.update');
        Route::delete('/ruangan/{id}', 'destroyRuangan')->name('ruangan.destroy');
    });

    // calender pages
    Route::get('/calendar', function () {
        return view('pages.calender', ['title' => 'Calendar']);
    })->name('calendar');

    // profile pages
    Route::get('/profile', function () {
        return view('pages.profile', ['title' => 'Profile']);
    })->name('profile');

    // form pages
    Route::get('/form-elements', function () {
        return view('pages.form.form-elements', ['title' => 'Form Elements']);
    })->name('form-elements');

    // tables pages
    Route::get('/basic-tables', function () {
        return view('pages.tables.basic-tables', ['title' => 'Basic Tables']);
    })->name('basic-tables');

    // pages
    Route::get('/blank', function () {
        return view('pages.blank', ['title' => 'Blank']);
    })->name('blank');

    // error pages
    Route::get('/error-404', function () {
        return view('pages.errors.error-404', ['title' => 'Error 404']);
    })->name('error-404');

    // chart pages
    Route::get('/line-chart', function () {
        return view('pages.chart.line-chart', ['title' => 'Line Chart']);
    })->name('line-chart');

    Route::get('/bar-chart', function () {
        return view('pages.chart.bar-chart', ['title' => 'Bar Chart']);
    })->name('bar-chart');

    // ui elements pages
    Route::get('/alerts', function () {
        return view('pages.ui-elements.alerts', ['title' => 'Alerts']);
    })->name('alerts');

    Route::get('/avatars', function () {
        return view('pages.ui-elements.avatars', ['title' => 'Avatars']);
    })->name('avatars');

    Route::get('/badge', function () {
        return view('pages.ui-elements.badges', ['title' => 'Badges']);
    })->name('badges');

    Route::get('/buttons', function () {
        return view('pages.ui-elements.buttons', ['title' => 'Buttons']);
    })->name('buttons');

    Route::get('/image', function () {
        return view('pages.ui-elements.images', ['title' => 'Images']);
    })->name('images');

    Route::get('/videos', function () {
        return view('pages.ui-elements.videos', ['title' => 'Videos']);
    })->name('videos');
});

// authentication pages
Route::get('/signin', function () {
    return view('pages.auth.signin', ['title' => 'Sign In']);
})->name('signin');

Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Redirect 'login' named route to 'signin' for auth middleware compatibility
Route::get('/login', function () {
    return redirect()->route('signin');
})->name('login');

Route::post('/signin', [AuthController::class, 'signin'])->name('signin.perform');

// Google Auth Routes
Route::get('/auth/google/redirect', [AuthController::class, 'redirectToGoogle'])->name('auth.google.redirect');
Route::get('/auth/callback', [AuthController::class, 'handleGoogleCallback'])->name('auth.google.callback');
Route::post('/auth/google/exchange', [AuthController::class, 'exchangeToken'])->name('auth.google.exchange');

Route::get('/signup', [AuthController::class, 'showSignup'])->name('signup');
Route::post('/signup', [AuthController::class, 'signup'])->name('signup.perform');
