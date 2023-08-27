<?php

namespace App\Http\Controllers;


use App\Models\Booking_Gym;
use App\Models\Gym;
use App\Models\Gym_Branch;
use App\Models\Gym_Class;
use App\Models\Gym_Trainer;
use App\Models\Trainer;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class GymController extends Controller
{

    public function index($numOfPage,$numOfRows)
    {
        try {
            $user = auth('user')->userOrFail();

            $gyms = Gym::all();
            $counter = count($gyms);
            $skippedNumbers = ($numOfPage - 1) * $numOfRows;
            $gyms = Gym::skip($skippedNumbers)
                ->take($numOfRows)
                ->get();

            $gyms = collect($gyms)->map(function($oneRecord)
            {
                if($oneRecord->image)
                {
                    $oneRecord->image = asset('/assets/gyms/' . $oneRecord->image );
                }
                return
                    [
                        "id" => $oneRecord->id,
                        "name" => $oneRecord->name,
                        "image" => $oneRecord->image,
                    ];

            });
            $result =
                [
                    'counter' => $counter,
                    'gyms' => $gyms,
                ];
            return $this->returnData(['response'], [$result],'Gym Data');
        } catch (\Tymon\JWTAuth\Exceptions\UserNotDefinedException $e) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
    }

    public function search($name,$numOfPage,$numOfRows)
    {
        try {
            $user = auth('user')->userOrFail();
            if($name == "all")
            {
                $gyms = Gym::get();

                $counter = count($gyms);
                $skippedNumbers = ($numOfPage - 1) * $numOfRows;

                $gyms = Gym::skip($skippedNumbers)->take($numOfRows)->get();
            }else
            {
                $gyms = Gym::where(function($query) use ($name) {
                    $query->where('name', 'LIKE',$name.'%')
                        ->orWhere('phone', 'LIKE',$name.'%');
                })->get();

                $counter = count($gyms);
                $skippedNumbers = ($numOfPage - 1) * $numOfRows;

                $gyms = Gym::where(function($query) use ($name) {
                    $query->where('name', 'LIKE',$name.'%')
                        ->orWhere('phone',$name);
                })->skip($skippedNumbers)->take($numOfRows)->get();
            }
            $gyms = collect($gyms)->map(function($oneRecord)
            {
                if($oneRecord->image)
                {
                    $oneRecord->image = asset('/assets/gyms/' . $oneRecord->image );
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
                'gyms'=>$gyms
            ];
            return $this->returnData(['response'], [$result],'Gyms Data');

        } catch (\Tymon\JWTAuth\Exceptions\UserNotDefinedException $e) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
    }


    public function addNewBranch(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|min:3|max:255',
            'time_from' => 'required|date_format:H:i',
            'time_to' => 'required|date_format:H:i',
            'address' => 'required|string',
            'location'=>'required|array',
        ]);
        if ($validator->fails()) {
            return $this->returnValidationError(422, $validator);
        }
        try {
            $gym = auth('gym')->userOrFail();

            $validator = Validator::make($request->all(), [
                'name'=> Rule::unique('gym_branches')->where(function ($query) use($gym){
                    return $query->where('gym_id', $gym->id);
                })
            ]);

            if ($validator->fails()) {
                return $this->returnValidationError(422, $validator);
            }

            $newBranch = new Gym_Branch;
            $newBranch->name = $request->name;
            $newBranch->time_from = $request->time_from;
            $newBranch->time_to = $request->time_to;
            $newBranch->address = $request->address;
            $location = implode(",", $request->location);
            $newBranch->location = $location;
            $newBranch->gym_id = $gym->id;
            $newBranch->save();

            return $this->returnSuccessMessage('Added Successfully',200);
        } catch (\Tymon\JWTAuth\Exceptions\UserNotDefinedException $e) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
    }

    public function getBranches()
    {
        try {
            $gym = auth('gym')->userOrFail();

            $branches = collect($gym->gym_branches)->map(function($oneRecord)
            {
                return
                    [
                        "id" => $oneRecord->id,
                        "name" => $oneRecord->name,
                        "address" => $oneRecord->address
                    ];

            });
            return $this->returnData(['response'], [$branches],'Branches Data');
        } catch (\Tymon\JWTAuth\Exceptions\UserNotDefinedException $e) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
    }


    public function home(Request $request)
    {
        try {
            $gym = auth('gym')->userOrFail();

            $allFeedbacks = [];
            foreach ($gym->gym_branches as $branch)
            {
                foreach ($branch->gym_trainers as $employee)
                {
                    $sum = 0;
                    $counter = count($employee->review_Gym_Trainers);
                    foreach ($employee->review_Gym_Trainers as $review)
                    {
                        $sum += $review->rate;
                    }
                    if($counter == 0)
                    {
                        $average = 0;
                    }else
                    {
                        $average = $sum / $counter;
                    }
                    $object =
                        [
                            'id' => $employee->id,
                            'average' => $average
                        ];
                    $allFeedbacks [] = $object;
                }
            }
            $this->array_sort_by_column($allFeedbacks, 'average');
            $firstThreeElements = array_slice($allFeedbacks, 0, 5);
            $topRated = collect($firstThreeElements)->map(function($oneRecord)
            {
                $employee = Gym_Trainer::find($oneRecord['id']);

                if($employee->image)
                {
                    $employee->image = asset('/assets/gym_trainers/' . $employee->image );
                }
                if($oneRecord['average'] == 0)
                {
                    $average = '0';
                }else
                {
                    $average = sprintf("%.1f", $oneRecord['average']);
                }

                return
                    [
                        "id" => $employee->id,
                        "name" => $employee->name,
                        "rate" => $average,
                        "image" => $employee->image,
                    ];
            });

            $allbooking = [];
            foreach ($gym->gym_branches as $branch)
            {
                foreach ($branch->booking as $booking)
                {
                    $today = Carbon::now();
                    if($today->format('Y-m-d') == $booking->date && $booking->attendance_status == '0')
                    {
                        $object =
                            [
                                'id' => $booking->id
                            ];
                        $allbooking [] = $object;
                    }
                }
            }
            $this->array_sort_by_column($allbooking, 'id',SORT_ASC);
            $firstThreeElements = array_slice($allbooking, 0, 5);
            $todayBooking = collect($firstThreeElements)->map(function($oneRecord)
            {
                $book = Booking_Gym::find($oneRecord['id']);

                $names = [];
                $classes = explode(",", $book->classes);
                foreach ($classes as $class)
                {
                    $Class = Gym_Class::find($class);
                    $names [] = $Class->name;
                }
                return
                    [
                        "id" => $book->id,
                        "user_name" => $book->user->name,
                        "date" => $book->date,
                        "classes_names" => $names,
                        "branch_name" => $book->gym_branch->name,
                        "isWithTrainer" => $book->isWithTrainer,
                    ];
            });
            $allService = [];
            foreach ($gym->gym_branches as $branch)
            {
                foreach ($branch->gym_classes as $class)
                {
                    $bookings = Booking_Gym::all();
                    $reserved = 0;
                    foreach ($bookings as $booking)
                    {
                        $classes = explode(",", $booking->classes);
                        foreach ($classes as $bookingClass)
                        {
                            $bookingClass = Gym_Class::find($bookingClass);
                            if($bookingClass->id == $class->id)
                            {
                                $reserved ++;
                            }
                        }
                    }

                    $object =
                        [
                            'id' => $class->id,
                            'reserved' => $reserved
                        ];
                    $allService [] = $object;
                }
            }
            $this->array_sort_by_column($allService, 'reserved');
            $firstThreeElements = array_slice($allService, 0, 5);
            $topServices = collect($firstThreeElements)->map(function($oneRecord)
            {
                $class = Gym_Class::find($oneRecord['id']);
                return
                    [
                        "id" => $class->id,
                        "name" => $class->name,
                        "branch_name" => $class->gym_branch->name,
                        "reserved_times" => $oneRecord['reserved'],
                    ];
            });
            unset(
                $gym->gym_branches
            );
            $result =
                [
                    'today_reservation' => $todayBooking,
                    'topRated' => $topRated,
                    'topServices' => $topServices,
                ];
            return $this->returnData(['response'], [$result],'Home Data');
        } catch (\Tymon\JWTAuth\Exceptions\UserNotDefinedException $e) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
    }


    function array_sort_by_column(&$array, $column, $direction = SORT_DESC) {
        $reference_array = array();

        foreach($array as $key => $row) {
            $reference_array[$key] = $row[$column];
        }

        array_multisort($reference_array, $direction, $array);
    }


    public function store(Request $request)
    {
        //
    }


    public function show(Gym $gym)
    {
        //
    }


    public function edit(Gym $gym)
    {
        //
    }

    public function update(Request $request, Gym $gym)
    {
        //
    }

    public function destroy(Gym $gym)
    {
        //
    }

    public function uploadImage(Request $request, $folderName,$filename)
    {

        $filename = strval($filename);
        if ($request->hasFile($filename)) {
            $extension = $request->file($filename)->extension();
            $image = time() . '.' . $request->file($filename)->getClientOriginalExtension();
            $request->file($filename)->move(public_path('/assets/'.$folderName), $image);
            return $image;

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
