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
    Route::post('/desirerRegister', 'Api\V1\AuthController@desirerRegister');

    Route::post('/forgotPassword', 'Api\V1\PasswordController@forgot');
    Route::post('/resetPassword', 'Api\V1\PasswordController@reset');

    Route::group([
        'middleware' => 'auth:api'
      ], function() {
          Route::post('alluser', 'Api\V1\UserController@alluser');
          Route::post('getDetails', 'Api\V1\UserController@getDetails');
          Route::post('verifyId/{id}', 'Api\V1\UserController@verifyId');
          Route::post('verifyemail/{id}', 'Api\V1\UserController@verifyemail');
          Route::post('updateProfile/{id}', 'Api\V1\UserController@updateProfile');
          Route::post('deleteUser/{id}', 'Api\V1\UserController@deleteUser');
          Route::post('addPaymentDetails/{id}', 'Api\V1\UserController@addPaymentDetails');
          Route::post('getCountries', 'Api\V1\UserController@getCountries');

          Route::post('/changePassword/{id}', 'Api\V1\PasswordController@change');

          Route::post('addPost/{id}', 'Api\V1\PostController@addPost');
      });

  });

  //auth routes



/*Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});*/
