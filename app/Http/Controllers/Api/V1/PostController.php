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
     *          path="/api/v1/getUserPost/{userid}/{start}/{limit}",
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

    public function getUserPost($id,$start,$limit){
        
        //echo $id; exit;
        $user = User::findOrFail($id);
        $allPost = $user->posts()->get();
        $postDetails = $user->posts()->offset($start)->limit($limit)->get();
        
        $i=0;
        foreach($postDetails as $postDetail){

            $likeDetails = Like::where('post_id',$postDetail['id'])
            ->join('users', 'users.id', '=', 'likes.user_id')
            ->get();
            $likeUsers = array();
            if(count($likeDetails) > 0){
                $j = 0;
                foreach($likeDetails as $likeDetail){
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

            $postData[$i]['id'] = $postDetail['id'];
            $postData[$i]['comment'] = $postDetail['comment'];
            $postData[$i]['media'] = (!empty($postDetail['media']) ? url('storage/'.$postDetail['media']) : '');
            $postData[$i]['tags'] = $postDetail['tags'];
            $postData[$i]['publish'] = $postDetail['publish'];
            $postData[$i]['schedule_at'] = (!empty($postDetail['schedule_at']))?date('m/d/Y H:i', $postDetail['schedule_at']) : 0 ;
            $postData[$i]['add_to_album'] = $postDetail['add_to_album'];
            $postData[$i]['likes'] = count($likeDetails);
            $postData[$i]['likeUsers'] = $likeUsers;
            $postData[$i]['comments'] = count($commentDetails);
            $postData[$i]['commentUsers'] = $commentUsers;
            $i++;
        }
        if(count($postDetails)){
            return response()->json([
                'message' => 'User post list!',
                'count' => count($allPost),
                'data' => $postData,
                'isError' => false
            ], 201);
        }else{
            return response()->json(['error'=>'No Post available','isError' => true]);
        }
        
        
        return response()->json(Post::find($id));
    	
    }

    /**
     * @OA\Post(
     *          path="/api/v1/getAllPost/{start}/{limit}",
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

    public function getAllPost($start,$limit){
        
        $allPost = Post::all();
        $postDetails = Post::orderBy('id','DESC')->offset($start)->limit($limit)->get();
        
        $i=0;
        foreach($postDetails as $postDetail){

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
            $postData[$i]['comment'] = $postDetail['comment'];
            $postData[$i]['media'] = (!empty($postDetail['media']) ? url('storage/'.$postDetail['media']) : '');
            $postData[$i]['tags'] = $postDetail['tags'];
            $postData[$i]['publish'] = $postDetail['publish'];
            $postData[$i]['schedule_at'] = (!empty($postDetail['schedule_at']))?date('m/d/Y H:i', $postDetail['schedule_at']) : 0 ;
            $postData[$i]['add_to_album'] = $postDetail['add_to_album'];
            $postData[$i]['likes'] = count($likeDetails);
            $postData[$i]['likeUsers'] = $likeUsers;
            $postData[$i]['comments'] = count($commentDetails);
            $postData[$i]['commentUsers'] = $commentUsers;
            $i++;
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
        
        //echo $request->ScheduleDateTime; exit;
        $post_view =  new Like([
            'post_id' => $postid,
            'user_id' => $userid,
            'ip_address' => $_SERVER['REMOTE_ADDR'],
        ]);

        $users = Like::where('post_id',$postid)->where('user_id',$userid)->get();
        $postData = $this->getPostResponse($postid);
        if(count($users) == 0){
            if($post_view->save()){
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

        $postData['id'] = $postDetail['id'];
        $postData['title'] = $postDetail['title'];
        $postData['caption'] = $postDetail['caption'];
        $postData['tags'] = $postDetail['tags'];
        $postData['publish'] = $postDetail['publish'];
        $postData['media'] = (!empty($postDetail['media']) ? url('storage/'.$postDetail['media']) : '');
        $postData['schedule_at'] = (!empty($postDetail['schedule_at']))?date('m/d/Y H:i', $postDetail['schedule_at']) : 0 ;
        $postData['add_to_album'] = $postDetail['add_to_album'];
        $postData['likes'] = count($likeDetails);
        $postData['likeUsers'] = $likeUsers;
        $postData['comments'] = count($commentDetails);
        $postData['commentUsers'] = $commentUsers;
        
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
