<?php
/**
 * version 1.0
 * Author Oxygen Airy
 */


namespace App\Http\Controllers\Management;

use Illuminate\Http\Request;
use illuminate\Support\facades\Auth;

use App\Http\Controllers\Api\HelperController as Helper;
use App\Model\User;

use Validator;

class AuthController extends Controller
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

         }catch (\Exception $e)
         {
            return $this->sendError(['error', $e->getMessage()], $e->getCode() );
         }
     }
}
