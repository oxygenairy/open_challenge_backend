<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Question extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function option()
        {
            return $this->hasOne(Option::class, 'qid', 'qid');
        }

    public function answer()
        {
            return $this->hasOne(Answer::class, 'qid', 'qid');
        }

}