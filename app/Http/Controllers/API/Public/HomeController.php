<?php

namespace App\Http\Controllers\API\Public;

use Illuminate\Http\Request;
use App\Http\Controllers\API\FoundationController as Foundation;
use App\Models\{User};

use Validator;
use App\Http\Resources\UserHomeResource;


class HomeController extends Foundation
{
    //
    /**
     * Home controller
     * handles all request to home page
     * 
     * @return \illuminate\Http\Response
     */

     public function getHome(Request $request)
     {
         $validator = Validator::make($request->all(),[
             'email' => 'required|email',
         ]);

         if($validator->fails()){
             return $this->sendError('Validation error.', $validator->erros(), 412);
         }
    // get user details and relationships
         $user = User::where('email',$request->email)->first();
         if(is_null($user)){
             return $this->sendError('User account not found');
         }

         return $this->sendResponse('User details retrieved succesfully.', new UserHomeResource($user), 200);
     }

}
