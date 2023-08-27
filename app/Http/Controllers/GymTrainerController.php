<?php

namespace App\Http\Controllers;

use App\Models\_GymClass;
use App\Models\Gym_Branch;
use App\Models\Gym_Trainer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class GymTrainerController extends Controller
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
            $newBranch =  Gym_Branch::find($id);
            if(! $newBranch || $newBranch->gym->id != $gym->id)
            {
                return $this->returnError(201, 'Invalid get gym trainer');
            }
            $gym_trainers = collect($newBranch->gym_trainers)->map(function($oneRecord)
            {
                if($oneRecord->image)
                {
                    $oneRecord->image = asset('/assets/gym_trainers/' . $oneRecord->image );
                }
                return
                    [
                        "id" => $oneRecord->id,
                        "name" => $oneRecord->name,
                        "image" => $oneRecord->image,
                    ];

            });
            return $this->returnData(['response'], [$gym_trainers],'Gym Trainers Data');
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
    public function store(Request $request,$id)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|min:3|max:255',
            'image' => 'required|file',
        ]);
        if ($validator->fails()) {
            return $this->returnValidationError(422, $validator);
        }
        try {
            $gym = auth('gym')->userOrFail();

            $newBranch =  Gym_Branch::find($id);
            if(! $newBranch || $newBranch->gym->id != $gym->id)
            {
                return $this->returnError(201, 'Invalid add gym trainer');
            }

            $validator = Validator::make($request->all(), [
                'name'=> Rule::unique('gym_trainers')->where(function ($query) use($newBranch){
                    return $query->where('branch_id', $newBranch->id);
                })
            ]);
            if ($validator->fails()) {
                return $this->returnValidationError(422, $validator);
            }
            $gym_Trainer = new Gym_Trainer;
            $image = $this->uploadImage($request,'gym_trainers','image');
            $gym_Trainer->image = $image;
            $gym_Trainer->name = $request->name;
            $gym_Trainer->branch_id = $newBranch->id;
            $gym_Trainer->save();
            return $this->returnSuccessMessage('Added Successfully',200);
        } catch (\Tymon\JWTAuth\Exceptions\UserNotDefinedException $e) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Gym_Trainer  $gym_Trainer
     * @return \Illuminate\Http\Response
     */
    public function show(Gym_Trainer $gym_Trainer)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Gym_Trainer  $gym_Trainer
     * @return \Illuminate\Http\Response
     */
    public function edit(Gym_Trainer $gym_Trainer)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Gym_Trainer  $gym_Trainer
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Gym_Trainer $gym_Trainer)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Gym_Trainer  $gym_Trainer
     * @return \Illuminate\Http\Response
     */
    public function destroy(Gym_Trainer $gym_Trainer)
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
