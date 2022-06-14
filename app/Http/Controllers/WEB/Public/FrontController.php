<?php

namespace App\Http\Controllers\WEB\Public;

use Illuminate\Http\Request;

use App\Http\Controllers\WEB\WebFoundationController as WebFoundation;
use App\Medels\User;

class FrontController extends WebFoundation
{
    //

    public function Junction()
    {
        $num = $this->generateRandom('string', 9);
        return $this->sendResponse('Correct..!\nYou are a good programmer', [
            'Programmer' => 'Oxygen Airy',
            'email' => 'asukuismail2019@gmail.com',
            'number' => $num,
        ]);
    }
}
