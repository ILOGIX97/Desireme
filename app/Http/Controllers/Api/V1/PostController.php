<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Post;
use App\User;
use App\Like;
use App\Comment;
use App\Views;
use App\comment_like;
use App\comment_comment;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;
use Carbon\Carbon;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Paysafe\ThreeDSecure;
use Paysafe\PaysafeApiClient;
use Paysafe\Environment;
use Paysafe\ThreeDSecureV2\Authentications;


class PostController extends Controller
{


    /**
     * @OA\Post(
     *          path="/api/v1/addPost/{userid}",
     *          operationId="Add User Post Details",
     *          tags={"Posts"},
     *      @OA\Parameter(
     *          name="userid",
     *          in="path",
     *          required=true,
     *          @OA\Schema(
     *              type="integer"
     *          )
     *      ),
     *
     *      summary="Add User Post Details",
     *      description="data of users post",
     *      @OA\RequestBody(
     *       @OA\MediaType(
     *           mediaType="multipart/form-data",
     *           @OA\Schema(
     *               required={"Title","Caption","Publish","PhotoorVideo"},
     *               @OA\Property(
     *                  property="Title",
     *                  type="string"
     *               ),
     *               @OA\Property(
     *                  property="Caption",
     *                  type="string"
     *               ),
     *               @OA\Property(
     *                  property="Tags",
     *                  type="string"
     *               ),
     *               @OA\Property(
     *                  property="PhotoorVideo",
     *                  type="string"
     *               ),
     *               @OA\Property(
     *                  property="Publish",
     *                  type="string",
     *                  default="now",
     *                  enum={"now", "draft", "schedule"}
     *               ),
     *               @OA\Property(
     *                  property="ScheduleDateTime",
     *                  type="string",
     *                  description = "d-m-Y H:i",
     *                  format="date-time"
     *               ),
     *               @OA\Property(
     *                  property="ChooseAlbum",
     *                  type="integer",
     *               ),
     *          )
     *       ),
     *   ),
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
     *      security={ {"passport": {}} },
     *  )
     */

    public function addPost(Request $request,$id){

        $validator = Validator::make($request->all(),[
            'Title' => 'required',
            'Caption' => 'required',
            'Publish' => 'required',
            'PhotoorVideo' => 'required',
            'ScheduleDateTime' => 'nullable|required_if:Publish,==,schedule|date_format:d-m-Y H:i'
        ]);

        if ($validator->fails()) {
            $failedRules = $validator->failed();
            return response()->json(['error'=>$validator->errors(),'isError' => true]);
        }

        if(null !== $request->PhotoorVideo){
            $image = $request->PhotoorVideo;
            $path = 'public/posts/';
            $media = $this->createImage($image,$path);
        }else{
            $media = '';
        }
        if(!empty($id)){
            $post =  new Post([
                'title' => $request->Title,
                'caption' => $request->Caption,
                'tags' => (!empty($request->Tags)) ? $request->Tags : '',
                'media' => $media,
                'publish' => $request->Publish,
                'schedule_at' => (!empty($request->ScheduleDateTime && $request->Publish == 'schedule')) ? strtotime($request->ScheduleDateTime) : 0,
                'add_to_album' => (!empty($request->ChooseAlbum)) ? $request->ChooseAlbum : '0',
            ]);
            if($post->save()){
                $post->users()->sync($id);
                $postData = $this->getPostResponse($post->id);
                return response()->json([
                    'message' => 'Post created user!',
                    'data' => $postData,
                    'isError' => false
                ], 201);
            }else{
                
            }
        }else{
            return response()->json(['error'=>'Provide proper details','isError' => true]);
        }
        
    }

    /**
     * @OA\Post(
     *          path="/api/v1/updatePost/{postid}",
     *          operationId="Update User Post Details",
     *          tags={"Posts"},
     *      @OA\Parameter(
     *          name="postid",
     *          in="path",
     *          required=true,
     *          @OA\Schema(
     *              type="integer"
     *          )
     *      ),
     *
     *      summary="Update User Post Details",
     *      description="data of users post",
     *      @OA\RequestBody(
     *       @OA\MediaType(
     *           mediaType="multipart/form-data",
     *           @OA\Schema(
     *               required={"Title","Caption","Publish","PhotoorVideo"},
     *               @OA\Property(
     *                  property="Title",
     *                  type="string"
     *               ),
     *               @OA\Property(
     *                  property="Caption",
     *                  type="string"
     *               ),
     *               @OA\Property(
     *                  property="Tags",
     *                  type="string"
     *               ),
     *               @OA\Property(
     *                  property="PhotoorVideo",
     *                  type="string"
     *               ),
     *               @OA\Property(
     *                  property="Publish",
     *                  type="string",
     *                  default="now",
     *                  enum={"now", "draft", "schedule"}
     *               ),
     *               @OA\Property(
     *                  property="ScheduleDateTime",
     *                  type="string",
     *                  description = "d-m-Y H:i",
     *                  format="date-time"
     *               ),
     *               @OA\Property(
     *                  property="ChooseAlbum",
     *                  type="integer",
     *               ),
     *          )
     *       ),
     *   ),
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
     *      security={ {"passport": {}} },
     *  )
     */

    public function updatePost(Request $request,$id){

        $validator = Validator::make($request->all(),[
            'Title' => 'required',
            'Caption' => 'required',
            'Publish' => 'required',
            'PhotoorVideo' => 'required',
            'ScheduleDateTime' => 'nullable|required_if:Publish,==,schedule|date_format:d-m-Y H:i'
        ]);

        if ($validator->fails()) {
            $failedRules = $validator->failed();
            return response()->json(['error'=>$validator->errors(),'isError' => true]);
        }

        if(null !== $request->PhotoorVideo){
            $image = $request->PhotoorVideo;
            $path = 'public/posts/';
            $media = $this->createImage($image,$path);
        }else{
            $media = '';
        }

        $postDetails = Post::where('id', $id)->update([
            'title' => $request->Title,
            'caption' => $request->Caption,
            'tags' => (!empty($request->Tags)) ? $request->Tags : '',
            'media' => $media,
    		'publish' => $request->Publish,
            'schedule_at' => (!empty($request->ScheduleDateTime && $request->Publish == 'schedule')) ? strtotime($request->ScheduleDateTime) : 0,
            'add_to_album' => (!empty($request->ChooseAlbum)) ? $request->ChooseAlbum : '0',
         ]);

        if($postDetails){
            $postData = $this->getPostResponse($id);
            return response()->json([
                'message' => 'Post updated successfully!',
                'data' => $postData,
                'isError' => false
            ], 201);
        }else{
            return response()->json(['error'=>'Provide proper details','isError' => true]);
        }


        return response()->json(Post::find($id));

    }

    /**
     * @OA\Post(
     *          path="/api/v1/getUserPost/{userid}/{loginUser}/{start}/{limit}",
     *          operationId="Get User Posts",
     *          tags={"Users"},
     *      @OA\Parameter(
     *          name="userid",
     *          in="path",
     *          required=true,
     *          @OA\Schema(
     *              type="integer"
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
     *      summary="Get User Posts",
     *      description="data of users post",
     *
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
     *      security={ {"passport": {}} },
     *  )
     */

