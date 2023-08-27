<?php

namespace App\Http\Controllers;

use App\Models\Favorite_Gym;
use App\Models\Favorite_Trainer;
use App\Models\Gym;
use App\Models\Trainer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class FavoriteController extends Controller
{

    public function getGymFavorites()
    {
        try {
            $user = auth('user')->userOrFail();
            $favorite_gyms = collect($user->favorite_gyms)->map(function($oneFavorite)
            {
                if($oneFavorite->gym->image)
                {
                    $image = asset('/assets/gyms/' . $oneFavorite->gym->image );
                }else
                {
                    $image = $oneFavorite->gym->image;
                }
                return
                    [
                        "id" => $oneFavorite->gym->id,
                        "name" => $oneFavorite->gym->name,
                        "image" => $image,
                    ];

            });
            return $this->returnData(['response'], [$favorite_gyms],'Favorite gyms Data');
        } catch (\Tymon\JWTAuth\Exceptions\UserNotDefinedException $e) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
    }


    public function getTrainerFavorites()
    {
        try {
            $user = auth('user')->userOrFail();
            $favorite_trainers = collect($user->favorite_trainers)->map(function($oneFavorite)
            {
                if($oneFavorite->trainer->image)
                {
                    $image = asset('/assets/trainers/' . $oneFavorite->trainer->image );
                }else
                {
                    $image = $oneFavorite->trainer->image;
                }
                return
                    [
                        "id" => $oneFavorite->trainer->id,
                        "name" => $oneFavorite->trainer->name,
                        "image" => $image,
                    ];

            });
            return $this->returnData(['response'], [$favorite_trainers],'Favorite Trainers Data');
        } catch (\Tymon\JWTAuth\Exceptions\UserNotDefinedException $e) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
    }



    public function favoriteGym(Request $request,$id)
    {
        try {
            $user = auth('user')->userOrFail();
            $gym = Gym::find($id);
            if(!$gym)
            {
                return $this->returnError(201, 'invalid id !');
            }
            $favorite = Favorite_Gym::where('user_id',$user->id)
                ->where('gym_id',$gym->id)
                ->first();
            if($favorite)
            {
                return $this->returnError(201, 'already added !');
            }
            $newFavorite = new Favorite_Gym;
            $newFavorite->user_id = $user->id;
            $newFavorite->gym_id = $gym->id;
            $newFavorite->save();
            return $this->returnSuccessMessage('Added Successfully',200);
        } catch (\Tymon\JWTAuth\Exceptions\UserNotDefinedException $e) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
    }

    public function favoriteTrainer(Request $request,$id)
    {
        try {
            $user = auth('user')->userOrFail();
            $trainer = Trainer::find($id);
            if(!$trainer)
            {
                return $this->returnError(201, 'invalid id !');
            }
            $favorite = Favorite_Trainer::where('user_id',$user->id)
                ->where('trainer_id',$trainer->id)
                ->first();
            if($favorite)
            {
                return $this->returnError(201, 'already added !');
            }
            $newFavorite = new Favorite_Trainer;
            $newFavorite->user_id = $user->id;
            $newFavorite->trainer_id = $trainer->id;
            $newFavorite->save();
            return $this->returnSuccessMessage('Added Successfully',200);
        } catch (\Tymon\JWTAuth\Exceptions\UserNotDefinedException $e) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
    }


    public function deleteGymFavorite($id)
    {
        try {
            $user = auth('user')->userOrFail();
            $gym = Gym::find($id);
            if(!$gym)
            {
                return $this->returnError(201, 'Not exists !');
            }
            $newFavorite = Favorite_Gym::where('user_id',$user->id)->where('gym_id',$gym->id)->first();
            $newFavorite->delete();
            return $this->returnSuccessMessage('Deleted Successfully',200);
        } catch (\Tymon\JWTAuth\Exceptions\UserNotDefinedException $e) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
    }


    public function deleteTrainerFavorite($id)
    {
        try {
            $user = auth('user')->userOrFail();
            $trainer = Trainer::find($id);
            if(!$trainer)
            {
                return $this->returnError(201, 'Not exists !');
            }
            $newFavorite = Favorite_Trainer::where('user_id',$user->id)->where('trainer_id',$trainer->id)->first();
            $newFavorite->delete();
            return $this->returnSuccessMessage('Deleted Successfully',200);
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
