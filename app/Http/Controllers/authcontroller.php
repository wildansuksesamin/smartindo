<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class authcontroller extends Controller
{
    public function Login (Request $request){
        $request ->validate([
            "email"=> "required|email", 
            "password"=> "required",
        ]);
    }
}