    public function getUserPost($id,$loginUser,$start,$limit){


        $user = User::findOrFail($id);
        //$allPost = $user->posts()->get();
        //$allPublishPost = $user->posts()->where('publish','now')->get();
        $imageTypes = array('jpg','jpeg','png','bmp','gif','webp');
        $videoTypes = array('mp4','webm','ogg');
        $videoCount = 0;
        $imageCount = 0;
        $followerList = array();
        $wishList = array();
        if(!empty($loginUser)){
            if($id === $loginUser){
                $allPost = $user->posts()->get();
                $allPublishPost = $user->posts()->where('publish','now')->get();
                $postDetails = $user->posts()->orderBy('id','DESC')->offset($start)->limit($limit)->get();
            }else{
               $allPost = Post::select('posts.*',DB::raw('post_user.post_id,post_user.user_id'))->where('publish','now')
                           ->leftJoin('post_user', 'posts.id', '=', 'post_user.post_id')
                           ->leftJoin('users', 'users.id', '=', 'post_user.user_id')
                           ->where('post_user.user_id',$id)
                           ->get();
                $allPublishPost = $user->posts()->where('publish','now')->get();
                $postDetails = Post::select('posts.*',DB::raw('post_user.post_id,post_user.user_id'))->where('publish','now')
                                ->leftJoin('post_user', 'posts.id', '=', 'post_user.post_id')
                                ->leftJoin('users', 'users.id', '=', 'post_user.user_id')
                                ->where('post_user.user_id',$id)
                                ->orderBy('posts.id','DESC')
                               ->offset($start)->limit($limit)->get();
            }
            
        }else{
            $postDetails = array();
        }
        if(count($allPost) > 0){
            foreach($allPost as $post){
                if(!empty($post['media'])){
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

            //$userId = $user['id'];
            $Followers = DB::table('follow')->where('user_id',$loginUser)->get();
            foreach($Followers as $follow){
                $followerList[] = $follow->follower_id;
            }

            $Wish_users = DB::table('wish_list')->where('user_id',$loginUser)->get();
            foreach($Wish_users as $Wish_user){
                $wishList[] = $Wish_user->contentwriter_id;
            }

            $userData['userId'] = $user['id'];    
            $userData['first_name'] = $user['first_name'];
            $userData['last_name'] = $user['last_name'];
            $userData['display_name'] = $user['display_name'];
            $userData['bio'] = $user['bio'];
            $userData['profile'] = (!empty($user['profile']) ? url('storage/'.$user['profile']) : '');
            $userData['banner'] = (!empty($user['cover']) ? url('storage/'.$user['cover']) : '');
            $userData['username'] = $user['username'];
            $userData['country'] = $user['country'];
            $userData['state'] = $user['state'];
            $userData['subscription_price'] = $user['subscription_price'];
            $userData['twitter_url'] = $user['twitter_url'];
            $userData['amazon_url'] = $user['amazon_url'];
            $userData['followerList'] = $followerList;
            $userData['wishList'] = $wishList;
            if(!empty($user['card_number'])){
                $cardDetails = 1;
            }else{
                $cardDetails = 0;
            }
            $userData['Role'] = (isset($user->roles->first()->name)) ? $user->roles->first()->name : '';
            $userData['cardDetails'] = $cardDetails;

        $ID = 0;
        foreach($postDetails as $postDetail){
            $likedbyme = 0;
            $commentByMe = 0;
            $lastCommenId = 0;
            $totalCount = 0;
            

            $likeDetails = Like::where('post_id',$postDetail['id'])
            ->join('users', 'users.id', '=', 'likes.user_id')
            ->get();
            $likeUsers = array();
            if(count($likeDetails) > 0){
                $j = 0;
                foreach($likeDetails as $likeDetail){
                    $likeUserIds[] = $likeDetail['user_id'];
                    $likeUsers[$j]['id'] = $likeDetail['user_id'];
                    $likeUsers[$j]['profile'] = (!empty($likeDetail['profile']) ? url('storage/'.$likeDetail['profile']) : '');
                    $likeUsers[$j]['banner'] = (!empty($likeDetail['cover']) ? url('storage/'.$likeDetail['cover']) : '');
                    $likeUsers[$j]['firstName'] = $likeDetail['first_name'];
                    $likeUsers[$j]['lastName'] = $likeDetail['last_name'];
                    $likeUsers[$j]['displayName'] = $likeDetail['display_name'];
                    $likeUsers[$j]['userName'] = $likeDetail['username'];
                    $j++;
                }
                if(in_array($loginUser, $likeUserIds)){
                    $likedbyme = 1;
                }
            }

            $commentDetails = Comment::select('comments.*','users.first_name','users.last_name','users.display_name','users.username','users.profile','users.cover','users.id as uid')->where('post_id',$postDetail['id'])
                                ->leftJoin('users', 'users.id', '=', 'comments.user_id')
                                ->get();

            
            
            $commentUsers = array();
            $k = 0;
            $totalCount = count($commentDetails);
                
            if(count($commentDetails) > 0){
                $getlastCommenId = Comment::limit(1)->orderBy('id','DESC')->get();
                $lastCommenId = $getlastCommenId[0]['id']; 
                foreach($commentDetails as $commentDetail){
                    $commentLikeByMe = 0;
                    $commentReplyByMe = 0;
                    
                    $commentIds[] = $commentDetail['uid'];
                    
                    $commentComentDetails = comment_comment::where('comment_id',$commentDetail['id'])->leftJoin('users', 'users.id', '=', 'comment_comments.user_id')->get();
                    $commentcommentUsers = array();
                    $commentCount = 0;
                    if(count($commentComentDetails) > 0){
                        $i = 0;
                        $commentCount = $commentCount + count($commentComentDetails);
                        foreach($commentComentDetails as $commentComentDetail){
                            $commentCommentUserIds[] = $commentComentDetail['user_id'];
                            $commentcommentDate = \Carbon\Carbon::parse($commentComentDetail['created_at'])->isoFormat('D MMMM YYYY');
                            $commentcommentUsers[$i]['userid'] = $commentComentDetail['user_id'];
                            $commentcommentUsers[$i]['comment'] = $commentComentDetail['comment'];
                            $commentcommentUsers[$i]['commentDate'] = $commentcommentDate;
                            $commentcommentUsers[$i]['profile'] = (!empty($commentComentDetail['profile']) ? url('storage/'.$commentComentDetail['profile']) : '');
                            $commentcommentUsers[$i]['firstName'] = $commentComentDetail['first_name'];
                            $commentcommentUsers[$i]['lastName'] = $commentComentDetail['last_name'];
                            $commentcommentUsers[$i]['displayName'] = $commentComentDetail['display_name'];
                            $commentcommentUsers[$i]['userName'] = $commentComentDetail['username'];
                            $i++;
                        }
                        if(in_array($loginUser, $commentCommentUserIds)){
                            $commentReplyByMe = 1;
                        }
                    }

                    $totalCount = $totalCount+$commentCount;
                        
                    $commentLikeDetails = comment_like::where('comment_id',$commentDetail['id'])->leftJoin('users', 'users.id', '=', 'comment_likes.user_id')->get();
                    $commentLikeUsers = array();
                    if(count($commentLikeDetails) > 0){
                        $i1 = 0;
                        
                        foreach($commentLikeDetails as $commentLikeDetail){
                            $commentUserIds[] = $commentLikeDetail['user_id'];
                            $commentLikeUsers[$i1]['userid'] = $commentLikeDetail['user_id'];
                            $commentLikeUsers[$i1]['profile'] = (!empty($commentLikeDetail['profile']) ? url('storage/'.$commentLikeDetail['profile']) : '');
                            $commentLikeUsers[$i1]['firstName'] = $commentLikeDetail['first_name'];
                            $commentLikeUsers[$i1]['lastName'] = $commentLikeDetail['last_name'];
                            $commentLikeUsers[$i1]['displayName'] = $commentLikeDetail['display_name'];
                            $commentLikeUsers[$i1]['userName'] = $commentLikeDetail['username'];
                            $i1++;
                        }
                        if(in_array($loginUser, $commentUserIds)){
                            $commentLikeByMe = 1;
                        }
                    }
                    

                    $commentDate = \Carbon\Carbon::parse($commentDetail['created_at'])->isoFormat('D MMMM YYYY'); 

                    $commentUsers[$k]['id'] = $commentDetail['id'];
                    $commentUsers[$k]['userid'] = $commentDetail['user_id'];
                    $commentUsers[$k]['comment'] = $commentDetail['comment'];
                    $commentUsers[$k]['commentDate'] = $commentDate;
                    $commentUsers[$k]['profile'] = (!empty($commentDetail['profile']) ? url('storage/'.$commentDetail['profile']) : '');
                    $commentUsers[$k]['firstName'] = $commentDetail['first_name'];
                    $commentUsers[$k]['lastName'] = $commentDetail['last_name'];
                    $commentUsers[$k]['displayName'] = $commentDetail['display_name'];
                    $commentUsers[$k]['userName'] = $commentDetail['username'];
                    $commentUsers[$k]['comments'] = count($commentComentDetails);
                    $commentUsers[$k]['commentUsers'] = $commentcommentUsers;
                    $commentUsers[$k]['likeByMe'] = $commentLikeByMe;
                    $commentUsers[$k]['commentByMe'] = $commentReplyByMe;
                    $commentUsers[$k]['likes'] = count($commentLikeDetails);
                    $commentUsers[$k]['likeUsers'] = $commentLikeUsers;
                    
                    $k++;
                }
                if(in_array($loginUser, $commentIds)){
                    $commentByMe = 1;
                }
            }


            $today = Carbon::now();
            $created_at = \Carbon\Carbon::parse($postDetail['created_at']);
            $updated_at = \Carbon\Carbon::parse($postDetail['updated_at']);
            $hours_created = $created_at->diffInHours($today);
            $hours_updated = $updated_at->diffInHours($today);
            if(empty($hours_created)){
                $hours_created = $created_at->diffInMinutes($today) . ' min';
            }

            if(empty($hours_updated)){
                $hours_updated = $created_at->diffInMinutes($today) . ' min';
            }

            if(!empty($hours_created) && $hours_created > 240){
                $hours_created = \Carbon\Carbon::parse($postDetail['created_at'])->isoFormat('D MMMM YYYY');
            }

            if(!empty($hours_updated && $hours_updated > 240)){
                $hours_updated = \Carbon\Carbon::parse($postDetail['updated_at'])->isoFormat('D MMMM YYYY');
            }
             
            $postData[$ID]['id'] = $postDetail['id'];
            $postData[$ID]['title'] = $postDetail['title'];
            $postData[$ID]['caption'] = $postDetail['caption'];
            $postData[$ID]['media'] = (!empty($postDetail['media']) ? url('storage/'.$postDetail['media']) : '');
            $postData[$ID]['tags'] = $postDetail['tags'];
            $postData[$ID]['publish'] = $postDetail['publish'];
            $postData[$ID]['schedule_at'] = (!empty($postDetail['schedule_at']))?date('m/d/Y H:i', $postDetail['schedule_at']) : 0 ;
            $postData[$ID]['add_to_album'] = $postDetail['add_to_album'];
            $postData[$ID]['created'] = $hours_created;
            $postData[$ID]['updated'] = $hours_updated;
            $postData[$ID]['likedByMe'] = $likedbyme;
            $postData[$ID]['likes'] = count($likeDetails);
            $postData[$ID]['likeUsers'] = $likeUsers;
            $postData[$ID]['commentByMe'] = $commentByMe;
            $postData[$ID]['comments'] = count($commentDetails);
            $postData[$ID]['totalComments'] = $totalCount;
            $postData[$ID]['commentUsers'] = $commentUsers;
            $ID++;
        }
        if(count($postDetails)){
            return response()->json([
                'message' => 'User post list!',
                'count' => count($allPost),
                'publishCount' => count($allPublishPost),
                'imageCount' => $imageCount,
                'videoCount' => $videoCount,
                'data' => $postData,
                'lastCommentId' => $lastCommenId,
                'userData' => $userData,
                'isError' => false
            ], 201);
        }else{
            return response()->json(['error'=>'No Post available','userData' => $userData,'imageCount' => $imageCount,
            'videoCount' => $videoCount,'isError' => true]);
        }


        return response()->json(Post::find($id));

    }

    /**
     * @OA\Post(
     *          path="/api/v1/getGuestUserPost/{userid}/{loginUser}/{start}/{limit}",
     *          operationId="Get Guest User Posts",
     *          tags={"Users"},
     *      @OA\Parameter(
     *          name="userid",
     *          in="path",
     *          required=true,
     *          @OA\Schema(
     *              type="integer"
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
     *      summary="Get Guest User Posts",
     *      description="data of Guest users post",
     *
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
     *      security={ {"passport": {}} },
     *  )
     */

    public function getGuestUserPost($id,$loginUser,$start,$limit){


        $user = User::findOrFail($id);
        $allPost = $user->posts()->get();
        $imageTypes = array('jpg','jpeg','png','bmp','gif','webp');
        $videoTypes = array('mp4','webm','ogg');
        $videoCount = 0;
        $imageCount = 0;
        if(!empty($loginUser)){
            $postDetails = $user->posts()->orderBy('id','DESC')->offset($start)->limit($limit)->get();
        }else{
            $postDetails = array();
        }

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
        //$postDetails = $user->posts()->orderBy('id','DESC')->offset($start)->limit($limit)->get();
            $userData['userId'] = $user['id'];
            $userData['first_name'] = $user['first_name'];
            $userData['last_name'] = $user['last_name'];
            $userData['display_name'] = $user['display_name'];
            $userData['bio'] = $user['bio'];
            $userData['profile'] = (!empty($user['profile']) ? url('storage/'.$user['profile']) : '');
            $userData['banner'] = (!empty($user['banner']) ? url('storage/'.$user['banner']) : '');
            $userData['username'] = $user['username'];
            $userData['country'] = $user['country'];
            $userData['state'] = $user['state'];
            $userData['subscription_price'] = $user['subscription_price'];

        $ID = 0;
        foreach($postDetails as $postDetail){
            //$ID = $postDetail['id'];
            $likedbyme = 0;
            // $Users = $postDetail->users()->get();
            // foreach($Users as $user){
            //     $UserDetails = $user;
            // }

            $likeDetails = Like::where('post_id',$postDetail['id'])
            ->join('users', 'users.id', '=', 'likes.user_id')
            ->get();
            $likeUsers = array();
            if(count($likeDetails) > 0){
                $j = 0;
                foreach($likeDetails as $likeDetail){
                    if($loginUser == $likeDetail['user_id']){
                        $likedbyme = 1;
                    }
                    $likeUsers[$j]['id'] = $likeDetail['user_id'];
                    $likeUsers[$j]['profile'] = (!empty($likeDetail['profile']) ? url('storage/'.$likeDetail['profile']) : '');
                    $likeUsers[$j]['banner'] = (!empty($likeDetail['cover']) ? url('storage/'.$likeDetail['cover']) : '');
                    $likeUsers[$j]['firstName'] = $likeDetail['first_name'];
                    $likeUsers[$j]['lastName'] = $likeDetail['last_name'];
                    $likeUsers[$j]['displayName'] = $likeDetail['display_name'];
                    $likeUsers[$j]['userName'] = $likeDetail['username'];
                    $j++;
                }
            }

            $commentDetails = Comment::where('post_id',$postDetail['id'])
                                ->join('users', 'users.id', '=', 'comments.user_id')
                                ->get();
            $commentUsers = array();
            $k = 0;
            if(count($commentDetails) > 0){
                foreach($commentDetails as $commentDetail){
                    $commentUsers[$k]['userid'] = $commentDetail['user_id'];
                    $commentUsers[$k]['comment'] = $commentDetail['comment'];
                    $commentUsers[$k]['profile'] = $commentDetail['profile'];
                    $commentUsers[$k]['banner'] = $commentDetail['cover'];
                    $commentUsers[$k]['firstName'] = $commentDetail['first_name'];
                    $commentUsers[$k]['lastName'] = $commentDetail['last_name'];
                    $commentUsers[$k]['displayName'] = $commentDetail['display_name'];
                    $commentUsers[$k]['userName'] = $commentDetail['username'];
                     $k++;
                }
            }


            $today = Carbon::now();
            $created_at = \Carbon\Carbon::parse($postDetail['created_at']);
            $updated_at = \Carbon\Carbon::parse($postDetail['updated_at']);
            $hours_created = $created_at->diffInHours($today);
            $hours_updated = $updated_at->diffInHours($today);
            if(empty($hours_created)){
                $hours_created = $created_at->diffInMinutes($today) . ' min';
            }

            if(empty($hours_updated)){
                $hours_updated = $created_at->diffInMinutes($today) . ' min';
            }

            if(!empty($hours_created) && $hours_created > 240){
                $hours_created = \Carbon\Carbon::parse($postDetail['created_at'])->isoFormat('D MMMM YYYY');
            }

            if(!empty($hours_updated && $hours_updated > 240)){
                $hours_updated = \Carbon\Carbon::parse($postDetail['updated_at'])->isoFormat('D MMMM YYYY');
            }

            $postData[$ID]['id'] = $postDetail['id'];
            $postData[$ID]['title'] = $postDetail['title'];
            $postData[$ID]['caption'] = $postDetail['caption'];
            $postData[$ID]['media'] = (!empty($postDetail['media']) ? url('storage/'.$postDetail['media']) : '');
            $postData[$ID]['tags'] = $postDetail['tags'];
            $postData[$ID]['publish'] = $postDetail['publish'];
            $postData[$ID]['schedule_at'] = (!empty($postDetail['schedule_at']))?date('m/d/Y H:i', $postDetail['schedule_at']) : 0 ;
            $postData[$ID]['add_to_album'] = $postDetail['add_to_album'];
            $postData[$ID]['created'] = $hours_created;
            $postData[$ID]['updated'] = $hours_updated;
            $postData[$ID]['likedByMe'] = $likedbyme;
            $postData[$ID]['likes'] = count($likeDetails);
            $postData[$ID]['likeUsers'] = $likeUsers;
            $postData[$ID]['comments'] = count($commentDetails);
            $postData[$ID]['commentUsers'] = $commentUsers;
            $ID++;
        }
        if(count($postDetails)){
            return response()->json([
                'message' => 'User post list!',
                'count' => count($allPost),
                'imageCount' => $imageCount,
                'videoCount' => $videoCount,
                'data' => $postData,
                'userData' => $userData,
                'isError' => false
            ], 201);
        }else{
            return response()->json(['error'=>'No Post available','userData' => $userData,'imageCount' => $imageCount,
            'videoCount' => $videoCount,'isError' => true]);
        }


        return response()->json(Post::find($id));

    }

    /**
     * @OA\Post(
     *          path="/api/v1/getAllPost/{loginUser}/{start}/{limit}",
     *          operationId="Get All Posts",
     *          tags={"Posts"},
     *      @OA\Parameter(
     *          name="start",
     *          in="path",
     *          required=true,
     *          @OA\Schema(
     *              type="integer"
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
     *          name="limit",
     *          in="path",
     *          required=true,
     *          @OA\Schema(
     *              type="integer"
     *          )
     *      ),
     *      summary="Get All Posts",
     *      description="data of post",
     *
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
     *      security={ {"passport": {}} },
     *  )
     */

    public function getAllPost($loginUser,$start,$limit){

        $followerList = array();
        
        $postCount = 0;
        $Followers = DB::table('follow')->where('user_id',$loginUser)->get();
            foreach($Followers as $follow){
                $followerList[] = $follow->follower_id;
            }
        if(count($Followers)<=0){
            return response()->json(['error'=>'No Post available','isError' => true]);
        }

        $user = User::findOrFail($loginUser);

        $loginUserPost = $user->posts()->get();
         
        $allPost = Post::where('publish','now')
                   ->leftJoin('post_user', 'posts.id', '=', 'post_user.post_id')
                   ->leftJoin('users', 'users.id', '=', 'post_user.user_id')
                   ->whereIn('post_user.user_id',$followerList)
                   ->get();
        $allCount = count($loginUserPost)+count($allPost);           
        
        $postDetails=DB::table('posts')->select('posts.*','users.first_name','users.last_name','users.display_name','users.username','users.profile','users.cover')
        ->leftJoin('post_user', 'posts.id', '=', 'post_user.post_id')
        ->leftJoin('users', 'users.id', '=', 'post_user.user_id')
        //->whereIn('post_user.user_id',$followerList)
        ->whereIn('posts.id',function ($query)  use ($followerList) {
            $query->select('posts.id')->from('posts')
            ->leftJoin('post_user', 'posts.id', '=', 'post_user.post_id')
            ->leftJoin('users', 'users.id', '=', 'post_user.user_id')
            ->whereIn('post_user.user_id',$followerList)
            ->Where('posts.publish','=','now');
        })
        ->orWhereIn('posts.id',function ($query1) use ($loginUser) {
            $query1->select('posts.id')->from('posts')
            ->leftJoin('post_user', 'posts.id', '=', 'post_user.post_id')
            ->leftJoin('users', 'users.id', '=', 'post_user.user_id')
            ->Where('post_user.user_id',$loginUser);
        })
        ->orderBy('posts.id','DESC')
        ->whereNull('posts.deleted_at')
        ->offset($start)
        ->limit($limit)
        ->get();
        //->toSql();    
        
        
        $ID = 0;
        foreach($postDetails as $postDetail){
            $likedbyme = 0;
            $commentByMe = 0;
            $lastCommenId = 0;
            $totalCount = 0;
                       
            $today = Carbon::now();
            $created_at = \Carbon\Carbon::parse($postDetail->created_at);
            $updated_at = \Carbon\Carbon::parse($postDetail->updated_at);
            $hours_created = $created_at->diffInHours($today);
            $hours_updated = $updated_at->diffInHours($today);
            if(empty($hours_created)){
                $hours_created = $created_at->diffInMinutes($today) . ' min';
            }

            if(empty($hours_updated)){
                $hours_updated = $created_at->diffInMinutes($today) . ' min';
            }

            if(!empty($hours_created) && $hours_created > 240){
                $hours_created = \Carbon\Carbon::parse($postDetail->created_at)->isoFormat('D MMMM YYYY');
            }

            if(!empty($hours_updated && $hours_updated > 240)){
                $hours_updated = \Carbon\Carbon::parse($postDetail->updated_at)->isoFormat('D MMMM YYYY');
            }

            $likeDetails = Like::where('post_id',$postDetail->id)
                            ->join('users', 'users.id', '=', 'likes.user_id')
                            ->get();
            $likeUsers = array();
            $j = 0;
            if(count($likeDetails) > 0){
                foreach($likeDetails as $likeDetail){
                    $likeUserIds[] = $likeDetail['user_id'];
                    $likeUsers[$j]['id'] = $likeDetail['user_id'];
                    $likeUsers[$j]['profile'] = (!empty($likeDetail['profile']) ? url('storage/'.$likeDetail['profile']) : '');
                    $likeUsers[$j]['firstName'] = $likeDetail['first_name'];
                    $likeUsers[$j]['lastName'] = $likeDetail['last_name'];
                    $likeUsers[$j]['displayName'] = $likeDetail['display_name'];
                    $likeUsers[$j]['userName'] = $likeDetail['username'];
                    $j++;
                }
                if(in_array($loginUser, $likeUserIds)){
                    $likedbyme = 1;
                }
            }

            $commentDetails = Comment::select('comments.*','users.first_name','users.last_name','users.display_name','users.username','users.profile','users.cover','users.id as uid')->where('post_id',$postDetail->id)
                                ->leftJoin('users', 'users.id', '=', 'comments.user_id')
                                ->get();

            
            $commentUsers = array();
            $k = 0;
            $totalCount = count($commentDetails);
                
            if(count($commentDetails) > 0){
                $getlastCommenId = Comment::limit(1)->orderBy('id','DESC')->get();
                $lastCommenId = $getlastCommenId[0]['id'];
                foreach($commentDetails as $commentDetail){
                    $commentLikeByMe = 0;
                    $commentReplyByMe = 0;
                    
                    $commentIds[] = $commentDetail['uid'];
                    
                    $commentComentDetails = comment_comment::where('comment_id',$commentDetail['id'])->leftJoin('users', 'users.id', '=', 'comment_comments.user_id')->get();
                    $commentcommentUsers = array();
                    $commentCount = 0;
                    if(count($commentComentDetails) > 0){
                        $i = 0;
                        $commentCount = $commentCount + count($commentComentDetails);
                        foreach($commentComentDetails as $commentComentDetail){
                            $commentCommentUserIds[] = $commentComentDetail['user_id'];
                            $commentcommentDate = \Carbon\Carbon::parse($commentComentDetail['created_at'])->isoFormat('D MMMM YYYY');
                            $commentcommentUsers[$i]['userid'] = $commentComentDetail['user_id'];
                            $commentcommentUsers[$i]['comment'] = $commentComentDetail['comment'];
                            $commentcommentUsers[$i]['commentDate'] = $commentcommentDate;
                            $commentcommentUsers[$i]['profile'] = (!empty($commentComentDetail['profile']) ? url('storage/'.$commentComentDetail['profile']) : '');
                            $commentcommentUsers[$i]['firstName'] = $commentComentDetail['first_name'];
                            $commentcommentUsers[$i]['lastName'] = $commentComentDetail['last_name'];
                            $commentcommentUsers[$i]['displayName'] = $commentComentDetail['display_name'];
                            $commentcommentUsers[$i]['userName'] = $commentComentDetail['username'];
                            $i++;
                        }
                        if(in_array($loginUser, $commentCommentUserIds)){
                            $commentReplyByMe = 1;
                        }
                    }

                    $totalCount = $totalCount+$commentCount;
                        
                    $commentLikeDetails = comment_like::where('comment_id',$commentDetail['id'])->leftJoin('users', 'users.id', '=', 'comment_likes.user_id')->get();
                    $commentLikeUsers = array();
                    if(count($commentLikeDetails) > 0){
                        $i1 = 0;
                        
                        foreach($commentLikeDetails as $commentLikeDetail){
                            $commentUserIds[] = $commentLikeDetail['user_id'];
                            $commentLikeUsers[$i1]['userid'] = $commentLikeDetail['user_id'];
                            $commentLikeUsers[$i1]['profile'] = (!empty($commentLikeDetail['profile']) ? url('storage/'.$commentLikeDetail['profile']) : '');
                            $commentLikeUsers[$i1]['firstName'] = $commentLikeDetail['first_name'];
                            $commentLikeUsers[$i1]['lastName'] = $commentLikeDetail['last_name'];
                            $commentLikeUsers[$i1]['displayName'] = $commentLikeDetail['display_name'];
                            $commentLikeUsers[$i1]['userName'] = $commentLikeDetail['username'];
                            $i1++;
                        }
                        if(in_array($loginUser, $commentUserIds)){
                            $commentLikeByMe = 1;
                        }
                    }
                    

                    $commentDate = \Carbon\Carbon::parse($commentDetail['created_at'])->isoFormat('D MMMM YYYY'); 

                    $commentUsers[$k]['id'] = $commentDetail['id'];
                    $commentUsers[$k]['userid'] = $commentDetail['user_id'];
                    $commentUsers[$k]['comment'] = $commentDetail['comment'];
                    $commentUsers[$k]['commentDate'] = $commentDate;
                    $commentUsers[$k]['profile'] = (!empty($commentDetail['profile']) ? url('storage/'.$commentDetail['profile']) : '');
                    $commentUsers[$k]['firstName'] = $commentDetail['first_name'];
                    $commentUsers[$k]['lastName'] = $commentDetail['last_name'];
                    $commentUsers[$k]['displayName'] = $commentDetail['display_name'];
                    $commentUsers[$k]['userName'] = $commentDetail['username'];
                    $commentUsers[$k]['comments'] = count($commentComentDetails);
                    $commentUsers[$k]['commentUsers'] = $commentcommentUsers;
                    $commentUsers[$k]['likeByMe'] = $commentLikeByMe;
                    $commentUsers[$k]['commentByMe'] = $commentReplyByMe;
                    $commentUsers[$k]['likes'] = count($commentLikeDetails);
                    $commentUsers[$k]['likeUsers'] = $commentLikeUsers;
                    
                    $j++;
                    $k++;
                }
                if(in_array($loginUser, $commentIds)){
                    $commentByMe = 1;
                }
            }
            $postData[$ID]['id'] = $postDetail->id;
            $postData[$ID]['firstName'] = $postDetail->first_name;
            $postData[$ID]['lastName'] = $postDetail->last_name;
            $postData[$ID]['displayName'] = $postDetail->display_name;
            $postData[$ID]['profile'] = (!empty($postDetail->profile) ? url('storage/'.$postDetail->profile) : '');
            $postData[$ID]['banner'] = (!empty($postDetail->cover) ? url('storage/'.$postDetail->cover) : '');
            $postData[$ID]['username'] = $postDetail->username;
            $postData[$ID]['title'] = $postDetail->title;
            $postData[$ID]['caption'] = $postDetail->caption;
            $postData[$ID]['media'] = (!empty($postDetail->media) ? url('storage/'.$postDetail->media) : '');
            $postData[$ID]['tags'] = $postDetail->tags;
            $postData[$ID]['publish'] = $postDetail->publish;
            $postData[$ID]['schedule_at'] = (!empty($postDetail->schedule_at))?date('m/d/Y H:i', $postDetail->schedule_at) : 0 ;
            $postData[$ID]['add_to_album'] = $postDetail->add_to_album;
            $postData[$ID]['created'] = $hours_created;
            $postData[$ID]['updated'] = $hours_updated;
            $postData[$ID]['likedByMe'] = $likedbyme;
            $postData[$ID]['likes'] = count($likeDetails);
            $postData[$ID]['likeUsers'] = $likeUsers;
            $postData[$ID]['commentByMe'] = $commentByMe;
            $postData[$ID]['comments'] = count($commentDetails);
            $postData[$ID]['totalComments'] = $totalCount;
            $postData[$ID]['commentUsers'] = $commentUsers;

            $ID++;
        }
        if(count($postDetails)){
            return response()->json([
                'message' => 'All post list!',
                'count' => $allCount,
                'data' => $postData,
                'lastCommentId' => $lastCommenId,
                'isError' => false
            ], 201);
        }else{
            return response()->json(['error'=>'No Post available','isError' => true]);
        }

    }

    /**
     * @OA\Post(
     *          path="/api/v1/deletePost/{postid}",
     *          operationId="Delete User Post Details",
     *          tags={"Posts"},
     *      @OA\Parameter(
     *          name="postid",
     *          in="path",
     *          required=true,
     *          @OA\Schema(
     *              type="integer"
     *          )
     *      ),
     *
     *      summary="Delete User Post Details",
     *      description="delete data of users post",
     *
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
     *      security={ {"passport": {}} },
     *  )
     */

    public function deletePost($id){
        if(Post::find($id)->delete()){
            return response()->json([
                'message' => 'Successfully deleted post!'
            ], 201);
        }else{
            return response()->json(['error'=>'Provide proper details','isError' => false]);
        }

    }



    /**
     * @OA\Post(
     *          path="/api/v1/likePost/{postid}/{userid}",
     *          operationId="Like User Post",
     *          tags={"Posts"},
     *      @OA\Parameter(
     *          name="postid",
     *          in="path",
     *          required=true,
     *          @OA\Schema(
     *              type="integer"
     *          )
     *      ),
     *      @OA\Parameter(
     *          name="userid",
     *          in="path",
     *          required=true,
     *          @OA\Schema(
     *              type="integer"
     *          )
     *      ),
     *
     *      summary="Like User Post",
     *      description="Like User Post",
     *
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
     *      security={ {"passport": {}} },
     *  )
     */

    public function likePost($postid,$userid){

        //echo $request->ScheduleDateTime; exit;
        $post_like =  new Like([
            'post_id' => $postid,
            'user_id' => $userid,
        ]);

        $users = Like::where('post_id',$postid)->where('user_id',$userid)->get();
        $postData = $this->getPostResponse($postid);
        if(count($users) == 0){
            if($post_like->save()){
                return response()->json([
                    'message' => 'Post liked successfully!',
                    'data' => $postData,
                    'isError' => false
                ], 201);
            }else{
                return response()->json(['error'=>'Provide proper details','isError' => true]);
            }
        }else{

            if(Like::where('post_id',$postid)->where('user_id',$userid)->delete())
            {
                return response()->json([
                    'message' => 'Post disliked successfully!',
                    'data' => $postData,
                    'isError' => false,
                ], 201);
            }else{
                return response()->json(['error'=>'Provide proper details','isError' => false]);
            }
        }
    }

    

    /**
     * @OA\Post(
     *          path="/api/v1/addCommenttoPost/{postid}/{userid}",
     *          operationId="Comment on User Post",
     *          tags={"Posts"},
     *      @OA\Parameter(
     *          name="postid",
     *          in="path",
     *          required=true,
     *          @OA\Schema(
     *              type="integer"
     *          )
     *      ),
     *      @OA\Parameter(
     *          name="userid",
     *          in="path",
     *          required=true,
     *          @OA\Schema(
     *              type="integer"
     *          )
     *      ),
     *      @OA\RequestBody(
     *       @OA\MediaType(
     *           mediaType="multipart/form-data",
     *           @OA\Schema(
     *               required={"Comment"},
     *               @OA\Property(
     *                  property="Comment",
     *                  type="string"
     *               ),
     *           )
     *         ),
     *      ),
     *
     *      summary="Comment on User Post",
     *      description="Comment on User Post",
     *
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
     *      security={ {"passport": {}} },
     *  )
     */

    public function addCommenttoPost(request $request, $postid,$userid){

        $validator = Validator::make($request->all(),[
            'Comment' => 'required',
         ]);

        if ($validator->fails()) {
            $failedRules = $validator->failed();
            return response()->json(['error'=>$validator->errors(),'isError' => true]);
        }
        $post_comment =  new Comment([
            'post_id' => $postid,
            'user_id' => $userid,
            'comment' => $request->Comment,
        ]);

       if($post_comment->save()){
                $postData = $this->getPostResponse($postid);
                return response()->json([
                    'message' => 'Comment added successfully!',
                    'data' => $postData,
                    'isError' => false
                ], 201);
            }else{
                return response()->json(['error'=>'Provide proper details','isError' => true]);
            }
    }

    /**
     * @OA\Post(
     *          path="/api/v1/updatePostComment/{commentid}",
     *          operationId="Update Comment on User Post",
     *          tags={"Posts"},
     *      @OA\Parameter(
     *          name="commentid",
     *          in="path",
     *          required=true,
     *          @OA\Schema(
     *              type="integer"
     *          )
     *      ),
     *
     *      @OA\RequestBody(
     *       @OA\MediaType(
     *           mediaType="multipart/form-data",
     *           @OA\Schema(
     *               required={"Comment"},
     *               @OA\Property(
     *                  property="Comment",
     *                  type="string"
     *               ),
     *           )
     *         ),
     *      ),
     *
     *      summary="Comment on User Post",
     *      description="Update Comment on User Post",
     *
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
     *      security={ {"passport": {}} },
     *  )
     */

    public function updatePostComment(request $request, $id){

        $validator = Validator::make($request->all(),[
            'Comment' => 'required',
         ]);

        if ($validator->fails()) {
            $failedRules = $validator->failed();
            return response()->json(['error'=>$validator->errors(),'isError' => true]);
        }


        $post_comment = Comment::where('id', $id)->update([
            'comment' => $request->Comment,
        ]);

        $comment = Comment::find($id);
         if($post_comment){
                $postData = $this->getPostResponse($comment['post_id']);
                return response()->json([
                    'message' => 'Comment updated successfully!',
                    'data' => $postData,
                    'isError' => false
                ], 201);
            }else{
                return response()->json(['error'=>'Provide proper details','isError' => true]);
            }
    }

    /**
     * @OA\Post(
     *          path="/api/v1/deletePostComment/{commentid}",
     *          operationId="Delete Comment of User Post",
     *          tags={"Posts"},
     *      @OA\Parameter(
     *          name="commentid",
     *          in="path",
     *          required=true,
     *          @OA\Schema(
     *              type="integer"
     *          )
     *      ),
     *
     *      summary="Comment on User Post",
     *      description="Delete Comment of User Post",
     *
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
     *      security={ {"passport": {}} },
     *  )
     */

    public function deletePostComment($id){

        if(Comment::where('id',$id)->delete())
        {
            return response()->json([
                'message' => 'Comment deleted successfully!'
            ], 201);
        }else{
            return response()->json(['error'=>'Provide proper details','isError' => false]);
        }
    }

    /**
     * @OA\Post(
     *          path="/api/v1/getPostDetail/{postid}",
     *          operationId="Get Post detail",
     *          tags={"Posts"},
     *      summary="Get Post detail",
     *      description="data of post",
     *      @OA\Parameter(
     *          name="postid",
     *          in="path",
     *          required=true,
     *          @OA\Schema(
     *              type="integer"
     *          )
     *      ),
     *
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
     *      security={ {"passport": {}} },
     *  )
     */

    public function getPostDetail($postid){

        $postData = $this->getPostResponse($postid);
        if(count($postData)){
            return response()->json([
                'message' => 'User post list!',
                'data' => $postData,
                'isError' => false
            ], 201);
        }else{
            return response()->json(['error'=>'No Post available','isError' => true]);
        }

    }


    /**
     * @OA\Post(
     *          path="/api/v1/viewPost/{postid}/{userid}",
     *          operationId="increse View count of User Post",
     *          tags={"Posts"},
     *      @OA\Parameter(
     *          name="postid",
     *          in="path",
     *          required=true,
     *          @OA\Schema(
     *              type="integer"
     *          )
     *      ),
     *      @OA\Parameter(
     *          name="userid",
     *          in="path",
     *          required=true,
     *          @OA\Schema(
     *              type="integer"
     *          )
     *      ),
     *
     *      summary="increse View count of User Post",
     *      description="Increse View count of User Post",
     *
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
     *      security={ {"passport": {}} },
     *  )
     */

    public function viewPost($postid,$userid){
        $ip = request()->ip();

        $post_view =  new Views([
            'post_id' => $postid,
            'user_id' => $userid,
            'ip_addr' => $ip,
        ]);
        $checkView = DB::table('views')
                    ->where('post_id',$postid)
                    ->where('user_id',$userid)
                    ->where('ip_addr',$ip)
                    ->get();
        $postData = $this->getPostResponse($postid);
        if(count($checkView) == 0){
            if($post_view->save()){
                return response()->json([
                    'message' => 'Post Viewed successfully!',
                    'data' => $postData,
                    'isError' => false
                ], 201);
            }else{
                return response()->json(['error'=>'Provide proper details','isError' => true]);
            }
        }else{
            return response()->json(['message'=>'Post Viewed successfully!','data' => $postData,'isError' => false]);
        }
    }

    /**
     * @OA\Post(
     *          path="/api/v1/mostViewed/{loginUser}/{start}/{limit}",
     *          operationId="List of most viewed posts",
     *          tags={"Posts"},
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
     *      summary="List of most viewed posts",
     *      description="List of most viewed posts",
     *
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
     *      security={ {"passport": {}} },
     *  )
     */

    public function mostViewed($loginUser,$start,$limit){

        $followerList = array();
        $Followers = DB::table('follow')->where('user_id',$loginUser)->get();
        foreach($Followers as $follow){
            $followerList[] = $follow->follower_id;
        }
        
        if(count($Followers)<=0){
            return response()->json(['error'=>'No Post available','isError' => true]);
        }
        $allPost = DB::table('posts')->select('posts.*','users.first_name','users.last_name','users.display_name','users.username','users.profile','users.cover')
        ->leftJoin('post_user', 'posts.id', '=', 'post_user.post_id')
        ->leftJoin('users', 'users.id', '=', 'post_user.user_id')
        ->whereIn('posts.id',function ($query)  use ($followerList) {
            $query->select('posts.id')->from('posts')
            ->leftJoin('post_user', 'posts.id', '=', 'post_user.post_id')
            ->join('views', 'posts.id', '=', 'views.post_id')
            ->leftJoin('users', 'users.id', '=', 'post_user.user_id')
            ->whereIn('post_user.user_id',$followerList)
            ->Where('posts.publish','=','now');
        })
        ->orWhereIn('posts.id',function ($query1) use ($loginUser) {
            $query1->select('posts.id')->from('posts')
            ->leftJoin('post_user', 'posts.id', '=', 'post_user.post_id')
            ->join('views', 'posts.id', '=', 'views.post_id')
            ->leftJoin('users', 'users.id', '=', 'post_user.user_id')
            ->Where('post_user.user_id',$loginUser);
        })
        ->orderBy('posts.id','DESC')
        ->whereNull('posts.deleted_at')
        ->get();
        //->toSql();

        //dd($posts); exit;

        
        $posts = DB::table('posts')->select('posts.*','users.first_name','users.last_name','users.display_name','users.username','users.profile','users.cover')
        ->leftJoin('post_user', 'posts.id', '=', 'post_user.post_id')
        ->leftJoin('users', 'users.id', '=', 'post_user.user_id')
        ->whereIn('posts.id',function ($query)  use ($followerList) {
            $query->select('posts.id')->from('posts')
            ->leftJoin('post_user', 'posts.id', '=', 'post_user.post_id')
            ->join('views', 'posts.id', '=', 'views.post_id')
            ->leftJoin('users', 'users.id', '=', 'post_user.user_id')
            ->whereIn('post_user.user_id',$followerList)
            ->Where('posts.publish','=','now');
        })
        ->orWhereIn('posts.id',function ($query1) use ($loginUser) {
            $query1->select('posts.id')->from('posts')
            ->leftJoin('post_user', 'posts.id', '=', 'post_user.post_id')
            ->join('views', 'posts.id', '=', 'views.post_id')
            ->leftJoin('users', 'users.id', '=', 'post_user.user_id')
            ->Where('post_user.user_id',$loginUser);
        })
        ->orderBy('posts.id','DESC')
        ->whereNull('posts.deleted_at')
        ->offset($start)
        ->limit($limit)
        ->get();
        //->toSql();

        //dd($posts); exit;

        //echo '<pre>'; print_r($posts); exit;
        $ID = 0;
        foreach($posts as $postDetail){
            //$ID = $postDetail['id'];
            $likedbyme = 0;
            $commentByMe = 0;
            $lastCommenId = 0;
            $totalCount = 0;
            $likeDetails = Like::where('post_id',$postDetail->id)
                            ->join('users', 'users.id', '=', 'likes.user_id')
                            ->get();
            $likeUsers = array();
            $j = 0;
            if(count($likeDetails) > 0){
                foreach($likeDetails as $likeDetail){
                    $likeUserIds[] = $likeDetail['user_id'];
                    $likeUsers[$j]['id'] = $likeDetail['user_id'];
                    $likeUsers[$j]['profile'] = (!empty($likeDetail['profile']) ? url('storage/'.$likeDetail['profile']) : '');
                    $likeUsers[$j]['firstName'] = $likeDetail['first_name'];
                    $likeUsers[$j]['lastName'] = $likeDetail['last_name'];
                    $likeUsers[$j]['displayName'] = $likeDetail['display_name'];
                    $likeUsers[$j]['userName'] = $likeDetail['username'];
                    
                    $j++;
                }
                if(in_array($loginUser, $likeUserIds)){
                    $likedbyme = 1;
                }
            }

            $commentDetails = Comment::select('comments.*','users.first_name','users.last_name','users.display_name','users.username','users.profile','users.cover','users.id as uid')->where('post_id',$postDetail->id)
                                ->leftJoin('users', 'users.id', '=', 'comments.user_id')
                                ->get();

            
            //$getlastCommenId = Comment::limit(1)->orderBy('id','DESC')->get();
            //$lastCommenId = $getlastCommenId[0]['id']; 
            $commentUsers = array();
            $k = 0;
            $totalCount = count($commentDetails);
                
            if(count($commentDetails) > 0){
                $getlastCommenId = Comment::limit(1)->orderBy('id','DESC')->get();
                $lastCommenId = $getlastCommenId[0]['id'];
                foreach($commentDetails as $commentDetail){
                    $commentLikeByMe = 0;
                    $commentReplyByMe = 0;
                    
                    $commentIds[] = $commentDetail['uid'];
                    
                    $commentComentDetails = comment_comment::where('comment_id',$commentDetail['id'])->leftJoin('users', 'users.id', '=', 'comment_comments.user_id')->get();
                    $commentcommentUsers = array();
                    $commentCount = 0;
                    if(count($commentComentDetails) > 0){
                        $i = 0;
                        $commentCount = $commentCount + count($commentComentDetails);
                        foreach($commentComentDetails as $commentComentDetail){
                            $commentCommentUserIds[] = $commentComentDetail['user_id'];
                            $commentcommentDate = \Carbon\Carbon::parse($commentComentDetail['created_at'])->isoFormat('D MMMM YYYY');
                            $commentcommentUsers[$i]['userid'] = $commentComentDetail['user_id'];
                            $commentcommentUsers[$i]['comment'] = $commentComentDetail['comment'];
                            $commentcommentUsers[$i]['commentDate'] = $commentcommentDate;
                            $commentcommentUsers[$i]['profile'] = (!empty($commentComentDetail['profile']) ? url('storage/'.$commentComentDetail['profile']) : '');
                            $commentcommentUsers[$i]['firstName'] = $commentComentDetail['first_name'];
                            $commentcommentUsers[$i]['lastName'] = $commentComentDetail['last_name'];
                            $commentcommentUsers[$i]['displayName'] = $commentComentDetail['display_name'];
                            $commentcommentUsers[$i]['userName'] = $commentComentDetail['username'];
                            $i++;
                        }
                        if(in_array($loginUser, $commentCommentUserIds)){
                            $commentReplyByMe = 1;
                        }
                    }

                    $totalCount = $totalCount+$commentCount;
                        
                    $commentLikeDetails = comment_like::where('comment_id',$commentDetail['id'])->leftJoin('users', 'users.id', '=', 'comment_likes.user_id')->get();
                    $commentLikeUsers = array();
                    if(count($commentLikeDetails) > 0){
                        $i1 = 0;
                        
                        foreach($commentLikeDetails as $commentLikeDetail){
                            $commentUserIds[] = $commentLikeDetail['user_id'];
                            $commentLikeUsers[$i1]['userid'] = $commentLikeDetail['user_id'];
                            $commentLikeUsers[$i1]['profile'] = (!empty($commentLikeDetail['profile']) ? url('storage/'.$commentLikeDetail['profile']) : '');
                            $commentLikeUsers[$i1]['firstName'] = $commentLikeDetail['first_name'];
                            $commentLikeUsers[$i1]['lastName'] = $commentLikeDetail['last_name'];
                            $commentLikeUsers[$i1]['displayName'] = $commentLikeDetail['display_name'];
                            $commentLikeUsers[$i1]['userName'] = $commentLikeDetail['username'];
                            $i1++;
                        }
                        if(in_array($loginUser, $commentUserIds)){
                            $commentLikeByMe = 1;
                        }
                    }
                    

                    $commentDate = \Carbon\Carbon::parse($commentDetail['created_at'])->isoFormat('D MMMM YYYY'); 

                    $commentUsers[$k]['id'] = $commentDetail['id'];
                    $commentUsers[$k]['userid'] = $commentDetail['user_id'];
                    $commentUsers[$k]['comment'] = $commentDetail['comment'];
                    $commentUsers[$k]['commentDate'] = $commentDate;
                    $commentUsers[$k]['profile'] = (!empty($commentDetail['profile']) ? url('storage/'.$commentDetail['profile']) : '');
                    $commentUsers[$k]['firstName'] = $commentDetail['first_name'];
                    $commentUsers[$k]['lastName'] = $commentDetail['last_name'];
                    $commentUsers[$k]['displayName'] = $commentDetail['display_name'];
                    $commentUsers[$k]['userName'] = $commentDetail['username'];
                    $commentUsers[$k]['comments'] = count($commentComentDetails);
                    $commentUsers[$k]['commentUsers'] = $commentcommentUsers;
                    $commentUsers[$k]['likeByMe'] = $commentLikeByMe;
                    $commentUsers[$k]['commentByMe'] = $commentReplyByMe;
                    $commentUsers[$k]['likes'] = count($commentLikeDetails);
                    $commentUsers[$k]['likeUsers'] = $commentLikeUsers;
                    
                    $j++;
                    $k++;
                }
                if(in_array($loginUser, $commentIds)){
                    $commentByMe = 1;
                }
            }
            $today = Carbon::now();
            $created_at = \Carbon\Carbon::parse($postDetail->created_at);
            $updated_at = \Carbon\Carbon::parse($postDetail->updated_at);
            $hours_created = $created_at->diffInHours($today);
            $hours_updated = $updated_at->diffInHours($today);

            if(empty($hours_created)){
                $hours_created = $created_at->diffInMinutes($today) . ' min';
            }

            if(empty($hours_updated)){
                $hours_updated = $created_at->diffInMinutes($today) . ' min';
            }

            if(!empty($hours_created) && $hours_created > 240){
                $hours_created = \Carbon\Carbon::parse($postDetail->created_at)->isoFormat('D MMMM YYYY');
            }

            if(!empty($hours_updated && $hours_updated > 240)){
                $hours_updated = \Carbon\Carbon::parse($postDetail->updated_at)->isoFormat('D MMMM YYYY');
            }

            $postData[$ID]['id'] = $postDetail->id;
            $postData[$ID]['firstName'] = $postDetail->first_name;
            $postData[$ID]['lastName'] = $postDetail->last_name;
            $postData[$ID]['displayName'] = $postDetail->display_name;
            $postData[$ID]['profile'] = (!empty($postDetail->profile) ? url('storage/'.$postDetail->profile) : '');
            $postData[$ID]['banner'] = (!empty($postDetail->cover) ? url('storage/'.$postDetail->cover) : '');
            $postData[$ID]['username'] = $postDetail->username;
            $postData[$ID]['title'] = $postDetail->title;
            $postData[$ID]['caption'] = $postDetail->caption;
            $postData[$ID]['media'] = (!empty($postDetail->media) ? url('storage/'.$postDetail->media) : '');
            $postData[$ID]['tags'] = $postDetail->tags;
            $postData[$ID]['publish'] = $postDetail->publish;
            $postData[$ID]['schedule_at'] = (!empty($postDetail->schedule_at))?date('m/d/Y H:i', $postDetail->schedule_at) : 0 ;
            $postData[$ID]['add_to_album'] = $postDetail->add_to_album;
            $postData[$ID]['created'] = $hours_created;
            $postData[$ID]['updated'] = $hours_updated;
            $postData[$ID]['likedByMe'] = $likedbyme;
            $postData[$ID]['likes'] = count($likeDetails);
            $postData[$ID]['likeUsers'] = $likeUsers;
            $postData[$ID]['commentByMe'] = $commentByMe;
            $postData[$ID]['comments'] = count($commentDetails);
            $postData[$ID]['totalComments'] = $totalCount;
            $postData[$ID]['commentUsers'] = $commentUsers;
            $ID++;
        }
        if(count($posts)){
            return response()->json([
                'message' => 'Most Viewed post list!',
                'count' => count($allPost),
                'data' => $postData,
                'lastCommentId' => $lastCommenId,
                'isError' => false
            ], 201);
        }else{
            return response()->json(['error'=>'No Post available','isError' => true]);
        }
    }


    /**
     * @OA\Post(
     *          path="/api/v1/mostPopular/{loginUser}/{start}/{limit}",
     *          operationId="List of most popular posts",
     *          tags={"Posts"},
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
     *      summary="List of most popular posts",
     *      description="List of most popular posts",
     *
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
     *      security={ {"passport": {}} },
     *  )
     */

    public function mostPopular($loginUser,$start,$limit){
        
        $followerList = array();
        $Followers = DB::table('follow')->where('user_id',$loginUser)->get();
        foreach($Followers as $follow){
            $followerList[] = $follow->follower_id;
        }
        $followerList[] = $loginUser;
        if(count($Followers)<=0){
            return response()->json(['error'=>'No Post available','isError' => true]);
        }
        $allPost = Post::where('publish','now')
        ->leftJoin('post_user', 'posts.id', '=', 'post_user.post_id')
        ->leftJoin('users', 'users.id', '=', 'post_user.user_id')
        ->whereIn('post_user.user_id',$followerList)
        // ->whereIn('posts.id',function ($query)  use ($followerList) {
        //     $query->select('posts.id')->from('posts')
        //     ->leftJoin('post_user', 'posts.id', '=', 'post_user.post_id')
        //     ->leftJoin('users', 'users.id', '=', 'post_user.user_id')
        //     ->whereIn('post_user.user_id',$followerList)
        //     ->Where('posts.publish','=','now');
        // })
        // ->orWhereIn('posts.id',function ($query1) use ($loginUser) {
        //     $query1->select('posts.id')->from('posts')
        //     ->leftJoin('post_user', 'posts.id', '=', 'post_user.post_id')
        //     ->leftJoin('users', 'users.id', '=', 'post_user.user_id')
        //     ->Where('post_user.user_id',$loginUser);
        // })
        ->whereNull('posts.deleted_at')
        ->get();
       $posts = DB::table("posts")
        ->select("posts.*",DB::raw('users.first_name,users.last_name,users.display_name,users.username,users.profile,users.cover'),
                DB::raw("(SELECT ifnull(COUNT(likes.post_id),0) FROM likes
                              WHERE likes.post_id = posts.id
                              GROUP BY likes.post_id) as likeCount"),
                DB::raw("(SELECT ifnull(COUNT(comments.post_id),0) FROM comments
                              WHERE comments.post_id = posts.id
                              GROUP BY comments.post_id) as commentCount"),
                DB::raw("(SELECT ifnull(COUNT(comments.post_id),0) FROM comments
                              WHERE comments.post_id = posts.id
                              GROUP BY comments.post_id) + (SELECT ifnull(COUNT(likes.post_id),0) FROM likes
                              WHERE likes.post_id = posts.id
                              GROUP BY likes.post_id) as totalCount"))
                ->leftJoin('post_user', 'post_user.post_id', '=', 'posts.id')
                ->leftJoin('users', 'users.id', '=', 'post_user.user_id')
                ->whereNull('posts.deleted_at')
                ->where('posts.publish','now')
                ->whereIn('post_user.user_id',$followerList)
                // ->whereIn('posts.id',function ($query)  use ($followerList) {
                //     $query->select('posts.id','ifnull(COUNT(comments.post_id),0')->from('posts')
                //     ->leftJoin('post_user', 'posts.id', '=', 'post_user.post_id')
                //     ->leftJoin('comments', 'comments.post_id', '=', 'posts.id')
                //     ->leftJoin('users', 'users.id', '=', 'post_user.user_id')
                //     ->whereIn('post_user.user_id',$followerList)
                //     ->Where('posts.publish','=','now');
                // })
                // ->orWhereIn('posts.id',function ($query1) use ($loginUser) {
                //     $query1->select('posts.id')->from('posts')
                //     ->leftJoin('post_user', 'posts.id', '=', 'post_user.post_id')
                //     ->leftJoin('users', 'users.id', '=', 'post_user.user_id')
                //     ->Where('post_user.user_id',$loginUser);
                // })
                ->orderBy('totalCount', 'DESC')
                ->orderBy('likeCount', 'DESC')->orderBy('commentCount', 'DESC')->orderBy('posts.id','DESC')->offset($start)->limit($limit)
                ->get();
                //->toSql();
                //dd($posts); exit;

        $ID=0;
        $posts = json_decode($posts, true);
        foreach($posts as $postDetail){
            $likedbyme = 0;
            $commentByMe = 0;
            $lastCommenId = 0;
            $totalCount = 0;
            $likeDetails = Like::where('post_id',$postDetail['id'])
                            ->join('users', 'users.id', '=', 'likes.user_id')
                            ->get();
            $likeUsers = array();
            $j = 0;
            if(count($likeDetails) > 0){
                foreach($likeDetails as $likeDetail){
                    $likeUserIds[] = $likeDetail['user_id'];
                    $likeUsers[$j]['id'] = $likeDetail['user_id'];
                    $likeUsers[$j]['profile'] = (!empty($likeDetail['profile']) ? url('storage/'.$likeDetail['profile']) : '');
                    $likeUsers[$j]['firstName'] = $likeDetail['first_name'];
                    $likeUsers[$j]['lastName'] = $likeDetail['last_name'];
                    $likeUsers[$j]['displayName'] = $likeDetail['display_name'];
                    $likeUsers[$j]['userName'] = $likeDetail['username'];
                    $j++;
                }
                if(in_array($loginUser, $likeUserIds)){
                    $likedbyme = 1;
                }
            }

            $commentDetails = Comment::select('comments.*','users.first_name','users.last_name','users.display_name','users.username','users.profile','users.cover','users.id as uid')->where('post_id',$postDetail['id'])
                                ->leftJoin('users', 'users.id', '=', 'comments.user_id')
                                ->get();

            
            //$getlastCommenId = Comment::limit(1)->orderBy('id','DESC')->get();
            //$lastCommenId = $getlastCommenId[0]['id']; 
            $commentUsers = array();
            $k = 0;
            $totalCount = count($commentDetails);
                
            if(count($commentDetails) > 0){
                $getlastCommenId = Comment::limit(1)->orderBy('id','DESC')->get();
                $lastCommenId = $getlastCommenId[0]['id'];
                foreach($commentDetails as $commentDetail){
                    $commentLikeByMe = 0;
                    $commentReplyByMe = 0;
                    
                    $commentIds[] = $commentDetail['uid'];
                    
                    $commentComentDetails = comment_comment::where('comment_id',$commentDetail['id'])->leftJoin('users', 'users.id', '=', 'comment_comments.user_id')->get();
                    $commentcommentUsers = array();
                    $commentCount = 0;
                    if(count($commentComentDetails) > 0){
                        $i = 0;
                        $commentCount = $commentCount + count($commentComentDetails);
                        foreach($commentComentDetails as $commentComentDetail){
                            $commentCommentUserIds[] = $commentComentDetail['user_id'];
                            $commentcommentDate = \Carbon\Carbon::parse($commentComentDetail['created_at'])->isoFormat('D MMMM YYYY');
                            $commentcommentUsers[$i]['userid'] = $commentComentDetail['user_id'];
                            $commentcommentUsers[$i]['comment'] = $commentComentDetail['comment'];
                            $commentcommentUsers[$i]['commentDate'] = $commentcommentDate;
                            $commentcommentUsers[$i]['profile'] = (!empty($commentComentDetail['profile']) ? url('storage/'.$commentComentDetail['profile']) : '');
                            $commentcommentUsers[$i]['firstName'] = $commentComentDetail['first_name'];
                            $commentcommentUsers[$i]['lastName'] = $commentComentDetail['last_name'];
                            $commentcommentUsers[$i]['displayName'] = $commentComentDetail['display_name'];
                            $commentcommentUsers[$i]['userName'] = $commentComentDetail['username'];
                            $i++;
                        }
                        if(in_array($loginUser, $commentCommentUserIds)){
                            $commentReplyByMe = 1;
                        }
                    }

                    $totalCount = $totalCount+$commentCount;
                        
                    $commentLikeDetails = comment_like::where('comment_id',$commentDetail['id'])->leftJoin('users', 'users.id', '=', 'comment_likes.user_id')->get();
                    $commentLikeUsers = array();
                    if(count($commentLikeDetails) > 0){
                        $i1 = 0;
                        
                        foreach($commentLikeDetails as $commentLikeDetail){
                            $commentUserIds[] = $commentLikeDetail['user_id'];
                            $commentLikeUsers[$i1]['userid'] = $commentLikeDetail['user_id'];
                            $commentLikeUsers[$i1]['profile'] = (!empty($commentLikeDetail['profile']) ? url('storage/'.$commentLikeDetail['profile']) : '');
                            $commentLikeUsers[$i1]['firstName'] = $commentLikeDetail['first_name'];
                            $commentLikeUsers[$i1]['lastName'] = $commentLikeDetail['last_name'];
                            $commentLikeUsers[$i1]['displayName'] = $commentLikeDetail['display_name'];
                            $commentLikeUsers[$i1]['userName'] = $commentLikeDetail['username'];
                            $i1++;
                        }
                        if(in_array($loginUser, $commentUserIds)){
                            $commentLikeByMe = 1;
                        }
                    }
                    

                    $commentDate = \Carbon\Carbon::parse($commentDetail['created_at'])->isoFormat('D MMMM YYYY'); 

                    $commentUsers[$k]['id'] = $commentDetail['id'];
                    $commentUsers[$k]['userid'] = $commentDetail['user_id'];
                    $commentUsers[$k]['comment'] = $commentDetail['comment'];
                    $commentUsers[$k]['commentDate'] = $commentDate;
                    $commentUsers[$k]['profile'] = (!empty($commentDetail['profile']) ? url('storage/'.$commentDetail['profile']) : '');
                    $commentUsers[$k]['firstName'] = $commentDetail['first_name'];
                    $commentUsers[$k]['lastName'] = $commentDetail['last_name'];
                    $commentUsers[$k]['displayName'] = $commentDetail['display_name'];
                    $commentUsers[$k]['userName'] = $commentDetail['username'];
                    $commentUsers[$k]['comments'] = count($commentComentDetails);
                    $commentUsers[$k]['commentUsers'] = $commentcommentUsers;
                    $commentUsers[$k]['likeByMe'] = $commentLikeByMe;
                    $commentUsers[$k]['commentByMe'] = $commentReplyByMe;
                    $commentUsers[$k]['likes'] = count($commentLikeDetails);
                    $commentUsers[$k]['likeUsers'] = $commentLikeUsers;
                    
                    $j++;
                    $k++;
                }
                if(in_array($loginUser, $commentIds)){
                    $commentByMe = 1;
                }
            }

            $today = Carbon::now();
            $created_at = \Carbon\Carbon::parse($postDetail['created_at']);
            $updated_at = \Carbon\Carbon::parse($postDetail['updated_at']);
            $hours_created = $created_at->diffInHours($today);
            $hours_updated = $updated_at->diffInHours($today);

            if(empty($hours_created)){
                $hours_created = $created_at->diffInMinutes($today) . ' min';
            }

            if(empty($hours_updated)){
                $hours_updated = $created_at->diffInMinutes($today) . ' min';
            }

            if(!empty($hours_created) && $hours_created > 240){
                $hours_created = \Carbon\Carbon::parse($postDetail['created_at'])->isoFormat('D MMMM YYYY');
            }

            if(!empty($hours_updated && $hours_updated > 240)){
                $hours_updated = \Carbon\Carbon::parse($postDetail['updated_at'])->isoFormat('D MMMM YYYY');
            }
            $postData[$ID]['id'] = $postDetail['id'];
            $postData[$ID]['firstName'] = $postDetail['first_name'];
            $postData[$ID]['lastName'] = $postDetail['last_name'];
            $postData[$ID]['displayName'] = $postDetail['display_name'];
            $postData[$ID]['profile'] = (!empty($postDetail['profile']) ? url('storage/'.$postDetail['profile']) : '');
            $postData[$ID]['banner'] = (!empty($postDetail['cover']) ? url('storage/'.$postDetail['cover']) : '');
            $postData[$ID]['username'] = $postDetail['username'];
            $postData[$ID]['title'] = $postDetail['title'];
            $postData[$ID]['caption'] = $postDetail['caption'];
            $postData[$ID]['media'] = (!empty($postDetail['media']) ? url('storage/'.$postDetail['media']) : '');
            $postData[$ID]['tags'] = $postDetail['tags'];
            $postData[$ID]['publish'] = $postDetail['publish'];
            $postData[$ID]['schedule_at'] = (!empty($postDetail['schedule_at']))?date('m/d/Y H:i', $postDetail['schedule_at']) : 0 ;
            $postData[$ID]['add_to_album'] = $postDetail['add_to_album'];
            $postData[$ID]['created'] = $hours_created;
            $postData[$ID]['updated'] = $hours_updated;
            $postData[$ID]['likedByMe'] = $likedbyme;
            $postData[$ID]['likes'] = count($likeDetails);
            $postData[$ID]['likeUsers'] = $likeUsers;
            $postData[$ID]['commentByMe'] = $commentByMe;
            $postData[$ID]['comments'] = count($commentDetails);
            $postData[$ID]['totalComments'] = $totalCount;
            $postData[$ID]['commentUsers'] = $commentUsers;

            $ID++;
        }
        if(count($posts)){
            return response()->json([
                'message' => 'Most Popular post list!',
                'count' => count($allPost),
                'data' => $postData,
                'lastCommentId' => $lastCommenId,
                'isError' => false
            ], 201);
        }else{
            return response()->json(['error'=>'No Post available','isError' => true]);
        }
    }

    /**
     * @OA\Post(
     *          path="/api/v1/guestMostPopular/{loginUser}/{start}/{limit}",
     *          operationId="List of most popular posts for guest",
     *          tags={"Posts"},
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
     *      summary="List of most popular posts for guest",
     *      description="List of most popular posts for guest",
     *
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
     *      security={ {"passport": {}} },
     *  )
     */

    public function guestMostPopular($loginUser,$start,$limit){
        $users = DB::table("users")
        ->leftJoin('post_user', function($join) {
            $join->on('post_user.user_id', '=', 'users.id');
             })
        ->leftJoin('posts', function($join) {
            $join->on('post_user.post_id', '=', 'posts.id');
         })
         ->select("users.*",
            DB::raw("(SELECT ifnull(COUNT(likes.user_id),0) FROM likes
                        WHERE likes.user_id = users.id
                        GROUP BY likes.user_id) as likeCount"),
            DB::raw("(SELECT ifnull(COUNT(comments.user_id),0) FROM comments
                        WHERE comments.user_id = users.id
                        GROUP BY comments.user_id) as commentCount"),
            DB::raw("(SELECT ifnull(COUNT(comments.user_id),0) FROM comments
                        WHERE comments.user_id = users.id
                        GROUP BY comments.user_id) + (SELECT ifnull(COUNT(likes.user_id),0) FROM likes
                        WHERE likes.user_id = users.id
                        GROUP BY likes.user_id) as totalCount"))
                        ->where('users.id','!=', $loginUser)
                        ->groupBy('users.id')
                        ->orderBy('totalCount', 'DESC')
                        ->orderBy('likeCount', 'DESC')->orderBy('commentCount', 'DESC')
                        ->orderBy('users.id', 'DESC')
                        ->offset($start)->limit($limit)->get();


         $users = json_decode($users, true);
         $i = 0;
         foreach($users as $user){
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
            $i++;
          }
         if(count($users)){
             return response()->json([
                 'message' => 'Most Popular post list!',
                 'data' => $userData,
                 'isError' => false
             ], 201);
         }else{
             return response()->json(['error'=>'No Post available','isError' => true]);
         }
    }

    /**
     * @OA\Post(
     *          path="/api/v1/mostPopularProfile/{loginUser}/{start}/{limit}",
     *          operationId="List of most popular Profile",
     *          tags={"Posts"},
     *       @OA\Parameter(
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
     *      summary="List of most popular Profile",
     *      description="List of most popular Profile",
     *
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
     *      security={ {"passport": {}} },
     *  )
     */

    public function mostPopularProfile($loginUser,$start,$limit){

        $users = DB::table("users")
        ->leftJoin('post_user', function($join) {
            $join->on('post_user.user_id', '=', 'users.id');
             })
        ->leftJoin('posts', function($join) {
            $join->on('post_user.post_id', '=', 'posts.id');
         })
         ->select("users.*",
            DB::raw("(SELECT ifnull(COUNT(likes.user_id),0) FROM likes
                        WHERE likes.user_id = users.id
                        GROUP BY likes.user_id) as likeCount"),
            DB::raw("(SELECT ifnull(COUNT(comments.user_id),0) FROM comments
                        WHERE comments.user_id = users.id
                        GROUP BY comments.user_id) as commentCount"),
            DB::raw("(SELECT ifnull(COUNT(comments.user_id),0) FROM comments
                        WHERE comments.user_id = users.id
                        GROUP BY comments.user_id) + (SELECT ifnull(COUNT(likes.user_id),0) FROM likes
                        WHERE likes.user_id = users.id
                        GROUP BY likes.user_id) as totalCount"))
                        ->where('users.id','!=', $loginUser)
                        ->groupBy('users.id')
                        ->orderBy('totalCount', 'DESC')
                        ->orderBy('likeCount', 'DESC')->orderBy('commentCount', 'DESC')
                        ->orderBy('users.id', 'DESC')
                        ->offset($start)->limit($limit)->get();


         $users = json_decode($users, true);
         $i = 0;
         foreach($users as $user){
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
            $i++;
          }
         if(count($users)){
             return response()->json([
                 'message' => 'Most Popular post list!',
                 'data' => $userData,
                 'isError' => false
             ], 201);
         }else{
             return response()->json(['error'=>'No Post available','isError' => true]);
         }
    }


    /**
     * @OA\Post(
     *          path="/api/v1/searchActivity/{search}/{loginUser}/{start}/{limit}",
     *          operationId="Post list",
     *          tags={"Posts"},
     *      @OA\Parameter(
     *          name="search",
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
     *              type="string"
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
     *      summary="Get list of posts",
     *      description="Returns list of post",
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
     *      security={ {"passport": {}} },
     *  )
     */
    
    public function searchActivity($search,$loginUser,$start,$limit){
        $followerList = array();
        $Followers = DB::table('follow')->where('user_id',$loginUser)->get();
        foreach($Followers as $follow){
            $followerList[] = $follow->follower_id;
        }
        if(!empty($limit)){
            $posts =  DB::table('posts')->select('posts.*','users.first_name','users.last_name','users.display_name','users.username','users.profile','users.cover')
            ->leftJoin('post_user', 'posts.id', '=', 'post_user.post_id')
            ->leftJoin('users', 'users.id', '=', 'post_user.user_id')
            ->whereIn('posts.id',function ($query)  use ($followerList,$search) {
                $query->select('posts.id')->from('posts')
                ->leftJoin('post_user', 'posts.id', '=', 'post_user.post_id')
                ->leftJoin('users', 'users.id', '=', 'post_user.user_id')
                ->whereIn('post_user.user_id',$followerList)
                ->Where('posts.publish','=','now')
                ->where(function ($query_sub) use ($search){
                    $query_sub->Where('posts.title', 'LIKE', '%'.$search.'%')
                          ->orWhere('posts.caption', 'LIKE', '%'.$search.'%')
                          ->orWhere('posts.tags', 'LIKE', '%'.$search.'%');
                });
            })
            ->orWhereIn('posts.id',function ($query1) use ($loginUser,$search) {
                $query1->select('posts.id')->from('posts')
                ->leftJoin('post_user', 'posts.id', '=', 'post_user.post_id')
                ->leftJoin('users', 'users.id', '=', 'post_user.user_id')
                ->Where('post_user.user_id',$loginUser)
                ->where(function ($query_sub) use ($search){
                    $query_sub->Where('posts.title', 'LIKE', '%'.$search.'%')
                          ->orWhere('posts.caption', 'LIKE', '%'.$search.'%')
                          ->orWhere('posts.tags', 'LIKE', '%'.$search.'%');
                });
            })
            ->whereNull('posts.deleted_at')
            ->offset($start)->limit($limit)
            ->get();
        }else{
            $posts =  DB::table('posts')->select('posts.*','users.first_name','users.last_name','users.display_name','users.username','users.profile','users.cover')
            ->leftJoin('post_user', 'posts.id', '=', 'post_user.post_id')
            ->leftJoin('users', 'users.id', '=', 'post_user.user_id')
            ->whereIn('posts.id',function ($query)  use ($followerList,$search) {
                $query->select('posts.id')->from('posts')
                ->leftJoin('post_user', 'posts.id', '=', 'post_user.post_id')
                ->leftJoin('users', 'users.id', '=', 'post_user.user_id')
                ->whereIn('post_user.user_id',$followerList)
                ->Where('posts.publish','=','now')
                ->where(function ($query_sub) use ($search){
                    $query_sub->Where('posts.title', 'LIKE', '%'.$search.'%')
                          ->orWhere('posts.caption', 'LIKE', '%'.$search.'%')
                          ->orWhere('posts.tags', 'LIKE', '%'.$search.'%');
                });
            })
            ->orWhereIn('posts.id',function ($query1) use ($loginUser,$search) {
                $query1->select('posts.id')->from('posts')
                ->leftJoin('post_user', 'posts.id', '=', 'post_user.post_id')
                ->leftJoin('users', 'users.id', '=', 'post_user.user_id')
                ->Where('post_user.user_id',$loginUser)
                ->where(function ($query_sub) use ($search){
                    $query_sub->Where('posts.title', 'LIKE', '%'.$search.'%')
                          ->orWhere('posts.caption', 'LIKE', '%'.$search.'%')
                          ->orWhere('posts.tags', 'LIKE', '%'.$search.'%');
                });
            })
            ->whereNull('posts.deleted_at')
            ->get();
        }

         $allPost = DB::table('posts')->select('posts.*','users.first_name','users.last_name','users.display_name','users.username','users.profile','users.cover')
        ->leftJoin('post_user', 'posts.id', '=', 'post_user.post_id')
        ->leftJoin('users', 'users.id', '=', 'post_user.user_id')
       
        ->whereIn('posts.id',function ($query)  use ($followerList,$search) {
            $query->select('posts.id')->from('posts')
            ->leftJoin('post_user', 'posts.id', '=', 'post_user.post_id')
            ->leftJoin('users', 'users.id', '=', 'post_user.user_id')
            ->whereIn('post_user.user_id',$followerList)
            ->Where('posts.publish','=','now')
            ->where(function ($query_sub) use ($search){
                $query_sub->Where('posts.title', 'LIKE', '%'.$search.'%')
                      ->orWhere('posts.caption', 'LIKE', '%'.$search.'%')
                      ->orWhere('posts.tags', 'LIKE', '%'.$search.'%');
            });
        })
        ->orWhereIn('posts.id',function ($query1) use ($loginUser,$search) {
            $query1->select('posts.id')->from('posts')
            ->leftJoin('post_user', 'posts.id', '=', 'post_user.post_id')
            ->leftJoin('users', 'users.id', '=', 'post_user.user_id')
            ->Where('post_user.user_id',$loginUser)
            ->where(function ($query_sub) use ($search){
                $query_sub->Where('posts.title', 'LIKE', '%'.$search.'%')
                      ->orWhere('posts.caption', 'LIKE', '%'.$search.'%')
                      ->orWhere('posts.tags', 'LIKE', '%'.$search.'%');
            });
        })
        ->whereNull('posts.deleted_at')
        ->get();
        //->toSql();

        //dd($allPost); exit;
        $postData = array();
        $ID = 0;
        $lastCommenId = 0;
        foreach($posts as $postDetail){
            $likedbyme = 0;
            $commentByMe = 0;
            
            $totalCount = 0;
            $likeDetails = Like::where('post_id',$postDetail->id)
                            ->join('users', 'users.id', '=', 'likes.user_id')
                            ->get();
            $likeUsers = array();
            $j = 0;
            if(count($likeDetails) > 0){
                foreach($likeDetails as $likeDetail){
                    $likeUserIds[] = $likeDetail['user_id'];
                    $likeUsers[$j]['id'] = $likeDetail['user_id'];
                    $likeUsers[$j]['profile'] = (!empty($likeDetail['profile']) ? url('storage/'.$likeDetail['profile']) : '');
                    $likeUsers[$j]['firstName'] = $likeDetail['first_name'];
                    $likeUsers[$j]['lastName'] = $likeDetail['last_name'];
                    $likeUsers[$j]['displayName'] = $likeDetail['display_name'];
                    $likeUsers[$j]['userName'] = $likeDetail['username'];
                    $j++;
                }
                if(in_array($loginUser, $likeUserIds)){
                    $likedbyme = 1;
                }
            }

            $commentDetails = Comment::select('comments.*','users.first_name','users.last_name','users.display_name','users.username','users.profile','users.cover','users.id as uid')->where('post_id',$postDetail->id)
                                ->leftJoin('users', 'users.id', '=', 'comments.user_id')
                                ->get();

            
            $commentUsers = array();
            $k = 0;
            $totalCount = count($commentDetails);
                
            if(count($commentDetails) > 0){
                $getlastCommenId = Comment::limit(1)->orderBy('id','DESC')->get();
                $lastCommenId = $getlastCommenId[0]['id'];
                foreach($commentDetails as $commentDetail){
                    $commentLikeByMe = 0;
                    $commentReplyByMe = 0;
                    
                    $commentIds[] = $commentDetail['uid'];
                    
                    $commentComentDetails = comment_comment::where('comment_id',$commentDetail['id'])->leftJoin('users', 'users.id', '=', 'comment_comments.user_id')->get();
                    $commentcommentUsers = array();
                    $commentCount = 0;
                    if(count($commentComentDetails) > 0){
                        $i = 0;
                        $commentCount = $commentCount + count($commentComentDetails);
                        foreach($commentComentDetails as $commentComentDetail){
                            $commentCommentUserIds[] = $commentComentDetail['user_id'];
                            $commentcommentDate = \Carbon\Carbon::parse($commentComentDetail['created_at'])->isoFormat('D MMMM YYYY');
                            $commentcommentUsers[$i]['userid'] = $commentComentDetail['user_id'];
                            $commentcommentUsers[$i]['comment'] = $commentComentDetail['comment'];
                            $commentcommentUsers[$i]['commentDate'] = $commentcommentDate;
                            $commentcommentUsers[$i]['profile'] = (!empty($commentComentDetail['profile']) ? url('storage/'.$commentComentDetail['profile']) : '');
                            $commentcommentUsers[$i]['firstName'] = $commentComentDetail['first_name'];
                            $commentcommentUsers[$i]['lastName'] = $commentComentDetail['last_name'];
                            $commentcommentUsers[$i]['displayName'] = $commentComentDetail['display_name'];
                            $commentcommentUsers[$i]['userName'] = $commentComentDetail['username'];
                            $i++;
                        }
                        if(in_array($loginUser, $commentCommentUserIds)){
                            $commentReplyByMe = 1;
                        }
                    }

                    $totalCount = $totalCount+$commentCount;
                        
                    $commentLikeDetails = comment_like::where('comment_id',$commentDetail['id'])->leftJoin('users', 'users.id', '=', 'comment_likes.user_id')->get();
                    $commentLikeUsers = array();
                    if(count($commentLikeDetails) > 0){
                        $i1 = 0;
                        
                        foreach($commentLikeDetails as $commentLikeDetail){
                            $commentUserIds[] = $commentLikeDetail['user_id'];
                            $commentLikeUsers[$i1]['userid'] = $commentLikeDetail['user_id'];
                            $commentLikeUsers[$i1]['profile'] = (!empty($commentLikeDetail['profile']) ? url('storage/'.$commentLikeDetail['profile']) : '');
                            $commentLikeUsers[$i1]['firstName'] = $commentLikeDetail['first_name'];
                            $commentLikeUsers[$i1]['lastName'] = $commentLikeDetail['last_name'];
                            $commentLikeUsers[$i1]['displayName'] = $commentLikeDetail['display_name'];
                            $commentLikeUsers[$i1]['userName'] = $commentLikeDetail['username'];
                            $i1++;
                        }
                        if(in_array($loginUser, $commentUserIds)){
                            $commentLikeByMe = 1;
                        }
                    }
                    

                    $commentDate = \Carbon\Carbon::parse($commentDetail['created_at'])->isoFormat('D MMMM YYYY'); 

                    $commentUsers[$k]['id'] = $commentDetail['id'];
                    $commentUsers[$k]['userid'] = $commentDetail['user_id'];
                    $commentUsers[$k]['comment'] = $commentDetail['comment'];
                    $commentUsers[$k]['commentDate'] = $commentDate;
                    $commentUsers[$k]['profile'] = (!empty($commentDetail['profile']) ? url('storage/'.$commentDetail['profile']) : '');
                    $commentUsers[$k]['firstName'] = $commentDetail['first_name'];
                    $commentUsers[$k]['lastName'] = $commentDetail['last_name'];
                    $commentUsers[$k]['displayName'] = $commentDetail['display_name'];
                    $commentUsers[$k]['userName'] = $commentDetail['username'];
                    $commentUsers[$k]['comments'] = count($commentComentDetails);
                    $commentUsers[$k]['commentUsers'] = $commentcommentUsers;
                    $commentUsers[$k]['likeByMe'] = $commentLikeByMe;
                    $commentUsers[$k]['commentByMe'] = $commentReplyByMe;
                    $commentUsers[$k]['likes'] = count($commentLikeDetails);
                    $commentUsers[$k]['likeUsers'] = $commentLikeUsers;
                    
                    $j++;
                    $k++;
                }
                if(in_array($loginUser, $commentIds)){
                    $commentByMe = 1;
                }
            }


            $today = Carbon::now();
            $created_at = \Carbon\Carbon::parse($postDetail->created_at);
            $updated_at = \Carbon\Carbon::parse($postDetail->updated_at);
            $hours_created = $created_at->diffInHours($today);
            $hours_updated = $updated_at->diffInHours($today);

            if(empty($hours_created)){
                $hours_created = $created_at->diffInMinutes($today) . ' min';
            }

            if(empty($hours_updated)){
                $hours_updated = $created_at->diffInMinutes($today) . ' min';
            }

            if(!empty($hours_created) && $hours_created > 240){
                $hours_created = \Carbon\Carbon::parse($postDetail->created_at)->isoFormat('D MMMM YYYY');
            }

            if(!empty($hours_updated && $hours_updated > 240)){
                $hours_updated = \Carbon\Carbon::parse($postDetail->updated_at)->isoFormat('D MMMM YYYY');
            }

            $postData[$ID]['id'] = $postDetail->id;
            $postData[$ID]['firstName'] = $postDetail->first_name;
            $postData[$ID]['lastName'] = $postDetail->last_name;
            $postData[$ID]['displayName'] = $postDetail->display_name;
            $postData[$ID]['profile'] = (!empty($postDetail->profile) ? url('storage/'.$postDetail->profile) : '');
            $postData[$ID]['banner'] = (!empty($postDetail->cover) ? url('storage/'.$postDetail->cover) : '');
            $postData[$ID]['username'] = $postDetail->username;
            $postData[$ID]['title'] = $postDetail->title;
            $postData[$ID]['caption'] = $postDetail->caption;
            $postData[$ID]['media'] = (!empty($postDetail->media) ? url('storage/'.$postDetail->media) : '');
            $postData[$ID]['tags'] = $postDetail->tags;
            $postData[$ID]['publish'] = $postDetail->publish;
            $postData[$ID]['schedule_at'] = (!empty($postDetail->schedule_at))?date('m/d/Y H:i', $postDetail->schedule_at) : 0 ;
            $postData[$ID]['add_to_album'] = $postDetail->add_to_album;
            $postData[$ID]['created'] = $hours_created;
            $postData[$ID]['updated'] = $hours_updated;
            $postData[$ID]['likedByMe'] = $likedbyme;
            $postData[$ID]['likes'] = count($likeDetails);
            $postData[$ID]['likeUsers'] = $likeUsers;
            $postData[$ID]['commentByMe'] = $commentByMe;
            $postData[$ID]['comments'] = count($commentDetails);
            $postData[$ID]['totalComments'] = $totalCount;
            $postData[$ID]['commentUsers'] = $commentUsers;
            $ID++;
        }

        return response()->json([
            'count' => count($allPost),
            'data' => $postData,
            'lastCommentId' => $lastCommenId,
            'isError' => false
        ]);
    }

    /**
     * @OA\Post(
     *          path="/api/v1/getRecentPost/{loginUser}/{start}/{limit}",
     *          operationId="Get All Posts",
     *          tags={"Posts"},
     *      @OA\Parameter(
     *          name="start",
     *          in="path",
     *          required=true,
     *          @OA\Schema(
     *              type="integer"
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
     *          name="limit",
     *          in="path",
     *          required=true,
     *          @OA\Schema(
     *              type="integer"
     *          )
     *      ),
     *      summary="Get All Posts",
     *      description="data of post",
     *
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
     *      security={ {"passport": {}} },
     *  )
     */

    public function getRecentPost($loginUser,$start,$limit){

        $allPost = Post::all();
        $postDetails = Post::orderBy('created_at','DESC')->offset($start)->limit($limit)->get();
        $ID = 0;
        if(count($postDetails)){
            foreach($postDetails as $postDetail){

                $likedbyme = 0;
                $Users = $postDetail->users()->get();
                foreach($Users as $user){
                    $UserDetails = $user;
                }
                //echo '<pre>'; print_r($UserDetails); exit;
    
    
                $likeDetails = Like::where('post_id',$postDetail['id'])
                                ->join('users', 'users.id', '=', 'likes.user_id')
                                ->get();
                $likeUsers = array();
                $j = 0;
                if(count($likeDetails) > 0){
                    foreach($likeDetails as $likeDetail){
                        if($loginUser == $likeDetail['user_id']){
                            $likedbyme = 1;
                        }
                        $likeUsers[$j]['id'] = $likeDetail['user_id'];
                        $likeUsers[$j]['profile'] = (!empty($likeDetail['profile']) ? url('storage/'.$likeDetail['profile']) : '');
                        $likeUsers[$j]['firstName'] = $likeDetail['first_name'];
                        $likeUsers[$j]['lastName'] = $likeDetail['last_name'];
                        $likeUsers[$j]['displayName'] = $likeDetail['display_name'];
                        $likeUsers[$j]['userName'] = $likeDetail['username'];
                        $j++;
                    }
                }
    
                $commentDetails = Comment::where('post_id',$postDetail['id'])
                                    ->join('users', 'users.id', '=', 'comments.user_id')
                                    ->get();
                $commentUsers = array();
                $k = 0;
                if(count($commentDetails) > 0){
                    foreach($commentDetails as $commentDetail){
                        $commentUsers[$k]['userid'] = $commentDetail['user_id'];
                        $commentUsers[$k]['comment'] = $commentDetail['comment'];
                        $commentUsers[$k]['profile'] = $commentDetail['profile'];
                        $commentUsers[$k]['firstName'] = $commentDetail['first_name'];
                        $commentUsers[$k]['lastName'] = $commentDetail['last_name'];
                        $commentUsers[$k]['displayName'] = $commentDetail['display_name'];
                        $commentUsers[$k]['userName'] = $commentDetail['username'];
                        $j++;
                        $k++;
                    }
                }
    
                $today = Carbon::now();
                $created_at = \Carbon\Carbon::parse($postDetail['created_at']);
                $updated_at = \Carbon\Carbon::parse($postDetail['updated_at']);
                $hours_created = $created_at->diffInHours($today);
                $hours_updated = $updated_at->diffInHours($today);
    
                if(empty($hours_created)){
                    $hours_created = $created_at->diffInMinutes($today) . ' min';
                }
    
                if(empty($hours_updated)){
                    $hours_updated = $created_at->diffInMinutes($today) . ' min';
                }
    
                if(!empty($hours_created) && $hours_created > 240){
                    $hours_created = \Carbon\Carbon::parse($postDetail['created_at'])->isoFormat('D MMMM YYYY');
                }
    
                if(!empty($hours_updated && $hours_updated > 240)){
                    $hours_updated = \Carbon\Carbon::parse($postDetail['updated_at'])->isoFormat('D MMMM YYYY');
                }
    
                $postData[$ID]['id'] = $postDetail['id'];
                $postData[$ID]['firstName'] = $UserDetails['first_name'];
                $postData[$ID]['lastName'] = $UserDetails['last_name'];
                $postData[$ID]['displayName'] = $UserDetails['display_name'];
                $postData[$ID]['profile'] = $UserDetails['profile'];
                $postData[$ID]['banner'] = $UserDetails['cover'];
                $postData[$ID]['username'] = $UserDetails['username'];
                $postData[$ID]['title'] = $postDetail['title'];
                $postData[$ID]['caption'] = $postDetail['caption'];
                $postData[$ID]['media'] = (!empty($postDetail['media']) ? url('storage/'.$postDetail['media']) : '');
                $postData[$ID]['tags'] = $postDetail['tags'];
                $postData[$ID]['publish'] = $postDetail['publish'];
                $postData[$ID]['schedule_at'] = (!empty($postDetail['schedule_at']))?date('m/d/Y H:i', $postDetail['schedule_at']) : 0 ;
                $postData[$ID]['add_to_album'] = $postDetail['add_to_album'];
                $postData[$ID]['created'] = $hours_created;
                $postData[$ID]['updated'] = $hours_updated;
                $postData[$ID]['likedByMe'] = $likedbyme;
                $postData[$ID]['likes'] = count($likeDetails);
                $postData[$ID]['likeUsers'] = $likeUsers;
                $postData[$ID]['comments'] = count($commentDetails);
                $postData[$ID]['commentUsers'] = $commentUsers;
                $ID++;
            }
            return response()->json([
                'message' => 'All post list!',
                'count' => count($allPost),
                'data' => $postData,
                'isError' => false
            ], 201);
        }else{
            return response()->json(['error'=>'No Post available','isError' => true]);
        }

    }

     function getPostResponse($postId){
        $postDetail = Post::find($postId);

        $likeDetails = Like::where('post_id',$postId)->get();
        $likeUsers = array();
        if(count($likeDetails) > 0){
            $i = 0;
            foreach($likeDetails as $likeDetail){
                $likeUsers[$i]['userid'] = $likeDetail['user_id'];
                $i++;
            }
        }

        $commentDetails = Comment::where('post_id',$postId)->get();
        $commentUsers = array();
        if(count($commentDetails) > 0){
            $i = 0;
            foreach($commentDetails as $commentDetail){
                $commentUsers[$i]['userid'] = $commentDetail['user_id'];
                $commentUsers[$i]['comment'] = $commentDetail['comment'];
                $i++;
            }
        }

        $viewDetails = Views::where('post_id',$postId)->get();
        $viewUsers = array();
        if(count($viewDetails) > 0){
            $i = 0;
            foreach($viewDetails as $viewDetail){
                $viewUsers[$i]['userid'] = $viewDetail['user_id'];
                $viewUsers[$i]['comment'] = $viewDetail['comment'];
                $i++;
            }
        }

        $postData['id'] = $postDetail['id'];
        $postData['title'] = $postDetail['title'];
        $postData['caption'] = $postDetail['caption'];
        $postData['tags'] = $postDetail['tags'];
        $postData['publish'] = $postDetail['publish'];
        $postData['media'] = (!empty($postDetail['media']) ? url('storage/'.$postDetail['media']) : '');
        $postData['schedule_at'] = (!empty($postDetail['schedule_at']))?date('m/d/Y H:i', $postDetail['schedule_at']) : 0 ;
        $postData['add_to_album'] = $postDetail['add_to_album'];
        $postData['created'] = \Carbon\Carbon::parse($postDetail['created_at'])->format('Y-m-d H:i:s');
        $postData['updated'] = \Carbon\Carbon::parse($postDetail['updated_at'])->format('Y-m-d H:i:s');
        $postData['likes'] = count($likeDetails);
        $postData['likeUsers'] = $likeUsers;
        $postData['comments'] = count($commentDetails);
        $postData['commentUsers'] = $commentUsers;
        $postData['views'] = count($viewDetails);
        $postData['viewUsers'] = $viewUsers;

        return $postData;
    }

    function createImage($image,$path){
        if (preg_match('/^data:image\/\w+;base64,/', $image) ||  preg_match('/^data:video\/\w+;base64,/', $image)) {
            $ext = explode(';base64',$image);
            $ext = explode('/',$ext[0]);
            $ext = $ext[1];
            if (preg_match('/^data:image\/\w+;base64,/', $image)){
                $image = preg_replace('/^data:image\/\w+;base64,/', '', $image);
            }

            if (preg_match('/^data:video\/\w+;base64,/', $image)){
                $image = preg_replace('/^data:video\/\w+;base64,/', '', $image);
            }
            $image = str_replace(' ', '+', $image);
            $imageName = Str::random(10).'.'.$ext;
            $full_path = $path . $imageName;
            Storage::put($full_path, base64_decode($image));
            $returnpath = str_replace("public/","",$path).$imageName;

        }else {
            $removeData = config('app.url').'storage/';
            $returnpath = str_replace($removeData,"",$image);
        }
        return $returnpath;

    }

    /**
     * @OA\Post(
     *          path="/api/v1/followContent/{userId}/{followerId}/{subscriptionPlan}",
     *          operationId="store check",
     *          tags={"Posts"},
     *      @OA\Parameter(
     *          name="userId",
     *          in="path",
     *          required=true,
     *          @OA\Schema(
     *              type="integer"
     *          )
     *      ),
     *      @OA\Parameter(
     *          name="followerId",
     *          in="path",
     *          required=true,
     *          @OA\Schema(
     *              type="integer"
     *          )
     *      ),
     *      @OA\Parameter(
     *          name="subscriptionPlan",
     *          in="path",
     *          required=true,
     *          @OA\Schema(
     *              type="integer"
     *          )
     *      ),
     *      summary="store check",
     *      description="store check",
     *      @OA\RequestBody(
     *       @OA\MediaType(
     *           mediaType="multipart/form-data",
     *           @OA\Schema(
     *               required={"amount","deviceFingerprinting_Id"},
     *              @OA\Property(
     *                  property="amount",
     *                  type="string"
     *               ),
     *               @OA\Property(
     *                  property="deviceFingerprinting_Id",
     *                  type="string"
     *               ),
     *           )
     *       ),
     *   ),
     *
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



    public function store(Request $request,$userId,$followerId,$subscriptionPlan)
    {
        $paysafeApiKeyId = config('app.paysafeApiKeyId');
        $paysafeApiKeySecret = config('app.paysafeApiKeySecret');
        $paysafeAccountNumber = config('app.paysafeAccountNumber');
        $client = new PaysafeApiClient($paysafeApiKeyId, $paysafeApiKeySecret, Environment::TEST, $paysafeAccountNumber);
        
        $followerList = array();
        $wishList = array();
        $newWishList = array();
        $user = User::findOrFail($userId);
        $follower = User::findOrFail($followerId);

        $checkFollows = DB::table('follow')->where('user_id',$userId)->where('follower_id',$followerId)->get();

        if(count($checkFollows)>0){
            return response()->json(['error'=>'Already Following','isError' => true]);
        }

        $plan = DB::table('subscription_plans')->where('id',$subscriptionPlan)->get();
        $discount = $plan[0]->discount;
        $amount = $request->amount;
        if($plan[0]->id == 2){
            $amount = $request->amount * 3;
        }
        if($plan[0]->id == 3){
            $amount = $request->amount * 6;
        }
        if(!empty($discount)){
            $new_amount = $amount - ($amount * ($discount / 100));
        }else{
            $new_amount = $amount;
        } 
        

        $test = array(
            'merchantRefNum' => uniqid(date('')),
            'amount' => $new_amount,
            'currency' => 'GBP',
            'deviceFingerprintingId' => $request->deviceFingerprinting_Id,
            'merchantUrl' => 'https://mysite.com',
            'authenticationPurpose' => 'PAYMENT_TRANSACTION',
            'deviceChannel' => 'BROWSER',
            'messageCategory' => 'PAYMENT',
            'card' => array(
                'holderName' => $user['account_name'],
                'cardNum' => $user['card_number'],
                'cardExpiry' => array(
                    'month' => $user['card_exp_month'],
                    'year' => $user['card_exp_year']
                )
            ));
        
        try{
            $auth = $client->threeDSecureV2Service()->authentications(new Authentications($test));
            if(isset($auth->id)){
                $percentage = 80;
                $get_amount = ($percentage / 100) * $amount;
                if($user['country'] == 'UK')
                {
                    $vat = 20;
                    $vatToPay = ($get_amount / 100) * $vat;
                    $new_amount = $get_amount + $vatToPay;
                }
                
                $final_amount = $follower['account_balance'] + $get_amount;
                $UpdateDetails = User::where('id', $followerId)->update([
                    'account_balance' => $final_amount,
                  ]);
    
                $data = DB::table('follow')
                  ->insert([
                   'user_id' => $userId,
                   'follower_id' => $followerId,
                   'subscription_plan' => $subscriptionPlan,
                ]);
    
                $Followers = DB::table('follow')->where('user_id',$userId)->get();
                foreach($Followers as $follow){
                    $followerList[] = $follow->follower_id;
                }
    
                $Wish_users = DB::table('wish_list')->where('user_id',$userId)->get();
                foreach($Wish_users as $Wish_user){
                    $wishList[] = $Wish_user->contentwriter_id;
                }
    
                if (in_array($followerId, $wishList)){
                    DB::table('wish_list')->where('user_id',$userId)->where('contentwriter_id',$followerId)->delete();
                }
    
                $new_Wish_users = DB::table('wish_list')->where('user_id',$userId)->get();
                foreach($new_Wish_users as $new_Wish_user){
                    $newWishList[] = $new_Wish_user->contentwriter_id;
                }
    
                return response()->json([
                    'message' => 'Successfully Followed',
                    'data' => $data,
                    'followerList' => $followerList,
                    'wishList' => $newWishList,
                    'isError' => false
                ], 201);
            }else{
                return response()->json(['error'=>'Error in payment','isError' => true]);
            }
        } catch (\Exception $e){
            return response()->json(['error'=>'Something went wrong .Some fields are missing or not properly entered','isError' => true]);
        }
    }


}
