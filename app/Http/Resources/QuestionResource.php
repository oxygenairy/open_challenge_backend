<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Models\Question;

class QuestionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        return 
        [
            'qid' => $this->qid,
            'title' => $this->title,
            'category' => $this->category,
            'level' => $this->level,
            'question' => $this->question,
            'opt1' => $this->option->opt1,
            'opt2' => $this->option->opt2,
            'opt3' => $this->option->opt3,
            'opt4' => $this->option->opt4,
            'hint' => $this->option->hint,
            'suggestion' => $this->option->suggestion,
            'answer' => $this->answer->answer,
        ];
    }
}
