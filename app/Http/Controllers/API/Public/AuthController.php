<?php

/**
 * version 1.0
 * Author Oxygen Airy
 */


namespace App\Http\Controllers\API\Public;

use Illuminate\Http\Request;
use illuminate\Support\facades\Auth;

use App\Http\Controllers\API\FoundationController as Foundation;
use App\Http\Controllers\API\Public\NotificationController;

use App\Models\{User, Account, Bot, Verification, PasswordReset };

use Illuminate\Support\Str;
use Validator;

class AuthController extends Foundation
{
    /**
     * Register public api
     * 
     * @return \illuminate\Http\Response
     */

     public function register(Request $request)
     {  
         try
         {
            $validator = Validator::make($request->all(),
                [
                'username' => 'required|string|max:60',
                'email' => 'required|email',
                'password' => 'required|string|min:8',
                ]
            );
        
            if($validator->fails()){
                return $this->sendError('Register', $validator->errors(), 412);
            }
        //check if username already exist
            if(User::where('username', Str::lower($request['username']))->first()  != null){
                return $this->sendError('Username is taken', [], 412);
            }
        //check if email already exist
            if(User::where('email', Str::lower($request['email']))->first()  != null){
                return $this->sendError('Email already exist', [], 412);
            }
            /**
             * get last id, interate and set new user id
             */ 
            $year = substr(date('Y'), 2);
            $month = substr(date('m'), 0);

             $input = $request->all();
        //get previous user id and increment
             $lastId = User::orderByDesc('id')->first();
             if(!is_null($lastId)){
                 $tempId = substr($lastId['userid'],8);
                 $tempId++;
                 $input['userid'] = 'OC'.$year.$month.'PL'.$tempId;
             }else{
                 //else set the default value counting starts at 1234
                 $input['userid'] = 'OC'.$year.$month.'PL'.'1234';
             }
        // add new user details to database
             $input['password'] = bcrypt($input['password']);
             if(!is_null($request->referer)){
                 $input['referer'] = Str::lower($request->referer);
             }
             User::create($input);

         //generate random verification code to be emailed to the user
            $verify_code = $this->generateRandom('int', '6');
        // add new codes to verification table
            Verification::create([
                 'userid' => $input['userid'],
                 'code' => $verify_code,
             ]);
       
        // email the code to user
        /**
         * here we will implement notification send to email later
         */
             $not = new NotificationController();
             $not->sendNotVerifyCode($verify_code);
             

        // send response back to frontend
            // $success['token'] = $user->createToken('challenge')->plainTextToken;
            //we dont need token since user wont auto login after Re
            $success['userid'] = $input['userid'];
            return $this->sendResponse('Player registration successful.\nPlease check your email and enter the verification code', $success);

         }
         catch (\Exception $e)
         {
            return $this->sendError('Registration error', $e->getMessage(), $e->getCode() );
         }
     }// End register function

     /**
     * Register public api
     * 
     * @return \illuminate\Http\Response
     */

    public function login(Request $request)
    {
        try
        {
        //check if email exist on server
            $chk = User::where('email', $request->email)->first();
            if(is_null($chk)){
                return $this->sendError('User Account not found',);
            }

            if(Auth::attempt(
                ['email' => $request->email, 'password' => $request->password]
             )){
                $user = Auth::user();// get authenticated user details

                //verification check
                if($user->status === 'pending'){
                    $success['status'] = 'pending';
                    $success['userid'] = $user->userid;
                    return $this->sendResponse('Account is not yet verified.\nPlease enter the verification code sent to your email', $success,);

                }

                //verification check complete & user is verified
                    $success['token'] = $user->createToken('challengeApi')->plainTextToken;
                    $success['username'] = $user->username;
                    $success['status'] = 'verified';

                    $user->update([
                        'last_login_at' => now()
                    ]);

                    return $this->sendResponse('Player login successful\nYou will be redirected soon.', $success, );
               }
               else{
                   throw new \Exception('Password does not match', 422);
               }

        }catch (\Exception $e)
        {
           return $this->sendError('Login error', $e->getMessage(), $e->getCode() );
        }
    }

    /**
     * Verify code activate account
     */

     public function verifyAccount(Request $request)
     {
         try
         {
            $validator = Validator::make($request->all(),
            [
               'userid' => 'required|string|max:60',
               'code' => 'required|string|max:6',
            ]);

            if($validator->fails()){
                return $this->sendError('Verify account error', $validator->errors(), 412);
            }
            
            $verifier = Verification::where('userid', $request['userid'])->first();

//to be updated Check if user is register in verification table
            if (!$verifier):
                throw new \Exception('Sorry,\nAccount not found.\nPlease register before you can verify account', 404);
            endif;
        //check if activation code is correct
            if($verifier->code != $request['code']){
                return $this->sendError('Invalid code\n Enter correct code.'); 
            }

     //if validation is successfull update user status to active and delete value from verication table
        // update user status  
            $user = User::where('userid', $request['userid'])->first();
            $user->status = 'verified';
            $user->save();
 
        // delete user detaials from verification table
            Verification::where('userid', $request['userid'])->delete();
        
        // add user account details to the account table
            $regbonus = $this->onAction('register');
            Account::create([
                'userid' => $request['userid'],
                'tokens' => $regbonus->tokens,
                'coins' => $regbonus->coins,
                'energy' => $regbonus->energy,
                'tickets' => $regbonus->tickets,
                'level' => $regbonus->level,
                'referer_coins' => $regbonus->referer_coins,
            ]);
        //update individual history
        $this->isHistory($user, $regbonus);
        
        //Create data for assistant bot and achievements
            Bot::create([
                'userid' => $request['userid'],
                'level' => 0,
                'challenges' => 0,
                'lost' => 0,
                'achievement' => 0,
            ]);

        // return success message
            return $this->sendResponse('Success.\nPlayer account Verified!.');

         }
         catch (\Exception $e)
         {
            return $this->sendError('Validation error', $e->getMessage(),  );
         }
     }

