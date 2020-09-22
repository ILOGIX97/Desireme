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
     *               required={"FirstName","LastName","Email","Password","Category","AgreeTerms","YearsOld","ConfirmPassword","Username"},
     *               @OA\Property(
     *                  property="FirstName",
     *                  type="string"
     *               ),
     *               @OA\Property(
     *                  property="LastName",
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
        $validator = $request->validate([
            'FirstName' => 'required|string',
            'LastName' => 'required|string',
            'Email' => 'required|string|email|unique:users',
            'Username' => 'required|string|unique:users|max:50',
            'Password' => 'min:6|required|string|required_with:ConfirmPassword|same:ConfirmPassword',
            'Category'=>'required|string',
        ]);
        if(isset($request->two_factor)){
            if($request->two_factor == 'Yes'){
                $twoFactor = 1;
            }else{
                $twoFactor = 0;
            }
        }else{
            $twoFactor = 1;
        }
        $user =  new User([
    		'first_name' => $request->FirstName,
    		'last_name' => $request->LastName,
    		'display_name' => $request->DisplayName,
    		'username' => $request->Username,
    		'email' => $request->Email,
    		'password' => bcrypt($request->Password),
    		'category' => $request->Category,
    		'year_old' => $request->YearsOld,
    		'two_factor' => $twoFactor  
    	]);
         // echo '<pre>';  print_r($user); exit;
    	 if($user->save()){
                return response()->json([
                    'message' => 'Successfully created user!'
                ], 201);
            }else{
                return response()->json(['error'=>'Provide proper details']);
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


}
