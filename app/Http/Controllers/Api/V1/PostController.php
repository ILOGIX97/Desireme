<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Post;
use App\User;
use App\Like;
use App\Comment;
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
     *          path="/api/v1/getUserPost/{userid}",
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

    public function getUserPost($id){
        
        //echo $id; exit;
        $user = User::findOrFail($id);
        $postDetails = $user->posts()->get();
        
        $i=0;
        foreach($postDetails as $postDetail){

            $likeDetails = Like::where('post_id',$postDetail['id'])->get();
            $likeUsers = array();
            if(count($likeDetails) > 0){
                $i = 0;
                foreach($likeDetails as $likeDetail){
                    $likeUsers[$i]['id'] = $likeDetail['user_id'];
                    $i++;
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
            $i++;
        }
        if(count($postDetails)){
            return response()->json([
                'message' => 'User post list!',
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
     *          path="/api/v1/getAllPost",
     *          operationId="Get All Posts",
     *          tags={"Posts"},
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

    public function getAllPost(){
        
        $postDetails = Post::all();
        
        $i=0;
        foreach($postDetails as $postDetail){

            $likeDetails = Like::where('post_id',$postDetail['id'])->get();
            $likeUsers = array();
            if(count($likeDetails) > 0){
                $i = 0;
                foreach($likeDetails as $likeDetail){
                    $likeUsers[$i]['id'] = $likeDetail['user_id'];
                    $i++;
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
            $i++;
        }
        if(count($postDetails)){
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
        //echo '<pre>'; print_r($users); exit;
        if(count($users) == 0){
            if($post_like->save()){
                $postData = $this->getPostResponse($postid);
                return response()->json([
                    'message' => 'Post liked successfully!',
                    'data' => $postData,
                    'isError' => false
                ], 201);
            }else{
                return response()->json(['error'=>'Provide proper details','isError' => true]);
            }
        }else{
            return response()->json(['error'=>'Post already liked','isError' => true]);
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

    function getPostResponse($postId){
        $postDetail = Post::find($postId);

        
        $postData['id'] = $postDetail['id'];
        $postData['title'] = $postDetail['title'];
        $postData['caption'] = $postDetail['caption'];
        $postData['tags'] = $postDetail['tags'];
        $postData['publish'] = $postDetail['publish'];
        $postData['media'] = (!empty($postDetail['media']) ? url('storage/'.$postDetail['media']) : '');
        $postData['schedule_at'] = (!empty($postDetail['schedule_at']))?date('m/d/Y H:i', $postDetail['schedule_at']) : 0 ;
        $postData['add_to_album'] = $postDetail['add_to_album'];
        
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
