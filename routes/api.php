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
    Route::post('addBlog', 'Api\V1\BlogController@addBlog');

    Route::post('/forgotPassword', 'Api\V1\PasswordController@forgot');
    Route::post('/resetPassword', 'Api\V1\PasswordController@reset');
    Route::get('getCountries', 'Api\V1\UserController@getCountries');
    Route::get('getStates/{countryName}', 'Api\V1\UserController@getStates');

    Route::post('getGuestUserPost/{userid}/{loginUser}/{start}/{limit}', 'Api\V1\PostController@getGuestUserPost');
    Route::post('guestMostPopular/{loginUser}/{start}/{limit}', 'Api\V1\PostController@guestMostPopular');

    Route::post('store', 'Api\V1\PostController@store');
    

    //Homepage apis
    Route::post('getUsersbyCategory/{category}/{start}/{limit}', 'Api\V1\HomeController@getUsersbyCategory');
    Route::post('getUsersbyName/{name}/{start}/{limit}', 'Api\V1\HomeController@getUsersbyName');

    Route::group([
        'middleware' => 'auth:api'
      ], function() {
    //User related routes
    Route::post('alluser', 'Api\V1\UserController@alluser');
    Route::post('getDetails/{id}', 'Api\V1\UserController@getDetails');
    Route::post('deleteUser/{id}', 'Api\V1\UserController@deleteUser');
    Route::post('/profileSettings/{id}', 'Api\V1\UserController@profileSettings');
    Route::post('/closeAccount/{id}', 'Api\V1\UserController@closeAccount');
    Route::post('updatePaymentDetails/{id}', 'Api\V1\UserController@updatePaymentDetails');

    Route::post('/changePassword/{id}', 'Api\V1\PasswordController@change');
    
    //Post related routes
    Route::post('addPost/{userid}', 'Api\V1\PostController@addPost');
    Route::post('updatePost/{postid}', 'Api\V1\PostController@updatePost');
    Route::post('getUserPost/{userid}/{loginUser}/{start}/{limit}', 'Api\V1\PostController@getUserPost');
    Route::post('getAllPost/{loginUser}/{start}/{limit}', 'Api\V1\PostController@getAllPost');
    Route::post('deletePost/{postid}', 'Api\V1\PostController@deletePost');
    Route::post('likePost/{postid}/{userid}', 'Api\V1\PostController@likePost');
    Route::post('dislikePost/{postid}/{userid}', 'Api\V1\PostController@dislikePost');
    Route::post('addCommenttoPost/{postid}/{userid}', 'Api\V1\PostController@addCommenttoPost');  
    Route::post('updatePostComment/{commentid}', 'Api\V1\PostController@updatePostComment'); 
    Route::post('deletePostComment/{commentid}', 'Api\V1\PostController@deletePostComment');
    Route::post('getPostDetail/{postid}', 'Api\V1\PostController@getPostDetail');  
    Route::post('viewPost/{postid}/{userid}', 'Api\V1\PostController@viewPost');  
    Route::post('mostViewed/{loginUser}/{start}/{limit}', 'Api\V1\PostController@mostViewed'); 
    Route::post('mostPopular/{loginUser}/{start}/{limit}', 'Api\V1\PostController@mostPopular'); 
    Route::post('mostPopularProfile/{loginUser}/{start}/{limit}', 'Api\V1\PostController@mostPopularProfile');
    Route::post('searchActivity/{search}/{loginUser}/{start}/{limit}', 'Api\V1\PostController@searchActivity');
    Route::post('getRecentPost/{loginUser}/{start}/{limit}', 'Api\V1\PostController@getRecentPost');

    //Album routes
    Route::post('addAlbum/{userid}', 'Api\V1\AlbumController@addAlbum');
    Route::post('updateAlbum/{albumid}', 'Api\V1\AlbumController@updateAlbum');
    Route::post('getUserAlbum/{userid}', 'Api\V1\AlbumController@getUserAlbum');
    Route::post('deleteAlbum/{albumid}', 'Api\V1\AlbumController@deleteAlbum');

    //Comment routes
    Route::post('likeComment/{commentid}/{userid}', 'Api\V1\CommentController@likeComment');
    Route::post('CommentComment/{commentid}/{userid}', 'Api\V1\CommentController@CommentComment');

    Route::post('getBlogs/{start}/{limit}', 'Api\V1\BlogController@getBlogs');
    Route::post('searchBlog/{search}/{start}/{limit}', 'Api\V1\BlogController@searchBlog');
    Route::post('getBlogDetail/{id}', 'Api\V1\BlogController@getBlogDetail');
});

    

  });

  //auth routes



/*Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});*/
