<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('welcome');
    // return 'Laravel root OK';
});

use App\Http\Controllers\ApprListControllers;

Route::get('/appr-list', [ApprListControllers::class, 'index']);
Route::get('/apprlist/data', [ApprListControllers::class, 'getData'])->name('apprlist.getData');
Route::post('/apprlist/sendData', [ApprListControllers::class, 'sendData'])->name('apprlist.sendData');