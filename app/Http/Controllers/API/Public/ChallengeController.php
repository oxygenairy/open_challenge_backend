<?php

namespace App\Http\Controllers\API\Public;

use Illuminate\Http\Request;
use illuminate\Support\facades\Auth;

use App\Http\Controllers\API\FoundationController as Foundation;
use App\Http\Controllers\API\Public\QuestionController;
use App\Models\{User, Account, Bot, Challenge, ChallengeAccount, ChallengeResult, EventAttrib, House};

use Validator;


class ChallengeController extends Foundation
{
    /**
     * create challenges and filter and set questions for challenges
     * 
     * requirement: player, title, amount
     * @return \illuminate\Http\Response
     */

     public function createOneVOne(Request $request)
     {
         // create setup 1v1 challenge
         $validator = Validator::make($request->all(),[
            'title' => 'required',
            'amount' => 'required',
        ]);

        if($validator->fails()){
            return $this->sendError('Validation error.', $validator->errors(), 412);
        }
        $input = $request->all();

        //get Authenticated user details
        $user = Auth::user();

        $input['player1'] = $user->userid;
        $input['status'] = 'pending';
    
        /**
        * set expiry time to 2 hours from now
        * user must complete challenge before
        */ 
        $expir = 2;
        $input['expiry'] = now()->addhours($expir); //expires in 2 hours if all question not asnwered (coin lost)

    
         /**
          * check if a challenge is pending
          * meaning user havnt answered all question
          */
         $isPending = Challenge::where('player1', $user->userid)
                        ->where('status', 'pending')->first();
         $isPending1 = ChallengeResult::where('challenge_id', $isPending->challenge_id ?? '')
                        ->where('status', 'pending')->first();

         if(!is_null($isPending)){//user have a challenge with incomplete questions
            $now = now();
            $rem = $now->diff($isPending->expiry);
            $data['challenge'] = $isPending->challenge_id;
            $data['expiry'] = 'Time left before challenge expires : '. $rem->h. 'hour(s):'.$rem->i.'min(s)';
            $data['progress'] = $isPending1->player1_progress.'/'.$isPending1->total_question. ' questions left.';
            return $this->sendError('You must complete your pending challenge before you create a new one.', $data);
         }

        /**
         * creating a new challenge
        * get last id, iterate and set new challenge id
        */ 

            $year = substr(date('Y'), 2);
            $month = substr(date('m'), 0);

            $lastId = Challenge::orderByDesc('id')->first();

            if(!is_null($lastId)){
                $tempId = substr($lastId['challenge_id'], 8);
                $tempId++;
                $input['challenge_id'] = 'OC'.$year.$month.'CL'.$tempId;
            }else{
                //else set the default value counting starts at 1234
                $input['challenge_id'] = 'OC'.$year.$month.'CL'.'1234';
            }


         $attrib = EventAttrib::where('type', '1v1')->first();// get attributes for challenge 1v1

            
        // deduct the reqired amount from user coin balance in in account.
            $requirement['challenge'] = $input['challenge_id'];
            $requirement['amount'] = $input['amount'];
            $requirement['player'] = $input['player1'];
            $requirement['which_player'] = 'player1';
            $requirement['percent'] = $attrib->house_percent;

            $deduct = $this->deductPlayer($requirement);
            if($deduct === 'not found'){
                return $this->sendError('This account is not found. please login to continue');
            }
            if($deduct === 'insufficient'){
                return $this->sendError('You do not have enough coins to create this challenge');
            }
            if($deduct != 'success'){
                return $this->sendError('Error with account transactions, please try again', $deduct);
            }
        
        /**
         * create new challenge for
         * present request and 
         * set status to pending
         */
         Challenge::create($input);
         ChallengeResult::create([
            'challenge_id' => $input['challenge_id'],
            'total_question' =>  $attrib->max,
            'player1_score' => 0,
            'player1_progress' => 0,
            'player1_bot' => 0,
            'player1_time' => 0,
            'status' => 'pending',
        ]);
            

         //add value to request object
         $request->request->add(
             [
                 'challenge' => $input['challenge_id'],
                 'which_player' => 'player1',
                 'player' => $input['player1'],
             ]
            );

         
          $res = $this->pl_GetNext($request);

        
        return $this->sendResponse('Challenge created successfully.\nAnswer all questions before time ('.$expir.' hours) expires or your coin will be lost', $res);

     }