     /**
     * Forgot Password request
     */
     public function forgotPassword(Request $request){
        try
        {
           $validator = Validator::make($request->all(),
               [
               'email' => 'required|email',
               ]
           );

           if($validator->fails()){
            return $this->sendError('Forgot password', $validator->errors(), 412);
        }
    //check if username already exist
        if(User::where('email', $request['email'])->first()  === null){
            return $this->sendError('Password request failed.\nAccount not found', [], 412);
        }
    // generate reset token
         $reset_code = $this->generateRandom('string', '6');
    // add a request field in password_reset table
            $verify = PasswordReset::create([
                'email' => $request['email'],
                'token' => $reset_code,
            ]);
    // send response back to frontend
            return $this->sendResponse('A password request code has been sent to your email.\n please enter it to reset your password.', [], );
            
        }
        catch(\Exception $e)
        {
            return $this->sendError('password request error', $e->getMessage(), $e->getCode() );
        }

    }

    /**
     * reset password with code
     */

     public function resetPassword(Request $request)
     {
        try
        {
           $validator = Validator::make($request->all(),
               [
               'email' => 'required|email',
               'password' => 'required|min:8',
               'code' => 'required|string|max:6',
               ]
           );

           if($validator->fails()){
            return $this->sendError('Reset password error', $validator->errors(), 412);
             }
    //check if email exists
            $reset = PasswordReset::where('email', $request['email'])->first();
            if($reset  === null){
                return $this->sendError('Cannot reset password.\nAccount does not exist', [], 412);
            }
    // check if reset code is correct
            if($reset->token != $request['code'])
            {
                return $this->sendError('reset code is not correct', [], 412);
            } 
    // update password in user table
            $user = User::where('email', $request['email'])->first();
            $encrypt = bcrypt($request['password']);
            $user->password = $encrypt;
            $user->save();
    // delete password request field(s) belonging to user from password table
            PasswordReset::where('email', $request['email'])->delete();

    // send response back to frontend
            return $this->sendResponse('Password has been reset successful', [], );
            
        }
        catch(\Exception $e)
        {
            return $this->sendError('password reset error', $e->getMessage(), $e->getCode() );
        }
     }

     /**
      * change password after logged in
      */
    
      public function changePassword(Request $request){
          $validator = Validator::make($request->all(),
               [
               'email' => 'required|email',
               'password' => 'required|min:8',
               'new-password' => 'required|min:8',
               ]
           );
           
           if($validator->fails()){
            return $this->sendError('Password change error', $validator->errors(), 412);
             }

        try
        {

            if(Auth::attempt(
                ['email' => $request->email, 'password' => $request->password]
             )){
                $user = Auth::user();// get authenticated user details
        //update password with new password
             $user->password = bcrypt($request['new-password']);
             $user->save();
        //send success message
             return $this->sendResponse('Password changed successfully', [], );

             }else{
                throw new \Exception('You are not Authorised to make this change', 422);
             }
        }
        catch(\Exception $e)
        {
            return $this->sendError('Change password error', $e->getMessage(), $e->getCode() );
        }

      }

      /**
     * Request new code incase player did not receive first one
     */
     public function newCode(Request $request){
        try
        {
           $validator = Validator::make($request->all(),
               [
               'email' => 'required|email',
               ]
           );

           if($validator->fails()){
            return $this->sendError('Code request', $validator->errors(), 412);
        }
    //check if user has an account
        if(User::where('email', $request['email'])->first()  === null){
            return $this->sendError('Failed.\nAccount not found', [], 414);
        }
    //get the object in the password reset table
        $old = PasswordReset::where('email', $request['email'])->first();
    //check if a request code exist in password reset table
        if($old  === null){
            return $this->sendError('You cannot request a new code without an old one.', [], 414);
        }
    //check if time interval of 5 min have elapse btw code request
        $now = now();
        $prev = $old->created_at;
        $interval = $now->diff($prev);
        //return $interval->format('%Y yeaars %m months %d days %H hours %i minutes %s seconds');
        $timeLeft = 5 - $interval-> i;
        if($timeLeft <= 5 && $timeLeft > -1){
            return $this->sendResponse($prev.' You have to wait '.$timeLeft.' minute to request a new code',);
        }
    // generate reset token
         $new_code = $this->generateRandom('string', '6');
        
         //send code to email
         $not = new NotificationController();
         $not->sendNotVerifyCode($new_code);
    
    // update reset code with new value
         $old->token = $new_code;
         $old->save();
    // send response back to frontend
         return $this->sendResponse('A new request code has been sent to your email.\n please enter it to reset your password.', [], );
            
        }
        catch(\Exception $e)
        {
            return $this->sendError('password request error', $e->getMessage(), $e->getCode() );
        }

    }


//End Authentication class
}
