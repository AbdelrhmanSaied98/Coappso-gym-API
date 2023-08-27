<?php

namespace App\Http\Controllers;

use App\Models\Booking_Gym;
use App\Models\Booking_Trainer;
use App\Models\Gym_Branch;
use App\Models\Gym_Class;
use App\Models\Review_Gym;
use App\Models\Review_Gym_Trainer;
use App\Models\Trainer_Schedule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ReviewController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request,$id)
    {

        $validator = Validator::make($request->all(), [
            'gym_rating' => 'required|in:1,2,3,4,5'
        ]);
        if ($validator->fails()) {
            return $this->returnValidationError(422, $validator);
        }

        try {
            $user = auth('user')->userOrFail();
            $Booking_Gym =  Booking_Gym::find($id);
            if(! $Booking_Gym || $Booking_Gym->user->id != $user->id)
            {
                return $this->returnError(201, 'Invalid id booking');
            }
            if($Booking_Gym->isWithTrainer == 0)
            {
                $validator = Validator::make($request->all(), [
                    'rating' => 'required|string',
                    'id_trainers' => 'required|string',
                    'feedbacks' => 'required|string',
                ]);
                if ($validator->fails()) {
                    return $this->returnValidationError(422, $validator);
                }
                $ratingArray = json_decode($request->rating);
                $id_trainersArray = json_decode($request->id_trainers);
                $feedbacksArray = json_decode($request->feedbacks);

                if (count($ratingArray) != count($id_trainersArray) || count($id_trainersArray) != count($feedbacksArray))
                {
                    return $this->returnError(201, 'invalid arrays');
                }

                foreach ($ratingArray as $oneRating)
                {
                    if(!is_numeric($oneRating))
                    {
                        return $this->returnError(201, 'invalid rating array');
                    }
                }
                for ($i = 0 ; $i < count($ratingArray) ; $i++)
                {

                    $review_Gym_Trainer = new Review_Gym_Trainer;
                    $review_Gym_Trainer->user_id = $user->id;
                    $review_Gym_Trainer->gym_trainer_id = $id_trainersArray[$i];
                    $review_Gym_Trainer->rate = $ratingArray[$i];
                    $review_Gym_Trainer->feedback = $feedbacksArray[$i];
                    $review_Gym_Trainer->save();
                }
                $gym_rating = new Review_Gym;
                $gym_rating->user_id = $user->id;
                $gym_rating->gym_id = $Booking_Gym->gym_branch->gym->id;
                $gym_rating->rate = $request->gym_rating;
                if($request->gym_feedback || $request->gym_feedback != null || $request->gym_feedback)
                {
                    $gym_rating->feedback = $request->gym_feedback;
                }else
                {
                    $gym_rating->feedback = "";
                }
                $gym_rating->save();
                return $this->returnSuccessMessage('Added Successfully',200);

            }elseif ($Booking_Gym->isWithTrainer == 1)
            {

                $trainerBooking = Booking_Trainer::where('user_id',$user->id)
                    ->where('date',$Booking_Gym->date)
                    ->first();

                if($trainerBooking)
                {
                    $validator = Validator::make($request->all(), [
                        'trainer_rating' => 'required|in:1,2,3,4,5'
                    ]);
                    if ($validator->fails()) {
                        return $this->returnValidationError(422, $validator);
                    }


                    $trainer_rating = new Review_Gym_Trainer;
                    $trainer_rating->user_id = $user->id;
                    $trainer_rating->gym_id = $Booking_Gym->id;
                    $trainer_rating->rate = $request->trainer_rating;
                    if($request->trainer_feedback || $request->trainer_feedback != null || $request->trainer_feedback)
                    {
                        $trainer_rating->feedback = $request->trainer_feedback;
                    }else
                    {
                        $trainer_rating->feedback = "";
                    }
                    $trainer_rating->save();
                }



                $gym_rating = new Review_Gym;
                $gym_rating->user_id = $user->id;
                $gym_rating->gym_id = $Booking_Gym->gym_branch->gym->id;
                $gym_rating->rate = $request->gym_rating;
                if($request->gym_feedback || $request->gym_feedback != null || $request->gym_feedback)
                {
                    $gym_rating->feedback = $request->gym_feedback;
                }else
                {
                    $gym_rating->feedback = "";
                }
                $gym_rating->save();
                return $this->returnSuccessMessage('Added Successfully',200);
            }
        } catch (\Tymon\JWTAuth\Exceptions\UserNotDefinedException $e) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }

    public function returnValidationError($code , $validator)
    {
        return $this->returnError($code, $validator->errors()->first());
    }

    public function returnError($errNum, $msg)
    {
        return response([
            'status' => false,
            'code' => $errNum,
            'msg' => $msg
        ], $errNum)
            ->header('Content-Type', 'text/json');
    }

    public function returnSuccessMessage($msg = '', $errNum = 'S000')
    {
        return [
            'status' => true,
            'msg' => $msg
        ];
    }

    public function returnData($keys, $values, $msg = '')
    {
        $data = [];
        for ($i = 0; $i < count($keys); $i++) {
            $data[$keys[$i]] = $values[$i];
        }

        return response()->json([
            'status' => true,
            'msg' => $msg,
            'data' => $data
        ]);
    }
}