     /**
      * Next get the next question details
      * requires challenge_id as challenge
      */

      public function pl_GetNext(Request $request)
     {
         // create setup 1v1 challenge
            $validator = Validator::make($request->all(),[
                'challenge' => 'required',
                'which_player'=> 'required',
                'player' => 'required',
            ]);
            
            if($validator->fails()){
                return $this->sendError('Get next Validation error.', $validator->errors(), 412);
            }
            //$input = $request->all();

        // check if challenge has not exceeded its maximum limit
             $chal = ChallengeResult::where('challenge_id', $request->challenge)->first();
             if(is_null($chal)){
                 return $this->sendError('challenge not found');
             }
        //switch/determine which player
             if($request->which_player === 'player1'){
                 $playerProgress = 'player1_progress';
             }else{
                $playerProgress = 'player2_progress';
             }
             
             $limit = $chal->total_question; //how many questioned allowed
             $cur = $chal->$playerProgress; //player's current progress
            if($cur === $limit || $cur > $limit){
                return $this->sendError('You have exceeded the max limit of questions for this challenge.',);
            }

        //get Authenticated user details
            $user = Auth::user();

            if($request->player != $user->userid){
                return $this->sendError('Authentication error.\n Player mismatch.\nPlease login again before proceeding');
            }

            $attrib = EventAttrib::where('type', '1v1')->first(); //get 1v1 attribute
            $acquire = new QuestionController();
            $question = $acquire->detailQuestion($user, $attrib->sequence);

            $data['challenge'] = $request->challenge;
            $data['question'] = $question;
           
        // return to frontend the questions
    
        return $data;

     }

    /**
     * request for full question
     * and start timer in register
     */
     public function plONE_Request(Request $request)
     {
        
            $validator = Validator::make($request->all(),[
                'challenge' => 'required',
                'question' => 'required',
                'player' => 'required',
            ]);
            if($validator->fails()){
                return $this->sendError('Validation error.', $validator->errors(), 412);
            }
            $input = $request->all();
            $user = Auth::user();
            if($request->player != $user->userid){
                return $this->sendError('Authentication error.\n Player mismatch.\nPlease login again before proceeding');
            }
        //register question and start timer
            $details['question'] = $input['question'];
            $details['player'] = $input['player'];
            $details['challenge'] = $input['challenge'];

            //question object
            $acquire = new QuestionController();
            $fullDetails = $acquire->registerQuestion($details);

        //return full question to frontend
            
            return $this->sendResponse('Answer the question before timer runs out', $fullDetails);

     }
     /**
      * player one answers the required questions
      * and result is updated in the challenge table
      */

