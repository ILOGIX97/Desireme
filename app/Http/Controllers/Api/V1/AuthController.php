<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use App\Mail\SendMailable;
use App\Mail\contactUs;
use Spatie\Permission\Models\Role;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;



class AuthController extends Controller
{

    /**
     * @OA\Post(
     ** path="/api/v1/register",
     *   tags={"Register"},
     *   summary="Register",
     *   operationId="register",
     *
     *   @OA\RequestBody(
     *       @OA\MediaType(
     *           mediaType="multipart/form-data",
     *           @OA\Schema(
     *               required={"Email","Password","Category","ConfirmPassword","Username","AgreeTerms","YearsOld","Role"},
     *               @OA\Property(
     *                  property="Forename",
     *                  type="string"
     *               ),
     *               @OA\Property(
     *                  property="Surname",
     *                  type="string"
     *               ),
     *               @OA\Property(
     *                  property="UserId",
     *                  type="integer"
     *               ),
     *               @OA\Property(
     *                  property="Role",
     *                  type="string",
     *                  default = "ContentCreator",
     *                  enum={"ContentCreator", "Desirer"}
     *               ),
     *               @OA\Property(
     *                  property="DisplayName",
     *                  type="string"
     *               ),
     *               @OA\Property(
     *                  property="Username",
     *                  type="string"
     *               ),
     *               @OA\Property(
     *                  property="Email",
     *                  type="string"
     *               ),
     *               @OA\Property(
     *                  property="Password",
     *                  format = "password",
     *                  type="string"
     *               ),
     *               @OA\Property(
     *                  property="ConfirmPassword",
     *                  format = "password",
     *                  type="string"
     *               ),
     *               @OA\Property(
     *                  property="Category",
     *                  type="string",
     *                  enum={"-" ,"Male", "Female", "Trans"}
     *               ),
     *               @OA\Property(
     *                  property="PhoneNumber",
     *                  type="string",
     *               ),
     *               @OA\Property(
     *                  property="ProfilePic",
     *                  type="string"
     *               ),
     *               @OA\Property(
     *                  property="Location",
     *                  type="string"
     *               ),
     *               @OA\Property(
     *                  property="TwoFactor",
     *                  type="integer",
     *                  default="0",
     *                  enum={"1", "0"}
     *               ),
     *               @OA\Property(
     *                  property="AgreeTerms",
     *                  type="integer",
     *                  default="1",
     *                  enum={"1", "0"}
     *               ),
     *               @OA\Property(
     *                  property="YearsOld",
     *                  type="integer",
     *                  default="1",
     *                  enum={"1", "0"}
     *               ),
     *
     *           )
     *       ),
     *   ),
     *   @OA\Response(
     *      response=201,
     *       description="Success",
     *      @OA\MediaType(
     *           mediaType="application/json",
     *      )
     *   ),
     *   @OA\Response(
     *      response=401,
     *       description="Unauthenticated"
     *   ),
     *   @OA\Response(
     *      response=400,
     *      description="Bad Request"
     *   ),
     *   @OA\Response(
     *      response=404,
     *      description="not found"
     *   ),
     *      @OA\Response(
     *          response=403,
     *          description="Forbidden"
     *      )
     *)
     **/
    /**
     * Register api
     *
     * @return \Illuminate\Http\Response
     */
    public function register(Request $request)
    {
        
        
        $messages = array(
            //'required' => 'The :attribute field is required.',
            'Forename.required' => 'First name required',
            'Surname.required' => 'Last name required',
            'AgreeTerms.gt' => 'please agree terms and conditions',
            'YearsOld.gt' => 'Years must be 18 or over',
        );
          

        if(isset($request->UserId) && !empty($request->UserId)){
            $validator = Validator::make($request->all(),[
                'Forename' => 'required|string',
                'Surname' => 'required|string',
                'Category'=>'required|string',
                'PhoneNumber'=>'nullable:min:10',
                'AgreeTerms'=>'required|gt:0',
                'YearsOld'=>'required|gt:0',
            ],$messages);
        }else{
            $validator = Validator::make($request->all(),[
                'Forename' => 'required|string',
                'Surname' => 'required|string',
                'Email' => 'required|string|email|unique:users',
                'Username' => 'required|string|unique:users|max:50',
                'Password' => 'required|min:6|string|required_with:ConfirmPassword|same:ConfirmPassword',
                'Category'=>'required|string',
                'PhoneNumber'=>'nullable:min:10',
                'AgreeTerms'=>'required|gt:0',
                'YearsOld'=>'required|gt:0',
                'Role'=>'required'
            ],$messages);
        }

        if ($validator->fails()) {
            $failedRules = $validator->failed();
            return response()->json(['error'=>$validator->errors(),'isError' => true]);
        }

        if(isset($request->DisplayName)){ $dpName = $request->DisplayName; }else{ $dpName = ''; }
        
        if(empty($request->UserId)){
            
            if($request->Role == 'ContentCreator'){
                $user =  new User([
                    'first_name' => $request->Forename,
                    'last_name' => $request->Surname,
                    'display_name' => $dpName,
                    'username' => $request->Username,
                    'contact' => $request->PhoneNumber,
                    'email' => $request->Email,
                    'password' => bcrypt($request->Password),
                    'category' => $request->Category,
                    'year_old' => $request->YearsOld,
                    'two_factor' => $request->TwoFactor,
                    'term' => $request->AgreeTerms
                ]);
            }else if($request->Role == 'Desirer'){
                if(null !== $request->ProfilePic){
                    $image = $request->ProfilePic;
                    $path = 'public/documents/';
                    $profile_pic = $this->createImage($image,$path);
                }else{
                    $profile_pic = '';
                }
                $user =  new User([
                    'first_name' => $request->Forename,
                    'last_name' => $request->Surname,
                    'display_name' => $dpName,
                    'username' => $request->Username,
                    'contact' => $request->PhoneNumber,
                    'email' => $request->Email,
                    'password' => bcrypt($request->Password),
                    'category' => $request->Category,
                    'year_old' => $request->YearsOld,
                    'two_factor' => $request->TwoFactor,
                    'term' => $request->AgreeTerms,
                    'profile' => $profile_pic,
                    'location'=>$request->Location
                ]);
            }
            
            if($user->save()){
                $user->assignRole([$request->Role]);
                $data['name'] = $request->Forename.' '.$request->Surname;
                $data['role'] = $user->roles->first()->name;
                $data['user_id'] = $user->id;
                $data['url'] = config('app.url').'verifyemail/'.$data['role'].'/'.$data['user_id'];

                    Mail::to($request->Email)->send(new SendMailable($data));
                    $user = User::find($user->id);

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
                    $userData['State'] = (!empty($request->State)) ? $request->State : '';
                    $userData['AccountName'] = $user['account_name'];
                    $userData['SortCode'] = $user['sort_code'];
                    $userData['AccountNumber'] = $user['account_number'];
                    $userData['PhotoId'] = (!empty($user['photo_id']) ? url('storage/'.$user['photo_id']) : '');
                    $userData['PhotowithId'] = (!empty($user['photo_id_1']) ? url('storage/'.$user['photo_id_1']) : '');
                    $userData['Category'] = $user['category'];
                    $userData['YearsOld'] = $user['year_old'];
                    $userData['AgreeTerms'] = $user['term'];
                    $userData['twoFactor'] = (!empty($user['two_factor']) ?  'Yes': 'No');
                    $userData['Location'] = $user['location'];
                    $userData['Role'] = $user->roles->first()->name;

                    return response()->json([
                        'message' => 'Successfully created user!',
                        'data' => $userData,
                        'isError' => false,
                        'user_id' => $data['user_id'],
                    ]);
                }else{
                    return response()->json(['error'=>'Provide proper details','isError' => true]);
                }
        }else{
            $user = User::find($request->UserId);
            $user->first_name = $request->Forename;
            $user->last_name = $request->Surname;
            $user->display_name = $dpName;
            $user->contact = $request->PhoneNumber;
            $user->category = $request->Category;
            $user->term = $request->AgreeTerms;
            $user->two_factor= $request->TwoFactor;
            $user->year_old= $request->YearsOld;
            if($user->roles->first()->name == 'Desirer'){
                if(null !== $request->ProfilePic){
                    $image = $request->ProfilePic;
                    $path = 'public/documents/';
                    $profile_pic = $this->createImage($image,$path);
                    $user->profile = $profile_pic;
                }
                $user->location = $request->Location;
            }
            if($user->save()){
                $user = User::findOrFail($request->UserId);

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
                $userData['PhotoId'] = (!empty($user['photo_id']) ? url('storage/'.$user['photo_id']) : '');
                $userData['PhotowithId'] = (!empty($user['photo_id_1']) ? url('storage/'.$user['photo_id_1']) : '');
                $userData['Category'] = $user['category'];
                $userData['YearsOld'] = $user['year_old'];
                $userData['AgreeTerms'] = $user['term'];
                $userData['twoFactor'] = (!empty($user['two_factor']) ?  'Yes': 'No');
                $userData['Location'] = $user['location'];
                $userData['Role'] = (isset($user->roles->first()->name)) ? $user->roles->first()->name : '';

                return response()->json([
                    'message' => 'User updated successfully!',
                    'user_id' => $request->UserId,
                    'data' => $userData,
                    'isError' => false
                ]);
        }else{
            return response()->json(['error'=>'Provide proper details','isError' => true]);
        }
        }
    }

