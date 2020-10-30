<?php

use Illuminate\Support\Facades\Route;

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
    return view('welcome');
});

/*Route::get('/verifyemail/{id}', function () {
    return view('verifyemail');
});*/

// Route::get('/verifyemail/{id}', 'UserController@verifyemail');
Route::get('/verifyemail/{rolename}/{id}', 'UserController@verifyemail');
Route::view('password/email', 'auth.reset_password')->name('password.reset');

Route::get('/index', 'UserController@paySafe');
Route::post('/Pay/store', 'PayController@store');
