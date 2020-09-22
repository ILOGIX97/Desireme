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
        //echo '<pre>'; print_r($request->request->all()); exit;
        return response()->json(User::all());
    	//return response()->json($request->user());
    }

    /**
     * @OA\Post(
     *          path="/api/v1/getuser",
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

    public function getuser(Request $request){
        
        return response()->json($request->user());
    	
    }

    /**
     * @OA\Post(
     *          path="/api/v1/updateuser/{id}",
     *          operationId="Update User",
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

    public function updateuser(Request $request,$id){
        
        //echo '<pre>'; print_r($request->request->all());
        $photo_id = $request->file('Photo_Id')->store('public/documents'); 
        $photo_with_id = $request->file('Photo_with_Id')->store('public/documents');
        $UpdateDetails = User::where('id', $id)->update([
            'photo_id' => $photo_id,
            'photo_id_1' => $photo_with_id
         ]);
        
        return response()->json($request->user());
    	
    }
}