      public function plONE_Response(Request $request)
     {
        // receive array of questions
            $validator = Validator::make($request->all(),[
                'challenge' => 'required',
                'player' => 'required',
                'question' => 'required',
                'answer' => 'required',
                'time' => 'required',
            ]);

            if($validator->fails()){
                return $this->sendError('Challenge error', $validator->errors(), 412);
            }
            $input = $request->all();
            //verify logged in player
                $user = Auth::user();
                if($request->player != $user->userid){
                    return $this->sendError('Authentication error.\n Player mismatch.\nPlease login again before proceeding');
                }

            //get challenge reference
                $progress = ChallengeResult::where('challenge_id', $input['challenge'])->first();
                if(is_null($progress)){
                    return $this->sendError('Unknown error!.\nChallenge not found');
                }
                if($progress->status === 'awaiting'){
                    return $this->sendError('You cannot answer anymore question in this challenge.\nNo more access.');
                }
                if($progress->status === 'taken'){
                return $this->sendError('This challenge has been accepted by another player\nYou cannot alter.');
                }
            
            //cache
                $curProgress = $progress->player1_progress;
                $curScore = $progress->player1_score;
                $tot = $progress->total_question;
            //set expiry
                $expr = now()->addhours(8); // 8 hours waiting time in the list for a challenger before expiry.
           
            //Process response and answer questions appropriately
                $Qcont = new QuestionController();
                $processQ = [
                    'player' => $input['player'],
                    'question' => $input['question'],
                    'answer' => $input['answer'],
                ];
                //confirm answer supplied
                $result = $Qcont->answerQuestion($processQ);

                if($result === 'not found'){
                /**
                 * question does not exist anymore
                 * if question is at limit.(answered required questions);
                 *  set player one status to awaiting (player 2)
                 */
                    if($curProgress === $tot || $curProgress > $tot){
                        
                        return $this->sendResponse('All questions have been attempted.\nThe question does not exist anymore.');
                    }

                    return $this->sendError('The question does not exist anymore. You either exceeded the time limit or you have already cleared it.\nCannot be found.');
                }
                if($result === 'expired'){
                $curProgress++;
                //calculate average time
                    if($curProgress < 2){
                        $avg = $request->time;
                    }else{
                        $avg = ($progress->player1_time + $request->time) / 2;
                    }
                
                //if progress exceeds total
                if($curProgress > $tot){
                    return $this->sendResponse('You have completed all the challenge requirements');
                }
            /*if the challenge has been completed
            * if question is at limit.(answered required questions);
            *  set player one status to awaiting (player 2)
            */
                if($curProgress === $tot ){
                    //update the challenge status to awaiting
                    Challenge::where('challenge_id', $input['challenge'])->first()
                        ->update([
                            'status' => 'awaiting',
                            'expiry' => $expr,
                        ]);
                    $progress->update([
                        'player1_progress' => $curProgress,
                        'player1_time' => $avg,    
                        'status' => 'awaiting',
                    ]);
                    return $this->sendResponse('Time has expired, You lost this question.!\nAll questions have been attempted');
                }
                /**
                 * question has expired, update the progress of challenge
                 * update the progress but dont update the score
                 */
                    $progress->update([
                        'player1_progress' => $curProgress,
                        'player1_time' => $avg,
                    ]);    
                    return $this->sendResponse('Time has expired, You lost this question.');
                }
            if($result === 'failed'){
                $curProgress++;
                //calculate average time
                if($curProgress < 2){
                    $avg = $request->time;
                }else{
                    $avg = ($progress->player1_time + $request->time) / 2;
                }
                
                //if progress exceeds total
                    if($curProgress > $tot){
                        return $this->sendResponse('You have completed all the challenge requirements');
                    }
                /*if the challenge has been completed
                * if question is at limit.(answered required questions);
                 *  set player one status to awaiting (player 2)
                 */
                    if($curProgress === $tot ){
                        //update the challenge status to awaiting
                        Challenge::where('challenge_id', $input['challenge'])->first()
                            ->update([
                                'status' => 'awaiting',
                                'expiry' => $expr,
                            ]);
                        $progress->update([
                            'status' => 'awaiting',
                            'player1_progress' => $curProgress,
                            'player1_time' => $avg,
                        ]);
                        return $this->sendError('Failed.\nYou did not get the right answer!\nYou have completed all the challenges');
                    }

                 /**
                 * question has expired, update the progress of challenge
                 * update the progress but dont update the score
                 */
                    $progress->update([
                        'player1_progress' => $curProgress,
                        'player1_time' => $avg,
                    ]);

                    return $this->sendError('Failed.\nYou did not get the right answer');
            
            }
            if($result === 'correct'){
                $curProgress++;
                $curScore++;
                //calculate average time
                if($curProgress < 2){
                    $avg = $request->time;
                }else{
                    $avg = ($progress->player1_time + $request->time) / 2;
                }

                
                //if progress exceeds total
                if($curProgress > $tot){
                    return $this->sendResponse('You have completed all the challenge requirements');
                }
            /*if the challenge has been completed
            * if question is at limit.(answered required questions);
            *  set player one status to awaiting (player 2)
            */
                if($curProgress === $tot ){
                    //update the challenge status to awaiting
                    Challenge::where('challenge_id', $input['challenge'])->first()
                        ->update([
                            'status' => 'awaiting',
                            'expiry' => $expr,
                        ]);
                    $progress->update([
                        'player1_progress' => $curProgress,
                        'player1_score' => $curScore,
                        'player1_time' => $avg,
                        'status' => 'awaiting',
                    ]);
                    return $this->sendResponse('Correct. Weldone!\nYou have completed all the challenges.\nCheck your email for the result.');
                }

                    /**
                     * question is correct, update the progress of challenge
                     * update the progress, also update the score
                     */
                    $progress->update([
                        'player1_progress' => $curProgress,
                        'player1_score' => $curScore,
                        'player1_time' => $avg,
                    ]);

                return $this->sendResponse('Correct. Weldone!');
            
            }

            //calculate the average time
            $currentTurn = $progress->player1_progress;

            
    }

