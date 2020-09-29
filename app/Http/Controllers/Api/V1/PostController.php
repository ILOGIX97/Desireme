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
     *               required={"Comment"},
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
     *                  example = "2020-09-23 08:12:21",
     *                  format="date-time"
     *               ),
     *               @OA\Property(
     *                  property="AddtoAlbum",
     *                  type="string",
     *                  default="No",
     *                  enum={"No", "Yes"}
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
        ]);

        if ($validator->fails()) {
            $failedRules = $validator->failed();
            return response()->json(['error'=>$validator->errors(),'isError' => true]);
        }
        if($request->ScheduleDateTime == 'No'){
            $addAlbum = 0;
        }else{
            $addAlbum = 1;
        }
        $post =  new Post([
    		'comment' => $request->Comment,
    		'tags' => $request->Tags,
    		'publish' => $request->Publish,
            'ScheduleDateTime' => $request->ScheduleDateTime,
            'add_to_album' => $addAlbum,
        ]);
        //$user = User::find($id)->posts()->save($post);
        if($post->save()){
            $post->user()->sync($id);
            return response()->json([
                'message' => 'Post created user!',
                'isError' => false
            ], 201);
        }else{
            return response()->json(['error'=>'Provide proper details','isError' => true]);
        }
        
        
        return response()->json(Post::find($id));
    	
    }

}
