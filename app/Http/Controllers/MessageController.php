<?php

namespace App\Http\Controllers;

use App\Events\Messaging;
use App\Models\Gym;
use App\Models\Message;
use App\Models\Trainer;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class MessageController extends Controller
{
    public function index(Request $request,$type,$id,$numOfPage,$numOfRows)
    {
        if($request->header('type') == 'user')
        {
            try {
                $user = auth('user')->userOrFail();
                if($type == 'gym')
                {
                    $gym = Gym::find($id);
                    if(!$gym)
                    {
                        return $this->returnError(201, 'Not gym id');
                    }
                    $messages = Message::where('user_id',$user->id)->where('gym_id',$gym->id)->orderBy('created_at', 'DESC')
                        ->get();
                    $counter = count($messages);
                    $skippedNumbers = ($numOfPage - 1) * $numOfRows;
                    $messages = Message::where('user_id',$user->id)->where('gym_id',$gym->id)->orderBy('created_at', 'DESC')
                        ->skip($skippedNumbers)
                        ->take($numOfRows)
                        ->get();
                    $messages = collect($messages)->map(function($oneMessage)
                    {
                        if($oneMessage->content_type != 'text')
                        {
                            $oneMessage->content = asset('/assets/messages/' . $oneMessage->content );
                        }
                        return
                            [
                                "content" => $oneMessage->content,
                                "content_type" => $oneMessage->content_type,
                                "sender_type" => $oneMessage->sender_type,
                                "created_at" => $oneMessage->created_at,
                            ];

                    });
                    $result = [
                        'messages' => $messages,
                        'length' =>$counter
                    ];
                    return $this->returnData(['response'], [$result],'Messages Data');
                }elseif ($type == 'trainer')
                {
                    $trainer = Trainer::find($id);
                    if(!$trainer)
                    {
                        return $this->returnError(201, 'Not trainer id');
                    }
                    $messages = Message::where('user_id',$user->id)->where('trainer_id',$trainer->id)->orderBy('created_at', 'DESC')
                        ->get();
                    $counter = count($messages);
                    $skippedNumbers = ($numOfPage - 1) * $numOfRows;
                    $messages = Message::where('user_id',$user->id)->where('trainer_id',$trainer->id)->orderBy('created_at', 'DESC')
                        ->skip($skippedNumbers)
                        ->take($numOfRows)
                        ->get();
                    $messages = collect($messages)->map(function($oneMessage)
                    {
                        if($oneMessage->content_type != 'text')
                        {
                            $oneMessage->content = asset('/assets/messages/' . $oneMessage->content );
                        }
                        return
                            [
                                "content" => $oneMessage->content,
                                "content_type" => $oneMessage->content_type,
                                "sender_type" => $oneMessage->sender_type,
                                "created_at" => $oneMessage->created_at,
                            ];

                    });
                    $result = [
                        'messages' => $messages,
                        'length' =>$counter
                    ];
                    return $this->returnData(['response'], [$result],'Messages Data');
                }
            } catch (\Tymon\JWTAuth\Exceptions\UserNotDefinedException $e) {
                return response()->json(['error' => 'Unauthorized'], 401);
            }
        }elseif($request->header('type') == 'gym')
        {
            try {
                $gym = auth('gym')->userOrFail();


                if($type == 'user')
                {
                    $user = User::find($id);
                    if(!$user)
                    {
                        return $this->returnError(201, 'Not user id');
                    }
                    $messages = Message::where('user_id',$user->id)->where('gym_id',$gym->id)->orderBy('created_at', 'DESC')
                        ->get();
                    $counter = count($messages);
                    $skippedNumbers = ($numOfPage - 1) * $numOfRows;
                    $messages = Message::where('user_id',$user->id)->where('gym_id',$gym->id)->orderBy('created_at', 'DESC')
                        ->skip($skippedNumbers)
                        ->take($numOfRows)
                        ->get();
                    $messages = collect($messages)->map(function($oneMessage)
                    {
                        if($oneMessage->content_type != 'text')
                        {
                            $oneMessage->content = asset('/assets/messages/' . $oneMessage->content );
                        }
                        return
                            [
                                "content" => $oneMessage->content,
                                "content_type" => $oneMessage->content_type,
                                "sender_type" => $oneMessage->sender_type,
                                "created_at" => $oneMessage->created_at,
                            ];

                    });
                    $result = [
                        'messages' => $messages,
                        'length' =>$counter
                    ];
                    return $this->returnData(['response'], [$result],'Messages Data');
                }elseif ($type == 'trainer')
                {
                    $trainer = Trainer::find($id);
                    if(!$trainer)
                    {
                        return $this->returnError(201, 'Not trainer id');
                    }
                    $messages = Message::where('gym_id',$gym->id)->where('trainer_id',$trainer->id)->orderBy('created_at', 'DESC')
                        ->get();
                    $counter = count($messages);
                    $skippedNumbers = ($numOfPage - 1) * $numOfRows;
                    $messages = Message::where('gym_id',$gym->id)->where('trainer_id',$trainer->id)->orderBy('created_at', 'DESC')
                        ->skip($skippedNumbers)
                        ->take($numOfRows)
                        ->get();
                    $messages = collect($messages)->map(function($oneMessage)
                    {
                        if($oneMessage->content_type != 'text')
                        {
                            $oneMessage->content = asset('/assets/messages/' . $oneMessage->content );
                        }
                        return
                            [
                                "content" => $oneMessage->content,
                                "content_type" => $oneMessage->content_type,
                                "sender_type" => $oneMessage->sender_type,
                                "created_at" => $oneMessage->created_at,
                            ];

                    });
                    $result = [
                        'messages' => $messages,
                        'length' =>$counter
                    ];
                    return $this->returnData(['response'], [$result],'Messages Data');
                }
            } catch (\Tymon\JWTAuth\Exceptions\UserNotDefinedException $e) {
                return response()->json(['error' => 'Unauthorized'], 401);
            }
        }elseif($request->header('type') == 'trainer')
        {
            try {
                $trainer = auth('trainer')->userOrFail();
                if($type == 'user')
                {
                    $user = User::find($id);
                    if(!$user)
                    {
                        return $this->returnError(201, 'Not user id');
                    }
                    $messages = Message::where('user_id',$user->id)->where('trainer_id',$trainer->id)->orderBy('created_at', 'DESC')
                        ->get();
                    $counter = count($messages);
                    $skippedNumbers = ($numOfPage - 1) * $numOfRows;
                    $messages = Message::where('user_id',$user->id)->where('trainer_id',$trainer->id)->orderBy('created_at', 'DESC')
                        ->skip($skippedNumbers)
                        ->take($numOfRows)
                        ->get();
                    $messages = collect($messages)->map(function($oneMessage)
                    {
                        if($oneMessage->content_type != 'text')
                        {
                            $oneMessage->content = asset('/assets/messages/' . $oneMessage->content );
                        }
                        return
                            [
                                "content" => $oneMessage->content,
                                "content_type" => $oneMessage->content_type,
                                "sender_type" => $oneMessage->sender_type,
                                "created_at" => $oneMessage->created_at,
                            ];

                    });
                    $result = [
                        'messages' => $messages,
                        'length' =>$counter
                    ];
                    return $this->returnData(['response'], [$result],'Messages Data');
                }elseif ($type == 'gym')
                {
                    $gym = Gym::find($id);
                    if(!$gym)
                    {
                        return $this->returnError(201, 'Not gym id');
                    }
                    $messages = Message::where('gym_id',$gym->id)->where('trainer_id',$trainer->id)->orderBy('created_at', 'DESC')
                        ->get();
                    $counter = count($messages);
                    $skippedNumbers = ($numOfPage - 1) * $numOfRows;
                    $messages = Message::where('gym_id',$gym->id)->where('trainer_id',$trainer->id)->orderBy('created_at', 'DESC')
                        ->skip($skippedNumbers)
                        ->take($numOfRows)
                        ->get();
                    $messages = collect($messages)->map(function($oneMessage)
                    {
                        if($oneMessage->content_type != 'text')
                        {
                            $oneMessage->content = asset('/assets/messages/' . $oneMessage->content );
                        }
                        return
                            [
                                "content" => $oneMessage->content,
                                "content_type" => $oneMessage->content_type,
                                "sender_type" => $oneMessage->sender_type,
                                "created_at" => $oneMessage->created_at,
                            ];

                    });
                    $result = [
                        'messages' => $messages,
                        'length' =>$counter
                    ];
                    return $this->returnData(['response'], [$result],'Messages Data');
                }
            } catch (\Tymon\JWTAuth\Exceptions\UserNotDefinedException $e) {
                return response()->json(['error' => 'Unauthorized'], 401);
            }
        }
    }

    public function store(Request $request,$type,$id)
    {
        $validator = Validator::make($request->all(), [
            'contentMessage' => 'required',
            'content_type' => 'required|string',
            'channel_name' =>'required|string'
        ]);
        if ($validator->fails()) {
            return $this->returnValidationError(422, $validator);
        }
        if($request->header('type') == 'user')
        {
            try {
                $user = auth('user')->userOrFail();
                if($type == 'gym')
                {
                    $gym = Gym::find($id);
                    if(!$gym)
                    {
                        return $this->returnError(201, 'Not Gym id');
                    }
                    if($request->content_type == 'text')
                    {
                        $newMessage = new Message;
                        $newMessage->content_type = $request->content_type;
                        $newMessage->content = $request->contentMessage;
                        $newMessage->user_id = $user->id;
                        $newMessage->gym_id = $gym->id;
                        $newMessage->sender_type = 'user';
                        $newMessage->receiver_type = 'gym';
                        $newMessage->save();
                        event(new Messaging($newMessage));
                        $pusher = new \Pusher\Pusher('8525a86f244e2694c04d','63dcb37f350e18687dd8','1430924',['cluster' =>'eu']);
                        $data = $pusher->getPresenceUsers($request->channel_name);
                        $size = count($data->users);
                        if($size <= 1)
                        {
                            $this->NotifyApi($gym->device_token,$user->name,$newMessage->content);
                        }
                    }else
                    {
                        $newMessage = new Message;
                        $newMessage->content_type = $request->content_type;
                        $image = $this->uploadImage($request,'messages','contentMessage');
                        $newMessage->content = $image;
                        $newMessage->user_id = $user->id;
                        $newMessage->gym_id = $gym->id;
                        $newMessage->sender_type = 'user';
                        $newMessage->receiver_type = 'gym';
                        event(new Messaging($newMessage));
                        $pusher = new \Pusher\Pusher('8525a86f244e2694c04d','63dcb37f350e18687dd8','1430924',['cluster' =>'eu']);
                        $data = $pusher->getPresenceUsers($request->channel_name);
                        $size = count($data->users);
                        if($size <= 1)
                        {
                            $this->NotifyApi($gym->device_token,$user->name,"image");
                        }
                    }
                }elseif ($type == 'trainer')
                {

                    $trainer = Trainer::find($id);
                    if(!$trainer)
                    {
                        return $this->returnError(201, 'Not trainer id');
                    }
                    if($request->content_type == 'text')
                    {
                        $newMessage = new Message;
                        $newMessage->content_type = $request->content_type;
                        $newMessage->content = $request->contentMessage;
                        $newMessage->user_id = $user->id;
                        $newMessage->trainer_id = $trainer->id;
                        $newMessage->sender_type = 'user';
                        $newMessage->receiver_type = 'trainer';
                        $newMessage->save();
                        event(new Messaging($newMessage));
                        $pusher = new \Pusher\Pusher('8525a86f244e2694c04d','63dcb37f350e18687dd8','1430924',['cluster' =>'eu']);
                        $data = $pusher->getPresenceUsers($request->channel_name);
                        $size = count($data->users);
                        if($size <= 1)
                        {
                            $this->NotifyApi($trainer->device_token,$user->name,$newMessage->content);
                        }

                    }else
                    {
                        $newMessage = new Message;
                        $newMessage->content_type = $request->content_type;
                        $image = $this->uploadImage($request,'messages','contentMessage');
                        $newMessage->content = $image;
                        $newMessage->user_id = $user->id;
                        $newMessage->trainer_id = $trainer->id;
                        $newMessage->sender_type = 'user';
                        $newMessage->receiver_type = 'trainer';
                        event(new Messaging($newMessage));
                        $pusher = new \Pusher\Pusher('8525a86f244e2694c04d','63dcb37f350e18687dd8','1430924',['cluster' =>'eu']);
                        $data = $pusher->getPresenceUsers($request->channel_name);
                        $size = count($data->users);
                        if($size <= 1)
                        {
                            $this->NotifyApi($trainer->device_token,$user->name,"image");
                        }
                    }
                }
                return $this->returnSuccessMessage('Added Successfully',200);
            } catch (\Tymon\JWTAuth\Exceptions\UserNotDefinedException $e) {
                return response()->json(['error' => 'Unauthorized'], 401);
            }
        }elseif ($request->header('type') == 'gym')
        {
            try {
                $gym = auth('gym')->userOrFail();

                if($type == 'user')
                {
                    $user = User::find($id);
                    if(!$user)
                    {
                        return $this->returnError(201, 'Not user id');
                    }
                    if($request->content_type == 'text')
                    {
                        $newMessage = new Message;
                        $newMessage->content_type = $request->content_type;
                        $newMessage->content = $request->contentMessage;
                        $newMessage->user_id = $user->id;
                        $newMessage->gym_id = $gym->id;
                        $newMessage->sender_type = 'gym';
                        $newMessage->receiver_type = 'user';
                        $newMessage->save();
                        event(new Messaging($newMessage));
                        $pusher = new \Pusher\Pusher('8525a86f244e2694c04d','63dcb37f350e18687dd8','1430924',['cluster' =>'eu']);
                        $data = $pusher->getPresenceUsers($request->channel_name);
                        $size = count($data->users);
                        if($size <= 1)
                        {
                            $this->NotifyApi($user->device_token,$gym->name,$newMessage->content);
                        }

                    }else
                    {
                        $newMessage = new Message;
                        $newMessage->content_type = $request->content_type;
                        $image = $this->uploadImage($request,'messages','contentMessage');
                        $newMessage->content = $image;
                        $newMessage->user_id = $user->id;
                        $newMessage->gym_id = $gym->id;
                        $newMessage->sender_type = 'gym';
                        $newMessage->receiver_type = 'user';
                        $newMessage->save();
                        event(new Messaging($newMessage));
                        $pusher = new \Pusher\Pusher('8525a86f244e2694c04d','63dcb37f350e18687dd8','1430924',['cluster' =>'eu']);
                        $data = $pusher->getPresenceUsers($request->channel_name);
                        $size = count($data->users);
                        if($size <= 1)
                        {
                            $this->NotifyApi($user->device_token,$gym->name,"image");
                        }
                    }
                }elseif ($type == 'trainer')
                {

                    $trainer = Trainer::find($id);
                    if(!$trainer)
                    {
                        return $this->returnError(201, 'Not trainer id');
                    }
                    if($request->content_type == 'text')
                    {
                        $newMessage = new Message;
                        $newMessage->content_type = $request->content_type;
                        $newMessage->content = $request->contentMessage;
                        $newMessage->gym_id = $gym->id;
                        $newMessage->trainer_id = $trainer->id;
                        $newMessage->sender_type = 'gym';
                        $newMessage->receiver_type = 'trainer';
                        $newMessage->save();
                        event(new Messaging($newMessage));
                        $pusher = new \Pusher\Pusher('8525a86f244e2694c04d','63dcb37f350e18687dd8','1430924',['cluster' =>'eu']);
                        $data = $pusher->getPresenceUsers($request->channel_name);
                        $size = count($data->users);
                        if($size <= 1)
                        {
                            $this->NotifyApi($trainer->device_token,$gym->name,$newMessage->content);
                        }

                    }else
                    {
                        $newMessage = new Message;
                        $newMessage->content_type = $request->content_type;
                        $image = $this->uploadImage($request,'messages','contentMessage');
                        $newMessage->content = $image;
                        $newMessage->gym_id = $gym->id;
                        $newMessage->trainer_id = $trainer->id;
                        $newMessage->sender_type = 'gym';
                        $newMessage->receiver_type = 'trainer';
                        $newMessage->save();
                        event(new Messaging($newMessage));
                        $pusher = new \Pusher\Pusher('8525a86f244e2694c04d','63dcb37f350e18687dd8','1430924',['cluster' =>'eu']);
                        $data = $pusher->getPresenceUsers($request->channel_name);
                        $size = count($data->users);
                        if($size <= 1)
                        {
                            $this->NotifyApi($trainer->device_token,$gym->name,"image");
                        }
                    }
                }
                return $this->returnSuccessMessage('Added Successfully',200);
            } catch (\Tymon\JWTAuth\Exceptions\UserNotDefinedException $e) {
                return response()->json(['error' => 'Unauthorized'], 401);
            }
        }elseif ($request->header('type') == 'trainer')
        {
            try {
                $trainer = auth('trainer')->userOrFail();

                if($type == 'user')
                {
                    $user = User::find($id);
                    if(!$user)
                    {
                        return $this->returnError(201, 'Not user id');
                    }
                    if($request->content_type == 'text')
                    {
                        $newMessage = new Message;
                        $newMessage->content_type = $request->content_type;
                        $newMessage->content = $request->contentMessage;
                        $newMessage->user_id = $user->id;
                        $newMessage->trainer_id = $trainer->id;
                        $newMessage->sender_type = 'trainer';
                        $newMessage->receiver_type = 'user';
                        $newMessage->save();
                        event(new Messaging($newMessage));
                        $pusher = new \Pusher\Pusher('8525a86f244e2694c04d','63dcb37f350e18687dd8','1430924',['cluster' =>'eu']);
                        $data = $pusher->getPresenceUsers($request->channel_name);
                        $size = count($data->users);
                        if($size <= 1)
                        {
                            $this->NotifyApi($user->device_token,$trainer->name,$newMessage->content);
                        }

                    }else
                    {
                        $newMessage = new Message;
                        $newMessage->content_type = $request->content_type;
                        $image = $this->uploadImage($request,'messages','contentMessage');
                        $newMessage->content = $image;
                        $newMessage->user_id = $user->id;
                        $newMessage->trainer_id = $trainer->id;
                        $newMessage->sender_type = 'trainer';
                        $newMessage->receiver_type = 'user';
                        $newMessage->save();
                        event(new Messaging($newMessage));
                        $pusher = new \Pusher\Pusher('8525a86f244e2694c04d','63dcb37f350e18687dd8','1430924',['cluster' =>'eu']);
                        $data = $pusher->getPresenceUsers($request->channel_name);
                        $size = count($data->users);
                        if($size <= 1)
                        {
                            $this->NotifyApi($user->device_token,$trainer->name,"image");
                        }
                    }
                }elseif ($type == 'gym')
                {

                    $gym = Gym::find($id);
                    if(!$gym)
                    {
                        return $this->returnError(201, 'Not gym id');
                    }
                    if($request->content_type == 'text')
                    {
                        $newMessage = new Message;
                        $newMessage->content_type = $request->content_type;
                        $newMessage->content = $request->contentMessage;
                        $newMessage->gym_id = $gym->id;
                        $newMessage->trainer_id = $trainer->id;
                        $newMessage->sender_type = 'trainer';
                        $newMessage->receiver_type = 'gym';
                        $newMessage->save();
                        event(new Messaging($newMessage));
                        $pusher = new \Pusher\Pusher('8525a86f244e2694c04d','63dcb37f350e18687dd8','1430924',['cluster' =>'eu']);
                        $data = $pusher->getPresenceUsers($request->channel_name);
                        $size = count($data->users);
                        if($size <= 1)
                        {
                            $this->NotifyApi($gym->device_token,$trainer->name,$newMessage->content);
                        }

                    }else
                    {
                        $newMessage = new Message;
                        $newMessage->content_type = $request->content_type;
                        $image = $this->uploadImage($request,'messages','contentMessage');
                        $newMessage->content = $image;
                        $newMessage->gym_id = $gym->id;
                        $newMessage->trainer_id = $trainer->id;
                        $newMessage->sender_type = 'trainer';
                        $newMessage->receiver_type = 'gym';
                        $newMessage->save();
                        event(new Messaging($newMessage));
                        $pusher = new \Pusher\Pusher('8525a86f244e2694c04d','63dcb37f350e18687dd8','1430924',['cluster' =>'eu']);
                        $data = $pusher->getPresenceUsers($request->channel_name);
                        $size = count($data->users);
                        if($size <= 1)
                        {
                            $this->NotifyApi($gym->device_token,$trainer->name,"image");
                        }
                    }
                }
                return $this->returnSuccessMessage('Added Successfully',200);
            } catch (\Tymon\JWTAuth\Exceptions\UserNotDefinedException $e) {
                return response()->json(['error' => 'Unauthorized'], 401);
            }
        }

    }

    public function chatRoom(Request $request)
    {
        if($request->header('type') == 'user')
        {
            try {
                $user = auth('user')->userOrFail();
                $messages = Message::where('user_id',$user->id)
                    ->orderBy('created_at', 'DESC')
                    ->get();
                $ids = [];
                foreach($messages as $key => $value){
                    if($value['gym_id'] != null)
                    {
                        $object =
                            [
                              'id' => $value['gym_id'],
                              'type' => 'gym',
                            ];
                        $ids [] = $object;
                    }elseif ($value['trainer_id'] != null)
                    {
                        $object =
                            [
                                'id' => $value['trainer_id'],
                                'type' => 'trainer',
                            ];
                        $ids [] = $object;
                    }
                }
                $messages = array_unique($ids,SORT_REGULAR);
                $messages = collect($messages)->map(function($oneRecord) use ($user)
                {
                    if($oneRecord['type'] == 'gym')
                    {
                        $gym = Gym::find($oneRecord['id']);
                        if($gym->image)
                        {
                            $gym->image = asset('/assets/gyms/' . $gym->image );
                        }
                        $message = Message::where('user_id',$user->id)->where('gym_id',$gym->id)->latest()->first();
                        if($message->content_type != 'text')
                        {
                            $message->content = 'image';
                        }
                        $notificationDate = $message->created_at->format('Y-m-d');
                        $time = $message->created_at->format('H:i');
                        $currentDate = Carbon::now();
                        $currentDate = $currentDate->toDateString();
                        if($currentDate == $notificationDate)
                        {
                            $date = 'Today';
                        }else
                        {
                            $date = $notificationDate;
                        }
                        return
                            [
                                "id" => $gym->id,
                                "name" => $gym->name,
                                "image" => $gym->image,
                                "lastMessage" => $message->content,
                                "type" => 'gym',
                                "date" => $date,
                                'time' => $time
                            ];
                    }elseif ($oneRecord['type'] == 'trainer')
                    {

                        $trainer= Trainer::find($oneRecord['id']);
                        if($trainer->image)
                        {
                            $trainer->image = asset('/assets/trainers/' . $trainer->image );
                        }
                        $message = Message::where('user_id',$user->id)->where('trainer_id',$trainer->id)->latest()->first();
                        if($message->content_type != 'text')
                        {
                            $message->content = 'image';
                        }
                        $notificationDate = $message->created_at->format('Y-m-d');
                        $time = $message->created_at->format('H:i');
                        $currentDate = Carbon::now();
                        $currentDate = $currentDate->toDateString();
                        if($currentDate == $notificationDate)
                        {
                            $date = 'Today';
                        }else
                        {
                            $date = $notificationDate;
                        }
                        return
                            [
                                "id" => $trainer->id,
                                "name" => $trainer->name,
                                "image" => $trainer->image,
                                "lastMessage" => $message->content,
                                "type" => 'trainer',
                                "date" => $date,
                                'time' => $time
                            ];
                    }

                });
                $array = [];
                foreach ($messages as $k => $v)
                {
                    $array [] = $v;
                }
                return $this->returnData(['response'], [$array],'Chat Room Data');
            } catch (\Tymon\JWTAuth\Exceptions\UserNotDefinedException $e) {
                return response()->json(['error' => 'Unauthorized'], 401);
            }
        }elseif($request->header('type') == 'gym')
        {
            try {
                $gym = auth('gym')->userOrFail();

                $messages = Message::where('gym_id',$gym->id)
                    ->orderBy('created_at', 'DESC')
                    ->get();
                $ids = [];
                foreach($messages as $key => $value){
                    if($value['user_id'] != null)
                    {
                        $object =
                            [
                                'id' => $value['user_id'],
                                'type' => 'user',
                            ];
                        $ids [] = $object;
                    }elseif ($value['trainer_id'] != null)
                    {
                        $object =
                            [
                                'id' => $value['trainer_id'],
                                'type' => 'trainer',
                            ];
                        $ids [] = $object;
                    }
                }
                $messages = array_unique($ids,SORT_REGULAR);
                $messages = collect($messages)->map(function($oneRecord) use ($gym)
                {
                    if($oneRecord['type'] == 'user')
                    {
                        $user= User::find($oneRecord['id']);
                        if($user->image)
                        {
                            $user->image = asset('/assets/users/' . $user->image );
                        }
                        $message = Message::where('user_id',$user->id)->where('gym_id',$gym->id)->latest()->first();
                        if($message->content_type != 'text')
                        {
                            $message->content = 'image';
                        }
                        $notificationDate = $message->created_at->format('Y-m-d');
                        $time = $message->created_at->format('H:i');
                        $currentDate = Carbon::now();
                        $currentDate = $currentDate->toDateString();
                        if($currentDate == $notificationDate)
                        {
                            $date = 'Today';
                        }else
                        {
                            $date = $notificationDate;
                        }
                        return
                            [
                                "id" => $user->id,
                                "name" => $user->name,
                                "image" => $user->image,
                                "lastMessage" => $message->content,
                                "type" => 'user',
                                "date" => $date,
                                'time' => $time
                            ];
                    }elseif ($oneRecord['type'] == 'trainer')
                    {

                        $trainer= Trainer::find($oneRecord['id']);
                        if($trainer->image)
                        {
                            $trainer->image = asset('/assets/trainers/' . $trainer->image );
                        }
                        $message = Message::where('gym_id',$gym->id)->where('trainer_id',$trainer->id)->latest()->first();
                        if($message->content_type != 'text')
                        {
                            $message->content = 'image';
                        }
                        $notificationDate = $message->created_at->format('Y-m-d');
                        $time = $message->created_at->format('H:i');
                        $currentDate = Carbon::now();
                        $currentDate = $currentDate->toDateString();
                        if($currentDate == $notificationDate)
                        {
                            $date = 'Today';
                        }else
                        {
                            $date = $notificationDate;
                        }
                        return
                            [
                                "id" => $trainer->id,
                                "name" => $trainer->name,
                                "image" => $trainer->image,
                                "lastMessage" => $message->content,
                                "type" => 'trainer',
                                "date" => $date,
                                'time' => $time
                            ];
                    }

                });
                $array = [];
                foreach ($messages as $k => $v)
                {
                    $array [] = $v;
                }

                return $this->returnData(['response'], [$array],'Chat Room Data');
            } catch (\Tymon\JWTAuth\Exceptions\UserNotDefinedException $e) {
                return response()->json(['error' => 'Unauthorized'], 401);
            }
        }elseif($request->header('type') == 'trainer')
        {
            try {
                $trainer = auth('trainer')->userOrFail();

                $messages = Message::where('trainer_id',$trainer->id)
                    ->orderBy('created_at', 'DESC')
                    ->get();
                $ids = [];
                foreach($messages as $key => $value){
                    if($value['user_id'] != null)
                    {
                        $object =
                            [
                                'id' => $value['user_id'],
                                'type' => 'user',
                            ];
                        $ids [] = $object;
                    }elseif ($value['gym_id'] != null)
                    {
                        $object =
                            [
                                'id' => $value['gym_id'],
                                'type' => 'gym',
                            ];
                        $ids [] = $object;
                    }
                }
                $messages = array_unique($ids,SORT_REGULAR);
                $messages = collect($messages)->map(function($oneRecord) use ($trainer)
                {
                    if($oneRecord['type'] == 'user')
                    {
                        $user= User::find($oneRecord['id']);
                        if($user->image)
                        {
                            $user->image = asset('/assets/users/' . $user->image );
                        }
                        $message = Message::where('user_id',$user->id)->where('trainer_id',$trainer->id)->latest()->first();
                        if($message->content_type != 'text')
                        {
                            $message->content = 'image';
                        }
                        $notificationDate = $message->created_at->format('Y-m-d');
                        $time = $message->created_at->format('H:i');
                        $currentDate = Carbon::now();
                        $currentDate = $currentDate->toDateString();
                        if($currentDate == $notificationDate)
                        {
                            $date = 'Today';
                        }else
                        {
                            $date = $notificationDate;
                        }
                        return
                            [
                                "id" => $user->id,
                                "name" => $user->name,
                                "image" => $user->image,
                                "lastMessage" => $message->content,
                                "type" => 'user',
                                "date" => $date,
                                'time' => $time
                            ];
                    }elseif ($oneRecord['type'] == 'gym')
                    {

                        $gym= Gym::find($oneRecord['id']);
                        if($gym->image)
                        {
                            $gym->image = asset('/assets/gyms' . $gym->image );
                        }
                        $message = Message::where('gym_id',$gym->id)->where('trainer_id',$trainer->id)->latest()->first();
                        if($message->content_type != 'text')
                        {
                            $message->content = 'image';
                        }
                        $notificationDate = $message->created_at->format('Y-m-d');
                        $time = $message->created_at->format('H:i');
                        $currentDate = Carbon::now();
                        $currentDate = $currentDate->toDateString();
                        if($currentDate == $notificationDate)
                        {
                            $date = 'Today';
                        }else
                        {
                            $date = $notificationDate;
                        }
                        return
                            [
                                "id" => $gym->id,
                                "name" => $gym->name,
                                "image" => $gym->image,
                                "lastMessage" => $message->content,
                                "type" => 'gym',
                                "date" => $date,
                                'time' => $time
                            ];
                    }

                });

                $array = [];
                foreach ($messages as $k => $v)
                {
                    $array [] = $v;
                }
                return $this->returnData(['response'], [$array],'Chat Room Data');
            } catch (\Tymon\JWTAuth\Exceptions\UserNotDefinedException $e) {
                return response()->json(['error' => 'Unauthorized'], 401);
            }
        }
    }

    public function NotifyApi($firebaseToken,$title,$message)
    {
        $SERVER_API_KEY = "AAAA1qvAnRw:APA91bEVWP6XBOMD59QpuBS7pHmXXAYADzMoaaXyksQjxXHkqzORDbq7E_kYMOIrSEr21BBxccwXGudzX2GYgRFs4D4vCFYNm5AUAeq2Y901LJRqsVsPRYEyw-PNYQ05oNBk6QvFUACY";

        $data = [
            "registration_ids" => [$firebaseToken],
            "notification" => [
                "title" => $title,
                "body" => $message,
                "content_available" => true,
                "priority" => "high",
            ]
        ];
        $dataString = json_encode($data);

        $headers = [
            'Authorization: key=' . $SERVER_API_KEY,
            'Content-Type: application/json',
        ];

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, 'https://fcm.googleapis.com/fcm/send');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $dataString);

        $response = curl_exec($ch);

        return 1;
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