    /**
     * -----------------------------------------------------------------------
     * Player 2 accepts challenge of player one
     * from list of challenges
     * -----------------------------------------------------------------------
     */

    public function acceptOnevOne(Request $request)
    {
        // create setup 1v1 challenge
            $validator = Validator::make($request->all(),[
                'challenge' => 'required',
                'player' => 'required',
             ]);

            if($validator->fails()){
                return $this->sendError('Acceptance validation error.', $validator->errors(), 412);
            }

       //get Authenticated user details
            $user = Auth::user();
            if($request->player != $user->userid){
                return $this->sendError('Authentication error.\n Player mismatch.\nPlease login again before proceeding');
            }
       
       /**
        * Update challenge and challenge result 
        * with player 2 info
        * update status
        */
        
         //update challenge status to taken(player 2 has taken up the challenge)
            $chk = Challenge::where('challenge_id', $request->challenge)
                        ->where('status', 'awaiting')->first();
            if(is_null($chk)){
                    return $this->sendError('This challenge has already been taken or doesn\'t exist anymore.\n Check your list of pending challenges.');
                }
        /**
         * process the amount from player account
         */

        // deduct the reqired amount from user coin balance in in account.
            $requirement['challenge'] = $request->challenge;
            $requirement['amount'] = $chk->amount;
            $requirement['player'] = $request->player;
            //$requirement['which_player'] = 'player2';
           

            $deduct = $this->deductPlayer($requirement);
            if($deduct === 'not found'){
                return $this->sendError('This account is not found. please login to continue');
            }
            if($deduct === 'insufficient'){
                return $this->sendError('You do not have enough coins to create this challenge');
            }
            if($deduct != 'success'){
                return $this->sendError('Error while deducting, please try again', $deduct);
            }
        

            //set expiry
            $expir = 2;
            $expr = now()->addhours($expir); // 2 hours to complete all questions.

            //update preferences
            $chk->update([
                            'player2' => $user->userid,
                            'status' => 'taken',
                            'expiry' => $expr,
                        ]);
            ChallengeResult::where('challenge_id', $request->challenge)
                        ->where('status', 'awaiting')
                        ->first()
                        ->update([
                            'status' => 'taken',
                        ]);

        //add value to request object
         $request->request->add(['which_player' => 'player2',]);
        // get question details
             $res = $this->pl_GetNext($request);
            
             //$request->request->add(['question' => $res['question'][0]['qid']]);//add new question id to Request data;
        
        // get full question details
          //  $fullD = $this->plTWO_Request($request);

       /*
        $attrib = EventAttrib::where('type', '1v1')->first();
        $acquire = new QuestionController();
        $question = $acquire->detailQuestion($user, $attrib->sequence);
        $data['question'] = $question;
        */
       return  $this->sendResponse('Challenge accepted successfully.\nAnswer all questions before time ('.$expir.' hours) expires and challenge closed', $res);

    }

    /**
     * request for full question
     * and start timer in register
     */
    public function plTWO_Request(Request $request)
    {
       // create setup 1v1 challenge
            $validator = Validator::make($request->all(),[
                'challenge' => 'required',
                'question' => 'required',
                'player' => 'required',
            ]);
            if($validator->fails()){
                return $this->sendError('Validation error.', $validator->errors(), 412);
            }
            $input = $request->all();
            $user = Auth::user();
            if($request->player != $user->userid){
                return $this->sendError('Authentication error.\n Player mismatch.\nPlease login again before proceeding');
            }

        //register and start timer
            $details['question'] = $input['question'];
            $details['player'] = $input['player'];
            $details['challenge'] = $input['challenge'];

            //question object
            $acquire = new QuestionController();
            $fullDetails = $acquire->registerQuestion($details);

        //return full question to frontend
            
            return $this->sendResponse('Answer the question before timer runs out', $fullDetails);
        }

