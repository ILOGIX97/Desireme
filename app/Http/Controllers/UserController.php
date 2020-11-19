<?php

namespace App\Http\Controllers;
use App\User;

use Illuminate\Http\Request;
use Paysafe\Environment;
use Paysafe\PaysafeApiClient;
use Paysafe\ThreeDSecureV2\Authentications;

class UserController extends Controller
{
    public function verifyemail($role,$id){
          
          $UpdateDetails = User::where('id', $id)->update([
             'email_verified' => now()
          ]);
        
          return redirect('http://122.179.134.51:3099/verify/'.$role.'/'.base64_encode($id));
    }

    public function paySafe(){
          
        return view('pay.index');
    }
}
