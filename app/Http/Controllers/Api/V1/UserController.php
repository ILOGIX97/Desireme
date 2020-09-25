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
     *          path="/api/v1/verifyemail/{id}",
     *          operationId="Verify User",
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
     *      summary="Verify User",
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

    public function verifyemail(Request $request,$id){
        
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
     *               required={"ProfilePic","ProfileBanner"},
     *               @OA\Property(
     *                  property="ProfilePic",
     *                  type="file"
     *               ),
     *               @OA\Property(
     *                  property="ProfileVideo",
     *                  type="file"
     *               ),
     *               @OA\Property(
     *                  property="ProfileBanner",
     *                  type="file"
     *               ),
     *               @OA\Property(
     *                  property="Location",
     *                  type="string"
     *               ),
     *               @OA\Property(
     *                  property="SubscriptionPrice",
     *                  type="string"
     *               ),
     *               @OA\Property(
     *                  property="TwitterURL",
     *                  type="string"
     *               ),
     *               @OA\Property(
     *                  property="AmazonURL",
     *                  type="string"
     *               ),
     *               @OA\Property(
     *                  property="Bio",
     *                  type="string"
     *               ),
     *               @OA\Property(
     *                  property="Tags",
     *                  type="string"
     *               ),
     *               
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
        
        $regex = '/^(https?:\/\/)?([\da-z\.-]+)\.([a-z\.]{2,6})([\/\w \.-]*)*\/?$/';
        $validator = $request->validate([
            'TwitterURL' => 'nullable:regex:'.$regex,
            'AmazonURL' => 'nullable:regex:'.$regex,
            'Bio' => 'nullable:min:20|max:200',
            'SubscriptionPrice' => 'nullable:integer|between:3,100',
            'ProfileVideo' => 'mimes:gif'
        ]);
        
        $profile_pic = $request->file('ProfilePic')->store('public/documents'); 
        $Profile_Banner = $request->file('ProfileBanner')->store('public/documents');
        $Profile_Video = $request->file('ProfileVideo')->store('public/documents/video');
        $UpdateDetails = User::where('id', $id)->update([
            'profile' => $profile_pic,
            'cover' => $Profile_Banner,
            'profile_video'=>$Profile_Video,
            'subscription_price'=>$request->SubscriptionPrice,
            'twitter_url'=>$request->TwitterURL,
            'amazon_url'=>$request->AmazonURL,
            'bio'=>$request->Bio,
            'tags'=>$request->Tags,
            'location'=>$request->Location
         ]);
        
        return response()->json(User::find($id));
    	
    }

    /**
     * @OA\Post(
     *          path="/api/v1/deleteUser/{id}",
     *          operationId="Delete User",
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
     *      summary="Delete User",
     *      description="delete user details",
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

    public function deleteUser(Request $request,$id){
        
        if(User::find($id)->delete()){
            return response()->json([
                'message' => 'Successfully deleted user!'
            ], 201);
        }else{
            return response()->json(['error'=>'Provide proper details']);
        }
    	
    }

    /**
     * @OA\Post(
     *          path="/api/v1/getCountries",
     *          operationId="Get country list",
     *          tags={"Users"},
     *      summary="Get Countries",
     *      description="name of all countries",
    
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

    public function getCountries(){
        
        $path =  storage_path('app/public').'/countries.json'; 
        $json = file_get_contents($path);
        return response()->json([
            'list' => $json,
        ]);
    }


    /**
     * @OA\Post(
     *          path="/api/v1/addPaymentDetails/{id}",
     *          operationId="Add User Payment Details",
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
     *      summary="Add User Payment Details",
     *      description="data of users account",
     *      @OA\RequestBody(
     *       @OA\MediaType(
     *           mediaType="multipart/form-data",
     *           @OA\Schema(
     *               @OA\Property(
     *                  property="Country",
     *                  type="string"
     *               ),
     *               @OA\Property(
     *                  property="AccountName",
     *                  type="string"
     *               ),
     *               @OA\Property(
     *                  property="SortCode",
     *                  type="number"
     *               ),
     *               @OA\Property(
     *                  property="AccountNumber",
     *                  type="number"
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

    public function addPaymentDetails(Request $request,$id){
        
        $validator = $request->validate([
            'SortCode' => 'numeric|digits:6',
            'AccountNumber' => 'numeric|digits:8',
            
        ]);

        $UpdateDetails = User::where('id', $id)->update([
            'country' => $request->Country,
            'account_name' => $request->AccountName,
            'sort_code'=> $request->SortCode,
            'account_number'=>$request->AccountNumber,
         ]);
        
        return response()->json(User::find($id));
    	
    }

}
