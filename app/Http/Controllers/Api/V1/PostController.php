<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Post;
use App\User;
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
     *               required={"Comment","Publish"},
     *               @OA\Property(
     *                  property="Comment",
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
     *                  property="AddtoAlbum",
     *                  type="string",
     *                  default="0",
     *                  enum={"0", "1"}
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
            'Comment' => 'required',
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
    		'comment' => $request->Comment,
            'tags' => (!empty($request->Tags)) ? $request->Tags : '',
            'media' => $media,
    		'publish' => $request->Publish,
            'schedule_at' => (!empty($request->ScheduleDateTime && $request->Publish == 'schedule')) ? strtotime($request->ScheduleDateTime) : 0,
            'add_to_album' => $request->AddtoAlbum,
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
     *               required={"Comment","Publish"},
     *               @OA\Property(
     *                  property="Comment",
     *                  type="string"
     *               ),
     *               @OA\Property(
     *                  property="Tags",
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
     *                  property="AddtoAlbum",
     *                  type="string",
     *                  default="0",
     *                  enum={"0", "1"}
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
            'Comment' => 'required',
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
            'comment' => $request->Comment,
            'tags' => (!empty($request->Tags)) ? $request->Tags : '',
            'media' => $media,
    		'publish' => $request->Publish,
            'schedule_at' => (!empty($request->ScheduleDateTime && $request->Publish == 'schedule')) ? strtotime($request->ScheduleDateTime) : 0,
            'add_to_album' => $request->AddtoAlbum,
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
            $postData[$i]['id'] = $postDetail['id'];
            $postData[$i]['comment'] = $postDetail['comment'];
            $postData[$i]['tags'] = $postDetail['tags'];
            $postData[$i]['publish'] = $postDetail['publish'];
            $postData[$i]['schedule_at'] = (!empty($postDetail['schedule_at']))?date('m/d/Y H:i', $postDetail['schedule_at']) : 0 ;
            $postData[$i]['add_to_album'] = ($postDetail['add_to_album'] == 1) ? 'Yes' : 'No';
            $i++;
        }
        if(count($postDetails)){
            return response()->json([
                'message' => 'Post updated successfully!',
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

    function getPostResponse($postId){
        $postDetail = Post::find($postId);
        $postData['id'] = $postDetail['id'];
        $postData['comment'] = $postDetail['comment'];
        $postData['tags'] = $postDetail['tags'];
        $postData['publish'] = $postDetail['publish'];
        $postData['media'] = (!empty($postDetail['media']) ? url('storage/'.$postDetail['media']) : '');
        $postData['schedule_at'] = (!empty($postDetail['schedule_at']))?date('m/d/Y H:i', $postDetail['schedule_at']) : 0 ;
        $postData['add_to_album'] = ($postDetail['add_to_album'] == 1) ? 'Yes' : 'No';
         
        
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
