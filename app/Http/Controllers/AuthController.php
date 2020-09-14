<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use App\User;
use Validator;

class AuthController extends Controller
{
    public function signup(Request $request){

    	$request->validate([
                'firstname' => 'required|string',
                'lastname' => 'required|string',
                'email' => 'required|string|email|unique:users',
                'password' => 'required|string|',
                'category'=>'required|string',
        ]);

    	
    	$user =  new User([
    		'firstname' => $request->firstname,
    		'lastname' => $request->lastname,
    		'displayname' => $request->displayname,
    		'username' => $request->username,
    		'email' => $request->email,
    		'password' => bcrypt($request->password),
    		'profile' => $request->profile,
    		'cover' => $request->cover,
    		'location' => $request->location,
    		'category' => $request->category,
    		'term' => $request->term,
    		'yearOld' => $request->yearOld,
    		'two_factor' => $request->two_factor  
    	]);

    	 if($user->save()){
                return response()->json([
                    'message' => 'Successfully created user!'
                ], 201);
            }else{
                return response()->json(['error'=>'Provide proper details']);
            }
    }

    public function login(Request $request){
    	
    	$request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string',
            'remember_me' => 'boolean'
        ]);

        $credentials = request(['email', 'password']);

        if(!Auth::attempt($credentials))
            return response()->json([
                'message' => 'Unauthorized'
            ], 401);

        $user = $request->user();
        $tokenResult = $user->createToken('Personal Access Token');
        $token = $tokenResult->token;

        if ($request->remember_me)
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

    public function logout(Request $request){
    	$request->user()->token()->revoke();
        return response()->json([
            'message' => 'Successfully logged out'
        ]);
    }

    public function user(Request $request){
    	return response()->json($request->user());
    }
}
