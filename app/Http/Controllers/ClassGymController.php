<?php

namespace App\Http\Controllers;

use App\Models\Gym_Branch;
use App\Models\Gym_Class;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class ClassGymController extends Controller
{

    public function index($id)
    {
        try {
            $gym_branch =  Gym_Branch::find($id);
            $classes = collect($gym_branch->gym_classes)->map(function($oneRecord)
            {
                if($oneRecord->image)
                {
                    $oneRecord->image = asset('/assets/classes/' . $oneRecord->image );
                }
                if($oneRecord->trainer_image)
                {
                    $oneRecord->trainer_image = asset('/assets/class_trainers/' . $oneRecord->trainer_image );
                }
                if($oneRecord->is_offer == '1')
                {
                    $price =  $oneRecord->new_price;
                }else
                {
                    $price =  $oneRecord->price;
                }
                return
                    [
                        "id" => $oneRecord->id,
                        "name" => $oneRecord->name,
                        "image" => $oneRecord->image,
                        "duration" => $oneRecord->duration,
                        "trainer_name" => $oneRecord->trainer_name,
                        "trainer_image" => $oneRecord->trainer_image,
                        "price" => $price,
                    ];

            });
            return $this->returnData(['response'], [$classes],'Classes Data');
        } catch (\Tymon\JWTAuth\Exceptions\UserNotDefinedException $e) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
    }

    public function create()
    {
        //
    }

    public function store(Request $request,$id)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|min:3|max:255',
            'price' => 'required|numeric',
            'duration' => 'required|string',
            'image' => 'required|file',
            'trainer_name' => 'required|string|min:3|max:255',
            'trainer_image' => 'required|file',
        ]);
        if ($validator->fails()) {
            return $this->returnValidationError(422, $validator);
        }
        try {
            $gym = auth('gym')->userOrFail();

            $newBranch =  Gym_Branch::find($id);
            if(! $newBranch || $newBranch->gym->id != $gym->id)
            {
                return $this->returnError(201, 'Invalid add class');
            }



            $validator = Validator::make($request->all(), [
                'name'=> Rule::unique('gym_classes')->where(function ($query) use($newBranch){
                    return $query->where('branch_id', $newBranch->id);
                })
            ]);
            if ($validator->fails()) {
                return $this->returnValidationError(422, $validator);
            }
            $class_Gym = new Gym_Class;
            $image = $this->uploadImage($request,'classes','image');
            $class_Gym->image = $image;
            $class_Gym->name = $request->name;
            $class_Gym->price = $request->price;
            $class_Gym->trainer_name = $request->trainer_name;
            $image = $this->uploadImage($request,'class_trainers','trainer_image');
            $class_Gym->trainer_image = $image;
            $class_Gym->branch_id = $newBranch->id;
            $class_Gym->is_offer = '0';
            $class_Gym->duration = $request->duration;
            $class_Gym->durationOffer = '0';
            $class_Gym->new_price = '0';
            $class_Gym->save();
            return $this->returnSuccessMessage('Added Successfully',200);
        } catch (\Tymon\JWTAuth\Exceptions\UserNotDefinedException $e) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
    }

    public function makeOffer(Request $request,$id)
    {
        $validator = Validator::make($request->all(), [
            'new_price' => 'required|numeric',
            'durationOffer' => 'required|numeric'
        ]);
        if ($validator->fails()) {
            return $this->returnValidationError(422, $validator);
        }
        try {
            $gym = auth('gym')->userOrFail();
            $class_Gym = Gym_Class::find($id);
            if(!$class_Gym || $class_Gym->gym_branch->gym->id != $gym->id || $class_Gym->price <= $request->new_price)
            {
                return $this->returnError(201, 'invalid offer');
            }
            $class_Gym->is_offer = '1';
            $class_Gym->new_price = $request->new_price;
            $class_Gym->durationOffer = $request->durationOffer;
            $class_Gym->save();
            return $this->returnSuccessMessage('Added Successfully',200);
        } catch (\Tymon\JWTAuth\Exceptions\UserNotDefinedException $e) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
    }

    public function show(_GymClass $class_Gym)
    {
        //
    }


    public function edit(_GymClass $class_Gym)
    {
        //
    }

    public function update(Request $request, _GymClass $class_Gym)
    {
        //
    }


    public function destroy(_GymClass $class_Gym)
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
