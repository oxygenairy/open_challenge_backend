<?php

namespace App\Http\Controllers\WEB\Public;

use Illuminate\Http\Request;

use App\Http\Controllers\WEB\WebFoundationController as WebFoundation;
use App\Medels\User;

class WebAuthController extends WebFoundation
{
    /**
     * Register 
     */

     public function register(Request $request)
     {
        $validator = Validator::make($request->all(),
                [
                'username' => 'required|string|max:60',
                'email' => 'required|email',
                'password' => 'required|string|min:8',
                ]
            );
        echo $request->referer;
     }
}
