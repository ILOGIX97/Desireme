<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use App\Mail\SendMailable;
use Spatie\Permission\Models\Role;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;



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
     *               required={"Forename","Surname","Email","Password","Category","ConfirmPassword","Username","AgreeTerms","YearsOld"},
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
        if(isset($request->UserId) && !empty($request->UserId)){
            $validator = Validator::make($request->all(),[
                'Forename' => 'required|string',
                'Surname' => 'required|string',
                'Category'=>'required|string',
                'PhoneNumber'=>'nullable:min:10'
            ]);
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
                'YearsOld'=>'required|gt:0'
            ]);
        }

        if ($validator->fails()) {
            $failedRules = $validator->failed();
            return response()->json(['error'=>$validator->errors(),'isError' => true]);
        }

        if(isset($request->DisplayName)){ $dpName = $request->DisplayName; }else{ $dpName = ''; }
        
        if(empty($request->UserId)){
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
            if($user->save()){
                $user->assignRole(['ContentCreator']);
                $data['name'] = $request->Forename.' '.$request->Surname;
                $data['user_id'] = $user->id;
                $data['url'] = config('app.url').'verifyemail/'.$data['user_id'];

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
                    $userData['AccountName'] = $user['account_name'];
                    $userData['SortCode'] = $user['sort_code'];
                    $userData['AccountNumber'] = $user['account_number'];
                    $userData['PhotoId'] = (!empty($user['photo_id']) ? url('storage/'.$user['photo_id']) : '');
                    $userData['PhotowithId'] = (!empty($user['photo_id_1']) ? url('storage/'.$user['photo_id_1']) : '');
                    $userData['Category'] = $user['category'];
                    $userData['YearsOld'] = $user['year_old'];
                    $userData['AgreeTerms'] = $user['term'];
                    $userData['twoFactor'] = (!empty($user['two_factor']) ?  'Yes': 'No');

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
            $user->last_name = $request->Forename;
            $user->display_name = $dpName;
            $user->contact = $request->PhoneNumber;
            $user->category = $request->Category;
            if($user->save()){
                $user = User::find($request->UserId);

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
                'message' => 'Unauthorized',
                'isError' => true
            ], 401);
        }
        $user = $request->user();
        $tokenResult = $user->createToken('Personal Access Token');
        $token = $tokenResult->token;

        if (isset($request->remember_me) && $request->remember_me == 'Yes')
            $token->expires_at = Carbon::now()->addWeeks(1);
            $token->save();

        return response()->json([
            'access_token' => $tokenResult->accessToken,
            'token_type' => 'Bearer',
            'expires_at' => Carbon::parse(
                $tokenResult->token->expires_at
            )->toDateTimeString(),
            'isError' => false
        ]);

    }

    /**
     * @OA\Post(
     ** path="/api/v1/desirerRegister",
     *   tags={"Register"},
     *   summary="Desirer Register",
     *   operationId="desirerRegister",
     *
     *   @OA\RequestBody(
     *       @OA\MediaType(
     *           mediaType="multipart/form-data",
     *           @OA\Schema(
     *               required={"Forename","Surname","Email","Password","Category","ConfirmPassword","Username","ProfilePic"},
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
     *              @OA\Property(
     *                  property="ProfilePic",
     *                  type="file"
     *               ),
     *              @OA\Property(
     *                  property="Category",
     *                  type="string",
     *                  enum={"-" ,"Male", "Female", "Trans"}
     *               ),
     *               @OA\Property(
     *                  property="PhoneNumber",
     *                  type="string",
     *               ),
     *               @OA\Property(
     *                  property="TwoFactor",
     *                  type="string",
     *                  default="No",
     *                  enum={"Yes", "No"}
     *               ),
     *               @OA\Property(
     *                  property="AgreeTerms",
     *                  type="string",
     *                  default="Yes",
     *                  enum={"Yes", "No"}
     *               ),
     *               @OA\Property(
     *                  property="YearsOld",
     *                  type="string",
     *                  default="Yes",
     *                  enum={"Yes", "No"}
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
    public function desirerRegister(Request $request)
    {
        if(isset($request->AgreeTerms)){
            if($request->AgreeTerms == 'No'){
                return response()->json(['error'=>'Please agree terms','isError' => true], 422);
            }
        }
        if(isset($request->YearsOld)){
            if($request->YearsOld == 'Yes'){
                $YearsOld = 1;
            }else{
                $YearsOld = 0;
            }
        }else{
            $YearsOld = 1;
        }

        if(isset($request->UserId) && !empty($request->UserId)){
            $validator = Validator::make($request->all(),[
                'Forename' => 'required|string',
                'Surname' => 'required|string',
                'Email' => 'required|string|email',
                'Username' => 'required|alpha_num|max:50',
                'Password' => 'required|min:6|string|required_with:ConfirmPassword|same:ConfirmPassword',
                'Category'=>'required|string',
                'PhoneNumber'=>'nullable:min:10'
            ]);
        }else{
            $validator = Validator::make($request->all(),[
                'Forename' => 'required|string',
                'Surname' => 'required|string',
                'Email' => 'required|string|email|unique:users,email',
                'Username' => 'required|alpha_num|unique:users,username|max:50',
                'Password' => 'required|min:6|string|required_with:ConfirmPassword|same:ConfirmPassword',
                'Category'=>'required|string',
                'PhoneNumber'=>'nullable:min:10'
            ]);
        }

        if ($validator->fails()) {
            $failedRules = $validator->failed();
            return response()->json(['error'=>$validator->errors(),'isError' => true]);
        }

        if(isset($request->two_factor)){
            if($request->two_factor == 'Yes'){
                $twoFactor = 1;
            }else{
                $twoFactor = 0;
            }
        }else{
            $twoFactor = 1;
        }
        if (isset($request->ProfilePic)){
            $profile_pic = $request->file('ProfilePic')->store('public/documents');
        }else{
            $profile_pic = null;
        }


        if(isset($request->DisplayName)){ $dpName = $request->DisplayName; }else{ $dpName = ''; }
        $user =  new User([
            'first_name' => $request->Forename,
            'last_name' => $request->Surname,
            'display_name' => $dpName,
            'username' => $request->Username,
            'contact' => $request->PhoneNumber,
            'profile' => $profile_pic,
            'email' => $request->Email,
            'password' => bcrypt($request->Password),
            'category' => $request->Category,
            'year_old' => $YearsOld,
            'two_factor' => $twoFactor
        ]);
        if(empty($request->UserId)){
            if($user->save()){
                $user->assignRole(['Desirer']);
                $tokenResult = $user->createToken('Personal Access Token');
                $token = $tokenResult->token;
                $token->expires_at = Carbon::now()->addWeeks(1);
                $token->save();
                $data['name'] = $request->Forename.' '.$request->Surname;
                $data['user_id'] = $user->id;
                $data['url'] = config('app.url').'verifyemail/'.$data['user_id'];

                Mail::to($request->Email)->send(new SendMailable($data));
                return response()->json([
                    'message' => 'Successfully created user!',
                    'user_id' => $data['user_id'],
                    'access_token' => $tokenResult->accessToken,
                    'isError' => false
                ], 201);
            }else{
                return response()->json(['error'=>'Provide proper details','isError' => true]);
            }
        }else{
            $user = User::find($request->UserId);
            $tokenResult = $user->createToken('Personal Access Token');
            $token = $tokenResult->token;
            $token->expires_at = Carbon::now()->addWeeks(1);
            $token->save();
            return response()->json([
                'message' => 'Successfully created user!',
                'user_id' => $request->UserId,
                'access_token' => $tokenResult->accessToken,
                'isError' => false
            ], 201);
        }
    }




}
