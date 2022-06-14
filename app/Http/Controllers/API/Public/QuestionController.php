<?php

namespace App\Http\Controllers\API\Public;

use Illuminate\Http\Request;
use illuminate\Support\facades\Auth;
use illuminate\Support\Str;

use App\Http\Controllers\API\FoundationController as Foundation;
use App\Models\{User, Question, Option, Answer, Log, TimerQuestion};



class QuestionController extends Foundation
{
    /**
     * Add question to database
     * 
     * @return \illuminate\Http\Response
     */

     public function detailQuestion($user, $val){
     // get the log of all the questions taken by the user
        $fetchLog = Log::where('userid', $user->userid)
                    ->get();
        $log = [];
        foreach($fetchLog as $lg){
            array_push($log, $lg->qid);
        }  

         $ques = Question::whereNotIn('qid', $log)
                        ->inRandomOrder()
                        ->limit($val)
                        ->get();
        
        //loop through to create a collection
            $collection = [];
            $defaultTime = 50;
            foreach($ques as $q){
                
                    $var['qid'] = $q->qid;
                    $var['title'] = $q->title;
                    $var['category'] = $q->category;
                    $var['type'] = $q->type;
                    $var['level'] = $q->level;
                    $var['time'] = ($q->level * 2) + $defaultTime;

                    array_push($collection, $var);
                
            }
            return $collection;

    /****  End get details*/
     }

     /**
      * Register question in timer_question table
      * for monitoring the limit and validity of the question
      * In otherwords, monitor the time required to submit the answer
      * requirement question as qid, event_id, userid as player , duration.
      */

      public function registerQuestion($details)
      {     
          $duration = 5; //extra time in min to submit before validity expires 3min
            $ques = Question::where('qid', $details['question'])
                        ->with('option')
                        ->with('answer')
                        ->first();
        //check if question is already registered
            $checkQ = TimerQuestion::where('question', $details['question'])
                        ->where('player', $details['player'])
                        ->first();
            if(is_null($checkQ)){
                // add details to timer question table
                TimerQuestion::create([
                    'event' => $details['challenge'],//event or challenge id
                    'player' => $details['player'],// userid of the current player
                    'question' => $details['question'],
                    'duration' => $duration, //extra time to submit before expire
                    'start' => now(),
                ]);
            }
        
        $defaultTime = 50;
        //return the full details of the question
        $collection['qid'] = $ques->qid;
        $collection['title'] = $ques->title;
        $collection['category'] = $ques->category;
        $collection['type'] = $ques->type;
        $collection['level'] = $ques->level;
        $collection['time'] = ($ques->level * 4) + $defaultTime;
        $collection['question' ]= $ques->question;
        $collection['opt1'] = $ques->option->opt1;
        $collection['opt2'] = $ques->option->opt2;
        $collection['opt3'] = $ques->option->opt3;
        $collection['opt4'] = $ques->option->opt4;
        $collection['hint'] = $ques->option->hint;
           // $var['suggestion'] = $q->option->suggestion;
           // $var['answer'] = $q->answer->answer;
            return $collection;

        //end register question 
      }

    /**
     * requirement qid as question, challenge_id as challenge, answer as answer,
     * whatPlayer(player1 or player 2), player(player_id),
     */
    public function answerQuestion($details)
    {   
        //check status/validity of question in database
            //check time interval
            $old = TimerQuestion::where('question', $details['question'])
                    ->where('player', $details['player'])
                    ->where('status', 'available')
                    ->first();
            //check if status available
                //question deleted from session 
            if(is_null($old)){
                return 'not found';
            }
        
        //check if time limit exceeded or question is valid
            $now = now();
            $start = $old->start;
            $duration = $old->duration; //default 5min
            $difference = $now->diff($start);
            if($difference->i > $duration){ //time expired
                //delete question
               TimerQuestion::where('question', $details['question'])->delete();
               return 'expired';
            }
            //if question is valid, time has not expired 
        // answer confirm answer
            $ans = Answer::where('qid', $details['question'])->first();

            if($ans->answer === $details['answer']){
            //delete question from timer table
                TimerQuestion::where('question', $details['question'])->delete();
                return 'correct';
            }else{
            //delete question from timer table
               TimerQuestion::where('question', $details['question'])->delete();
                return 'failed';
            }
        //if code reachech this point then there is an error
            return 'error';
    }

    /**
     * update score in the database
     * -requires boolean(if score increases or reduces)
     * @String either challenge or event id
     * 
     */
    
     public function updateScore($isCorrect, $challenge, $whatPlayer, $player)
     {
        //get the challenge details
            $chal = Challenge::where('challenge_id', $challenge)
                        ->with('challenge_results')
                        ->first();
        
            // check if challenge is valid
            if($chal->status != 'awaiting' || $chal->status != 'ongoing'){
                return $this->sendError('Challenge is not valid');
            }
        
            //check if maximum questions have been answered
            $thisPlayer = $whatPlayer === 'player1' ? 'player1_score' : 'player2_score';
            
            //process the score
            $upd = ChallengeResult::where($thisPlayer, $player)->first();
            if($isCorect){//if score is correct
                $score = $upd->$thisPlayer +1;
            }else{
                $score = $upd->$thisPlayer -1;
            }
            if($score < 0){
                $score = 0;
            }
            //update score
            $upd->score = $score;
            $upd->save();
            return true;

     }  

}
