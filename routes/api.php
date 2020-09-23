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

    Route::group([
        'middleware' => 'auth:api'
      ], function() {
          Route::post('alluser', 'Api\V1\UserController@alluser');
          Route::post('getDetails', 'Api\V1\UserController@getDetails');
          Route::post('verifyId/{id}', 'Api\V1\UserController@verifyId');
          Route::post('varifyemail/{id}', 'Api\V1\UserController@varifyemail');
          Route::post('updateProfile/{id}', 'Api\V1\UserController@updateProfile');
      });

  });
  
  //auth routes
  
 

/*Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});*/
