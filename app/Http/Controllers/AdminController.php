<?php

namespace App\Http\Controllers;

use App\Models\Admin;
use App\Models\Booking_Gym;
use App\Models\Booking_Trainer;
use App\Models\Gym;
use App\Models\Gym_Branch;
use App\Models\Gym_Class;
use App\Models\Gym_Trainer;
use App\Models\Trainer;
use App\Models\Trainer_Media;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AdminController extends Controller
{
    public function getGyms($numOfPage,$numOfRows)
    {
        try {
            $admin = auth('admin')->userOrFail();

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
                    $oneRecord->image = asset('/assets/gyms/' . $oneRecord->image);
                }

                $sum = 0;
                $counter = count($oneRecord->reviews);
                foreach ($oneRecord->reviews as $review) {
                    $sum += $review->rate;
                }
                    if($counter == 0)
                    {
                        $average = sprintf("%.1f", 0);
                    }else
                    {
                        $average = $sum / $counter;
                        $average = sprintf("%.1f", $average);

                    }
                return
                    [
                        "id" => $oneRecord->id,
                        "name" => $oneRecord->name,
                        "image" => $oneRecord->image,
                        "isBlocked" => $oneRecord->isBlocked,
                        "ban_times" => $oneRecord->ban_times,
                        "average" =>$average
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

    public function getGymsSearch($name,$numOfPage,$numOfRows)
    {
        try {
            $admin = auth('admin')->userOrFail();

            $gyms = Gym::where(function($query) use ($name) {
                $query->where('name', 'LIKE',$name.'%')
                    ->orWhere('phone', $name)
                    ->orWhere('email', 'LIKE',$name.'%');
            })->get();
            $counter = count($gyms);
            $skippedNumbers = ($numOfPage - 1) * $numOfRows;

            $gyms = Gym::where(function($query) use ($name) {
                $query->where('name', 'LIKE',$name.'%')
                    ->orWhere('phone', $name)
                    ->orWhere('email', 'LIKE',$name.'%');
            })->skip($skippedNumbers)
                ->take($numOfRows)
                ->get();
            $gyms = collect($gyms)->map(function($oneRecord)
            {
                if($oneRecord->image)
                {
                    $oneRecord->image = asset('/assets/gyms/' . $oneRecord->image);
                }

                $sum = 0;
                $counter = count($oneRecord->reviews);
                foreach ($oneRecord->reviews as $review) {
                    $sum += $review->rate;
                }
                if($counter == 0)
                {
                    $average = sprintf("%.1f", 0);
                }else
                {
                    $average = $sum / $counter;
                    $average = sprintf("%.1f", $average);

                }
                return
                    [
                        "id" => $oneRecord->id,
                        "name" => $oneRecord->name,
                        "image" => $oneRecord->image,
                        "average" =>$average,
                        "isBlocked" => $oneRecord->isBlocked,
                        "ban_times" => $oneRecord->ban_times,
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

    public function getGymDetails($id)
    {
        try {
            $admin = auth('admin')->userOrFail();

            $gym = Gym::find($id);

            if(! $gym )
            {
                return $this->returnError(201, 'Invalid id');
            }
            $reservedTimes = 0;
            $classesNumber = 0;
            $trainersNumber = 0;

            $branches = [];
            foreach ($gym->gym_branches as $branch)
            {
                $reservedTimes += count($branch->booking);
                $classesNumber += count($branch->gym_classes);
                $trainersNumber += count($branch->gym_trainers);

                $object =
                    [
                        'id' => $branch->id,
                        'name' => $branch->name,
                        'address' => $branch->address,
                    ];
                $branches [] = $object;
            }
            if($gym->image)
            {
                $gym->image = asset('/assets/gyms/' . $gym->image);
            }
            $result =
                [
                    'reservedTimes' => $reservedTimes,
                    'classesNumber' => $classesNumber,
                    'trainersNumber' => $trainersNumber,
                    'branches' => $branches,
                    'phone' => $gym->phone,
                    'email' => $gym->email,
                    'image' => $gym->image,
                    'name' => $gym->name,
                ];

            return $this->returnData(['response'], [$result],'Gym Data');
        } catch (\Tymon\JWTAuth\Exceptions\UserNotDefinedException $e) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
    }

    public function getBranchDetails($id)
    {
        try {
            $admin = auth('admin')->userOrFail();
            $gym_branch =  Gym_Branch::find($id);
            $classes = collect($gym_branch->gym_classes)->map(function($oneRecord) use ($gym_branch)
            {
                if($oneRecord->image)
                {
                    $oneRecord->image = asset('/assets/classes/' . $oneRecord->image );
                }
                $reserved = 0;
                foreach ($gym_branch->booking as $booking)
                {
                    $classes = explode(",", $booking->classes);
                    foreach ($classes as $bookingClass)
                    {
                        $bookingClass = Gym_Class::find($bookingClass);
                        if($bookingClass->id == $oneRecord->id)
                        {
                            $reserved ++;
                        }
                    }
                }
                return
                    [
                        "id" => $oneRecord->id,
                        "name" => $oneRecord->name,
                        "image" => $oneRecord->image,
                        "trainer_name" => $oneRecord->trainer_name,
                        "reserved" => $reserved,
                    ];

            });
            $gym_trainers = collect($gym_branch->gym_trainers)->map(function($oneRecord)
            {
                if($oneRecord->image)
                {
                    $oneRecord->image = asset('/assets/gym_trainers/' . $oneRecord->image );
                }

                $sum = 0;
                $counter = count($oneRecord->review_Gym_Trainers);
                foreach ($oneRecord->review_Gym_Trainers as $review) {
                    $sum += $review->rate;
                }
                if($counter == 0)
                {
                    $average = sprintf("%.1f", 0);
                }else
                {
                    $average = $sum / $counter;
                    $average = sprintf("%.1f", $average);

                }

                return
                    [
                        "id" => $oneRecord->id,
                        "name" => $oneRecord->name,
                        "image" => $oneRecord->image,
                        "average" => $average,
                    ];

            });
            $result =
                [
                    'classes' => $classes,
                    'trainers' => $gym_trainers
                ];
            return $this->returnData(['response'], [$result],'Gym Data');
        } catch (\Tymon\JWTAuth\Exceptions\UserNotDefinedException $e) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
    }

    public function getUsers($numOfPage,$numOfRows)
    {
        try {
            $admin = auth('admin')->userOrFail();

            $users = User::all();
            $counter = count($users);
            $skippedNumbers = ($numOfPage - 1) * $numOfRows;

            $users = User::skip($skippedNumbers)
                ->take($numOfRows)
                ->get();
            $users = collect($users)->map(function($oneRecord)
            {
                if($oneRecord->image)
                {
                    $oneRecord->image = asset('/assets/users/' . $oneRecord->image);
                }
                return
                    [
                        "id" => $oneRecord->id,
                        "name" => $oneRecord->name,
                        "image" => $oneRecord->image,
                        "isBlocked" => $oneRecord->isBlocked,
                        "ban_times" => $oneRecord->ban_times,
                    ];
            });
            $result = [
                'counter'=>$counter,
                'users'=>$users
            ];
            return $this->returnData(['response'], [$result],'Users Data');
        } catch (\Tymon\JWTAuth\Exceptions\UserNotDefinedException $e) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
    }

    public function getUsersSearch($name,$numOfPage,$numOfRows)
    {
        try {
            $admin = auth('admin')->userOrFail();

            $users = User::where(function($query) use ($name) {
                $query->where('name', 'LIKE',$name.'%')
                    ->orWhere('phone', $name)
                    ->orWhere('email', 'LIKE',$name.'%');
            })->get();
            $counter = count($users);
            $skippedNumbers = ($numOfPage - 1) * $numOfRows;

            $users = User::where(function($query) use ($name) {
                $query->where('name', 'LIKE',$name.'%')
                    ->orWhere('phone', $name)
                    ->orWhere('email', 'LIKE',$name.'%');
            })->skip($skippedNumbers)
                ->take($numOfRows)
                ->get();
            $users = collect($users)->map(function($oneRecord)
            {
                if($oneRecord->image)
                {
                    $oneRecord->image = asset('/assets/users/' . $oneRecord->image);
                }
                return
                    [
                        "id" => $oneRecord->id,
                        "name" => $oneRecord->name,
                        "image" => $oneRecord->image,
                        "isBlocked" => $oneRecord->isBlocked,
                        "ban_times" => $oneRecord->ban_times,
                    ];
            });
            $result = [
                'counter'=>$counter,
                'users'=>$users
            ];
            return $this->returnData(['response'], [$result],'Users Data');
        } catch (\Tymon\JWTAuth\Exceptions\UserNotDefinedException $e) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
    }

    public function getUserDetails($id)
    {
        try {
            $admin = auth('admin')->userOrFail();

            $user = User::find($id);

            if(! $user )
            {
                return $this->returnError(201, 'Invalid id');
            }
            $gymReservation = count($user->booking);
            $trainerReservation = count($user->booking_trainers);
            $attendedClasses = 0;
            foreach ($user->booking as $booking)
            {
                if($booking->attendance_status == '1')
                {
                    $classes = explode(",", $booking->classes);
                    $counter = count($classes);
                    $attendedClasses += $counter;
                }

            }
            if($user->image)
            {
                $user->image = asset('/assets/users/' . $user->image);
            }
            $result =
                [
                    'gymReservation' => $gymReservation,
                    'trainerReservation' => $trainerReservation,
                    'attendedClasses' => $attendedClasses,
                    'phone' => $user->phone,
                    'email' => $user->email,
                    'image' => $user->image,
                    'name' => $user->name,
                    'age' => $user->age,
                    'height' => $user->height,
                    'weight' => $user->weight,
                ];

            return $this->returnData(['response'], [$result],'User Data');
        } catch (\Tymon\JWTAuth\Exceptions\UserNotDefinedException $e) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
    }

    public function getTrainers($numOfPage,$numOfRows)
    {
        try {
            $admin = auth('admin')->userOrFail();

            $trainers = Trainer::all();
            $counter = count($trainers);
            $skippedNumbers = ($numOfPage - 1) * $numOfRows;

            $trainers = Trainer::skip($skippedNumbers)
                ->take($numOfRows)
                ->get();
            $trainers = collect($trainers)->map(function($oneRecord)
            {
                if($oneRecord->image)
                {
                    $oneRecord->image = asset('/assets/trainers/' . $oneRecord->image);
                }

                $sum = 0;
                $counter = count($oneRecord->review_Trainers);
                foreach ($oneRecord->review_Trainers as $review) {
                    $sum += $review->rate;
                }
                if($counter == 0)
                {
                    $average = sprintf("%.1f", 0);
                }else
                {
                    $average = $sum / $counter;
                    $average = sprintf("%.1f", $average);

                }
                return
                    [
                        "id" => $oneRecord->id,
                        "name" => $oneRecord->name,
                        "image" => $oneRecord->image,
                        "average" =>$average,
                        "isBlocked" => $oneRecord->isBlocked,
                        "ban_times" => $oneRecord->ban_times,
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

    public function getTrainersSearch($name,$numOfPage,$numOfRows)
    {
        try {
            $admin = auth('admin')->userOrFail();

            $trainers = Trainer::where(function($query) use ($name) {
                $query->where('name', 'LIKE',$name.'%')
                    ->orWhere('phone', $name)
                    ->orWhere('email', 'LIKE',$name.'%');
            })->get();
            $counter = count($trainers);
            $skippedNumbers = ($numOfPage - 1) * $numOfRows;

            $trainers = Trainer::where(function($query) use ($name) {
                $query->where('name', 'LIKE',$name.'%')
                    ->orWhere('phone', $name)
                    ->orWhere('email', 'LIKE',$name.'%');
            })->skip($skippedNumbers)
                ->take($numOfRows)
                ->get();
            $trainers = collect($trainers)->map(function($oneRecord)
            {
                if($oneRecord->image)
                {
                    $oneRecord->image = asset('/assets/trainers/' . $oneRecord->image);
                }

                $sum = 0;
                $counter = count($oneRecord->review_Trainers);
                foreach ($oneRecord->review_Trainers as $review) {
                    $sum += $review->rate;
                }
                if($counter == 0)
                {
                    $average = sprintf("%.1f", 0);
                }else
                {
                    $average = $sum / $counter;
                    $average = sprintf("%.1f", $average);

                }
                return
                    [
                        "id" => $oneRecord->id,
                        "name" => $oneRecord->name,
                        "image" => $oneRecord->image,
                        "average" =>$average,
                        "isBlocked" => $oneRecord->isBlocked,
                        "ban_times" => $oneRecord->ban_times,
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

    public function getTrainerDetails($id)
    {
        try {
            $admin = auth('admin')->userOrFail();

            $trainer = Trainer::find($id);

            if(! $trainer )
            {
                return $this->returnError(201, 'Invalid id');
            }
            $hiredTimes = count($trainer->booking_trainers);

            $sum = 0;
            $counter = count($trainer->review_Trainers);
            foreach ($trainer->review_Trainers as $review) {
                $sum += $review->rate;
            }
            if($counter == 0)
            {
                $average = sprintf("%.1f", 0);
            }else
            {
                $average = $sum / $counter;
                $average = sprintf("%.1f", $average);

            }
            if($trainer->image)
            {
                $trainer->image = asset('/assets/trainers/' . $trainer->image );
            }
            $result =
                [
                    'hiredTimes' => $hiredTimes,
                    'average' => $average,
                    'name' => $trainer->name,
                    'email' => $trainer->email,
                    'phone' => $trainer->phone,
                    'image' => $trainer->image,
                    'id' => $trainer->id,
                ];

            return $this->returnData(['response'], [$result],'Trainer Data');
        } catch (\Tymon\JWTAuth\Exceptions\UserNotDefinedException $e) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
    }

    public function getTrainerImages($id,$numOfPage,$numOfRows)
    {
        try {

            $admin = auth('admin')->userOrFail();
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

    public function getTrainerVideos($id,$numOfPage,$numOfRows)
    {
        try {
            $admin = auth('admin')->userOrFail();

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

    public function getGymBooking($numOfPage,$numOfRows)
    {
        try {
            $admin = auth('admin')->userOrFail();

            $booking_gyms = Booking_Gym::all();
            $counter = count($booking_gyms);
            $skippedNumbers = ($numOfPage - 1) * $numOfRows;

            $booking_gyms = Booking_Gym::skip($skippedNumbers)
                ->take($numOfRows)
                ->get();
            $booking_gyms = collect($booking_gyms)->map(function($oneRecord)
            {
                return
                    [
                        "id" => $oneRecord->id,
                        "user_name" => $oneRecord->user->name,
                        "date" => $oneRecord->date,
                        "attendance_status" => $oneRecord->attendance_status,
                        "gym_name" =>$oneRecord->gym_branch->gym->name
                    ];
            });
            $result = [
                'counter'=>$counter,
                'booking_gyms'=>$booking_gyms
            ];
            return $this->returnData(['response'], [$result],'Booking Gym Data');
        } catch (\Tymon\JWTAuth\Exceptions\UserNotDefinedException $e) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
    }

    public function getGymBookingSearch($name,$numOfPage,$numOfRows)
    {
        try {

            $admin = auth('admin')->userOrFail();

            $user = User::where('phone',$name)->first();
            if(! $user)
            {
                $counter = 0;
                $bookings = [];
            }else
            {
                $bookings= Booking_Gym::where(function($query) use ($user) {
                    $query->where('user_id',$user->id);
                })->get();
                $counter = count($bookings);
                $skippedNumbers = ($numOfPage - 1) * $numOfRows;

                $bookings = Booking_Gym::where(function($query) use ($user) {
                    $query->where('user_id',$user->id);
                })->orderBy('date', 'DESC')
                    ->skip($skippedNumbers)
                    ->take($numOfRows)
                    ->get();
                $bookings = collect($bookings)->map(function($oneRecord)
                {
                    return
                        [
                            "id" => $oneRecord->id,
                            "user_name" => $oneRecord->user->name,
                            "date" => $oneRecord->date,
                            "attendance_status" => $oneRecord->attendance_status,
                            "gym_name" =>$oneRecord->gym_branch->gym->name
                        ];
                });
            }

            $result = [
                'counter'=>$counter,
                'booking'=>$bookings
            ];
            return $this->returnData(['response'], [$result],'Booking Gym Data');
        } catch (\Tymon\JWTAuth\Exceptions\UserNotDefinedException $e) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
    }

    public function getGymBookingDetails($id)
    {
        try {
            $admin = auth('admin')->userOrFail();

            $booking_gym = Booking_Gym::find($id);

            if(! $booking_gym )
            {
                return $this->returnError(201, 'Invalid id');
            }

            $names = [];
            $classes = explode(",", $booking_gym->classes);
            foreach ($classes as $class)
            {
                $Class = Gym_Class::find($class);
                $names [] = $Class->name;
            }

            $result =
                    [
                        "id" => $booking_gym->id,
                        "user_name" => $booking_gym->user->name,
                        "date" => $booking_gym->date,
                        "attendance_status" => $booking_gym->attendance_status,
                        "gym_name" =>$booking_gym->gym_branch->gym->name,
                        "class_names" => $names
                    ];

            return $this->returnData(['response'], [$result],'Gym Booking Data');
        } catch (\Tymon\JWTAuth\Exceptions\UserNotDefinedException $e) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
    }

    public function getTrainerBooking($numOfPage,$numOfRows)
    {
        try {
            $admin = auth('admin')->userOrFail();

            $booking_trainers = Booking_Trainer::all();
            $counter = count($booking_trainers);
            $skippedNumbers = ($numOfPage - 1) * $numOfRows;

            $booking_trainers = Booking_Trainer::skip($skippedNumbers)
                ->take($numOfRows)
                ->get();
            $booking_trainers = collect($booking_trainers)->map(function($oneRecord)
            {
                return
                    [
                        "id" => $oneRecord->id,
                        "user_name" => $oneRecord->user->name,
                        "date" => $oneRecord->date,
                        "trainer_name" =>$oneRecord->trainer->name
                    ];
            });
            $result = [
                'counter'=>$counter,
                'booking_trainers'=>$booking_trainers
            ];
            return $this->returnData(['response'], [$result],'Booking Trainer Data');
        } catch (\Tymon\JWTAuth\Exceptions\UserNotDefinedException $e) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
    }

    public function getTrainerBookingSearch($name,$numOfPage,$numOfRows)
    {
        try {

            $admin = auth('admin')->userOrFail();

            $user = User::where('phone',$name)->first();
            if(! $user)
            {
                $counter = 0;
                $bookings = [];
            }else
            {
                $bookings= Booking_Trainer::where(function($query) use ($user) {
                    $query->where('user_id',$user->id);
                })->get();
                $counter = count($bookings);
                $skippedNumbers = ($numOfPage - 1) * $numOfRows;

                $bookings = Booking_Trainer::where(function($query) use ($user) {
                    $query->where('user_id',$user->id);
                })->orderBy('date', 'DESC')
                    ->skip($skippedNumbers)
                    ->take($numOfRows)
                    ->get();
                $bookings = collect($bookings)->map(function($oneRecord)
                {
                    return
                        [
                            "id" => $oneRecord->id,
                            "user_name" => $oneRecord->user->name,
                            "date" => $oneRecord->date,
                            "trainer_name" =>$oneRecord->trainer->name
                        ];
                });
            }

            $result = [
                'counter'=>$counter,
                'booking'=>$bookings
            ];
            return $this->returnData(['response'], [$result],'Booking Trainer Data');
        } catch (\Tymon\JWTAuth\Exceptions\UserNotDefinedException $e) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
    }

    public function getTrainerBookingDetails($id)
    {
        try {
            $admin = auth('admin')->userOrFail();

            $Booking_Trainer = Booking_Trainer::find($id);

            if(! $Booking_Trainer )
            {
                return $this->returnError(201, 'Invalid id');
            }

            $result =
                [
                    "id" => $Booking_Trainer->id,
                    "user_name" => $Booking_Trainer->user->name,
                    "date" => $Booking_Trainer->date,
                    "trainer_name" =>$Booking_Trainer->trainer->name
                ];

            return $this->returnData(['response'], [$result],'Gym Booking Data');
        } catch (\Tymon\JWTAuth\Exceptions\UserNotDefinedException $e) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
    }

    public function deleteUser($id)
    {
        try {
            $admin = auth('admin')->userOrFail();

            $user = User::find($id);
            if(!$user)
            {
                return $this->returnError(201, 'Not available User');
            }
            $user->delete();
            return $this->returnSuccessMessage('deleted Successfully',200);
        } catch (\Tymon\JWTAuth\Exceptions\UserNotDefinedException $e) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
    }

    public function deleteGym($id)
    {
        try {
            $admin = auth('admin')->userOrFail();

            $Gym = Gym::find($id);
            if(!$Gym)
            {
                return $this->returnError(201, 'Not available Gym');
            }
            $Gym->delete();
            return $this->returnSuccessMessage('deleted Successfully',200);
        } catch (\Tymon\JWTAuth\Exceptions\UserNotDefinedException $e) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
    }

    public function deleteTrainer($id)
    {
        try {
            $admin = auth('admin')->userOrFail();

            $trainer = Trainer::find($id);
            if(!$trainer)
            {
                return $this->returnError(201, 'Not available Trainer');
            }
            $trainer->delete();
            return $this->returnSuccessMessage('deleted Successfully',200);
        } catch (\Tymon\JWTAuth\Exceptions\UserNotDefinedException $e) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
    }

    public function deleteTrainerMedia($id)
    {
        try {
            $admin = auth('admin')->userOrFail();

            $trainer_media = Trainer_Media::find($id);
            if(!$trainer_media)
            {
                return $this->returnError(201, 'Not available Trainer Media');
            }
            $trainer_media->delete();
            return $this->returnSuccessMessage('deleted Successfully',200);
        } catch (\Tymon\JWTAuth\Exceptions\UserNotDefinedException $e) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
    }

    public function deleteGymTrainer($id)
    {
        try {
            $admin = auth('admin')->userOrFail();

            $gym_trainer = Gym_Trainer::find($id);
            if(!$gym_trainer)
            {
                return $this->returnError(201, 'Not available Gym Trainer');
            }
            $gym_trainer->delete();
            return $this->returnSuccessMessage('deleted Successfully',200);
        } catch (\Tymon\JWTAuth\Exceptions\UserNotDefinedException $e) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
    }

    public function deleteGymBooking($id)
    {
        try {
            $admin = auth('admin')->userOrFail();

            $booking_gym = Booking_Gym::find($id);
            if(!$booking_gym)
            {
                return $this->returnError(201, 'Not available Gym Booking');
            }
            $booking_gym->delete();
            return $this->returnSuccessMessage('deleted Successfully',200);
        } catch (\Tymon\JWTAuth\Exceptions\UserNotDefinedException $e) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
    }

    public function deleteTrainerBooking($id)
    {
        try {
            $admin = auth('admin')->userOrFail();

            $booking_trainer = Booking_Trainer::find($id);
            if(!$booking_trainer)
            {
                return $this->returnError(201, 'Not available Booking Trainer');
            }
            $booking_trainer->delete();
            return $this->returnSuccessMessage('deleted Successfully',200);
        } catch (\Tymon\JWTAuth\Exceptions\UserNotDefinedException $e) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
    }

    public function changePassword(Request $request)
    {
        try {
            $admin = auth('admin')->userOrFail();
            $validator = Validator::make($request->all(), [
                'old_password' => 'required|min:8',
                'password' => 'required|confirmed|min:8',
            ]);
            if ($validator->fails()) {
                return $this->returnValidationError(422, $validator);
            }
            if (! Hash::check($request->old_password, $admin->password)) {

                return $this->returnError(201, 'Wrong Password');
            }
            $admin->password = Hash::make($request->password);
            $admin->save();
            return  $this->returnSuccessMessage('password have been changed',200);
        } catch (\Tymon\JWTAuth\Exceptions\UserNotDefinedException $e) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
    }

    public function updateUser(Request $request,$id)
    {
        try {
            $admin = auth('admin')->userOrFail();
            $user = User::find($id);
            if(!$user)
            {
                return $this->returnError(201, 'Not available user');
            }
            if($request->email && $request->email != "")
            {
                $validator = Validator::make($request->all(), [
                    'email' => 'string|email|min:5|max:255|unique:users',
                ]);
                if ($validator->fails()) {
                    return $this->returnValidationError(422, $validator);
                }
                $user->email = $request->email;
                $user->save();
            }

            if($request->name && $request->name != "")
            {
                $validator = Validator::make($request->all(), [
                    'name' => 'required|string|min:3|max:255',
                ]);
                if ($validator->fails()) {
                    return $this->returnValidationError(422, $validator);
                }
                $user->name = $request->name;
                $user->save();
            }

            if($request->phone && $request->phone != "")
            {
                $validator = Validator::make($request->all(), [
                    'phone' => 'string|min:9|unique:users',
                ]);
                if ($validator->fails()) {
                    return $this->returnValidationError(422, $validator);
                }
                $user->phone = $request->phone;
                $user->save();
            }

            if($request->age && $request->age != "")
            {
                $validator = Validator::make($request->all(), [
                    'age' => 'required|numeric',
                ]);
                if ($validator->fails()) {
                    return $this->returnValidationError(422, $validator);
                }
                $user->age = $request->age;
                $user->save();
            }

            if($request->height && $request->height != "")
            {
                $validator = Validator::make($request->all(), [
                    'height' => 'required|numeric|min:90|max:250',
                ]);
                if ($validator->fails()) {
                    return $this->returnValidationError(422, $validator);
                }
                $user->height = $request->height;
                $user->save();
            }

            if($request->weight && $request->weight != "")
            {
                $validator = Validator::make($request->all(), [
                    'weight' => 'required|numeric|min:30|max:300',
                ]);
                if ($validator->fails()) {
                    return $this->returnValidationError(422, $validator);
                }
                $user->weight = $request->weight;
                $user->save();
            }

            if($request->image && $request->image != "")
            {
                $validator = Validator::make($request->all(), [
                    'image' => 'required|file',
                ]);
                if ($validator->fails()) {
                    return $this->returnValidationError(422, $validator);
                }
                if($user->image)
                {
                    $path =  public_path('/assets/users/'.$user->image);
                    $image_path = $path;
                    if(File::exists($image_path)) {
                        File::delete($image_path);
                    }
                }
                $image = $this->uploadImage($request,'users','image');
                $user->image = $image;
                $user->save();
            }

            return  $this->returnSuccessMessage('updated Successfully',200);
        } catch (\Tymon\JWTAuth\Exceptions\UserNotDefinedException $e) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
    }

    public function updateGym(Request $request,$id)
    {
        try {
            $admin = auth('admin')->userOrFail();
            $gym = Gym::find($id);
            if(!$gym)
            {
                return $this->returnError(201, 'Not available gym');
            }
            if($request->email && $request->email != "")
            {
                $validator = Validator::make($request->all(), [
                    'email' => 'string|email|min:5|max:255|unique:gyms',
                ]);
                if ($validator->fails()) {
                    return $this->returnValidationError(422, $validator);
                }
                $gym->email = $request->email;
                $gym->save();
            }

            if($request->name && $request->name != "")
            {
                $validator = Validator::make($request->all(), [
                    'name' => 'required|string|min:3|max:255',
                ]);
                if ($validator->fails()) {
                    return $this->returnValidationError(422, $validator);
                }
                $gym->name = $request->name;
                $gym->save();
            }

            if($request->phone && $request->phone != "")
            {
                $validator = Validator::make($request->all(), [
                    'phone' => 'string|min:9|unique:gyms',
                ]);
                if ($validator->fails()) {
                    return $this->returnValidationError(422, $validator);
                }
                $gym->phone = $request->phone;
                $gym->save();
            }

            if($request->image && $request->image != "")
            {
                $validator = Validator::make($request->all(), [
                    'image' => 'required|file',
                ]);
                if ($validator->fails()) {
                    return $this->returnValidationError(422, $validator);
                }
                if($gym->image)
                {
                    $path =  public_path('/assets/gyms/'.$gym->image);
                    $image_path = $path;
                    if(File::exists($image_path)) {
                        File::delete($image_path);
                    }
                }
                $image = $this->uploadImage($request,'gyms','image');
                $gym->image = $image;
                $gym->save();
            }

            return  $this->returnSuccessMessage('updated Successfully',200);
        } catch (\Tymon\JWTAuth\Exceptions\UserNotDefinedException $e) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
    }

    public function updateTrainer(Request $request,$id)
    {
        try {
            $admin = auth('admin')->userOrFail();
            $trainer = Trainer::find($id);
            if(!$trainer)
            {
                return $this->returnError(201, 'Not available gym');
            }
            if($request->email && $request->email != "")
            {
                $validator = Validator::make($request->all(), [
                    'email' => 'string|email|min:5|max:255|unique:trainers',
                ]);
                if ($validator->fails()) {
                    return $this->returnValidationError(422, $validator);
                }
                $trainer->email = $request->email;
                $trainer->save();
            }

            if($request->name && $request->name != "")
            {
                $validator = Validator::make($request->all(), [
                    'name' => 'required|string|min:3|max:255',
                ]);
                if ($validator->fails()) {
                    return $this->returnValidationError(422, $validator);
                }
                $trainer->name = $request->name;
                $trainer->save();
            }

            if($request->phone && $request->phone != "")
            {
                $validator = Validator::make($request->all(), [
                    'phone' => 'string|min:9|unique:trainers',
                ]);
                if ($validator->fails()) {
                    return $this->returnValidationError(422, $validator);
                }
                $trainer->phone = $request->phone;
                $trainer->save();
            }

            if($request->image && $request->image != "")
            {
                $validator = Validator::make($request->all(), [
                    'image' => 'required|file',
                ]);
                if ($validator->fails()) {
                    return $this->returnValidationError(422, $validator);
                }
                if($trainer->image)
                {
                    $path =  public_path('/assets/trainers/'.$trainer->image);
                    $image_path = $path;
                    if(File::exists($image_path)) {
                        File::delete($image_path);
                    }
                }
                $image = $this->uploadImage($request,'trainers','image');
                $trainer->image = $image;
                $trainer->save();
            }

            return  $this->returnSuccessMessage('updated Successfully',200);
        } catch (\Tymon\JWTAuth\Exceptions\UserNotDefinedException $e) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
    }

    public function updateGymBooking(Request $request,$id)
    {
        try {
            $admin = auth('admin')->userOrFail();
            $booking = Booking_Gym::find($id);
            if(!$booking)
            {
                return $this->returnError(201, 'Not available booking');
            }

            if($request->date && $request->date != "")
            {
                $validator = Validator::make($request->all(), [
                    'date' => 'required|date_format:Y-m-d',
                ]);
                if ($validator->fails()) {
                    return $this->returnValidationError(422, $validator);
                }
                $booking->date = $request->date;
                $booking->save();
            }


            if($request->attendance_status == '0' || $request->attendance_status && $request->attendance_status != "")
            {
                $validator = Validator::make($request->all(), [
                    'attendance_status' => 'required|in:"0","1"',
                ]);
                if ($validator->fails()) {
                    return $this->returnValidationError(422, $validator);
                }
                $booking->attendance_status = $request->attendance_status;
                $booking->save();
            }

            return  $this->returnSuccessMessage('updated Successfully',200);
        } catch (\Tymon\JWTAuth\Exceptions\UserNotDefinedException $e) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
    }

    public function updateTrainerBooking(Request $request,$id)
    {
        try {
            $admin = auth('admin')->userOrFail();
            $booking = Booking_Trainer::find($id);
            if(!$booking)
            {
                return $this->returnError(201, 'Not available booking');
            }

            if($request->date && $request->date != "")
            {
                $validator = Validator::make($request->all(), [
                    'date' => 'required|date_format:Y-m-d',
                ]);
                if ($validator->fails()) {
                    return $this->returnValidationError(422, $validator);
                }
                $booking->date = $request->date;
                $booking->save();
            }

            return  $this->returnSuccessMessage('updated Successfully',200);
        } catch (\Tymon\JWTAuth\Exceptions\UserNotDefinedException $e) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
    }

    public function blockUser(Request $request,$id)
    {
        try {
            $admin = auth('admin')->userOrFail();
            $user = User::find($id);
            if (!$user) {
                return $this->returnError(201, 'invalid id');
            }
            $user->isBlocked = 1;
            $user->save();
            return  $this->returnSuccessMessage('Blocked Successfully',200);
        } catch (\Tymon\JWTAuth\Exceptions\UserNotDefinedException $e) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
    }

    public function blockGym(Request $request,$id)
    {
        try {
            $admin = auth('admin')->userOrFail();
            $gym = Gym::find($id);
            if (!$gym) {
                return $this->returnError(201, 'invalid id');
            }
            $gym->isBlocked = 1;
            $gym->save();
            return  $this->returnSuccessMessage('Blocked Successfully',200);
        } catch (\Tymon\JWTAuth\Exceptions\UserNotDefinedException $e) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
    }

    public function blockTrainer(Request $request,$id)
    {
        try {
            $admin = auth('admin')->userOrFail();
            $trainer = Trainer::find($id);
            if (!$trainer) {
                return $this->returnError(201, 'invalid id');
            }
            $trainer->isBlocked = 1;
            $trainer->save();
            return  $this->returnSuccessMessage('Blocked Successfully',200);
        } catch (\Tymon\JWTAuth\Exceptions\UserNotDefinedException $e) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
    }

    public function unblockUser(Request $request,$id)
    {
        try {
            $admin = auth('admin')->userOrFail();
            $user = User::find($id);
            if (!$user) {
                return $this->returnError(201, 'invalid id');
            }
            $user->isBlocked = 0;
            $user->save();
            return  $this->returnSuccessMessage('unblocked Successfully',200);
        } catch (\Tymon\JWTAuth\Exceptions\UserNotDefinedException $e) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
    }

    public function unblockGym(Request $request,$id)
    {
        try {
            $admin = auth('admin')->userOrFail();
            $gym = Gym::find($id);
            if (!$gym) {
                return $this->returnError(201, 'invalid id');
            }
            $gym->isBlocked = 0;
            $gym->save();
            return  $this->returnSuccessMessage('unblocked Successfully',200);
        } catch (\Tymon\JWTAuth\Exceptions\UserNotDefinedException $e) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
    }

    public function unblockTrainer(Request $request,$id)
    {
        try {
            $admin = auth('admin')->userOrFail();
            $trainer = Trainer::find($id);
            if (!$trainer) {
                return $this->returnError(201, 'invalid id');
            }
            $trainer->isBlocked = 0;
            $trainer->save();
            return  $this->returnSuccessMessage('unblocked Successfully',200);
        } catch (\Tymon\JWTAuth\Exceptions\UserNotDefinedException $e) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
    }

    public function banUser(Request $request,$id)
    {
        $validator = Validator::make($request->all(), [
            'ban_times' => 'required|numeric',
        ]);
        if ($validator->fails()) {
            return $this->returnValidationError(422, $validator);
        }
        try {
            $admin = auth('admin')->userOrFail();
            $user = User::find($id);
            if (!$user) {
                return $this->returnError(201, 'invalid id');
            }
            $user->ban_times = $request->ban_times;
            $user->save();
            return  $this->returnSuccessMessage('Banned Successfully',200);
        } catch (\Tymon\JWTAuth\Exceptions\UserNotDefinedException $e) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
    }

    public function banGym(Request $request,$id)
    {
        $validator = Validator::make($request->all(), [
            'ban_times' => 'required|numeric',
        ]);
        if ($validator->fails()) {
            return $this->returnValidationError(422, $validator);
        }
        try {
            $admin = auth('admin')->userOrFail();
            $gym = Gym::find($id);
            if (!$gym) {
                return $this->returnError(201, 'invalid id');
            }
            $gym->ban_times = $request->ban_times;
            $gym->save();
            return  $this->returnSuccessMessage('Banned Successfully',200);
        } catch (\Tymon\JWTAuth\Exceptions\UserNotDefinedException $e) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
    }

    public function banTrainer(Request $request,$id)
    {
        $validator = Validator::make($request->all(), [
            'ban_times' => 'required|numeric',
        ]);
        if ($validator->fails()) {
            return $this->returnValidationError(422, $validator);
        }
        try {
            $admin = auth('admin')->userOrFail();
            $trainer = Trainer::find($id);
            if (!$trainer) {
                return $this->returnError(201, 'invalid id');
            }
            $trainer->ban_times = $request->ban_times;
            $trainer->save();
            return  $this->returnSuccessMessage('Banned Successfully',200);
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
}
