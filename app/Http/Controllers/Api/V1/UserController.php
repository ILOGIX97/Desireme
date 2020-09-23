<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Validator;

class UserController extends Controller
{
    /**
     * @OA\Post(
     *          path="/api/v1/alluser",
     *          operationId="Users",
     *          tags={"Users"},
     *      
     *      summary="Get list of users",
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
     *      security={ {"passport": {}} },
     *  )
     */
    public function alluser(Request $request){
        return response()->json(User::all());
    }

    /**
     * @OA\Post(
     *          path="/api/v1/getDetails",
     *          operationId="Get User",
     *          tags={"Users"},
     *      
     *      summary="get user data",
     *      description="data of user",
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

    public function getDetails(Request $request){
        
        return response()->json($request->user());
    	
    }

    /**
     * @OA\Post(
     *          path="/api/v1/verifyId/{id}",
     *          operationId="Update User Ids",
     *          tags={"Users"},
     *      @OA\Parameter(
     *          name="id",
     *          in="path",
     *          required=true,
     *          @OA\Schema(
     *              type="integer"
     *          )
     *      ),
     *      
     *      summary="Update User Ids",
     *      description="data of users",
     *      @OA\RequestBody(
     *       @OA\MediaType(
     *           mediaType="multipart/form-data",
     *           @OA\Schema(
     *               @OA\Property(
     *                  property="FirstName",
     *                  type="string"
     *               ),
     *               @OA\Property(
     *                  property="LastName",
     *                  type="string"
     *               ),
     *               @OA\Property(
     *                  property="Photo Id",
     *                  type="file"
     *               ),
     *               @OA\Property(
     *                  property="Photo with Id",
     *                  type="file"
     *               ),
     *           )
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

    public function verifyId(Request $request,$id){
        
        //echo '<pre>'; print_r($request->request->all());
        $photo_id = $request->file('Photo_Id')->store('public/documents'); 
        $photo_with_id = $request->file('Photo_with_Id')->store('public/documents');
        $UpdateDetails = User::where('id', $id)->update([
            'photo_id' => $photo_id,
            'photo_id_1' => $photo_with_id
         ]);
        
        return response()->json(User::find($id));
    	
    }

    /**
     * @OA\Post(
     *          path="/api/v1/varifyemail/{id}",
     *          operationId="Varify User",
     *          tags={"Users"},
     *      @OA\Parameter(
     *          name="id",
     *          in="path",
     *          required=true,
     *          @OA\Schema(
     *              type="integer"
     *          )
     *      ),
     *      
     *      summary="Varify User",
     *      description="data of users",
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

    public function varifyemail(Request $request,$id){
        
        $UpdateDetails = User::where('id', $id)->update([
            'email_verified' => now()
         ]);
        
        return response()->json(User::find($id));
    	
    }

    /**
     * @OA\Post(
     *          path="/api/v1/updateProfile/{id}",
     *          operationId="Update User Profle",
     *          tags={"Users"},
     *      @OA\Parameter(
     *          name="id",
     *          in="path",
     *          required=true,
     *          @OA\Schema(
     *              type="integer"
     *          )
     *      ),
     *      
     *      summary="update users",
     *      description="data of users",
     *      @OA\RequestBody(
     *       @OA\MediaType(
     *           mediaType="multipart/form-data",
     *           @OA\Schema(
     *               @OA\Property(
     *                  property="Profile Pic",
     *                  type="file"
     *               ),
     *               @OA\Property(
     *                  property="Profile Video",
     *                  type="file"
     *               ),
     *               @OA\Property(
     *                  property="Profile Banner",
     *                  type="file"
     *               ),
     *           )
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

    public function updateProfile(Request $request,$id){
        
        //echo '<pre>'; print_r($request->files); exit;
        $profile_pic = $request->file('Profile_Pic')->store('public/documents'); 
        $Profile_Banner = $request->file('Profile_Banner')->store('public/documents');
        $Profile_Video = $request->file('Profile_Video')->store('public/documents/video');
        $UpdateDetails = User::where('id', $id)->update([
            'profile' => $profile_pic,
            'cover' => $Profile_Banner,
            'profile_video'=>$Profile_Video
         ]);
        
        return response()->json(User::find($id));
    	
    }


}
