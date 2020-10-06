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
        
          return redirect('http://localhost:3000/verify/'.$role.'/'.base64_encode($id));
    }
}
