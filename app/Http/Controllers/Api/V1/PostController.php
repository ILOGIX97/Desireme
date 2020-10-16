<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Post;
use App\User;
use App\Like;
use App\Comment;
use App\Views;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;
use Carbon\Carbon;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

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
     *               required={"Title","Caption","Publish"},
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
     *                  default="new",
     *                  enum={"new", "draft", "schedule"}
     *               ),
     *               @OA\Property(
     *                  property="ScheduleDateTime",
     *                  type="string",
     *                  description = "d/m/Y H:i",
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
            'ScheduleDateTime' => 'nullable|required_if:Publish,==,schedule|date_format:d/m/Y H:i'
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

        //echo $request->ScheduleDateTime; exit;
        $post =  new Post([
            'title' => $request->Title,
            'caption' => $request->Caption,
            'tags' => (!empty($request->Tags)) ? $request->Tags : '',
            'media' => $media,
    		'publish' => $request->Publish,
            'schedule_at' => (!empty($request->ScheduleDateTime && $request->Publish == 'schedule')) ? strtotime($request->ScheduleDateTime) : 0,
            'add_to_album' => (!empty($request->ChooseAlbum)) ? $request->ChooseAlbum : '0',
        ]);
        //echo '<pre>'; print_r($post); exit;
        //$user = User::find($id)->posts()->save($post);
        if($post->save()){
            $post->users()->sync($id);
            $postData = $this->getPostResponse($post->id);
            return response()->json([
                'message' => 'Post created user!',
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
     *               required={"Title","Caption","Publish"},
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
     *                  default="new",
     *                  enum={"new", "draft", "schedule"}
     *               ),
     *               @OA\Property(
     *                  property="ScheduleDateTime",
     *                  type="string",
     *                  description = "d/m/Y H:i",
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
            'ScheduleDateTime' => 'nullable|required_if:Publish,==,schedule|date_format:d/m/Y H:i'
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
        $allPost = $user->posts()->get();
        $postDetails = $user->posts()->offset($start)->limit($limit)->get();
        $userData['first_name'] = $user['first_name'];
            $userData['last_name'] = $user['last_name'];
            $userData['display_name'] = $user['display_name'];
            $userData['profile'] = $user['profile'];
            $userData['banner'] = $user['banner'];
            $userData['username'] = $user['username'];
            $userData['country'] = $user['country'];
            $userData['state'] = $user['state'];
        
        foreach($postDetails as $postDetail){
            $ID = $postDetail['id'];
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
                    $likeUsers[$j]['profile'] = $likeDetail['profile'];
                    $likeUsers[$j]['banner'] = $likeDetail['cover'];
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
            
            $postData[$ID]['id'] = $postDetail['id'];
            // $postData[$ID]['firstName'] = $UserDetails['first_name'];
            // $postData[$ID]['lastName'] = $UserDetails['last_name'];
            // $postData[$ID]['displayName'] = $UserDetails['display_name'];
            // $postData[$ID]['profile'] = $UserDetails['profile'];
            // $postData[$ID]['banner'] = $UserDetails['cover'];
            // $postData[$ID]['username'] = $UserDetails['username'];
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
        }
        if(count($postDetails)){
            return response()->json([
                'message' => 'User post list!',
                'count' => count($allPost),
                'data' => $postData,
                'userData' => $userData,
                'isError' => false
            ], 201);
        }else{
            return response()->json(['error'=>'No Post available','userData' => $userData,'isError' => true]);
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

        $allPost = Post::all();
        $postDetails = Post::orderBy('id','DESC')->offset($start)->limit($limit)->get();
        foreach($postDetails as $postDetail){
            $ID = $postDetail['id'];
            $likedbyme = 0;
            $Users = $postDetail->users()->get();
            foreach($Users as $user){
                $UserDetails = $user;
            }
            //echo '<pre>'; print_r($UserDetails); exit;

            $today = Carbon::now();
            $created_at = \Carbon\Carbon::parse($postDetail['created_at']);
            $updated_at = \Carbon\Carbon::parse($postDetail['updated_at']);
            $hours_created = $created_at->diffInHours($today);
            $hours_updated = $updated_at->diffInHours($today);
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
                    $likeUsers[$j]['profile'] = $likeDetail['profile'];
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
        }
        if(count($postDetails)){
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
     *          path="/api/v1/dislikePost/{postid}/{userid}",
     *          operationId="DisLike User Post",
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
     *      summary="DisLike User Post",
     *      description="DisLike User Post",
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

    public function dislikePost($postid,$userid){



        if(Like::where('post_id',$postid)->where('user_id',$userid)->delete())
        {
            return response()->json([
                'message' => 'Post disliked successfully!'
            ], 201);
        }else{
            return response()->json(['error'=>'Provide proper details','isError' => false]);
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
        $postData = $this->getPostResponse($postid);
        if($post_view->save()){
                return response()->json([
                    'message' => 'Post Viewed successfully!',
                    'data' => $postData,
                    'isError' => false
                ], 201);
            }else{
                return response()->json(['error'=>'Provide proper details','isError' => true]);
            }
    }

    /**
     * @OA\Post(
     *          path="/api/v1/mostViewed/{start}/{limit}",
     *          operationId="List of most viewed posts",
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

    public function mostViewed($start,$limit){
        $allPost = Views::select('posts.*', DB::raw('count(post_id) as count'))
        ->leftJoin('posts', 'posts.id', '=', 'views.post_id')
        ->groupBy('views.post_id')
        ->get();
        
        $posts = Views::select('posts.*',DB::raw('users.first_name,users.last_name,users.display_name,users.username,users.profile,users.cover'), DB::raw('count(views.post_id) as count'))
        ->leftJoin('posts', 'posts.id', '=', 'views.post_id')
        ->leftJoin('post_user', 'post_user.post_id', '=', 'posts.id')
        ->leftJoin('users', 'users.id', '=', 'post_user.user_id')
        ->groupBy('views.post_id')
        ->groupBy('post_user.user_id')
        ->orderBy('count','DESC')
        ->offset($start)->limit($limit)->get();

        //echo '<pre>'; print_r($posts); exit;
        $i = 0;
        foreach($posts as $postDetail){
            //$ID = $postDetail['id'];

            $likeDetails = Like::where('post_id',$postDetail['id'])
                            ->join('users', 'users.id', '=', 'likes.user_id')
                            ->get();
            $likeUsers = array();
            $j = 0;
            if(count($likeDetails) > 0){
                foreach($likeDetails as $likeDetail){
                    $likeUsers[$j]['id'] = $likeDetail['user_id'];
                    $likeUsers[$j]['profile'] = $likeDetail['profile'];
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


            $postData[$i]['id'] = $postDetail['id'];
            $postData[$i]['viewCount'] = $postDetail['count'];
            $postData[$i]['firstName'] = $postDetail['first_name'];
            $postData[$i]['lastName'] = $postDetail['last_name'];
            $postData[$i]['displayName'] = $postDetail['display_name'];
            $postData[$i]['profile'] = $postDetail['profile'];
            $postData[$i]['banner'] = $postDetail['cover'];
            $postData[$i]['username'] = $postDetail['username'];
            $postData[$i]['title'] = $postDetail['title'];
            $postData[$i]['caption'] = $postDetail['caption'];
            $postData[$i]['media'] = (!empty($postDetail['media']) ? url('storage/'.$postDetail['media']) : '');
            $postData[$i]['tags'] = $postDetail['tags'];
            $postData[$i]['publish'] = $postDetail['publish'];
            $postData[$i]['schedule_at'] = (!empty($postDetail['schedule_at']))?date('m/d/Y H:i', $postDetail['schedule_at']) : 0 ;
            $postData[$i]['add_to_album'] = $postDetail['add_to_album'];
            $postData[$i]['created'] = \Carbon\Carbon::parse($postDetail['created_at'])->format('Y-m-d H:i:s');
            $postData[$i]['updated'] = \Carbon\Carbon::parse($postDetail['updated_at'])->format('Y-m-d H:i:s');
            $postData[$i]['likes'] = count($likeDetails);
            $postData[$i]['likeUsers'] = $likeUsers;
            $postData[$i]['comments'] = count($commentDetails);
            $postData[$i]['commentUsers'] = $commentUsers;
            $i++;
        }
        if(count($posts)){
            return response()->json([
                'message' => 'Most Viewed post list!',
                'count' => count($allPost),
                'data' => $postData,
                'isError' => false
            ], 201);
        }else{
            return response()->json(['error'=>'No Post available','isError' => true]);
        }
    }


    /**
     * @OA\Post(
     *          path="/api/v1/mostPopular/{start}/{limit}",
     *          operationId="List of most popular posts",
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

    public function mostPopular($start,$limit){
       $allPost = Post::all();
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
                ->orderBy('totalCount', 'DESC')
                ->orderBy('likeCount', 'DESC')->orderBy('commentCount', 'DESC')->offset($start)->limit($limit)->get();

        
        $i=0;
        $posts = json_decode($posts, true);
        foreach($posts as $postDetail){

            

            $likeDetails = Like::where('post_id',$postDetail['id'])
                            ->join('users', 'users.id', '=', 'likes.user_id')
                            ->get();
            $likeUsers = array();
            $j = 0;
            if(count($likeDetails) > 0){
                foreach($likeDetails as $likeDetail){
                    $likeUsers[$j]['id'] = $likeDetail['user_id'];
                    $likeUsers[$j]['profile'] = $likeDetail['profile'];
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

            $postData[$i]['id'] = $postDetail['id'];
            $postData[$i]['firstName'] = $postDetail['first_name'];
            $postData[$i]['lastName'] = $postDetail['last_name'];
            $postData[$i]['displayName'] = $postDetail['display_name'];
            $postData[$i]['profile'] = $postDetail['profile'];
            $postData[$i]['banner'] = $postDetail['cover'];
            $postData[$i]['username'] = $postDetail['username'];
            $postData[$i]['title'] = $postDetail['title'];
            $postData[$i]['caption'] = $postDetail['caption'];
            $postData[$i]['media'] = (!empty($postDetail['media']) ? url('storage/'.$postDetail['media']) : '');
            $postData[$i]['tags'] = $postDetail['tags'];
            $postData[$i]['publish'] = $postDetail['publish'];
            $postData[$i]['schedule_at'] = (!empty($postDetail['schedule_at']))?date('m/d/Y H:i', $postDetail['schedule_at']) : 0 ;
            $postData[$i]['add_to_album'] = $postDetail['add_to_album'];
            $postData[$i]['created'] = \Carbon\Carbon::parse($postDetail['created_at'])->format('Y-m-d H:i:s');
            $postData[$i]['updated'] = \Carbon\Carbon::parse($postDetail['updated_at'])->format('Y-m-d H:i:s');
            $postData[$i]['likes'] = count($likeDetails);
            $postData[$i]['likeUsers'] = $likeUsers;
            $postData[$i]['comments'] = count($commentDetails);
            $postData[$i]['commentUsers'] = $commentUsers;
            
            $i++;
        }
        if(count($posts)){
            return response()->json([
                'message' => 'Most Popular post list!',
                'count' => count($allPost),
                'data' => $postData,
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
                        ->orderBy('likeCount', 'DESC')->orderBy('commentCount', 'DESC')->offset($start)->limit($limit)->get();
 
         
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
        if(!empty($limit)){
            $posts = Post::where('title','LIKE', '%' . $search . '%')
            ->orWhere('caption', 'LIKE','%' . $search . '%')
            ->orWhere('tags', 'LIKE','%' . $search . '%')
            ->offset($start)->limit($limit)
            ->get();
        }else{
            $posts = Post::where('title','LIKE', '%' . $search . '%')
            ->orWhere('caption', 'LIKE','%' . $search . '%')
            ->orWhere('tags', 'LIKE','%' . $search . '%')
            ->get();
        }
        $allPost = Post::where('title','LIKE', '%' . $search . '%')
        ->orWhere('caption', 'LIKE','%' . $search . '%')
        ->orWhere('tags', 'LIKE','%' . $search . '%')
        ->get();
        $postData = array();
        
        foreach($posts as $postDetail){
            $ID = $postDetail['id'];
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
                    $likeUsers[$j]['profile'] = $likeDetail['profile'];
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
        }
        
        return response()->json([
            'count' => count($allPost),
            'data' => $postData,
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
                    $likeUsers[$j]['profile'] = $likeDetail['profile'];
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
        if(count($postDetails)){
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

    

}
