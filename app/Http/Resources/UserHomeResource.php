<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Models\Challenge;

class UserHomeResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
         $isPending = $this->challenge === null ? []
                            : $this->challenge->where('status', 'pending')
                            ->orwhere('status', 'ongoing')
                            ->orwhere('status', 'awaiting')
                            ->get();
          
        return [
            'user_id' => $this->userid,
            'username' => $this->username,
            'email' => $this->email,
            'display' => $this->display,
            'role' => $this->role,
            'status' => $this->status,
            'token' => $this->account->tokens,
            'coins' => $this->account->coins,
            'energy' => $this->account->energy,
            'level' => $this->account->level,
            'referer_coins' => $this->account->referer_coins,
            'bot' => $this->bot->name,
            'bot_level' => $this->bot->level,
            'response' => $this->bot->response,
            'challenges' => $this->bot->challenges,
            'lost' => $this->bot->lost,
            'achievement' => $this->bot->achievement,
            //challenge data
            'pending' => count($isPending) > 0 ? true : false,
 
            //more to be added {bot,}
        ];
    }
}