      /**
      * player one answers the required questions
      * and result is updated in the challenge table
      */

      public function plTWO_Response(Request $request)
      {
         // receive array of questions
             $validator = Validator::make($request->all(),[
                 'challenge' => 'required',
                 'player' => 'required',
                 'question' => 'required',
                 'answer' => 'required',
                 'time' => 'required',
             ]);
 
             if($validator->fails()){
                 return $this->sendError('1v1 Challenge error', $validator->errors(), 412);
             }
             //get Authenticated user details
            $user = Auth::user();

            if($request->player != $user->userid){
                return $this->sendError('Authentication error.\n Player mismatch.\nPlease login again before proceeding');
            }

             $input = $request->all();
 
             $progress = ChallengeResult::where('challenge_id', $input['challenge'])->first();
                if(is_null($progress)){
                    return 'Unknown error!.\nChallenge not found.';
                }
            
            //cache
                $curProgress = $progress->player2_progress;
                $curScore = $progress->player2_score;
                $tot = $progress->total_question;
            //set expiry
                //$expr = now()->addhours(2); // 2 hours waiting time for challenger to complete before challenge is closed.

            //Process response and answer questions appropriately
                $Qcont = new QuestionController();
                $processQ = [
                    'player' => $input['player'],
                    'question' => $input['question'],
                    'answer' => $input['answer'],
                ];
            //confirm answer supplied
                $result = $Qcont->answerQuestion($processQ);

             if($result === 'not found'){
                 /**
                  * question does not exist anymore
                  * if question is at limit.(answered required questions);
                  *  set player one status to awaiting (player 2)
                  */
                  if($curProgress === $tot || $curProgress > $tot){
                        
                    return $this->sendResponse('All questions have been attempted.\nThe question does not exist anymore.');
                  }

                return $this->sendError('The question does not exist anymore. You either exceeded the time limit or you have already cleared it.\nCannot be found.');
             }
             //expired
             if($result === 'expired'){
                 $curProgress++;

                //calculate average time
                 if($curProgress < 2){
                    $avg = $request->time;
                 }else{
                    $avg = ($progress->player2_time + $request->time) / 2;
                 }
                 
                 //if progress exceeds total
                    if($curProgress > $tot){
                        return $this->sendResponse('You have completed all the challenge requirements');
                    }
                 //if the challenge has been completed
                    if($curProgress === $tot ){
                        //update the challenge status to completed
                        Challenge::where('challenge_id', $input['challenge'])->first()
                            ->update([
                                'status' => 'completed',
                            ]);
                        $progress->update([
                            'player2_progress' => $curProgress,
                            'player2_time' => $avg,
                            'status' => 'completed',
                        ]);
                        
                        //process the winner
                        $result = [
                            'challenge' => $progress->challenge_id,
                            'player1' => $progress->player1_score,
                            'player1_time' => $progress->player1_time,
                            'player2' => $progress->player2_score,
                            'player2_time' => $progress->player2_time,
                        ];
                        $winr = $this->setWinner($result);
                            if($winr === 'error'){
                                $this->sendError('If you see this message, then something has broken that we do not know of.\nPlease contact the administrator');
                            }elseif($winr === 'draw'){
                                //refund both party coins

                            }else{
                               $winner = [
                                   'challenge' => $progress->challenge_id,
                                   'winner' => $winr,
                               ];
                               $cred = $this->creditPlayer($winner);

                               if($cred === 'not found'){
                                   return $this->sendError('Problem with user account.\nCannot process, please contact the administrator');
                               }elseif($cred === 'closed'){
                                    return $this->sendError('This challenge has already been concluded.\nCheck your account history for result.');
                               }elseif($cred === 'success'){
                                return $this->sendError('Expired!.\nTime expired, You lost this question.\nYou have completed all the challenge requirements.\nYour result will be sent to your email.');
                               }else{
                                return $this->sendError('Unknown Error.');
                               }
                       
                        }
                   
                    }
                     /**
                     * question has expired, update the progress of challenge
                    * update the progress but dont update the score
                    * if question is at limit.(answered required questions);
                    *  set player one status to awaiting (player 2)
                    */

                    $progress->update([
                        'player2_progress' => $curProgress,
                        'player2_time' => $avg,
                    ]);
                    return $this->sendResponse('Time has expired, You lost this question.');
            }
            //failed question
             if($result === 'failed'){
                 $curProgress++;
                 //calculate average time
                    if($curProgress < 2){
                        $avg = $request->time;
                    }else{
                        $avg = ($progress->player2_time + $request->time) / 2;
                    }

                //if progress exceeds total
                    if($curProgress > $tot){
                        return $this->sendResponse('You have completed all the challenge requirements');
                    }

                 //if the challenge has been completed
                    if($curProgress === $tot ){
                        //update the challenge status to completed
                        Challenge::where('challenge_id', $input['challenge'])->first()
                            ->update([
                                'status' => 'completed',
                            ]);
                        $progress->update([
                            'player2_progress' => $curProgress,
                            'player2_time' => $avg,
                            'status' => 'completed',
                        ]);
                        //process the winner
                        $result = [
                            'challenge' => $progress->challenge_id,
                            'player1' => $progress->player1_score,
                            'player1_time' => $progress->player1_time,
                            'player2' => $progress->player2_score,
                            'player2_time' => $progress->player2_time,
                        ];
                        $winr = $this->setWinner($result);
                            if($winr === 'error'){
                                $this->sendError('If you see this message, then something has broken that we do not know of.\nPlease contact the administrator');
                            }elseif($winr === 'draw'){
                                //refund the coins to both parties
                            }else{
                               $winner = [
                                   'challenge' => $progress->challenge_id,
                                   'winner' => $winr,
                               ];
                               $cred = $this->creditPlayer($winner);

                               if($cred === 'not found'){
                                   return $this->sendError('Problem with user account.\nCannot process, please contact the administrator');
                               }elseif($cred === 'closed'){
                                    return $this->sendError('This challenge has already been concluded.\nCheck your account history for result.');
                               }elseif($cred === 'success'){
                                return $this->sendError('Failed. \nYou did not get the right answer!\nYou have completed all the challenge requirements.\nYour result will be sent to your email.');
                               }else{
                                return $this->sendError('Unknown Error.');
                               }
                 }
             
                    }
                    /**
                     * question has expired, update the progress of challenge
                    * update the progress but dont update the score
                    * if question is at limit.(answered required questions);
                    *  set player one status to awaiting (player 2)
                    */
                    $progress->update([
                        'player2_progress' => $curProgress,
                        'player2_time' => $avg,
                    ]);
 
                    return $this->sendError('Failed.\nYou did not get the right answer');
            }
             if($result === 'correct'){
                 $curProgress++;
                 $curScore++;
                 //calculate average time
                    if($curProgress < 2){
                        $avg = $request->time;
                        }else{
                            $avg = ($progress->player2_time + $request->time) / 2;
                        }
                 
                 //if progress exceeds total
                    if($curProgress > $tot){
                        return $this->sendResponse('You have completed all the challenge requirements');
                    }
                 //if the challenge has been completed
                    if($curProgress === $tot ){
                     //update the challenge status to completed
                        Challenge::where('challenge_id', $input['challenge'])->first()
                            ->update([
                                'status' => 'completed',
                            ]);
                        $progress->update([
                            'player2_progress' => $curProgress,
                            'player2_score' => $curScore,
                            'player2_time' => $avg,
                            'status' => 'completed',
                        ]);
                        //process the winner
                        $result = [
                            'challenge' => $progress->challenge_id,
                            'player1' => $progress->player1_score,
                            'player1_time' => $progress->player1_time,
                            'player2' => $progress->player2_score,
                            'player2_time' => $progress->player2_time,
                        ];
                        $winr = $this->setWinner($result);
                            if($winr === 'error'){
                                $this->sendError('If you see this message, then something has broken that we do not know of.\nPlease contact the administrator');
                            }elseif($winr === 'draw'){

                            }else{
                               $winner = [
                                   'challenge' => $progress->challenge_id,
                                   'winner' => $winr,
                               ];
                               $cred = $this->creditPlayer($winner);

                               if($cred === 'not found'){
                                   return $this->sendError('Problem with user account.\nCannot process, please contact the administrator');
                               }elseif($cred === 'closed'){
                                    return $this->sendError('This challenge has already been concluded.\nCheck your account history for result.');
                               }elseif($cred === 'success'){
                                return $this->sendResponse('Correct. Weldone!\nYou have completed all the challenge requirements.\nYour result will be sent to your email.');
                               }else{
                                return $this->sendError('Unknown Error.');
                               }
                                
                            }
             
             }
              /**
               * update scores and progress
               */
                  $progress->update([
                    'player2_progress' => $curProgress,
                    'player2_score' => $curScore,
                    'player2_time' => $avg,
                ]);
             return $this->sendResponse('Correct. Weldone!');
            }
    }
     /***
      * Calculate winner of 1v1 challenge
      * determine draw and send appropriate 
      * results
      */
 
