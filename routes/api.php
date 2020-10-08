<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

//  Route::group([
//       'prefix' => 'auth'
//     ], function () {
//       Route::post('login', 'AuthController@login');
//       Route::post('register', 'AuthController@signup');

//       Route::group([
//         'middleware' => 'auth:api'
//       ], function() {
//           Route::get('logout', 'AuthController@logout');
//           Route::get('user', 'AuthController@user');
//       });
//     });

  Route::group([
      'prefix' => 'v1',
      'as' => 'api',
      //'middleware' => ['auth:api']
  ], function () {
    Route::post('login', 'Api\V1\AuthController@login');
    Route::post('register', 'Api\V1\AuthController@register');
    Route::post('verifyId/{id}', 'Api\V1\UserController@verifyId');
    Route::post('verifyemail/{id}', 'Api\V1\UserController@verifyemail');
    Route::post('updateProfile/{id}', 'Api\V1\UserController@updateProfile');
    Route::post('addPaymentDetails/{id}', 'Api\V1\UserController@addPaymentDetails');
    Route::post('/desirerRegister', 'Api\V1\AuthController@desirerRegister');

    Route::post('/forgotPassword', 'Api\V1\PasswordController@forgot');
    Route::post('/resetPassword', 'Api\V1\PasswordController@reset');
    Route::get('getCountries', 'Api\V1\UserController@getCountries');
    

    //Homepage apis
    Route::post('getUsersbyCategory/{category}/{start}/{limit}', 'Api\V1\HomeController@getUsersbyCategory');
    Route::post('getUsersbyName/{name}', 'Api\V1\HomeController@getUsersbyName');

    Route::group([
        'middleware' => 'auth:api'
      ], function() {
    //User related routes
    Route::post('alluser', 'Api\V1\UserController@alluser');
    Route::post('getDetails/{id}', 'Api\V1\UserController@getDetails');
    Route::post('deleteUser/{id}', 'Api\V1\UserController@deleteUser');
    Route::post('/profileSettings/{id}', 'Api\V1\UserController@profileSettings');
    Route::post('/closeAccount/{id}', 'Api\V1\UserController@closeAccount');
    Route::post('updatePaymentDetails/{id}', 'Api\V1\UserController@addPaymentDetails');

    Route::post('/changePassword/{id}', 'Api\V1\PasswordController@change');
    
    //Post related routes
    Route::post('addPost/{userid}', 'Api\V1\PostController@addPost');
    Route::post('updatePost/{postid}', 'Api\V1\PostController@updatePost');
    Route::post('getUserPost/{userid}', 'Api\V1\PostController@getUserPost');
    Route::post('getAllPost', 'Api\V1\PostController@getAllPost');
    Route::post('deletePost/{postid}', 'Api\V1\PostController@deletePost');
    Route::post('likePost/{postid}/{userid}', 'Api\V1\PostController@likePost');
    Route::post('dislikePost/{postid}/{userid}', 'Api\V1\PostController@dislikePost');    

    //Album routes
    Route::post('addAlbum/{userid}', 'Api\V1\AlbumController@addAlbum');
    Route::post('updateAlbum/{albumid}', 'Api\V1\AlbumController@updateAlbum');
    Route::post('getUserAlbum/{userid}', 'Api\V1\AlbumController@getUserAlbum');
    Route::post('deleteAlbum/{albumid}', 'Api\V1\AlbumController@deleteAlbum');

  });

    

  });

  //auth routes



/*Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});*/
