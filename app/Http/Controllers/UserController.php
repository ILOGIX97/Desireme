<?php

namespace App\Http\Controllers;
use App\User;

use Illuminate\Http\Request;

class UserController extends Controller
{
    public function verifyemail($id){
        
          $UpdateDetails = User::where('id', $id)->update([
             'email_verified' => now()
          ]);
        
          return redirect('http://localhost:3000/profile/'.$id);
    }
}
