<?php


/**
 * version 1.0
 * Author Oxygen Airy
 */


namespace App\Http\Controllers\API\Public;

use Illuminate\Http\Request;

use App\Http\Controllers\API\FoundationController as Foundation;
use App\Models\{User};
use App\Notifications\VerifyCode;

class NotificationController extends Foundation
{
    //
    $private $code;
    public function __construct($code)
    {
        $this->middleware('auth');
        $this->$code = $code;
    }

    public function sendNotVerifyCode()
    {
        $user = User::first();

        $details = [
            'greeting' => 'Hi '.$user->username,
            'message' => 'This is your verification code',
            'code' => $code,
            'thanks' => 'All thanks from OxyAiry Games. Wish you a plesant experience',
        ]

        //send notification
        $user->notify(new VerifyCode($details));

        return 'success';
    }
}
