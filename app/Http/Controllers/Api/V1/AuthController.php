<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use App\Mail\SendMailable;
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
     *               required={"Forename","Surname","Email","Password","Category","AgreeTerms","YearsOld","ConfirmPassword","Username"},
     *               @OA\Property(
     *                  property="Forename",
     *                  type="string"
     *               ),
     *               @OA\Property(
     *                  property="Surname",
     *                  type="string"
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
     *                  type="string",
     *                  enum={"Yes", "No"}
     *               ),
     *               @OA\Property(
     *                  property="AgreeTerms",
     *                  type="string",
     *                  enum={"-" , "Yes", "No"}
     *               ),
     *               @OA\Property(
     *                  property="YearsOld",
     *                  type="string",
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
    public function register(Request $request)
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
        $validator = Validator::make($request->all(),[
            'Forename' => 'required|string',
            'Surname' => 'required|string',
            'Email' => 'required|string|email|unique:users',
            'Username' => 'required|string|unique:users|max:50',
            'Password' => 'required|min:6|string|required_with:ConfirmPassword|same:ConfirmPassword',
            'Category'=>'required|string',
            'PhoneNumber'=>'nullable:min:10'
        ]);

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
        
        if(isset($request->DisplayName)){ $dpName = $request->DisplayName; }else{ $dpName = ''; }
        $user =  new User([
    		'first_name' => $request->Forename,
    		'last_name' => $request->Surname,
    		'display_name' => $dpName,
            'username' => $request->Username,
            'contact' => $request->PhoneNumber,
    		'email' => $request->Email,
    		'password' => bcrypt($request->Password),
    		'category' => $request->Category,
    		'year_old' => $YearsOld,
    		'two_factor' => $twoFactor
    	]);
         // echo '<pre>';  print_r($user); exit;
    	 if($user->save()){
            $data['name'] = $request->Forename.' '.$request->Surname;
            $data['user_id'] = $user->id;
            $data['url'] = config('app.url').'/verifyemail/'.$data['user_id'];
                
                Mail::to($request->Email)->send(new SendMailable($data)); 
                return response()->json([
                    'message' => 'Successfully created user!',
                    'isError' => false
                ], 201);
            }else{
                return response()->json(['error'=>'Provide proper details','isError' => true]);
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
     *                  enum={"-" , "Yes", "No"}
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
        $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string',
            'remember_me' => 'string'
        ]);

        $credentials = request(['email', 'password']);
        //echo '<pre>'; print_r($credentials); exit;
        if(!Auth::attempt($credentials))
            return response()->json([
                'message' => 'Unauthorized'
            ], 401);

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
            )->toDateTimeString()
        ]);

    }




}