      public function setWinner($results){
      //requirement: challenge id, playe1 score, player 2 score, player1 time, player2 time
         $player1 = $results['player1'];
         $player1_time = $results['player2_time'];
         $player2 = $results['player2'];
         $player2_time = $results['player2_time'];
         $challenge = Challenge::where('challenge_id', $results['challenge'])->first();
     
      // get bot details of both players
        $bot1 = Bot::where('userid', $challenge->player1)->first();
        $bot2 = Bot::where('userid', $challenge->player2)->first();
        // check for draw
            if($player1 === $player2){
                //draw and check time difference
                if($player1_time > $player2_time){
                    //$player1 is winner, update challenge
                        $challenge->update([
                            'winner' => $challenge->player1,
                            'status' => 'completed',
                        ]);
                    //update bot for player1
                            $bot1->update([
                                'challenges' => $bot1->challenges + 1,
                                'achievement' => $bot1->achievement + 1,
                            ]);
                    //update bot for player2
                            $bot2->update([
                                'challenges' => $bot2->challenges + 1,
                                'lost' => $bot2->lost + 1,
                            ]);

                    return $challenge->player1;

                }elseif($player2_time > $player1_time){
                    //player 2 is winner by time difference, update challenge
                        $challenge->update([
                            'winner' => $challenge->player2,
                            'status' => 'completed',
                        ]);

                        //update bot for player1
                            $bot1->update([
                                'challenges' => $bot1->challenges + 1,
                                'lost' => $bot1->lost + 1,
                            ]);
                        //update bot for player2
                            $bot2->update([
                                'challenges' => $bot2->challenges + 1,
                                'achievement' => $bot2->achievement + 1,
                            ]);

                     return $challenge->player2;

                }else{
                    return 'draw';
                }

                //code ends here with internal break
                return 'error';
            }
            //check for winner
                if($player1 > $player2){
                    // player 1 is the winner, update score
                        $challenge->update([
                            'winner' => $challenge->player1,
                            'status' => 'completed',
                        ]);
                    
                    //update bot for player1
                            $bot1->update([
                                'challenges' => $bot1->challenges + 1,
                                'achievement' => $bot1->achievement + 1,
                            ]);
                    //update bot for player2
                            $bot2->update([
                                'challenges' => $bot2->challenges + 1,
                                'lost' => $bot2->lost + 1,
                            ]);

                    return $challenge->player1;

                }elseif($player2 > $player1){
                    //player 2 is the winner
                        $challenge->update([
                            'winner' => $challenge->player2,
                            'status' => 'completed',
                        ]);

                    //update bot for player1
                            $bot1->update([
                                'challenges' => $bot1->challenges + 1,
                                'lost' => $bot1->lost + 1,
                            ]);
                    //update bot for player2
                            $bot2->update([
                                'challenges' => $bot2->challenges + 1,
                                'achievement' => $bot2->achievement + 1,
                            ]);
                        
                    return $challenge->player2;
                }
                    

            }

