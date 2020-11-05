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
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use App\Mail\IdVerification;
use Carbon\Carbon;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

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
        $users = User::all();
        foreach($users as $user){
            $userid = $user['id'];
            $userData[$userid]['Forename'] = $user['first_name'];
            $userData[$userid]['Surname'] = $user['last_name'];
            $userData[$userid]['DisplayName'] = $user['display_name'];
            $userData[$userid]['Username'] = $user['username'];
            $userData[$userid]['Email'] = $user['email'];
            $userData[$userid]['EmailVerified'] = $user['email_verified'];
            $userData[$userid]['PhoneNumber'] = $user['contact'];
            $userData[$userid]['ProfilePic'] = (!empty($user['profile']) ? url('storage/'.$user['profile']) : '');
            $userData[$userid]['ProfileBanner'] = (!empty($user['cover']) ? url('storage/'.$user['cover']) : '');
            $userData[$userid]['ProfileVideo'] = (!empty($user['profile_video']) ? url('storage/'.$user['profile_video']) : '');
            $userData[$userid]['SubscriptionPrice'] = $user['subscription_price'];
            $userData[$userid]['TwitterURL'] = $user['twitter_url'];
            $userData[$userid]['AmazonURL'] = $user['amazon_url'];
            $userData[$userid]['Bio'] = $user['bio'];
            $userData[$userid]['Tags'] = $user['tags'];
            $userData[$userid]['Country'] = $user['country'];
            $userData[$userid]['State'] = $user['state'];
            $userData[$userid]['AccountName'] = $user['account_name'];
            $userData[$userid]['SortCode'] = $user['sort_code'];
            $userData[$userid]['AccountNumber'] = $user['account_number'];
            $userData[$userid]['PhotoId'] = (!empty($user['photo_id']) ? url('storage/'.$user['photo_id']) : '');
            $userData[$userid]['PhotowithId'] = (!empty($user['photo_id_1']) ? url('storage/'.$user['photo_id_1']) : '');
            $userData[$userid]['Category'] = $user['category'];
            $userData[$userid]['YearsOld'] = $user['year_old'];
            $userData[$userid]['AgreeTerms'] = $user['term'];
            $userData[$userid]['twoFactor'] = (!empty($user['two_factor']) ?  'Yes': 'No');
            $userData[$userid]['Location'] = $user['location'];
            $userData[$userid]['Role'] = !empty($user->roles->first()->name) ? $user->roles->first()->name : '';
        }
        return response()->json([
            'data' => $userData,
            'isError' => false
        ]);
    }

    /**
     * @OA\Post(
     *          path="/api/v1/getDetails/{id}",
     *          operationId="Get User",
     *          tags={"Users"},
     *      @OA\Parameter(
     *          name="id",
     *          in="path",
     *          required=true,
     *          @OA\Schema(
     *              type="integer"
     *          )
     *      ),
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

    public function getDetails($id){
        $userData = $this->getResponse($id);

        return response()->json([
            'data' => $userData,
            'isError' => false
        ]);
        //return response()->json($request->user());

    }

    /**
     * @OA\Post(
     *          path="/api/v1/verifyId/{id}",
     *          operationId="Update User Ids",
     *          tags={"Register"},
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
     *               required={"PhotoId","PhotowithId"},
     *               @OA\Property(
     *                  property="ForeName",
     *                  type="string"
     *               ),
     *               @OA\Property(
     *                  property="LastName",
     *                  type="string"
     *               ),
     *               @OA\Property(
     *                  property="PhotoId",
     *                  type="string"
     *               ),
     *               @OA\Property(
     *                  property="PhotowithId",
     *                  type="string"
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
     *  )
     */

    public function verifyId(Request $request,$id){
        Log::info($request);
        $admin = User::where('type',2)->get();
        $userDetails = User::where('id',$id)->get();
        $data['email'] = $admin[0]->email;
        $data['username'] = ucfirst($userDetails[0]->first_name).' '.ucfirst($userDetails[0]->last_name);

        $validator = Validator::make($request->all(),[
            'PhotoId' => 'required',
            'PhotowithId' => 'required',
        ]);

        if ($validator->fails()) {
            $failedRules = $validator->failed();
            return response()->json(['error'=>$validator->errors(),'isError' => true]);
        }

        if(isset($request->ForeName) && $request->ForeName != ''){
            $firstName = $request->ForeName;
        }else{
            $firstName = $userDetails[0]->first_name;
        }

        if(isset($request->LastName) && $request->LastName != ''){
            $lastName = $request->LastName;
        }else{
            $lastName = $userDetails[0]->last_name;
        }
        $photo_id = '';
        $photo_with_id = '';
        if(null !== $request->PhotoId){
            $image = $request->PhotoId;
            $path = 'public/documents/';
            $photo_id = $this->createImage($image,$path);
        }
        if(null !== $request->PhotowithId){
            $image1 = $request->PhotowithId;  // your base64 encoded
            $path = 'public/documents/';
            $photo_with_id = $this->createImage($image1,$path);
            
        }
        //echo $photo_id; exit;
        $UpdateDetails = User::where('id', $id)->update([
            'photo_id' => $photo_id,
            'photo_id_1' => $photo_with_id,
            'first_name' => $firstName,
            'last_name' => $lastName
         ]);
         //echo '<pre>'; print_r($data); exit();

         Mail::to($admin[0]->email)->send(new IdVerification($data));

         $userData = $this->getResponse($id);
        return response()->json([
            'data' => $userData,
            'isError' => false
        ]);
        //return response()->json(User::find($id));

    }

    /**
     * @OA\Post(
     *          path="/api/v1/verifyemail/{id}",
     *          operationId="Verify User",
     *          tags={"Register"},
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
     *  )
     */

    public function verifyemail(Request $request,$id){

        $UpdateDetails = User::where('id', $id)->update([
            'email_verified' => now()
         ]);

         $userData = $this->getResponse($id);

     return response()->json([
         'data' => $userData,
         'isError' => false
     ]);

       // return response()->json(User::find($id));

    }

    /**
     * @OA\Post(
     *          path="/api/v1/updateProfile/{id}",
     *          operationId="Update User Profle",
     *          tags={"Register"},
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
     *               required={"ProfilePic","SubscriptionPrice"},
     *               @OA\Property(
     *                  property="ProfilePic",
     *                  type="string"
     *               ),
     *               @OA\Property(
     *                  property="ProfileVideo",
     *                  type="string"
     *               ),
     *               @OA\Property(
     *                  property="ProfileBanner",
     *                  type="string"
     *               ),
     *               @OA\Property(
     *                  property="Location",
     *                  type="string"
     *               ),
     *               @OA\Property(
     *                  property="SubscriptionPrice",
     *                  type="integer"
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
     *  )
     */

    public function updateProfile(Request $request,$id){

        $regex = '/^(https?:\/\/)?([\da-z\.-]+)\.([a-z\.]{2,6})([\/\w \.-]*)*\/?$/';
        $validator = Validator::make($request->all(),[
            'ProfilePic' => 'required',
            //'ProfileBanner' => 'required',
            //'ProfileVideo' => 'required',
            'TwitterURL' => 'nullable:regex:'.$regex,
            'AmazonURL' => 'nullable:regex:'.$regex,
            'Bio' => 'nullable:min:20|max:200',
            'SubscriptionPrice' => 'required|integer|between:3,100|nullable',
        ]);

        if ($validator->fails()) {
            $failedRules = $validator->failed();
            return response()->json(['error'=>$validator->errors(),'isError' => true]);
        }

        if(null !== $request->ProfilePic){
            $image = $request->ProfilePic;
            $path = 'public/documents/';
            $profile_pic = $this->createImage($image,$path);
        }

        if(null !== $request->ProfileBanner){
            $image = $request->ProfileBanner;
            $path = 'public/documents/';
            $Profile_Banner = $this->createImage($image,$path);
        }else{
            $Profile_Banner = '';
        }

        if(null !== $request->ProfileVideo){
            $image = $request->ProfileVideo;
                
            if (preg_match('/^data:image\/\w+;base64,/', $image)) {
                $ext = explode(';base64',$image);
                $ext = explode('/',$ext[0]);			
                $ext = $ext[1];
                $ext = trim(strtolower($ext));
                if($ext != 'gif'){
                    //return response()->json(['error'=>'{ProfileVideo: ["The profile video must be a file of type: gif."]}','isError' => true], 422);
                    $error = json_decode('{"error": {"ProfileVideo": ["The profile video must be a file of type: gif."]}, "isError": true}', 422);
                    return response()->json($error);
                }
            }
            
            $path = 'public/documents/video/';
            $Profile_Video = $this->createImage($image,$path);
        }else{
            $Profile_Video = '';
        }

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

        $userData = $this->getResponse($id);

        return response()->json([
            'data' => $userData,
            'isError' => false
        ]);

        //return response()->json(User::find($id));

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
            return response()->json(['error'=>'Provide proper details','isError' => false]);
        }

    }

    /**
     * @OA\Get(
     *          path="/api/v1/getCountries",
     *          operationId="Get country list",
     *          tags={"General"},
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
     *  )
     */

    public function getCountries(){

        $path =  storage_path('app/public').'/countries.json';
        $json = file_get_contents($path);
        $newJson = json_decode($json);
        $i = 0;
        foreach($newJson as $json1){
            $jsonC[$i]['id'] = $json1->id;
            $jsonC[$i]['name'] = $json1->name;
            $jsonC[$i]['code'] = $json1->alpha2;
            $i++;
        }
        return response()->json([
            'list' => $jsonC,
            'isError' => false
        ]);
    }


     /**
     * @OA\Get(
     *          path="/api/v1/getStates/{countryName}",
     *          operationId="Get state list for country",
     *          tags={"General"},
     *      @OA\Parameter(
     *          name="countryName",
     *          in="path",
     *          required=true,
     *          @OA\Schema(
     *              type="string"
     *          )
     *      ),
     *      summary="Get Countries",
     *      description="name of all states",

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

    public function getStates($name){

        $path =  storage_path('app/public').'/countriesStates.json';
        $json = file_get_contents($path);
        $newJson = json_decode($json);
        //echo '<pre>'; print_r($newJson); exit;
        $i = 0;
        foreach($newJson as $json1){
            if($json1->name == $name){
                $states = $json1->states;
            }
            
            $i++;
        }
        return response()->json([
            'list' => $states,
            'isError' => false
        ]);
    }


    /**
     * @OA\Post(
     *          path="/api/v1/addPaymentDetails/{id}",
     *          operationId="Add User Payment Details",
     *          tags={"Register"},
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
     *                  property="State",
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
     *               @OA\Property(
     *                  property="cardNumber",
     *                  type="number"
     *               ),
     *               @OA\Property(
     *                  property="cardExpMonth",
     *                  type="number"
     *               ),
     *               @OA\Property(
     *                  property="cardExpYear",
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
     *  )
     */

    public function addPaymentDetails(Request $request,$id){

        $validator = Validator::make($request->all(),[
            'SortCode' => 'nullable|numeric|digits:6',
            'AccountNumber' => 'nullable|numeric|digits:8',

        ]);

        if ($validator->fails()) {
            $failedRules = $validator->failed();
            return response()->json(['error'=>$validator->errors(),'isError' => true]);
        }

        $UpdateDetails = User::where('id', $id)->update([
            'country' => $request->Country,
            'state' => $request->State,
            'account_name' => $request->AccountName,
            'sort_code'=> $request->SortCode,
            'account_number'=>$request->AccountNumber,
            'card_number'=>$request->cardNumber,
            'card_exp_month'=>$request->cardExpMonth,
            'card_exp_year'=>$request->cardExpYear,
            'check_registration'=>1,
         ]);

        $userData = $this->getResponse($id); 

        return response()->json([
            'data' => $userData,
            'isError' => false
        ]);

        //return response()->json(User::find($id));

    }


    /**
     * @OA\Post(
     *          path="/api/v1/profileSettings/{id}",
     *          operationId="Update User Profile",
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
     *      summary="Update User Profile",
     *      description="data of user",
     *      @OA\RequestBody(
     *       @OA\MediaType(
     *           mediaType="multipart/form-data",
     *           @OA\Schema(
     *               required={"FirstName","LastName","Email","ProfilePhoto","CoverPhoto"},
     *               @OA\Property(
     *                  property="FirstName",
     *                  type="string"
     *               ),
     *               @OA\Property(
     *                  property="LastName",
     *                  type="string"
     *               ),
     *               @OA\Property(
     *                  property="Email",
     *                  type="string"
     *               ),
     *               @OA\Property(
     *                  property="DisplayName",
     *                  type="string"
     *               ),
     *               @OA\Property(
     *                  property="ProfilePhoto",
     *                  type="string"
     *               ),
     *               @OA\Property(
     *                  property="CoverPhoto",
     *                  type="string"
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

    public function profileSettings(Request $request,$id){
        
        $validator = Validator::make($request->all(),[
            'Email' => 'required|string|email',
            'FirstName' => 'required|string',
            'LastName' => 'required|string',
            'ProfilePhoto' => 'required',
            'CoverPhoto' => 'required',
        ]);

        if ($validator->fails()) {
            $failedRules = $validator->failed();
            return response()->json(['error'=>$validator->errors(),'isError' => true]);
        }

        
        $profile_pic = '';
        $cover_pic = '';
        if(null !== $request->ProfilePhoto){
            $image = $request->ProfilePhoto;
            $path = 'public/documents/';
            $profile_pic = $this->createImage($image,$path);
        }
        if(null !== $request->CoverPhoto){
            $image1 = $request->CoverPhoto;  // your base64 encoded
            $path = 'public/documents/';
            $cover_pic = $this->createImage($image1,$path);
            
        }
        $UpdateDetails = User::where('id', $id)->update([
            'profile' => $profile_pic,
            'cover' => $cover_pic,
            'first_name' => $request->FirstName,
            'last_name' => $request->LastName,
            'display_name' => $request->DisplayName
         ]);
        $userData = $this->getResponse($id);
        return response()->json([
            'data' => $userData,
            'isError' => false
        ]);
    }

    /**
     * @OA\Post(
     *          path="/api/v1/closeAccount/{id}",
     *          operationId="Close user account",
     *          tags={"Users"},
     *      @OA\Parameter(
     *          name="id",
     *          in="path",
     *          required=true,
     *          @OA\Schema(
     *              type="integer"
     *          )
     *      ),
     *      summary="Close user account",
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

    public function closeAccount($id){
        $UpdateDetails = User::where('id', $id)->update([
            'check_activation' => 0
         ]);

        //$userData = $this->getResponse($id);

        return response()->json([
            'message' => 'Account closed successfully',
            'data' => '',
            'isError' => false
        ]);
        //return response()->json($request->user());

    }

     /**
     * @OA\Post(
     *          path="/api/v1/updatePaymentDetails/{id}",
     *          operationId="Update User Payment Details",
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
     *      summary="Update User Payment Details",
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
     *                  property="State",
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
     *               @OA\Property(
     *                  property="cardNumber",
     *                  type="number"
     *               ),
     *               @OA\Property(
     *                  property="cardExpMonth",
     *                  type="number"
     *               ),
     *               @OA\Property(
     *                  property="cardExpYear",
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

    public function updatePaymentDetails(Request $request,$id){
        $validator = Validator::make($request->all(),[
            'SortCode' => 'nullable|numeric|digits:6',
            'AccountNumber' => 'nullable|numeric|digits:8',

        ]);

        if ($validator->fails()) {
            $failedRules = $validator->failed();
            return response()->json(['error'=>$validator->errors(),'isError' => true]);
        }

        $UpdateDetails = User::where('id', $id)->update([
            'country' => $request->Country,
            'state' => $request->State,
            'account_name' => $request->AccountName,
            'sort_code'=> $request->SortCode,
            'account_number'=>$request->AccountNumber,
            'card_number'=>$request->cardNumber,
            'card_exp_month'=>$request->cardExpMonth,
            'card_exp_year'=>$request->cardExpYear,
          ]);

        $userData = $this->getResponse($id); 

        return response()->json([
            'message' => 'Account details updated successfully',
            'data' => $userData,
            'isError' => false
        ]);

        //return response()->json(User::find($id));

    }


    /**
     * @OA\Post(
     *          path="/api/v1/addToWishList/{userId}/{writerId}",
     *          operationId="Add to wish list",
     *          tags={"Users"},
     *      @OA\Parameter(
     *          name="userId",
     *          in="path",
     *          required=true,
     *          @OA\Schema(
     *              type="integer"
     *          )
     *      ),
     *      @OA\Parameter(
     *          name="writerId",
     *          in="path",
     *          required=true,
     *          @OA\Schema(
     *              type="integer"
     *          )
     *      ),
     *      
     *      summary="Add to wish list",
     *      description="Add to wish list",
     *      
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

    public function addToWishList(Request $request,$userId,$writerId){
        $checkList = DB::table('wish_list')->where('user_id',$userId)->where('contentwriter_id',$writerId)->get();

        if(count($checkList)>0){
            return response()->json(['error'=>'Already added in wish list','isError' => true]);
        }
        $data = DB::table('wish_list')
            ->insert([
            'user_id' => $userId,
            'contentwriter_id' => $writerId,
        ]);
        $wishList = array();
        $getUsers = DB::table('wish_list')->where('user_id',$userId)->get();
            foreach($getUsers as $getUser){
                $wishList[] = $getUser->contentwriter_id;
            }
        return response()->json([
            'message' => 'Wish list updated successfully',
            'data' => $wishList,
            'isError' => false
        ]);

        //return response()->json(User::find($id));

    }

    /**
     * @OA\Post(
     *          path="/api/v1/getWishList/{userId}/{start}/{limit}",
     *          operationId="Get user wish list",
     *          tags={"Users"},
     *      @OA\Parameter(
     *          name="userId",
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
     *      summary="Get user wish list",
     *      description="Get user wish list",
     *      
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

    public function getWishList(Request $request,$userId,$start,$limit){
        $wishList = array();
        $all = $wishList = DB::table('wish_list')->where('user_id',$userId)->get();;
        $wishList = DB::table('wish_list')->where('user_id',$userId)->offset($start)->limit($limit)->get();
        $userData = array();
            foreach($wishList as $User){
               
                $userData[] = $this->getResponse($User->contentwriter_id); 
            }
        
        return response()->json([
            'message' => 'Wish List Details',
            'count' => count($all),
            'data' => $userData,
            'isError' => false
        ]);

        //return response()->json(User::find($id));

    }

    

    function createImage($image,$path){
        if (preg_match('/^data:image\/\w+;base64,/', $image)) {
            $ext = explode(';base64',$image);
            $ext = explode('/',$ext[0]);			
            $ext = $ext[1];
            $image = preg_replace('/^data:image\/\w+;base64,/', '', $image);
            $image = str_replace(' ', '+', $image);
            $imageName = Str::random(10).'.'.$ext;
            $full_path = $path . $imageName;
            Storage::put($full_path, base64_decode($image));
            $returnpath = str_replace("public/","",$path).$imageName;
            
        } else {
            $removeData = config('app.url').'storage/'; 
            $returnpath = str_replace($removeData,"",$image);
            
            //$returnpath = $image;
        }
        return $returnpath;
            
    }

    function getResponse($userId){
        $user = User::findOrFail($userId);

        if(!empty($user['card_number'])){
            $cardDetails = 1;
        }else{
            $cardDetails = 0;
        }

        $followerList = array();
        $wishList = array();
        $userId = $user['id'];
            $Followers = DB::table('follow')->where('user_id',$userId)->get();
            foreach($Followers as $follow){
                $followerList[] = $follow->follower_id;
            }

            $Wish_users = DB::table('wish_list')->where('user_id',$userId)->get();
            foreach($Wish_users as $Wish_user){
                $wishList[] = $Wish_user->contentwriter_id;
            }
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
         $userData['userId'] = $user['id'];
         $userData['Forename'] = $user['first_name'];
         $userData['Surname'] = $user['last_name'];
         $userData['DisplayName'] = $user['display_name'];
         $userData['Username'] = $user['username'];
         $userData['Email'] = $user['email'];
         $userData['EmailVerified'] = $user['email_verified'];
         $userData['PhoneNumber'] = $user['contact'];
         $userData['ProfilePic'] = (!empty($user['profile']) ? url('storage/'.$user['profile']) : '');
         $userData['ProfileBanner'] = (!empty($user['cover']) ? url('storage/'.$user['cover']) : '');
         $userData['ProfileVideo'] = (!empty($user['profile_video']) ? url('storage/'.$user['profile_video']) : '');
         $userData['SubscriptionPrice'] = $user['subscription_price'];
         $userData['TwitterURL'] = $user['twitter_url'];
         $userData['AmazonURL'] = $user['amazon_url'];
         $userData['Bio'] = $user['bio'];
         $userData['Tags'] = $user['tags'];
         $userData['Country'] = $user['country'];
         $userData['State'] = $user['state'];
         $userData['AccountName'] = $user['account_name'];
         $userData['SortCode'] = $user['sort_code'];
         $userData['AccountNumber'] = $user['account_number'];
         $userData['cardNumber'] = $user['card_number'];
         $userData['cardEcpMonth'] = $user['card_exp_month'];
         $userData['cardEcpYear'] = $user['card_exp_year'];

         $userData['PhotoId'] = (!empty($user['photo_id']) ? url('storage/'.$user['photo_id']) : '');
         $userData['PhotowithId'] = (!empty($user['photo_id_1']) ? url('storage/'.$user['photo_id_1']) : '');
         $userData['Category'] = $user['category'];
         $userData['YearsOld'] = $user['year_old'];
         $userData['AgreeTerms'] = $user['term'];
         $userData['twoFactor'] = (!empty($user['two_factor']) ?  'Yes': 'No');
         $userData['Location'] = $user['location'];
         $userData['Role'] = (isset($user->roles->first()->name)) ? $user->roles->first()->name : '';
         $userData['cardDetails'] = $cardDetails;
         $userData['followerList'] = $followerList;
         $userData['wishList'] = $wishList;
         $userData['imageCount'] = $imageCount;
         $userData['videoCount'] = $videoCount;

        
         return $userData;
    }


}