    /**
     * @OA\Post(
     ** path="/api/v1/login",
     *   tags={"Login"},
     *   summary="Login",
     *   operationId="login",
     *   
     *   @OA\RequestBody(
     *       @OA\MediaType(
     *           mediaType="multipart/form-data",
     *           @OA\Schema(
     *               required={"email","password"},
     *               @OA\Property(
     *                  property="email",
     *                  type="string"
     *               ),
     *               @OA\Property(
     *                  property="password",
     *                  format = "password",
     *                  type="string"
     *               ),
     *               @OA\Property(
     *                  property="remember_me",
     *                  type="string",
     *                  default = "Yes",
     *                  enum={"Yes", "No"}
     *               ),
     *           )
     *       ),
     *   ),
     *   @OA\Response(
     *      response=201,
     *       description="Success",
     *      @OA\MediaType(
     *           mediaType="application/json",
     *      )
     *   ),
     *   @OA\Response(
     *      response=401,
     *       description="Unauthenticated"
     *   ),
     *   @OA\Response(
     *      response=400,
     *      description="Bad Request"
     *   ),
     *   @OA\Response(
     *      response=404,
     *      description="not found"
     *   ),
     *      @OA\Response(
     *          response=403,
     *          description="Forbidden"
     *      )
     *)
     **/
    /**
     * Register api
     *
     * @return \Illuminate\Http\Response
     */
    public function login(Request $request){

        //echo '<pre>'; print_r($request->request->all()); exit;
        $validator = Validator::make($request->all(),[
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            $failedRules = $validator->failed();
            return response()->json(['error'=>$validator->errors(),'isError' => true]);
        }

        $credentials = request(['email', 'password']);
        //echo '<pre>'; print_r($credentials); exit;
        if(!Auth::attempt($credentials)){
            return response()->json([
                'message' => 'Your email or password is wrong! Please try again!',
                'isError' => true
            ], 401);
        }
        $user = $request->user();
        if(!empty($user['card_number'])){
            $cardDetails = 1;
        }else{
            $cardDetails = 0;
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
        $userData['AccountName'] = $user['account_name'];
        $userData['SortCode'] = $user['sort_code'];
        $userData['AccountNumber'] = $user['account_number'];
        $userData['PhotoId'] = (!empty($user['photo_id']) ? url('storage/'.$user['photo_id']) : '');
        $userData['PhotowithId'] = (!empty($user['photo_id_1']) ? url('storage/'.$user['photo_id_1']) : '');
        $userData['Category'] = $user['category'];
        $userData['YearsOld'] = $user['year_old'];
        $userData['AgreeTerms'] = $user['term'];
        $userData['twoFactor'] = (!empty($user['two_factor']) ?  'Yes': 'No');
        $userData['Location'] = $user['location'];
        $userData['Role'] = (isset($user->roles->first()->name)) ? $user->roles->first()->name : '';
        $userData['cardDetails'] = $cardDetails;
        
        if(!empty($user->email_verified) && !empty($user->check_registration) && !empty($user->check_activation))
        {
            $tokenResult = $user->createToken('Personal Access Token');
            $token = $tokenResult->token;
        }else{
            if(empty($user->email_verified)){
                $message = 'Email address not verified';
            }
            if(empty($user->check_registration)){
                $message = 'Registration process not completed';
            }
            if(empty($user->check_activation)){
                $message = 'User is not activated';
            }
            return response()->json([
                'message' => $message,
                'access_token' => '',
                'token_type' => '',
                'expires_at' => '',
                'data' => '',
                'isError' => true
            ]);
        }
            
        
        if (isset($request->remember_me) && $request->remember_me == 'Yes'){
            $token->expires_at = Carbon::now()->addHours(6);
            $token->save();
            $user->remember_me = 1;
            $user->save();
        }else{
            $token->expires_at = Carbon::now()->addHours(6);
            $token->save();
            $user->remember_me = 0;
            $user->save();
        }
            
        
        return response()->json([
            'message' => 'Registration process completed',
            'access_token' => $tokenResult->accessToken,
            'token_type' => 'Bearer',
            'currentTime' =>  Carbon::now(),
            'expires_at' => Carbon::parse(
                $tokenResult->token->expires_at
            )->toDateTimeString(),
            'remember_me' => $request->remember_me,
            'data' => $userData,
            'isError' => false
        ]);

    }
   
   /**
     * @OA\Post(
     ** path="/api/v1/logout/{id}",
     *   tags={"Logout"},
     *   summary="Logout",
     *   operationId="Logout",
     *   @OA\Parameter(
     *          name="id",
     *          in="path",
     *          required=true,
     *          @OA\Schema(
     *              type="integer"
     *          )
     *      ),
     *   @OA\Response(
     *      response=201,
     *       description="Success",
     *      @OA\MediaType(
     *           mediaType="application/json",
     *      )
     *   ),
     *   @OA\Response(
     *      response=401,
     *       description="Unauthenticated"
     *   ),
     *   @OA\Response(
     *      response=400,
     *      description="Bad Request"
     *   ),
     *   @OA\Response(
     *      response=404,
     *      description="not found"
     *   ),
     *      @OA\Response(
     *          response=403,
     *          description="Forbidden"
     *      ),
     *      
     *)
     **/
    public function logout(Request $request,$id){
        $user = User::where('id', $id)->update([
            'remember_me' => 0
         ]);
        return response()->json([
            'message' => 'Successfully logged out'
        ]);
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


     /**
     * @OA\Post(
     ** path="/api/v1/contactus",
     *   tags={"General"},
     *   summary="Contact Us",
     *   operationId="contactus",
     *
     *   @OA\RequestBody(
     *       @OA\MediaType(
     *           mediaType="multipart/form-data",
     *           @OA\Schema(
     *               required={"Fullname","Email"},
     *               @OA\Property(
     *                  property="Fullname",
     *                  type="string"
     *               ),
     *               @OA\Property(
     *                  property="message",
     *                  type="string"
     *               ),
     *               @OA\Property(
     *                  property="Email",
     *                  type="string"
     *               ),
     *           )
     *       ),
     *   ),
     *   @OA\Response(
     *      response=201,
     *       description="Success",
     *      @OA\MediaType(
     *           mediaType="application/json",
     *      )
     *   ),
     *   @OA\Response(
     *      response=401,
     *       description="Unauthenticated"
     *   ),
     *   @OA\Response(
     *      response=400,
     *      description="Bad Request"
     *   ),
     *   @OA\Response(
     *      response=404,
     *      description="not found"
     *   ),
     *      @OA\Response(
     *          response=403,
     *          description="Forbidden"
     *      )
     *)
     **/
    /**
     * Contact Us api
     *
     * @return \Illuminate\Http\Response
     */

    public function contactus(Request $request){
        $sendTo = config('app.CONTACTUS_MAIL');
        $validator = Validator::make($request->all(),[
            'Fullname' => 'required|string',
            'Email' => 'required|string|email',
        ]);
        if ($validator->fails()) {
            $failedRules = $validator->failed();
            return response()->json(['error'=>$validator->errors(),'isError' => true]);
        }

        $data['name'] = $request->Fullname;
        $data['message'] = $request->message;
        $data['email'] = $request->Email;
       
        try{
            Mail::to($sendTo)->send(new contactUs($data));
            return response()->json([
                'message' => 'Message sent succesfully! We will get back to you soon !',
                'isError' => false
            ], 201);
        }catch (\Exception $e){
            return response()->json(['error'=>'Something went wrong .Please try after sometime','isError' => true]);
        }

        
    }






}