            /**
             * Deduct balance for player 1 
             * and save in temporary
             * challenge account.
             * requirement challenge, userid as player, which_player, amount,
             */
            
             public function deductPlayer($requirement){
                 $acc = Account::where('userid', $requirement['player'])->first();
                 if(is_null($acc)){
                     return 'not found';
                 }
                 if($acc->coins < $requirement['amount']){
                     return 'insufficient';
                 }
                 if($acc->coins === $requirement['amount'] || $acc > $requirement['amount']){
                    //deduct neccesary amount and challenge account
                        $userAcc = $acc->coins - $requirement['amount'];
                    
                    //get house account
                        $house = House::where('type', 'default')->first();
       
                    //create a field for the new challenge
                        if($requirement['which_player'] === 'player1'){
                            $percentage = ($requirement['amount'] / 100) * $requirement['percent'];
                            ChallengeAccount::create([
                                'challenge' => $requirement['challenge'],
                                $requirement['which_player'] => $requirement['player'],
                                'total' => ($requirement['amount'] - $percentage) * 2,
                            ]);
                        }else{
                            ChallengeAccount::where('challenge', $requirement['challenge'])
                                ->update([
                                    $requirement['which_player'] => $requirement['player'],
                                    'status' => 'ongoing',
                                ]);
                        }

                    //update house 
                       $houseCharge = $house->challenge_coins + $requirement['amount'];
                       $house->update([
                            'challenge_coins' => $houseCharge,
                        ]);

                    //update current user account
                         $acc->update([
                            'coins' => $userAcc,
                        ]);

                        return 'success';
                }else{
                    return 'unknown';
                }
             }

            
        /**
         * credit winner with full amount
         * deduct house percentage and credit referal fee
         * update ledger
         */
        
