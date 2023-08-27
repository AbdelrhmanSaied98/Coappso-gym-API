<?php

namespace App\Http\Controllers;

use App\Models\Booking_Gym;
use App\Models\_GymClass;
use App\Models\Gym_Branch;
use App\Models\Gym_Class;
use App\Models\Notification;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class BookingGymController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index($id)
    {
        try {
            $gym = auth('gym')->userOrFail();


            $gym_branch =  Gym_Branch::find($id);
            if(! $gym_branch || $gym_branch->gym->id != $gym->id)
            {
                return $this->returnError(201, 'Invalid id branch');
            }

            $booking = collect($gym_branch->booking)->map(function($oneRecord)
            {
                $names = [];
                $classes = explode(",", $oneRecord->classes);
                foreach ($classes as $class)
                {
                    $Class = Gym_Class::find($class);
                    $names [] = $Class->name;
                }
                return
                    [
                        "id" => $oneRecord->id,
                        "client_name" => $oneRecord->user->name,
                        "date" => $oneRecord->date,
                        "classes" => $names,
                        "isWithTrainer" => $oneRecord->isWithTrainer,
                        "attendance_status" => $oneRecord->attendance_status,
                    ];

            });
            return $this->returnData(['response'], [$booking],'Booking Data');
        } catch (\Tymon\JWTAuth\Exceptions\UserNotDefinedException $e) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
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
            'classes' => 'required|string',
            'price' => 'required|numeric',
            'date' => 'required|date_format:Y-m-d|after_or_equal:today',
            'branch_id'=>'required|exists:gym_branches,id',
            'withTrainer' => 'required|in:0,1'
        ]);
        if ($validator->fails()) {
            return $this->returnValidationError(422, $validator);
        }
        $classesArray = json_decode($request->classes);
        $classes = implode(',',$classesArray);
        try {
            $user = auth('user')->userOrFail();

            foreach ($classesArray as $oneClassID)
            {
                $repeated = 0;
                foreach ($classesArray as $twoClass)
                {
                    if($oneClassID == $twoClass)
                    {
                        $repeated++;
                    }
                }
                if($repeated > 1)
                {
                    return $this->returnError(201, 'already add this class');
                }
            }


            foreach ($classesArray as $oneClassID)
            {
                $bookings = Booking_Gym::where('date', $request->date)
                    ->where('user_id',$user->id)
                    ->get();
                foreach ($bookings as $booking)
                {
                    $bookedClasses = explode(",", $booking->classes);
                    foreach ($bookedClasses as $item)
                    {
                        $classOld = Gym_Class::find($item);
                        $classNew = Gym_Class::find($oneClassID);
                        if($classNew->name == $classOld->name)
                        {
                            return $this->returnError(201, 'You have done this class '.$classNew->name.' before ');
                        }
                    }
                }
            }

            $book = new Booking_Gym;
            $book->classes = $classes;
            $book->date = $request->date;
            $book->branch_id = $request->branch_id;
            $book->isWithTrainer = $request->withTrainer;
            $book->price = $request->price;
            $book->user_id = $user->id;
            $book->attendance_status = '0';
            $book->save();

            $newNotification = new Notification;
            $newNotification->user_type = 'gym';
            $newNotification->user_id = $book->gym_branch->gym->id;
            $newNotification->content_type = 'gym_booking';
            $newNotification->content_id = $book->id;
            $newNotification->seen = 0;
            $newNotification->notification = $user->name.' booked an appointment on '.$book->date;
            $newNotification->save();

            (new MessageController())->NotifyApi(
                $book->gym_branch->gym->device_token,
                "New Reservation",
                $user->name.' booked an appointment on '.$book->date
            );
            return $this->returnSuccessMessage('Reservation Successfully',200);
        } catch (\Tymon\JWTAuth\Exceptions\UserNotDefinedException $e) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
    }


    public function bookingWithTrainer($id)
    {
        try {
            $user = auth('user')->userOrFail();

            $booking_gym =  Booking_Gym::find($id);
            if(!$booking_gym || $booking_gym->user->id != $user->id)
            {
                return $this->returnError(201, 'Invalid get gym booking');
            }
            $booking_gym->isWithTrainer = 1;
            $booking_gym->save();
            return $this->returnSuccessMessage('Edit Successfully',200);
        } catch (\Tymon\JWTAuth\Exceptions\UserNotDefinedException $e) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Booking_Gym  $booking_Gym
     * @return \Illuminate\Http\Response
     */
    public function show(Booking_Gym $booking_Gym)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Booking_Gym  $booking_Gym
     * @return \Illuminate\Http\Response
     */
    public function edit(Booking_Gym $booking_Gym)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Booking_Gym  $booking_Gym
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Booking_Gym $booking_Gym)
    {
        //
    }


    public function destroy(Booking_Gym $booking_Gym)
    {
        //
    }


    public function verify(Request $request,$id)
    {
        try {
            $gym = auth('gym')->userOrFail();

            $booking_gym =  Booking_Gym::find($id);
            if(!$booking_gym || $booking_gym->gym_branch->gym->id != $gym->id)
            {
                return $this->returnError(201, 'Invalid get gym booking');
            }
            $today = Carbon::now();
            if($today->format('Y-m-d') != $booking_gym->date)
            {
                return $this->returnError(201, 'booking in not today');
            }
            $booking_gym->attendance_status = '1';
            $booking_gym->save();


            $user = User::find($booking_gym->user_id);

            $newNotification = new Notification;
            $newNotification->user_type = 'user';
            $newNotification->user_id = $user->id;
            $newNotification->content_type = 'book';
            $newNotification->content_id = $booking_gym->id;
            $newNotification->seen = 0;
            $newNotification->notification = $gym->name.' waiting for your feedback';
            $newNotification->save();

            (new MessageController())->NotifyApi(
                $user->device_token,
                "Feedback Time",
                $gym->name.' waiting for your feedback '
            );

            return $this->returnSuccessMessage('Added Successfully',200);
        } catch (\Tymon\JWTAuth\Exceptions\UserNotDefinedException $e) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
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
