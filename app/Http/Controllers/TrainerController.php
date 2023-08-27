<?php

namespace App\Http\Controllers;

use App\Models\Booking_Trainer;
use App\Models\Gym;
use App\Models\Gym_Branch;
use App\Models\Trainer;
use App\Models\Trainer_Media;
use App\Models\Trainer_Schedule;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Validator;

class TrainerController extends Controller
{
    public function index()
    {
        //
    }


    public function search($name,$numOfPage,$numOfRows)
    {
        try {
            $user = auth('user')->userOrFail();
            if($name == "all")
            {
                $trainers = Trainer::get();

                $counter = count($trainers);
                $skippedNumbers = ($numOfPage - 1) * $numOfRows;

                $trainers = Trainer::skip($skippedNumbers)->take($numOfRows)->get();
            }else
            {
                $trainers = Trainer::where(function($query) use ($name) {
                    $query->where('name', 'LIKE',$name.'%')
                        ->orWhere('phone', 'LIKE',$name.'%');
                })->get();

                $counter = count($trainers);
                $skippedNumbers = ($numOfPage - 1) * $numOfRows;

                $trainers = Trainer::where(function($query) use ($name) {
                    $query->where('name', 'LIKE',$name.'%')
                        ->orWhere('phone',$name);
                })->skip($skippedNumbers)->take($numOfRows)->get();
            }
            $trainers = collect($trainers)->map(function($oneRecord)
            {
                if($oneRecord->image)
                {
                    $oneRecord->image = asset('/assets/trainers/' . $oneRecord->image );
                }
                return
                    [
                        "id" => $oneRecord->id,
                        "name" => $oneRecord->name,
                        "image" => $oneRecord->image,
                    ];

            });
            $result = [
                'counter'=>$counter,
                'trainers'=>$trainers
            ];
            return $this->returnData(['response'], [$result],'Trainers Data');
        } catch (\Tymon\JWTAuth\Exceptions\UserNotDefinedException $e) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
    }

    public function create()
    {
        //
    }

    public function store(Request $request)
    {
        //
    }

    public function show(Trainer $trainer)
    {
        //
    }

    public function edit(Trainer $trainer)
    {
        //
    }

    public function update(Request $request, Trainer $trainer)
    {
        //
    }

    public function destroy(Trainer $trainer)
    {
        //
    }

