<?php

namespace App\Http\Controllers\API\Management;

use Illuminate\Http\Request;
use illuminate\Support\facades\Auth;

use App\Http\Controllers\API\FoundationController as Foundation;
use App\Models\{User, Question, Option, Answer};

use Validator;
use App\Http\Resources\UserHomeResource;


class QuestionController extends Foundation
{
    /**
     * Add question to database
     * 
     * @return \illuminate\Http\Response
     */

     public function addQuestion(Request $request)
     {
        $validator = Validator::make($request->all(),[
            'title' => 'required',
            'category' => 'required',
            'type' => 'required',
            'level' => 'required',
            'question' => 'required',
            'opt1' => 'required',
            'opt2' => 'required',
            'opt3' => 'required',
            'opt4' => 'required',
            'hint' => 'required',
            'suggestion' => 'required',
            'answer' => 'required',
        ]);

        if($validator->fails()){
            return $this->sendError('Validation error.', $validator->errors(), 412);
        }
        $input = $request->all();
    //get user permission
        $user = Auth::user();// get authenticated user details
        if($user->role < 2){
            return $this->sendError('UnAuthorised access.\nYou do not have permission to perform that action',412);
        }
        $input['createdby'] = $user->userid;
    
    //check if a question with same id already exist
        $qChk = Question::where('title', $input['title'])->first();
        if(!is_null($qChk)){
            return $this->sendError('Duplicate error.', 'A question with same title already exist', 416);
        }

    //get last added question id and iterate/increment for new id
        $year = substr(date('Y'), 2);
        $month = substr(date('m'), 0);

        $lastId = Question::orderByDesc('id')->first();
        if(!is_null($lastId)){
            $tempId = substr($lastId['qid'],8);
            $tempId++;
            $input['qid'] = 'OC'.$year.$month.'QA'.$tempId;
        }else{
            //else set the default value counting starts at 1234
            $input['qid'] = 'OC'.$year.$month.'QA'.'1234';
        }

    //add questions to database
        Question::create([
            'qid' => $input['qid'],
            'title' => $input['title'],
            'category' => $input['category'],
            'type' => $input['type'],
            'level' => $input['level'],
            'question' => $input['question'],
            'created_by' => $input['createdby'],
        ]);
    //add question option to database
        Option::create([
            'qid' => $input['qid'],
            'opt1' => $input['opt1'],
            'opt2' => $input['opt2'],
            'opt3' => $input['opt3'],
            'opt4' => $input['opt4'],
            'hint' => $input['hint'],
            'suggestion' => $input['suggestion'],
        ]);
    //add question answer to database
        Answer::create([
            'qid' => $input['qid'],
            'answer' => $input['answer'],
        ]);
    
    //send success response reply
    $success['qid'] = $input['qid'];
    return $this->sendResponse('Question added successful', $success);

     }

}
