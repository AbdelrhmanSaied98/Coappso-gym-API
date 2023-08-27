<?php

namespace App\Http\Controllers;

use App\Models\Booking_Gym;
use App\Models\Booking_Trainer;
use App\Models\Gym_Class;
use App\Models\Notification;
use App\Models\Trainer;
use App\Models\Trainer_Schedule;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class BookingTrainerController extends Controller
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
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'date' => 'required|date_format:Y-m-d|after_or_equal:today',
            'trainer_id'=>'required|exists:trainers,id'
        ]);
        if ($validator->fails()) {
            return $this->returnValidationError(422, $validator);
        }
        try {
            $user = auth('user')->userOrFail();

            $trainer = Trainer::find($request->trainer_id);

            $dayOfTheWeek = Carbon::parse($request->date)->dayName;

            $scheduler = Trainer_Schedule::where('trainer_id',$request->trainer_id)
            ->where('day',$dayOfTheWeek)
            ->first();

            if(!$scheduler)
            {
                return $this->returnError(201, 'not available ');
            }

            $bookingGym = Booking_Gym::where('user_id',$user->id)
                ->where('date',$request->date)
                ->where('isWithTrainer',1)
                ->first();

            if(!$bookingGym)
            {
                return $this->returnError(201, 'should have trainer option with gym');
            }

            $book = new Booking_Trainer;
            $book->date = $request->date;
            $book->trainer_id = $request->trainer_id;
            $book->user_id = $user->id;
            $book->save();

            $newNotification = new Notification;
            $newNotification->user_type = 'trainer';
            $newNotification->user_id = $trainer->id;
            $newNotification->content_type = 'trainer_booking';
            $newNotification->content_id = $book->id;
            $newNotification->seen = 0;
            $newNotification->notification = $user->name.' booked an appointment on '.$book->date;
            $newNotification->save();

            (new MessageController())->NotifyApi(
                $trainer->device_token,
                "New Reservation",
                $user->name.' booked an appointment on '.$book->date
            );
            return $this->returnSuccessMessage('Reservation Successfully',200);
        } catch (\Tymon\JWTAuth\Exceptions\UserNotDefinedException $e) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Booking_Trainer  $booking_Trainer
     * @return \Illuminate\Http\Response
     */
    public function show(Booking_Trainer $booking_Trainer)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Booking_Trainer  $booking_Trainer
     * @return \Illuminate\Http\Response
     */
    public function edit(Booking_Trainer $booking_Trainer)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Booking_Trainer  $booking_Trainer
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Booking_Trainer $booking_Trainer)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Booking_Trainer  $booking_Trainer
     * @return \Illuminate\Http\Response
     */
    public function destroy(Booking_Trainer $booking_Trainer)
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