    public function addToMedia(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'image' => 'required',
            'type' => 'required|string|in:"image","video"',
        ]);
        if ($validator->fails()) {
            return $this->returnValidationError(422, $validator);
        }
        try {
            $trainer = auth('trainer')->userOrFail();
            $images = $this->uploadImages($request);
            $arr = explode('|',$images);
            foreach ($arr as $ar){
                Trainer_Media::create([
                    'trainer_id'=>$trainer->id,
                    'file'=>$ar,
                    'type' =>$request->type
                ]);
            }
            return $this->returnSuccessMessage('Added Successfully',200);
        } catch (\Tymon\JWTAuth\Exceptions\UserNotDefinedException $e) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
    }

    public function removeMedia(Request $request,$id)
    {
        try {
            $trainer = auth('trainer')->userOrFail();
            $newMedia = Trainer_Media::find($id);
            if(!$newMedia || $newMedia->trainer->id != $trainer->id)
            {
                return $this->returnError(201, 'invalid');
            }
            $path =  public_path('/assets/trainer_medias/'.$newMedia->file);
            $image_path = $path;
            if(File::exists($image_path)) {
                File::delete($image_path);
            }
            $newMedia->delete();
            return $this->returnSuccessMessage('Deleted Successfully',200);
        } catch (\Tymon\JWTAuth\Exceptions\UserNotDefinedException $e) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
    }

    public function uploadImages(Request $request)
    {
        if ($request->hasFile('image')) {

            $files = $request->file('image');
            foreach ($files as $file) {

                $fileextension = $file->getClientOriginalExtension();


                $filename = $file->getClientOriginalName();
                $file_to_store = time() . '_' . explode('.', $filename)[0] . '_.' . $fileextension;

                $test = $file->move(public_path('assets/trainer_medias'), $file_to_store);
                if ($test) {
                    $images [] = $file_to_store;
                }
            }
            $images = implode('|', $images);
            return $images;
        }

    }

    public function getImages($id,$numOfPage,$numOfRows)
    {
        try {
            //$gym = auth('gym')->userOrFail();

            $trainer = Trainer::find($id);

            if(! $trainer)
            {
                return $this->returnError(201, 'trainer id is invalid');
            }
            $medias = Trainer_Media::where('trainer_id',$trainer->id)
                ->where('type','image')
                ->get();

            $counter = count($medias);

            $skippedNumbers = ($numOfPage - 1) * $numOfRows;

            $medias = Trainer_Media::where('trainer_id',$trainer->id)
                ->where('type','image')
                ->skip($skippedNumbers)->take($numOfRows)
                ->get();

            $medias = collect($medias)->map(function($oneRecord)
            {
                if($oneRecord->file)
                {
                    $oneRecord->file = asset('/assets/trainer_medias/' . $oneRecord->file );
                }
                return
                    [
                        "id" => $oneRecord->id,
                        "image" => $oneRecord->file,
                    ];

            });
            $result = [
                'counter'=>$counter,
                'medias'=>$medias
            ];

            return $this->returnData(['response'], [$result],'Images Data');
        } catch (\Tymon\JWTAuth\Exceptions\UserNotDefinedException $e) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
    }

    public function getVideos($id,$numOfPage,$numOfRows)
    {
        try {
            //$gym = auth('gym')->userOrFail();

            $trainer = Trainer::find($id);

            if(! $trainer)
            {
                return $this->returnError(201, 'trainer id is invalid');
            }
            $medias = Trainer_Media::where('trainer_id',$trainer->id)
                ->where('type','video')
                ->get();

            $counter = count($medias);

            $skippedNumbers = ($numOfPage - 1) * $numOfRows;

            $medias = Trainer_Media::where('trainer_id',$trainer->id)
                ->where('type','video')
                ->skip($skippedNumbers)->take($numOfRows)
                ->get();

            $medias = collect($medias)->map(function($oneRecord)
            {
                if($oneRecord->file)
                {
                    $oneRecord->file = asset('/assets/trainer_medias/' . $oneRecord->file );
                }
                return
                    [
                        "id" => $oneRecord->id,
                        "image" => $oneRecord->file,
                    ];

            });
            $result = [
                'counter'=>$counter,
                'medias'=>$medias
            ];

            return $this->returnData(['response'], [$result],'Images Data');
        } catch (\Tymon\JWTAuth\Exceptions\UserNotDefinedException $e) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
    }

    public function makeSchedule(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'days' => 'required|string',
            'times_from' => 'required|string',
            'times_to' => 'required|string',
        ]);
        if ($validator->fails()) {
            return $this->returnValidationError(422, $validator);
        }
        try {
            $trainer = auth('trainer')->userOrFail();

            $days =
                [
                    "Saturday","Sunday","Monday","Tuesday","Wednesday","Thursday","Friday"
                ];


            $daysArray = json_decode($request->days);
            $times_fromArray = json_decode($request->times_from);
            $times_toArray = json_decode($request->times_to);



            if (count($daysArray) != count($times_fromArray) || count($times_fromArray) != count($times_toArray))
            {
                return $this->returnError(201, 'invalid arrays');
            }

            foreach ($daysArray as $oneDay)
            {
                if(!in_array($oneDay,$days))
                {
                    return $this->returnError(201, 'invalid days arrays');
                }
            }


            if(count(array_unique($daysArray))  != count($daysArray))
            {
                return $this->returnError(201, 'repeated day');
            }


            foreach ($times_fromArray as $oneFromTime)
            {
                $dateObj = \DateTime::createFromFormat('H:i', $oneFromTime);
                if(!$dateObj)
                {
                    return $this->returnError(201, 'invalid times from arrays');
                }
            }
            foreach ($times_toArray as $oneToTime)
            {
                $dateObj = \DateTime::createFromFormat('H:i', $oneToTime);
                if(!$dateObj)
                {
                    return $this->returnError(201, 'invalid times to arrays');
                }
            }

            for ($i = 0 ; $i < count($daysArray) ; $i++)
            {
                $scheduler =  Trainer_Schedule::where('trainer_id',$trainer->id)
                    ->where('day',$daysArray[$i])
                    ->first();
                if($scheduler)
                {
                    $scheduler->time_from = $times_fromArray[$i];
                    $scheduler->time_to = $times_toArray[$i];
                    $scheduler->save();
                }else
                {
                    $newDay = new Trainer_Schedule;
                    $newDay->trainer_id = $trainer->id;
                    $newDay->time_from = $times_fromArray[$i];
                    $newDay->time_to = $times_toArray[$i];
                    $newDay->day = $daysArray[$i];
                    $newDay->save();
                }
            }
            return $this->returnSuccessMessage('Added Successfully',200);
        } catch (\Tymon\JWTAuth\Exceptions\UserNotDefinedException $e) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
    }

    function home($status)
    {
        try {
            $trainer = auth('trainer')->userOrFail();
            $currentData = [];
            $history = [];
            $today = [];
            foreach ($trainer->booking_trainers as $booking_trainer)
            {
                $notificationDate = $booking_trainer->date;
                $currentDate = Carbon::now();
                $currentDate = $currentDate->toDateString();
                if($currentDate == $notificationDate)
                {
                    $today [] = $booking_trainer->id;
                }elseif($currentDate <= $notificationDate)
                {
                    $currentData [] = $booking_trainer->id;
                }else
                {
                    $history [] = $booking_trainer->id;
                }
            }

            if($status == '1')
            {
                $reservations = collect($history)->map(function($oneRecord)
                {
                    $booking_trainer = Booking_Trainer::find($oneRecord);
                    if($booking_trainer->user->image)
                    {
                        $booking_trainer->user->image = asset('/assets/users/' . $booking_trainer->user->image);
                    }
                    return
                        [
                            "id" => $booking_trainer->id,
                            "user_name" => $booking_trainer->user->name,
                            "user_phone" => $booking_trainer->user->phone,
                            "user_image" => $booking_trainer->user->image,
                            "date" => $booking_trainer->date,

                        ];

                });
            }elseif($status == '2')
            {
                $reservations = collect($currentData)->map(function($oneRecord)
                {
                    $booking_trainer = Booking_Trainer::find($oneRecord);
                    if($booking_trainer->user->image)
                    {
                        $booking_trainer->user->image = asset('/assets/users/' . $booking_trainer->user->image);
                    }
                    return
                        [
                            "id" => $booking_trainer->id,
                            "user_name" => $booking_trainer->user->name,
                            "user_phone" => $booking_trainer->user->phone,
                            "user_image" => $booking_trainer->user->image,
                            "date" => $booking_trainer->date,

                        ];

                });
            }else
            {
                $reservations = collect($today)->map(function($oneRecord)
                {
                    $booking_trainer = Booking_Trainer::find($oneRecord);
                    if($booking_trainer->user->image)
                    {
                        $booking_trainer->user->image = asset('/assets/users/' . $booking_trainer->user->image);
                    }
                    return
                        [
                            "id" => $booking_trainer->id,
                            "user_name" => $booking_trainer->user->name,
                            "user_phone" => $booking_trainer->user->phone,
                            "user_image" => $booking_trainer->user->image,
                            "date" => $booking_trainer->date,

                        ];

                });
            }
            return $this->returnData(['response'], [$reservations],'Reservations Data');
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
