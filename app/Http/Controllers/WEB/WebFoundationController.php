<?php

namespace App\Http\Controllers\WEB;

use illuminate\Http\Request;
use App\Http\Controllers\Controller as Controller;

use App\Models\{User, Account, Action, History} ;

class WebFoundationController extends Controller
{
    /**
     * Success response method and 
     * 
     * @return \illuminate\Response
     */

     public function sendResponse($message, $result = [] )
     {
         $response = [
             'success' => true,
             'message' => $message,
         ];
         if(!empty($result)){
            $response['data'] = $result;
        }

         return response()->json($response, 200);
     }

     /**
      * failure response
      */

      public function sendError($error, $errorMessages = [], $code = 404)
      {
          $response = [
              'success' => false,
              'reason' => $error,
          ];
          if(!empty($errorMessages)){
              $response['data'] = $errorMessages;
          }

          if($code == 0 || $code > 500){
              $code = 500;
          }

          return response()->json($response, $code);
      }

      /**
       * generate random number
       * accept integer limit
       */
      public function generateRandom($type, $limit)
      {
          if($type === 'int'){
            $rand = substr(str_shuffle('0123456789'), 0, $limit);
          }else{
            $rand = substr(str_shuffle('123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ'), 0, $limit); 
          }
        
        return $rand;
      }

      /**
       * Attribute default value and bonus to actions performed
       */
      public function onAction($action){
        //get default value from db for actions
                $task = Action::where('event', $action)->first();
            return $task;
      }

      /**
       * Update history with actions performed. maximum 500 history per user
       * categories{user(singup,referer bonus), challenge(win, lose), event win loose }
       */

       public function addHistory($user, $data, $set )
       {      $max = 1; //maxmimum history allowed in table
           // get user account
                $acc = Account::where('userid', $user->userid)->first();
            // get account history
                $hist = History::where('userid', $user->userid)->get();
                if($hist->count() > $max){
            //delete oldest history input
                    History::where('userid', $user->userid)->oldest()->delete();
                }
            //add anew history to the table
                History::create([
                    'userid' => $user->userid,
                    'category' => $data->category,
                    'description' => $data->description,
                    'value' => $data->$set,
                    'balance' => $acc->$set,
                    'direction' => $data->direction ,
                ]);

       }    

       public function isHistory($user, $data )
       {
            if($data->coins > 0){
                $this->addHistory($user, $data, 'coins' );
            }
            if($data->tokens > 0){
                $this->addHistory($user, $data, 'tokens' );
            }
            if($data->tickets > 0){
                $this->addHistory($user, $data, 'tickets' );
            }
            if($data->energy > 0){
                $this->addHistory($user, $data, 'energy' );
            }
            if($data->level > 0){
                $this->addHistory($user, $data, 'level' );
            }
            if($data->referer_coins > 0){
                $this->addHistory($user, $data, 'referer_coins' );
            }
          
        }
    
}