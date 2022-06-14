<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'userid',
        'username',
        'email',
        'display',
        'password',
        'role',
        'referer',
        'status,'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        
    ];

//custom methods
    public function account()
    {
        return $this->hasOne(Account::class, 'userid', 'userid');
    }

    public function challenge()
    {
        return $this->hasOne(Challenge::class, 'player1', 'userid')
                    ->orwhere('player2', $this->userid);
    }

    public function event()
    {
        return $this->belongsTo(Account::class, 'eventid', 'userid');
    }

    public function log()
    {
        return $this->hasOne(Log::class, 'userid', 'userid');
    }

    public function history()
    {
        return $this->hasMany(History::class, 'userid', 'userid');
    }

    public function verification()
    {
        return $this->hasOne(Verification::class, 'userid', 'userid');
    }

    public function bot()
    {
        return $this->hasOne(Bot::class, 'userid', 'userid');
    }

//End of main class
}
