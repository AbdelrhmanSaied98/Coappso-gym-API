<?php

namespace App\Http\Controllers;

use App\Mail\sendingEmail;
use App\Models\Admin;
use App\Models\Booking_Gym;
use App\Models\Booking_Trainer;
use App\Models\Gym;
use App\Models\Gym_Branch;
use App\Models\Gym_Class;
use App\Models\Gym_Trainer;
use App\Models\Notification;
use App\Models\Trainer;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class AuthController extends Controller
{

    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'type' => 'required|string|in:"trainer","gym","user"'
        ]);
        if ($validator->fails()) {
            return $this->returnValidationError(422, $validator);
        }
        if($request->type == 'trainer')
        {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|min:3|max:255',
                'phone' => 'required|string|min:9|unique:users|unique:gyms|unique:trainers',
                'email' => 'required|string|email|min:5|max:255|unique:users|unique:gyms|unique:trainers',
                'password' => 'required|string|min:8',
                'device_token'=>'required|string',
            ]);
            if ($validator->fails()) {
                return $this->returnValidationError(422, $validator);
            }
            $newTrainer = new Trainer;
            $newTrainer->name = $request->name;
            $newTrainer->phone = $request->phone;
            $newTrainer->email = $request->email;
            $newTrainer->password = Hash::make($request->password);
            $newTrainer->device_token = $request->device_token;
            $newTrainer->save();
            $credentials = request(['email', 'password']);
            $token = auth('trainer')->setTTL(5)->attempt($credentials);
            $tokenRefresh = auth('trainer')->setTTL(1440)->attempt($credentials);
            $newTrainer->refresh_token = $tokenRefresh;
            $newTrainer->save();

            return $this->respondWithToken($token,$tokenRefresh,$newTrainer);

        }elseif ($request->type == 'gym')
        {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|min:3|max:255',
                'phone' => 'required|string|min:9|unique:users|unique:gyms|unique:trainers',
                'email' => 'required|string|email|min:5|max:255|unique:users|unique:gyms|unique:trainers',
                'password' => 'required|string|min:8',
                'device_token'=>'required|string',
                'location'=>'required|array',
                'address' => 'required|string'
            ]);
            if ($validator->fails()) {
                return $this->returnValidationError(422, $validator);
            }
            $gym = new Gym;
            $gym->name = $request->name;
            $gym->phone = $request->phone;
            $gym->email = $request->email;
            $gym->password = Hash::make($request->password);
            $gym->device_token = $request->device_token;
            $gym->save();

            $newBranch = new Gym_Branch;
            $newBranch->name = "main";
            $newBranch->address = $request->address;
            $location = implode(",", $request->location);
            $newBranch->location = $location;
            $newBranch->gym_id = $gym->id;
            $newBranch->save();

            $credentials = request(['email', 'password']);
            $token = auth('gym')->setTTL(5)->attempt($credentials);
            $tokenRefresh = auth('gym')->setTTL(1440)->attempt($credentials);
            $gym->refresh_token = $tokenRefresh;
            $gym->save();
            return $this->respondWithToken($token,$tokenRefresh,$gym);
        }elseif ($request->type == 'user')
        {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|min:3|max:255',
                'phone' => 'required|string|min:9|unique:users|unique:gyms|unique:trainers',
                'email' => 'required|string|email|min:5|max:255|unique:users|unique:gyms|unique:trainers',
                'password' => 'required|string|min:8',
                'device_token'=>'required|string',
                'age'=>'required',
                'height'=>'required|numeric|min:90|max:250',
                'weight'=>'required|numeric|min:30|max:300',
                'location'=>'required|array',
            ]);
            if ($validator->fails()) {
                return $this->returnValidationError(422, $validator);
            }
            $user = new User;
            $user->name = $request->name;
            $user->phone = $request->phone;
            $user->email = $request->email;
            $user->password = Hash::make($request->password);
            $user->device_token = $request->device_token;

            $date = Carbon::parse($request->age);
            $today = date("Y-m-d");
            $diff = date_diff(date_create($date->format('Y-m-d')), date_create($today));

            $user->age = $diff->format('%y');
            $user->height = $request->height;
            $user->weight = $request->weight;
            $location = implode(",", $request->location);
            $user->location = $location;
            $user->save();
            $credentials = request(['email', 'password']);
            $token = auth('user')->setTTL(5)->attempt($credentials);
            $tokenRefresh = auth('user')->setTTL(1440)->attempt($credentials);
            $user->refresh_token = $tokenRefresh;
            $user->save();
            return $this->respondWithToken($token,$tokenRefresh,$user);
        }

    }

    public function test()
    {
        $basic  = new \Nexmo\Client\Credentials\Basic('d5a7b4e9', 'HVAXftnCYUCM94qJ');
        $client = new \Nexmo\Client($basic);

        $message = $client->message()->send([
            'to' => '201203650266',
            'from' => 'hera',
            'text' => 'Test from hera app verification code is 9857'
        ]);

        dd('SMS message has been delivered.');

    }

    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'type' => 'required|string|in:"trainer","gym","user"',
            'device_token'=>'required|string',
            'isRemembered'=>'required|in:0,1',
        ]);
        if ($validator->fails()) {
            return $this->returnValidationError(422, $validator);
        }
        if($request->type == 'trainer')
        {
            $credentials = request(['email', 'password']);
            $user = null;
            if (! $token = auth('trainer')->setTTL(5)->attempt($credentials)) {
                if(! $token = auth('trainer')->setTTL(5)->attempt(['phone' => $request->email, 'password' => $request->password]))
                {
                    return response()->json(['error' => 'Unauthorized'], 401);
                }
                $user = Trainer::where('phone',$request->email)->first();
            }
            if(! $user)
            {
                $user = Trainer::where('email',$request->email)->first();
            }

            if ($request->isRemembered)
            {
                $tokenRefresh = auth('trainer')->setTTL(259200)->attempt($credentials);
            }else
            {
                $tokenRefresh = auth('trainer')->setTTL(1440)->attempt($credentials);
            }

        }elseif ($request->type == 'gym')
        {
            $credentials = request(['email', 'password']);
            $user = null;

            if ( $token = auth('admin')->setTTL(1440)->attempt($credentials)) {
                $user = Admin::where('email',$request->email)->first();
                $user->device_token = $request->device_token;
                $user->save();
                $user->isAdmin = 1;
                return $this->respondWithToken($token,"",$user);

            }


            if (! $token = auth('gym')->setTTL(5)->attempt($credentials)) {
                if(! $token = auth('gym')->setTTL(5)->attempt(['phone' => $request->email, 'password' => $request->password]))
                {
                    return response()->json(['error' => 'Unauthorized'], 401);
                }
                $user = Gym::where('phone',$request->email)->first();
            }
            if(! $user)
            {
                $user = Gym::where('email',$request->email)->first();
            }

            if ($request->isRemembered)
            {
                $tokenRefresh = auth('gym')->setTTL(259200)->attempt($credentials);
            }else
            {
                $tokenRefresh = auth('gym')->setTTL(1440)->attempt($credentials);
            }

        }elseif ($request->type == 'user')
        {
            $credentials = request(['email', 'password']);
            $user = null;
            if (! $token = auth('user')->setTTL(5)->attempt($credentials)) {
                if(! $token = auth('user')->setTTL(5)->attempt(['phone' => $request->email, 'password' => $request->password]))
                {
                    return response()->json(['error' => 'Unauthorized'], 401);
                }
                $user = User::where('phone',$request->email)->first();
            }
            if(! $user)
            {
                $user = User::where('email',$request->email)->first();
            }

            if ($request->isRemembered)
            {
                $tokenRefresh = auth('user')->setTTL(259200)->attempt($credentials);
            }else
            {
                $tokenRefresh = auth('user')->setTTL(1440)->attempt($credentials);
            }
        }
        $user->device_token = $request->device_token;
        $user->refresh_token = $tokenRefresh;
        $user->save();
        $user->isAdmin = 0;
        return $this->respondWithToken($token,$tokenRefresh,$user);
    }

    public function me()
    {
        return response()->json(auth()->user());
    }

    public function logout(Request $request)
    {

        if($request->header('type') == 'trainer')
        {
            try {
                $user = auth('trainer')->userOrFail();
                if($request->device_token == $user->device_token)
                {
                    $user->device_token = "";
                    $user->save();
                }
                auth('trainer')->logout();
                return response()->json(['message' => 'Successfully logged out']);
            } catch (\Tymon\JWTAuth\Exceptions\UserNotDefinedException $e) {
                return response()->json(['error' => 'Unauthorized'], 401);
            }
        }elseif($request->header('type') == 'user')
        {
            try {
                $user = auth('user')->userOrFail();
                if($request->device_token == $user->device_token)
                {
                    $user->device_token = "";
                    $user->save();
                }
                auth('user')->logout();
                return response()->json(['message' => 'Successfully logged out']);
            } catch (\Tymon\JWTAuth\Exceptions\UserNotDefinedException $e) {
                return response()->json(['error' => 'Unauthorized'], 401);
            }
        }elseif($request->header('type') == 'gym')
        {
            try {
                $user = auth('gym')->userOrFail();
                if($request->device_token == $user->device_token)
                {
                    $user->device_token = "";
                    $user->save();
                }
                auth('gym')->logout();
                return response()->json(['message' => 'Successfully logged out']);
            } catch (\Tymon\JWTAuth\Exceptions\UserNotDefinedException $e) {
                return response()->json(['error' => 'Unauthorized'], 401);
            }
        }elseif($request->header('type') == 'admin')
        {
            try {
                $user = auth('admin')->userOrFail();
                if($request->device_token == $user->device_token)
                {
                    $user->device_token = "";
                    $user->save();
                }
                auth('admin')->logout();
                return response()->json(['message' => 'Successfully logged out']);
            } catch (\Tymon\JWTAuth\Exceptions\UserNotDefinedException $e) {
                return response()->json(['error' => 'Unauthorized'], 401);
            }
        }
    }

    public function uploadProfileImage(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'image' => 'required|file',
        ]);
        if ($validator->fails()) {
            return $this->returnValidationError(422, $validator);
        }
        if($request->header('type') == 'user')
        {
            try {
                $user = auth('user')->userOrFail();
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
                return response()->json(['message' => 'Successfully Uploaded']);
            } catch (\Tymon\JWTAuth\Exceptions\UserNotDefinedException $e) {
                return response()->json(['error' => 'Unauthorized'], 401);
            }
        }elseif ($request->header('type') == 'gym')
        {
            try {
                $user = auth('gym')->userOrFail();
                if($user->image)
                {
                    $path =  public_path('/assets/gyms/'.$user->image);
                    $image_path = $path;
                    if(File::exists($image_path)) {
                        File::delete($image_path);
                    }
                }
                $image = $this->uploadImage($request,'gyms','image');
                $user->image = $image;
                $user->save();
                return response()->json(['message' => 'Successfully Uploaded']);
            } catch (\Tymon\JWTAuth\Exceptions\UserNotDefinedException $e) {
                return response()->json(['error' => 'Unauthorized'], 401);
            }
        }elseif ($request->header('type') == 'trainer')
        {
            try {
                $user = auth('trainer')->userOrFail();
                if($user->image)
                {
                    $path =  public_path('/assets/trainers/'.$user->image);
                    $image_path = $path;
                    if(File::exists($image_path)) {
                        File::delete($image_path);
                    }
                }
                $image = $this->uploadImage($request,'trainers','image');
                $user->image = $image;
                $user->save();
                return response()->json(['message' => 'Successfully Uploaded']);
            } catch (\Tymon\JWTAuth\Exceptions\UserNotDefinedException $e) {
                return response()->json(['error' => 'Unauthorized'], 401);
            }
        }
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

    public function forgetPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'type' => 'required|in:"user","gym","trainer"',
            'email' => 'required|string|email',
        ]);
        if ($validator->fails()) {
            return $this->returnValidationError(422, $validator);
        }

        $type = $request->type;
        if($type == 'user')
        {
            $user = User::where('email', $request->email)->get();
        }elseif ($type == 'gym')
        {
            $user = Gym::where('email', $request->email)->get();
        }elseif ($type == 'trainer')
        {
            $user = Trainer::where('email', $request->email)->get();
        }

        if (count($user) > 0) {
            $rand = mt_rand(10000, 99999);
            $objDemo = 'Hello There , Your Activation code is '. $rand;
            Mail::to($user[0]->email)->send(new sendingEmail($objDemo));
            $user[0]->update([
                'verification_code' => $rand
            ]);
            return $this->returnSuccessMessage(
                [
                    'msg' => 'Check Your Phone And Enter the code'
                ], 200);

        } else {
            return $this->returnError(201, 'Email Not Found');
        }
    }

    public function updatePassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'type' => 'required|in:"user","gym","trainer"',
            'email' => 'required|string|email',
            'password' => 'required|string|min:8',
        ]);
        if ($validator->fails()) {
            return $this->returnValidationError(422, $validator);
        }

        $type = $request->type;
        if($type == 'user')
        {
            $user = User::where('email', $request->email)->get();
        }elseif ($type == 'gym')
        {
            $user = Gym::where('email', $request->email)->get();
        }elseif ($type == 'trainer')
        {
            $user = Trainer::where('email', $request->email)->get();
        }

        if (count($user) > 0) {
            $rand = mt_rand(10000, 99999);
            $user[0]->verification_code = $rand;
            $user[0]->password = Hash::make($request->password);
            $user[0]->save();
            return $this->returnSuccessMessage('Updated Successfully',200);
        } else {
            return $this->returnError(201, 'Email Not Found');
        }
    }

    public function verifyCode(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'type' => 'required|in:"user","gym","trainer"',
            'email' => 'required|string|email',
            'verification_code'=>'required',
        ]);
        if ($validator->fails()) {
            return $this->returnValidationError(422, $validator);
        }

        $type = $request->type;
        if($type == 'user')
        {
            $user = User::where('email', $request->email)->get();
        }elseif ($type == 'gym')
        {
            $user = Gym::where('email', $request->email)->get();
        }elseif ($type == 'trainer')
        {
            $user = Trainer::where('email', $request->email)->get();
        }

        if (count($user) > 0) {
            if($request->verification_code == $user[0]->verification_code)
            {

                return $this->returnSuccessMessage('Go to Next Step',200);
            }else
            {
                return $this->returnError(201, 'verification code is wrong');
            }
        } else {
            return $this->returnError(201, 'Email Not Found');
        }
    }

    public function getUserBooking($numOfPage,$numOfRows)
    {
        try {
            $user = auth('user')->userOrFail();

            $booking = Booking_Gym::where('user_id',$user->id)->orderBy('date','DESC')->get();
            $counter = count($booking);
            $skippedNumbers = ($numOfPage - 1) * $numOfRows;

            $booking = Booking_Gym::where('user_id',$user->id)->orderBy('date','DESC')
                ->skip($skippedNumbers)
                ->take($numOfRows)
                ->get();



            $booking = collect($booking)->map(function($oneRecord)
            {
                if($oneRecord->gym_branch->gym->image)
                {
                    $oneRecord->gym_branch->gym->image = asset('/assets/gyms/' . $oneRecord->gym_branch->gym->image);
                }
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
                        "name" => $oneRecord->gym_branch->gym->name,
                        "image" => $oneRecord->gym_branch->gym->image,
                        "address" => $oneRecord->gym_branch->address,
                        "date" => $oneRecord->date,
                        "classes" => $names,
                        "isWithTrainer" => $oneRecord->isWithTrainer,
                        "attendance_status" => $oneRecord->attendance_status,
                    ];
            });
            $result = [
                'counter'=>$counter,
                'booking'=>$booking
            ];
            return $this->returnData(['response'], [$result],'Booking Data');
        } catch (\Tymon\JWTAuth\Exceptions\UserNotDefinedException $e) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
    }

    public function getOneGymBooking($id)
    {
        try {
            $user = auth('user')->userOrFail();
            $booking_Gym =  Booking_Gym::find($id);
            if(! $booking_Gym || $booking_Gym->user->id != $user->id)
            {
                return $this->returnError(201, 'Invalid booking id');
            }


            if($booking_Gym->gym_branch->gym->image)
            {
                $booking_Gym->gym_branch->gym->image = asset('/assets/gyms/' . $booking_Gym->gym_branch->gym->image);
            }
            $names = [];
            $classes = explode(",", $booking_Gym->classes);
            foreach ($classes as $class)
            {
                $Class = Gym_Class::find($class);
                $names [] = $Class->name;
            }

            $result =
                [
                    "id" => $booking_Gym->id,
                    "name" => $booking_Gym->gym_branch->gym->name,
                    "image" => $booking_Gym->gym_branch->gym->image,
                    "address" => $booking_Gym->gym_branch->address,
                    "date" => $booking_Gym->date,
                    "classes" => $names,
                    "isWithTrainer" => $booking_Gym->isWithTrainer,
                    "attendance_status" => $booking_Gym->attendance_status,
                    "trainers" => $booking_Gym->gym_branch->gym_trainers,
                ];
            return $this->returnData(['response'], [$result],'Booking Data');
        } catch (\Tymon\JWTAuth\Exceptions\UserNotDefinedException $e) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
    }

    public function getUserBookingTrainer($numOfPage,$numOfRows)
    {
        try {
            $user = auth('user')->userOrFail();

            $booking = Booking_Trainer::where('user_id',$user->id)->orderBy('date','DESC')->get();
            $counter = count($booking);
            $skippedNumbers = ($numOfPage - 1) * $numOfRows;

            $booking = Booking_Trainer::where('user_id',$user->id)->orderBy('date','DESC')
                ->skip($skippedNumbers)
                ->take($numOfRows)
                ->get();



            $booking = collect($booking)->map(function($oneRecord)
            {
                if($oneRecord->trainer->image)
                {
                    $oneRecord->trainer->image = asset('/assets/gyms/' . $oneRecord->trainer->image);
                }

                return
                    [
                        "id" => $oneRecord->id,
                        "name" => $oneRecord->trainer->name,
                        "image" => $oneRecord->trainer->image,
                        "date" => $oneRecord->date,
                    ];
            });
            $result = [
                'counter'=>$counter,
                'booking'=>$booking
            ];
            return $this->returnData(['response'], [$result],'Booking Data');
        } catch (\Tymon\JWTAuth\Exceptions\UserNotDefinedException $e) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
    }

    public function getNotification(Request $request,$numOfPage,$numOfRows)
    {
        if($request->header('type') == 'user')
        {
            try {
                $user = auth('user')->userOrFail();

                $notifications = Notification::where('user_type','user')->where('user_id',$user->id)->orderBy('created_at', 'DESC')
                    ->get();
                $counter = count($notifications);
                $skippedNumbers = ($numOfPage - 1) * $numOfRows;
                $notifications = Notification::where('user_type','user')->where('user_id',$user->id)->orderBy('created_at', 'DESC')
                    ->skip($skippedNumbers)
                    ->take($numOfRows)
                    ->get();
                Notification::where('user_type','user')->where('user_id',$user->id)->orderBy('created_at', 'DESC')
                    ->update(['seen' => 1]);
                $notifications = collect($notifications)->map(function($oneNotification)
                {
                    return
                        [
                            "content_type" => $oneNotification->content_type,
                            "notification" => $oneNotification->notification,
                            "content_id" => $oneNotification->content_id,
                            "seen" => $oneNotification->seen,
                            "created_at" => date('g:i A', strtotime($oneNotification->created_at)),

                        ];

                });
                $result = [
                    'notification' => $notifications,
                    'length' =>$counter
                ];
                return $this->returnData(['response'], [$result],'Notifications Data');
            } catch (\Tymon\JWTAuth\Exceptions\UserNotDefinedException $e) {
                return response()->json(['error' => 'Unauthorized'], 401);
            }
        }elseif($request->header('type') == 'gym')
        {
            try {
                $user = auth('gym')->userOrFail();
                $notifications = Notification::where('user_type','gym')->where('user_id',$user->id)->orderBy('created_at', 'DESC')
                    ->get();
                $counter = count($notifications);
                $skippedNumbers = ($numOfPage - 1) * $numOfRows;
                $notifications = Notification::where('user_type','gym')->where('user_id',$user->id)->orderBy('created_at', 'DESC')
                    ->skip($skippedNumbers)
                    ->take($numOfRows)
                    ->get();
                Notification::where('user_type','gym')->where('user_id',$user->id)->orderBy('created_at', 'DESC')
                    ->update(['seen' => 1]);
                $notifications = collect($notifications)->map(function($oneNotification)
                {
                    return
                        [
                            "content_type" => $oneNotification->content_type,
                            "notification" => $oneNotification->notification,
                            "content_id" => $oneNotification->content_id,
                            "seen" => $oneNotification->seen,
                            "created_at" => date('g:i A', strtotime($oneNotification->created_at)),
                        ];

                });
                $result = [
                    'notification' => $notifications,
                    'length' =>$counter
                ];
                return $this->returnData(['response'], [$result],'Notifications Data');
            } catch (\Tymon\JWTAuth\Exceptions\UserNotDefinedException $e) {
                return response()->json(['error' => 'Unauthorized'], 401);
            }
        }elseif($request->header('type') == 'trainer')
        {
            try {
                $user = auth('trainer')->userOrFail();
                $notifications = Notification::where('user_type','trainer')->where('user_id',$user->id)->orderBy('created_at', 'DESC')
                    ->get();
                $counter = count($notifications);
                $skippedNumbers = ($numOfPage - 1) * $numOfRows;
                $notifications = Notification::where('user_type','trainer')->where('user_id',$user->id)->orderBy('created_at', 'DESC')
                    ->skip($skippedNumbers)
                    ->take($numOfRows)
                    ->get();
                Notification::where('user_type','trainer')->where('user_id',$user->id)->orderBy('created_at', 'DESC')
                    ->update(['seen' => true]);
                $notifications = collect($notifications)->map(function($oneNotification)
                {
                    return
                        [
                            "content_type" => $oneNotification->content_type,
                            "notification" => $oneNotification->notification,
                            "content_id" => $oneNotification->content_id,
                            "seen" => $oneNotification->seen,
                            "created_at" => date('g:i A', strtotime($oneNotification->created_at)),
                        ];

                });
                $result = [
                    'notification' => $notifications,
                    'length' =>$counter
                ];
                return $this->returnData(['response'], [$result],'Notifications Data');
            } catch (\Tymon\JWTAuth\Exceptions\UserNotDefinedException $e) {
                return response()->json(['error' => 'Unauthorized'], 401);
            }
        }
    }

    public function updateUser(Request $request)
    {
        try {
            $user = auth('user')->userOrFail();
            if(!$user)
            {
                return $this->returnError(201, 'Not available user');
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

            return  $this->returnSuccessMessage('updated Successfully',200);
        } catch (\Tymon\JWTAuth\Exceptions\UserNotDefinedException $e) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
    }

    public function home()
    {
        try {
            $user = auth('user')->userOrFail();

            $offers = Gym_Class::where('is_offer','1')->get();
            $alloffers = [];
            foreach ($offers as $offer)
            {
                $newPrice = $offer->new_price;
                $precent = ($newPrice / $offer->price) * 100;
                $object =
                    [
                        'id' => $offer->id,
                        'price' => $precent
                    ];
                $alloffers [] = $object;
            }
            $this->array_sort_by_column($alloffers, 'price');
            $firstThreeElements = array_slice($alloffers, 0, 3);
            $bestOffers = collect($firstThreeElements)->map(function($oneRecord)
            {
                $gym_class = Gym_Class::find($oneRecord['id']);

                if($gym_class->image)
                {
                    $gym_class->image = asset('/assets/classes/' . $gym_class->image );
                }
                return
                    [
                        "id" => $gym_class->gym_branch->gym->id,
                        "name" => $gym_class->name,
                        "image" => $gym_class->image,
                        "percent" => $oneRecord['price'],
                    ];
            });
            return $this->returnData(['response'], [$bestOffers],'Home Data');
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

    public function teste()
    {
        return QrCode::size(200)
        ->generate('1001');
    }

    public function getBranches()
    {
        $branches = Gym_Branch::all();
        $branches = collect($branches)->map(function($oneRecord)
        {

            if($oneRecord->gym->image)
            {
                $oneRecord->gym->image = asset('/assets/gyms/' . $oneRecord->gym->image );
            }
            $location = explode(',',$oneRecord->location);
            return
                [
                    "id" => $oneRecord->gym->id,
                    "name" => $oneRecord->gym->name,
                    "image" => $oneRecord->gym->image,
                    "location" => $location,
                ];
        });
        return $this->returnData(['response'], [$branches],'Branches Data');
    }

    public function profile($type,$id)
    {
        if($type == 'user')
        {
            try {
                $user = User::find($id);
                if(!$user)
                {
                    return $this->returnError(201, 'Not a user');
                }
                if($user->image)
                {
                    $user->image = asset('/assets/users/' . $user->image );
                }
                return $this->returnData(['response'], [$user],'User Data');
            } catch (\Tymon\JWTAuth\Exceptions\UserNotDefinedException $e) {
                return response()->json(['error' => 'Unauthorized'], 401);
            }
        }elseif($type == 'gym')
        {
            try {
                $gym = Gym::find($id);
                if(!$gym)
                {
                    return $this->returnError(201, 'Not a gym');
                }
                if($gym->image)
                {
                    $gym->image = asset('/assets/gyms/' . $gym->image );
                }

                $gym_trainers = [];
                foreach ($gym->gym_branches as $branch)
                {
                    foreach ($branch->gym_trainers as $trainer)
                    {
                        $gym_trainers [] = $trainer->id;
                    }
                }

                $gym_branches = collect($gym->gym_branches)->map(function($oneRecord)
                {
                    return
                        [
                            "id" => $oneRecord->id,
                            "name" => $oneRecord->name,
                            "address" => $oneRecord->address,
                            "time_to" => $oneRecord->time_to,
                            "time_from" => $oneRecord->time_from,
                        ];

                });

                $gym_trainers = collect($gym_trainers)->map(function($oneRecord)
                {
                    $trainer = Gym_Trainer::find($oneRecord);
                    if($trainer->image)
                    {
                        $trainer->image = asset('/assets/gym_trainers/' . $trainer->image );
                    }
                    return
                        [
                            "id" => $trainer->id,
                            "name" => $trainer->name,
                            "image" => $trainer->image,
                        ];

                });
                try {
                    $user = auth('user')->userOrFail();
                    $gym->isFavorite = false;
                    foreach ($user->favorite_gyms as $favoirte)
                    {
                        if($favoirte->gym->id == $gym->id)
                        {
                            $gym->isFavorite = true;
                            break;
                        }
                    }
                } catch (\Tymon\JWTAuth\Exceptions\UserNotDefinedException $e) {
                    $gym->isFavorite = false;
                }

                unset(
                        $gym->gym_branches
                    );
                $result =
                    [
                        'gym' => $gym,
                        'gym_branches' => $gym_branches,
                        'gym_trainers' => $gym_trainers,
                    ];

                return $this->returnData(['response'], [$result],'Gym Data');
            } catch (\Tymon\JWTAuth\Exceptions\UserNotDefinedException $e) {
                return response()->json(['error' => 'Unauthorized'], 401);
            }
        }elseif($type == 'trainer')
        {
            try {
                $trainer = Trainer::find($id);
                if(!$trainer)
                {
                    return $this->returnError(201, 'Not a Trainer');
                }
                if($trainer->image)
                {
                    $trainer->image = asset('/assets/trainers/' . $trainer->image );
                }


                try {
                    $user = auth('user')->userOrFail();
                    $trainer->isFavorite = false;
                    foreach ($user->favorite_trainers as $favoirte)
                    {
                        if($favoirte->trainer->id == $trainer->id)
                        {
                            $trainer->isFavorite = true;
                            break;
                        }
                    }
                } catch (\Tymon\JWTAuth\Exceptions\UserNotDefinedException $e) {
                    $trainer->isFavorite = false;
                }



                return $this->returnData(['response'], [$trainer],'Gym Data');
            } catch (\Tymon\JWTAuth\Exceptions\UserNotDefinedException $e) {
                return response()->json(['error' => 'Unauthorized'], 401);
            }
        }
    }

    public function refresh()
    {
        return $this->respondWithToken(auth()->refresh());
    }

    public function getNewToken(Request $request,$type)
    {
        if ($type == "user")
        {
            try {
                $user = auth('user')->userOrFail();

                $token = auth('user')->setTTL(5)->login($user);


                return $this->returnData(['response'], [$token],'Token');
            } catch (\Tymon\JWTAuth\Exceptions\UserNotDefinedException $e) {
                return response()->json(['error' => 'Unauthorized'], 401);
            }
        }elseif ($type == "gym")
        {

            try {
                $gym = auth('gym')->userOrFail();

                $token = auth('gym')->setTTL(5)->login($gym);


                return $this->returnData(['response'], [$token],'Token');
            } catch (\Tymon\JWTAuth\Exceptions\UserNotDefinedException $e) {
                return response()->json(['error' => 'Unauthorized'], 401);
            }

        }elseif ($type == "trainer")
        {

            try {
                $trainer = auth('trainer')->userOrFail();

                $token = auth('trainer')->setTTL(5)->login($trainer);


                return $this->returnData(['response'], [$token],'Token');
            } catch (\Tymon\JWTAuth\Exceptions\UserNotDefinedException $e) {
                return response()->json(['error' => 'Unauthorized'], 401);
            }

        }
    }

    public function testAuth()
    {
        try {
            $gym = auth('gym')->userOrFail();
            return $gym;
        } catch (\Tymon\JWTAuth\Exceptions\UserNotDefinedException $e) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
    }

    protected function respondWithToken($token,$tokenRefresh,$user)
    {
        if($user->gym_branches)
        {
            $user->main_branch_id = $user->gym_branches[0]->id;
            $result = $user;
            unset(
                $user->gym_branches
            );
        }else
        {
            $result = $user;
        }
        return response()->json([
            'access_token' => $token,
            'refresh_token' => $tokenRefresh,
            'token_type' => 'Bearer',
            'users'=>$result,
        ]);
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
