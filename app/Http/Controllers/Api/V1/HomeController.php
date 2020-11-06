<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;
use Carbon\Carbon;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class HomeController extends Controller
{
    /**
     * @OA\Post(
     *          path="/api/v1/getUsersbyCategory/{category}/{loginUser}/{start}/{limit}",
     *          operationId="User category",
     *          tags={"Homepage"},
     *      @OA\Parameter(
     *          name="category",
     *          in="path",
     *          required=true,
     *          @OA\Schema(
     *              type="string"
     *          )
     *      ),
     *      @OA\Parameter(
     *          name="loginUser",
     *          in="path",
     *          required=true,
     *          @OA\Schema(
     *              type="integer"
     *          )
     *      ),
     *      @OA\Parameter(
     *          name="start",
     *          in="path",
     *          required=true,
     *          @OA\Schema(
     *              type="integer"
     *          )
     *      ),
     *      @OA\Parameter(
     *          name="limit",
     *          in="path",
     *          required=true,
     *          @OA\Schema(
     *              type="integer"
     *          )
     *      ),
     *
     *      summary="Get list of users filter by category",
     *      description="Returns list of users",
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *          @OA\MediaType(
     *           mediaType="application/json",
     *      )
     *      ),
     *      @OA\Response(
     *          response=401,
     *          description="Unauthenticated",
     *      ),
     *      @OA\Response(
     *          response=403,
     *          description="Forbidden"
     *      ),
     *      @OA\Response(
     *          response=400,
     *          description="Bad Request"
     *      ),
     *      @OA\Response(
     *          response=404,
     *          description="not found"
     *      ),
     *  )
     */
    public function getUsersbyCategory($category,$loginUser,$start,$limit){
        if(strtolower($category) != 'all'){
            $all = User::whereHas(
                'roles', function($q){
                    $q->where('name', 'ContentCreator');
                }
            )
            ->where('id_verified', 1)
            ->where('category', ucfirst($category))->get();
        }else{
            $all = User::whereHas(
                'roles', function($q){
                    $q->where('name', 'ContentCreator');
                }
            )->where('id_verified', 1)->get();
        }
        if(strtolower($category) != 'all'){
            $users = User::whereHas(
                'roles', function($q){
                    $q->where('name', 'ContentCreator');
                }
            )
            ->where('id_verified', 1)
            ->where('category', ucfirst($category))->offset($start)->limit($limit)
            ->get();
            //->toSql();
        }else{
            $users = User::whereHas(
                'roles', function($q){
                    $q->where('name', 'ContentCreator');
                }
            )
            ->where('id_verified', 1)
            ->offset($start)->limit($limit)->get();
        }
        
        //dd($users); exit();
        $userData = array();
        $i = 0;
        foreach($users as $user){
            if(!empty($loginUser) && $user['id'] == $loginUser)
                $allPost = $user->posts()->get();
            else
            $allPost = $user->posts()->where('publish','now')->get();
            $imageTypes = array('jpg','jpeg','png','bmp','gif','webp');
            $videoTypes = array('mp4','webm','ogg');
            $videoCount = 0;
            $imageCount = 0;
            $followerList = array();
            $wishList = array();
            if(count($allPost) > 0){
                foreach($allPost as $post){
                    if(!empty($post['media'])){
                       //$getMedia = explode(".",$post['media']);
                       //$extMedia = end($getMedia);
                       $path = $post['media'];
                       $ext = pathinfo($path, PATHINFO_EXTENSION);
                       if (in_array($ext, $imageTypes)){
                         $imageCount++;
                       }
    
                       if (in_array($ext, $videoTypes)){
                        $videoCount++;
                      }
                    }
                }
            }
            $userId = $user['id'];
            $Followers = DB::table('follow')->where('user_id',$userId)->get();
            foreach($Followers as $follow){
                $followerList[] = $follow->follower_id;
            }
            $Wish_users = DB::table('wish_list')->where('user_id',$userId)->get();
            foreach($Wish_users as $Wish_user){
                $wishList[] = $Wish_user->contentwriter_id;
            }

            $userData[$i]['id'] = $user['id'];
            $userData[$i]['Forename'] = $user['first_name'];
            $userData[$i]['Surname'] = $user['last_name'];
            $userData[$i]['DisplayName'] = $user['display_name'];
            $userData[$i]['Username'] = $user['username'];
            $userData[$i]['Email'] = $user['email'];
            $userData[$i]['EmailVerified'] = $user['email_verified'];
            $userData[$i]['PhoneNumber'] = $user['contact'];
            $userData[$i]['ProfilePic'] = (!empty($user['profile']) ? url('storage/'.$user['profile']) : '');
            $userData[$i]['ProfileBanner'] = (!empty($user['cover']) ? url('storage/'.$user['cover']) : '');
            $userData[$i]['ProfileVideo'] = (!empty($user['profile_video']) ? url('storage/'.$user['profile_video']) : '');
            $userData[$i]['SubscriptionPrice'] = $user['subscription_price'];
            $userData[$i]['TwitterURL'] = $user['twitter_url'];
            $userData[$i]['AmazonURL'] = $user['amazon_url'];
            $userData[$i]['Bio'] = $user['bio'];
            $userData[$i]['Tags'] = $user['tags'];
            $userData[$i]['Country'] = $user['country'];
            $userData[$i]['AccountName'] = $user['account_name'];
            $userData[$i]['SortCode'] = $user['sort_code'];
            $userData[$i]['AccountNumber'] = $user['account_number'];
            $userData[$i]['PhotoId'] = (!empty($user['photo_id']) ? url('storage/'.$user['photo_id']) : '');
            $userData[$i]['PhotowithId'] = (!empty($user['photo_id_1']) ? url('storage/'.$user['photo_id_1']) : '');
            $userData[$i]['Category'] = $user['category'];
            $userData[$i]['YearsOld'] = $user['year_old'];
            $userData[$i]['AgreeTerms'] = $user['term'];
            $userData[$i]['twoFactor'] = (!empty($user['two_factor']) ?  'Yes': 'No');
            $userData[$i]['Location'] = $user['location'];
            $userData[$i]['Role'] = !empty($user->roles->first()->name) ? $user->roles->first()->name : '';
            $userData[$i]['imageCount'] = $imageCount;
            $userData[$i]['videoCount'] = $videoCount;
            $userData[$i]['followerList'] = $followerList;
            $userData[$i]['wishList'] = $wishList;
            $i++;
        }
        return response()->json([
            'userCount' => count($all),
            'data' => $userData,
            'isError' => false
        ]);
    }

    /**
     * @OA\Post(
     *          path="/api/v1/getUsersbyName/{name}/{loginUser}/{start}/{limit}",
     *          operationId="User profile",
     *          tags={"Homepage"},
     *      @OA\Parameter(
     *          name="name",
     *          in="path",
     *          required=true,
     *          @OA\Schema(
     *              type="string"
     *          )
     *      ),
     *      @OA\Parameter(
     *          name="loginUser",
     *          in="path",
     *          required=true,
     *          @OA\Schema(
     *              type="integer"
     *          )
     *      ),
     *      @OA\Parameter(
     *          name="start",
     *          in="path",
     *          required=true,
     *          @OA\Schema(
     *              type="integer"
     *          )
     *      ),
     *      @OA\Parameter(
     *          name="limit",
     *          in="path",
     *          required=true,
     *          @OA\Schema(
     *              type="integer"
     *          )
     *      ),
     *
     *      summary="Get list of users filter by name",
     *      description="Returns list of users",
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *          @OA\MediaType(
     *           mediaType="application/json",
     *      )
     *      ),
     *      @OA\Response(
     *          response=401,
     *          description="Unauthenticated",
     *      ),
     *      @OA\Response(
     *          response=403,
     *          description="Forbidden"
     *      ),
     *      @OA\Response(
     *          response=400,
     *          description="Bad Request"
     *      ),
     *      @OA\Response(
     *          response=404,
     *          description="not found"
     *      ),
     *  )
     */
    public function getUsersbyName($name,$loginUser,$start,$limit){
        if(!empty($limit)){
            $users = User::whereHas(
                'roles', function($q){
                    $q->where('name', 'ContentCreator');
                }
            )
            ->where('id_verified', 1)
            ->where(function ($query) use ($name){
                $query->where('first_name', 'LIKE', '%'.$name.'%')
                      ->orWhere('last_name', 'LIKE', '%'.$name.'%')
                      ->orWhere('username', 'LIKE', '%'.$name.'%')
                      ->orWhere('email', 'LIKE', '%'.$name.'%)');
            })
            ->offset($start)->limit($limit)
            //->toSql();
            ->get();
        }else{
            $users = User::whereHas(
                'roles', function($q){
                    $q->where('name', 'ContentCreator');
                }
            )
            ->where('id_verified', 1)
            ->where(function ($query) use ($name){
                $query->where('first_name', 'LIKE', '%'.$name.'%')
                      ->orWhere('last_name', 'LIKE', '%'.$name.'%')
                      ->orWhere('username', 'LIKE', '%'.$name.'%')
                      ->orWhere('email', 'LIKE', '%'.$name.'%)');
            })
            ->get();
        }
        //dd($users); exit;
        //echo '<pre>'; print_r($users); exit();
        $userData = array();
        $i = 0;
        foreach($users as $user){
            //$allPost = $user->posts()->where('publish','now')->get();
            if(!empty($loginUser) && $user['id'] == $loginUser)
                $allPost = $user->posts()->get();
            else
                $allPost = $user->posts()->where('publish','now')->get();
            $imageTypes = array('jpg','jpeg','png','bmp','gif','webp');
            $videoTypes = array('mp4','webm','ogg');
            $videoCount = 0;
            $imageCount = 0;
            $followerList = array();
            $wishList = array();
            if(count($allPost) > 0){
                foreach($allPost as $post){
                    if(!empty($post['media'])){
                       //$getMedia = explode(".",$post['media']);
                       //$extMedia = end($getMedia);
                       $path = $post['media'];
                       $ext = pathinfo($path, PATHINFO_EXTENSION);
                       if (in_array($ext, $imageTypes)){
                         $imageCount++;
                       }
    
                       if (in_array($ext, $videoTypes)){
                        $videoCount++;
                      }
                    }
                }
            }

            $userId = $user['id'];
            $Followers = DB::table('follow')->where('user_id',$userId)->get();
            foreach($Followers as $follow){
                $followerList[] = $follow->follower_id;
            }

            $Wish_users = DB::table('wish_list')->where('user_id',$userId)->get();
            foreach($Wish_users as $Wish_user){
                $wishList[] = $Wish_user->contentwriter_id;
            }

            $userData[$i]['id'] = $user['id'];
            $userData[$i]['Forename'] = $user['first_name'];
            $userData[$i]['Surname'] = $user['last_name'];
            $userData[$i]['DisplayName'] = $user['display_name'];
            $userData[$i]['Username'] = $user['username'];
            $userData[$i]['Email'] = $user['email'];
            $userData[$i]['EmailVerified'] = $user['email_verified'];
            $userData[$i]['PhoneNumber'] = $user['contact'];
            $userData[$i]['ProfilePic'] = (!empty($user['profile']) ? url('storage/'.$user['profile']) : '');
            $userData[$i]['ProfileBanner'] = (!empty($user['cover']) ? url('storage/'.$user['cover']) : '');
            $userData[$i]['ProfileVideo'] = (!empty($user['profile_video']) ? url('storage/'.$user['profile_video']) : '');
            $userData[$i]['SubscriptionPrice'] = $user['subscription_price'];
            $userData[$i]['TwitterURL'] = $user['twitter_url'];
            $userData[$i]['AmazonURL'] = $user['amazon_url'];
            $userData[$i]['Bio'] = $user['bio'];
            $userData[$i]['Tags'] = $user['tags'];
            $userData[$i]['Country'] = $user['country'];
            $userData[$i]['AccountName'] = $user['account_name'];
            $userData[$i]['SortCode'] = $user['sort_code'];
            $userData[$i]['AccountNumber'] = $user['account_number'];
            $userData[$i]['PhotoId'] = (!empty($user['photo_id']) ? url('storage/'.$user['photo_id']) : '');
            $userData[$i]['PhotowithId'] = (!empty($user['photo_id_1']) ? url('storage/'.$user['photo_id_1']) : '');
            $userData[$i]['Category'] = $user['category'];
            $userData[$i]['YearsOld'] = $user['year_old'];
            $userData[$i]['AgreeTerms'] = $user['term'];
            $userData[$i]['twoFactor'] = (!empty($user['two_factor']) ?  'Yes': 'No');
            $userData[$i]['Location'] = $user['location'];
            $userData[$i]['Role'] = !empty($user->roles->first()->name) ? $user->roles->first()->name : '';
            $userData[$i]['imageCount'] = $imageCount;
            $userData[$i]['videoCount'] = $videoCount;
            $userData[$i]['followerList'] = $followerList;
            $userData[$i]['wishList'] = $wishList;
            $i++;
        }
        return response()->json([
            'data' => $userData,
            'isError' => false
        ]);
    }

    

}