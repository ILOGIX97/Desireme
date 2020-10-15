<?php

namespace App\Http\Controllers;
use App\User;

use Illuminate\Http\Request;

class UserController extends Controller
{
    public function verifyemail($role,$id){
          
          $UpdateDetails = User::where('id', $id)->update([
             'email_verified' => now()
          ]);
        
          return redirect('http://brainstream.ddns.net:3099/verify/'.$role.'/'.base64_encode($id));
    }
}