         //collect money from users and add to 
         public function creditPlayer($requirement){
            //get player account
                $acc = Account::where('userid', $requirement['winner'])->first();
                    if(is_null($acc)){
                        return 'not found';
                    }
            //get referal account
                $user = User::where('userid', $requirement['winner'])->first();
                $ref = $user->referer;//username

                //challenge details
                    $attrib = EventAttrib::where('type', '1v1')->first();// get attributes for challenge 1v1
                    $ch = Challenge::where('challenge_id', $requirement['challenge'])->first();
                    $chAm = $ch->amount * 2;

                    
            //get challenge reference account
                $chalA = ChallengeAccount::where('challenge', $requirement['challenge'])
                        ->where('status', 'ongoing')
                        ->first();

                if(is_null($chalA)){
                        return 'closed';
                    }  
            //update referer account with 
                 if($ref === 'non'){
                    //do nothing
                }else{
                    //process referal
                        $refr = User::where('username', $ref)->first();
                        $refAcc = Account::where('userid', $refr->userid)->first();
                        $refPer = ($chAm / 100) * $attrib->referal_percent;// percentage of to be credited to referer
                    
                    //add referal amount to referer account
                        $refAcc->update([
                            'coins' => $refAcc->coins + $refPer,
                        ]);
                    //add to transaction history

                }
                

            //get house Account
                $house = House::where('type', 'default')->first();
            
            //debit house
                $newAccount = $house->challenge_coins - $chalA->total;
            
            //update player account
                $acc->update([
                    'coins' => $acc->coins + $chalA->total,
                ]);
            //process house amount
                if($ref === 'non'){
                    $processP = $attrib->house_percent;
                }else{
                    $processP = ($attrib->house_percent - $attrib->referal_percent);
                }

                $percentage = ($chAm / 100) * $processP;// percentage of to be credited to house
                
            //update house account
                $house->update([
                    'challenge_coins' => $newAccount - $percentage,
                    'house_coins' => $house->house_coins + $percentage,
                ]);
            
            //update challenge account (completed)
                $chalA->update([
                    'winner' => $requirement['winner'],
                    'status' => 'completed',
                ]);
            
            return 'success';
            
         }
            

//End of challege controller method
}
