<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\InputDataController;

// dashboard pages
Route::get('/', function () {
    return view('pages.dashboard.ecommerce', ['title' => 'E-commerce Dashboard']);
})->name('dashboard');

// Input Data Routes (consolidated)
Route::controller(InputDataController::class)->prefix('api')->group(function () {
    // Dosen
    Route::get('/dosen', 'indexDosen');
    Route::post('/dosen', 'storeDosen');
    Route::get('/dosen/{id}', 'showDosen');
    Route::put('/dosen/{id}', 'updateDosen');
    Route::delete('/dosen/{id}', 'destroyDosen');

    // Hari
    Route::get('/hari', 'indexHari');
    Route::post('/hari', 'storeHari');
    Route::get('/hari/{id}', 'showHari');
    Route::put('/hari/{id}', 'updateHari');
    Route::delete('/hari/{id}', 'destroyHari');

    // Jam
    Route::get('/jam', 'indexJam');
    Route::post('/jam', 'storeJam');
    Route::get('/jam/{id}', 'showJam');
    Route::put('/jam/{id}', 'updateJam');
    Route::delete('/jam/{id}', 'destroyJam');

    // Mata Kuliah
    Route::get('/mata-kuliah', 'indexMataKuliah');
    Route::post('/mata-kuliah', 'storeMataKuliah');
    Route::get('/mata-kuliah/{id}', 'showMataKuliah');
    Route::put('/mata-kuliah/{id}', 'updateMataKuliah');
    Route::delete('/mata-kuliah/{id}', 'destroyMataKuliah');

    // Ruangan
    Route::get('/ruangan', 'indexRuangan');
    Route::post('/ruangan', 'storeRuangan');
    Route::get('/ruangan/{id}', 'showRuangan');
    Route::put('/ruangan/{id}', 'updateRuangan');
    Route::delete('/ruangan/{id}', 'destroyRuangan');
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


// authentication pages
Route::get('/signin', function () {
    return view('pages.auth.signin', ['title' => 'Sign In']);
})->name('signin');

Route::get('/signup', function () {
    return view('pages.auth.signup', ['title' => 'Sign Up']);
})->name('signup');

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
