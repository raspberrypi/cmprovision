<?php

use Illuminate\Support\Facades\Route;
use App\Http\Livewire\Cms;
use App\Http\Livewire\Images;
use App\Http\Livewire\Labels;
use App\Http\Livewire\Projects;
use App\Http\Livewire\Scripts;
use App\Http\Livewire\Firmwares;
use App\Http\Livewire\Settings;
use App\Http\Controllers\AddImageController;
use App\Http\Controllers\ScriptExecuteController;

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
    //return view('welcome');
    return redirect()->route('login');
});

Route::middleware(['auth:sanctum', 'verified'])->get('/dashboard', function () {
    return view('dashboard', ['log' => \App\Models\Cmlog::orderBy('id','desc')->limit(100)->get()] );
})->name('dashboard');

Route::middleware(['auth:sanctum', 'verified'])->any('/cms', Cms::class)->name('cms');
Route::middleware(['auth:sanctum', 'verified'])->any('/images', Images::class)->name('images');
Route::middleware(['auth:sanctum', 'verified'])->post('/addImage', [AddImageController::class, 'store']);
Route::middleware(['auth:sanctum', 'verified'])->any('/projects', Projects::class)->name('projects');
Route::middleware(['auth:sanctum', 'verified'])->any('/scripts', Scripts::class)->name('scripts');
Route::middleware(['auth:sanctum', 'verified'])->any('/labels', Labels::class)->name('labels');
Route::middleware(['auth:sanctum', 'verified'])->any('/firmware', Firmwares::class)->name('firmware');
Route::middleware(['auth:sanctum', 'verified'])->any('/settings', Settings::class)->name('settings');

Route::any('/scriptexecute', ScriptExecuteController::class);
