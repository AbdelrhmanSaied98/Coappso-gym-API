<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Booking_Gym;
use App\Models\Finance;
use App\Models\Gym_Class;
use App\Models\Gym_Trainer;
use App\Models\Vacation;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class crmController extends Controller
{
    public function addAttendance(Request $request,$id)
    {

        $validator = Validator::make($request->all(), [
            'type' => 'required|in:"presence","absent"',
            'date' => 'required|date_format:Y-m-d',
        ]);
        if ($validator->fails()) {
            return $this->returnValidationError(422, $validator);
        }

        try {
            $admin = auth('admin')->userOrFail();
            $gym_trainer = Gym_Trainer::find($id);
            if(!$gym_trainer)
            {
                return $this->returnError(201, 'Invalid id');
            }
            $oldAttendance = Attendance::where('gym_trainer_id',$gym_trainer->id)
                ->where('date',$request->date)
                ->first();
            if($oldAttendance)
            {
                return $this->returnError(201, 'already add in this day');
            }

            $newAttendance = new Attendance;
            $newAttendance->type = $request->type;
            $newAttendance->date = $request->date;
            $newAttendance->gym_trainer_id = $gym_trainer->id;

            if($request->type == 'presence')
            {
                $validator = Validator::make($request->all(), [
                    'time_attendance' => 'required|date_format:H:i',
                    'time_checkout' => 'required|date_format:H:i',
                ]);
                if ($validator->fails()) {
                    return $this->returnValidationError(422, $validator);
                }
                if($request->time_attendance > $request->time_checkout)
                {
                    return $this->returnError(201, 'Invalid times');
                }
                $newAttendance->time_attendance = $request->time_attendance;
                $newAttendance->time_checkout = $request->time_checkout;
                $newAttendance->save();

            }else
            {
                $validator = Validator::make($request->all(), [
                    'absent_type' => 'required|in:"paid","unpaid"',
                ]);
                if ($validator->fails()) {
                    return $this->returnValidationError(422, $validator);
                }
                $newAttendance->absent_type = $request->absent_type;
                $newAttendance->save();

                $newVacation = new Vacation;
                $newVacation->date = $request->date;
                $newVacation->type = $request->absent_type;
                $newVacation->gym_trainer_id = $gym_trainer->id;
                $newVacation->save();
            }
            return $this->returnSuccessMessage('Attendance Added Successfully',200);
        } catch (\Tymon\JWTAuth\Exceptions\UserNotDefinedException $e) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
    }

    public function addFinance(Request $request,$id)
    {

        $validator = Validator::make($request->all(), [
            'pay_cut' => 'required|numeric',
            'month' => 'required|in:"January","February","March","April","May","June","July","August","September","October","November","December"',
            'year' => 'required|max:'.date("2030"),
        ]);
        if ($validator->fails()) {
            return $this->returnValidationError(422, $validator);
        }

        try {
            $admin = auth('admin')->userOrFail();
            $gym_trainer = Gym_Trainer::find($id);
            if(!$gym_trainer)
            {
                return $this->returnError(201, 'Invalid id');
            }

            $oldFinance = Finance::where('gym_trainer_id',$gym_trainer->id)
                ->where('month',$request->month)
                ->where('year',$request->year)
                ->first();
            if($oldFinance)
            {
                return $this->returnError(201, 'already add in this month');
            }
            $finalSalary = $gym_trainer->salary - $request->pay_cut;
            if($finalSalary <= 0)
            {
                return $this->returnError(201, 'invalid pay-cut');
            }
            $newFinance = new Finance;
            $newFinance->month = $request->month;
            $newFinance->year = $request->year;
            $newFinance->base_salary = $gym_trainer->salary;
            $newFinance->pay_cut = $request->pay_cut;
            $newFinance->final_salary = $finalSalary;
            $newFinance->gym_trainer_id = $gym_trainer->id;
            $newFinance->save();
            return $this->returnSuccessMessage('Salary Added Successfully',200);
        } catch (\Tymon\JWTAuth\Exceptions\UserNotDefinedException $e) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
    }

    public function getAttendance($id,$monthName)
    {
        try {
            $admin = auth('admin')->userOrFail();
            $gym_trainer = Gym_Trainer::find($id);
            if(!$gym_trainer)
            {
                return $this->returnError(201, 'Invalid id');
            }
            $monthsArray = ["January","February","March","April","May","June","July","August","September","October","November","December"];

            if(!in_array($monthName,$monthsArray))
            {
                return $this->returnError(201, 'invalid month name');
            }



            $attendances = [];
            foreach ($gym_trainer->attendances as $attendance)
            {
                $attendanceMonth = Carbon::parse($attendance->date)->monthName;
                if($attendanceMonth == $monthName)
                {
                    $object =
                        [
                            'id' => $attendance->id,
                            'date' => $attendance->date,
                        ];
                    $attendances [] = $object;
                }
            }
            $this->array_sort_by_column($attendances, 'date',SORT_DESC);

            $countAll = 0;
            $countPresent = 0;
            $countAbsent = 0;

            foreach ($attendances as $oneRecord)
            {
                $attendance = Attendance::find($oneRecord['id']);
                $countAll++;
                if($attendance->type == 'presence')
                {
                    $countPresent++;
                }else
                {
                    $countAbsent++;
                }
            }

            $attendances = collect($attendances)->map(function($oneRecord) use ($countAll,$countPresent,$countAbsent)
            {
                $attendance = Attendance::find($oneRecord['id']);

                $time_attendance = null;

                $time_checkout = null;

                if($attendance->time_attendance)
                {
                    $time_attendance = date('g:i a', strtotime($attendance->time_attendance));
                    $time_checkout = date('g:i a', strtotime($attendance->time_checkout));
                }
                return
                    [
                        "id" => $attendance->id,
                        "date" => $attendance->date,
                        "type" => $attendance->type,
                        "time_attendance" => $time_attendance,
                        "time_checkout" => $time_checkout,
                        "absent_type" => $attendance->absent_type,
                    ];
            });

            $presentPercent = 0;
            $absentPercent = 0;
            if($countAll != 0)
            {
                $presentPercent = ( $countPresent / $countAll ) * 100;
                $presentPercent = sprintf("%.1f", $presentPercent);
                $absentPercent = ( $countAbsent / $countAll ) * 100;
                $absentPercent = sprintf("%.1f", $absentPercent);
            }else
            {
                $presentPercent = "0";
                $absentPercent = "0";
            }

            $result =
                [
                    'attendances' => $attendances,
                    'presentPercent' => $presentPercent,
                    'absentPercent' => $absentPercent,
                ];
            return $this->returnData(['response'], [$result],'Attendances Data');
        } catch (\Tymon\JWTAuth\Exceptions\UserNotDefinedException $e) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
    }

    public function getVacations($id)
    {
        try {
            $admin = auth('admin')->userOrFail();
            $gym_trainer = Gym_Trainer::find($id);
            if(!$gym_trainer)
            {
                return $this->returnError(201, 'Invalid id');
            }
            $vacations = Vacation::where('gym_trainer_id',$gym_trainer->id)
                ->get();

            $countPaid = 0;
            $countUnpaid = 0;

            foreach ($vacations as $vacation)
            {
                if($vacation->type == 'paid')
                {
                    $countPaid++;
                }else
                {
                    $countUnpaid++;
                }
            }
            $vacations = collect($vacations)->map(function($oneRecord)
            {
                return
                    [
                        "id" => $oneRecord->id,
                        "date" => $oneRecord->date,
                        "type" => $oneRecord->type,
                    ];
            });

            $paidPercent = ( $countPaid / 21 ) * 100;
            $paidPercent = sprintf("%.1f", $paidPercent);

            $remainingPercent = ( (21 - $countPaid) / 21 ) * 100;
            $remainingPercent = sprintf("%.1f", $remainingPercent);

            $result =
                [
                    'vacations' => $vacations,
                    'paid' => $countPaid,
                    'unpaid' => $countUnpaid,
                    'paidPercent' => $paidPercent,
                    'remainingPercent' => $remainingPercent,
                ];
            return $this->returnData(['response'], [$result],'Vacations Data');
        } catch (\Tymon\JWTAuth\Exceptions\UserNotDefinedException $e) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
    }

    public function getFinances($id)
    {
        try {
            $admin = auth('admin')->userOrFail();
            $gym_trainer = Gym_Trainer::find($id);
            if(!$gym_trainer)
            {
                return $this->returnError(201, 'Invalid id');
            }
            $finances = Finance::where('gym_trainer_id',$gym_trainer->id)
                ->get();


            $totalSalary = 0;
            $finalSalary = 0;
            $totalPayCut = 0;

            foreach ($finances as $finance)
            {
                $totalSalary += $finance->base_salary;
                $finalSalary += $finance->final_salary;
                $totalPayCut += $finance->pay_cut;
            }


            $finances = collect($finances)->map(function($oneRecord)
            {
                return
                    [
                        "id" => $oneRecord->id,
                        "month" => $oneRecord->month,
                        "year" => $oneRecord->year,
                        "base_salary" => $oneRecord->base_salary,
                        "pay_cut" => $oneRecord->pay_cut,
                        "final_salary" => $oneRecord->final_salary,
                    ];
            });

            if($totalSalary != 0)
            {
                $paidPercent = ( $finalSalary / $totalSalary ) * 100;
                $paidPercent = sprintf("%.1f", $paidPercent);

                $PayCutPercent = (  $totalPayCut / $totalSalary ) * 100;
                $PayCutPercent = sprintf("%.1f", $PayCutPercent);
            }else
            {
                $paidPercent = "0";
                $PayCutPercent = "0";
            }



            $result =
                [
                    'finances' => $finances,
                    'paidPercent' => $paidPercent,
                    'PayCutPercent' => $PayCutPercent,
                ];
            return $this->returnData(['response'], [$result],'Finances Data');
        } catch (\Tymon\JWTAuth\Exceptions\UserNotDefinedException $e) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
    }

    public function getAllAttendance($monthName)
    {
        try {
            $admin = auth('admin')->userOrFail();
            $monthsArray = ["January","February","March","April","May","June","July","August","September","October","November","December"];

            if(!in_array($monthName,$monthsArray))
            {
                return $this->returnError(201, 'invalid month name');
            }

            $attendancesAll = Attendance::all();

            $attendances = [];
            foreach ($attendancesAll as $attendance)
            {
                $attendanceMonth = Carbon::parse($attendance->date)->monthName;
                if($attendanceMonth == $monthName)
                {
                    $object =
                        [
                            'id' => $attendance->id,
                            'date' => $attendance->date,
                        ];
                    $attendances [] = $object;
                }
            }
            $this->array_sort_by_column($attendances, 'date',SORT_DESC);

            $countAll = 0;
            $countPresent = 0;
            $countAbsent = 0;

            foreach ($attendances as $oneRecord)
            {
                $attendance = Attendance::find($oneRecord['id']);
                $countAll++;
                if($attendance->type == 'presence')
                {
                    $countPresent++;
                }else
                {
                    $countAbsent++;
                }
            }

            $attendances = collect($attendances)->map(function($oneRecord) use ($countAll,$countPresent,$countAbsent)
            {
                $attendance = Attendance::find($oneRecord['id']);

                $time_attendance = null;

                $time_checkout = null;

                if($attendance->time_attendance)
                {
                    $time_attendance = date('g:i a', strtotime($attendance->time_attendance));
                    $time_checkout = date('g:i a', strtotime($attendance->time_checkout));
                }
                return
                    [
                        "id" => $attendance->id,
                        "date" => $attendance->date,
                        "type" => $attendance->type,
                        "time_attendance" => $time_attendance,
                        "time_checkout" => $time_checkout,
                        "absent_type" => $attendance->absent_type,
                        "gym_trainer_name" => $attendance->gym_trainer->name,
                        "gym_name" => $attendance->gym_trainer->gym_branch->gym->name,
                        "gym_branch_name" => $attendance->gym_trainer->gym_branch->name,
                    ];
            });

            $presentPercent = 0;
            $absentPercent = 0;
            if($countAll != 0)
            {
                $presentPercent = ( $countPresent / $countAll ) * 100;
                $presentPercent = sprintf("%.1f", $presentPercent);
                $absentPercent = ( $countAbsent / $countAll ) * 100;
                $absentPercent = sprintf("%.1f", $absentPercent);
            }else
            {
                $presentPercent = "0";
                $absentPercent = "0";
            }

            $result =
                [
                    'attendances' => $attendances,
                    'presentPercent' => $presentPercent,
                    'absentPercent' => $absentPercent,
                ];
            return $this->returnData(['response'], [$result],'Attendances Data');
        } catch (\Tymon\JWTAuth\Exceptions\UserNotDefinedException $e) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
    }

    public function getAllVacations()
    {
        try {
            $admin = auth('admin')->userOrFail();

            $vacations = Vacation::all();

            $vacationsArray = [];
            foreach ($vacations as $vacation)
            {
                $object =
                    [
                        'id' => $vacation->id,
                        'date' => $vacation->date,
                    ];
                $vacationsArray [] = $object;
            }
            $this->array_sort_by_column($vacationsArray, 'date',SORT_DESC);
            $vacations = collect($vacationsArray)->map(function($oneRecord)
            {
                $oneVacation = Vacation::find($oneRecord['id']);
                return
                    [
                        "id" => $oneVacation->id,
                        "date" => $oneVacation->date,
                        "type" => $oneVacation->type,
                        "gym_trainer_name" => $oneVacation->gym_trainer->name,
                        "gym_name" => $oneVacation->gym_trainer->gym_branch->gym->name,
                        "gym_branch_name" => $oneVacation->gym_trainer->gym_branch->name,
                    ];
            });

            $result =
                [
                    'vacations' => $vacations,
                ];
            return $this->returnData(['response'], [$result],'Vacations Data');
        } catch (\Tymon\JWTAuth\Exceptions\UserNotDefinedException $e) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
    }

    public function getAllFinances()
    {
        try {
            $admin = auth('admin')->userOrFail();
            $finances = Finance::all();

            $financesArray = [];
            foreach ($finances as $finance)
            {
                $dateCarbon = Carbon::parse('first day of '.$finance->month.' '.$finance->year)->format('Y-m-d');
                $object =
                    [
                        'id' => $finance->id,
                        'date' => $dateCarbon,
                    ];
                $financesArray [] = $object;
            }

            $this->array_sort_by_column($financesArray, 'date',SORT_DESC);


            $finances = collect($financesArray)->map(function($oneRecord)
            {
                $oneFinance = Finance::find($oneRecord['id']);
                return
                    [
                        "id" => $oneFinance->id,
                        "month" => $oneFinance->month,
                        "year" => $oneFinance->year,
                        "base_salary" => $oneFinance->base_salary,
                        "pay_cut" => $oneFinance->pay_cut,
                        "final_salary" => $oneFinance->final_salary,
                        "gym_trainer_name" => $oneFinance->gym_trainer->name,
                        "gym_name" => $oneFinance->gym_trainer->gym_branch->gym->name,
                        "gym_branch_name" => $oneFinance->gym_trainer->gym_branch->name,
                    ];
            });

            $result =
                [
                    'finances' => $finances,
                ];
            return $this->returnData(['response'], [$result],'Finances Data');
        } catch (\Tymon\JWTAuth\Exceptions\UserNotDefinedException $e) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
    }




    public function getGymAttendance($id,$monthName)
    {
        try {
            $gym = auth('gym')->userOrFail();
            $gym_trainer = Gym_Trainer::find($id);
            if(!$gym_trainer || $gym->id != $gym_trainer->gym_branch->gym->id)
            {
                return $this->returnError(201, 'Invalid id');
            }
            $monthsArray = ["January","February","March","April","May","June","July","August","September","October","November","December"];

            if(!in_array($monthName,$monthsArray))
            {
                return $this->returnError(201, 'invalid month name');
            }



            $attendances = [];
            foreach ($gym_trainer->attendances as $attendance)
            {
                $attendanceMonth = Carbon::parse($attendance->date)->monthName;
                if($attendanceMonth == $monthName)
                {
                    $object =
                        [
                            'id' => $attendance->id,
                            'date' => $attendance->date,
                        ];
                    $attendances [] = $object;
                }
            }
            $this->array_sort_by_column($attendances, 'date',SORT_DESC);

            $countAll = 0;
            $countPresent = 0;
            $countAbsent = 0;

            foreach ($attendances as $oneRecord)
            {
                $attendance = Attendance::find($oneRecord['id']);
                $countAll++;
                if($attendance->type == 'presence')
                {
                    $countPresent++;
                }else
                {
                    $countAbsent++;
                }
            }

            $attendances = collect($attendances)->map(function($oneRecord) use ($countAll,$countPresent,$countAbsent)
            {
                $attendance = Attendance::find($oneRecord['id']);

                $time_attendance = null;

                $time_checkout = null;

                if($attendance->time_attendance)
                {
                    $time_attendance = date('g:i a', strtotime($attendance->time_attendance));
                    $time_checkout = date('g:i a', strtotime($attendance->time_checkout));
                }
                return
                    [
                        "id" => $attendance->id,
                        "date" => $attendance->date,
                        "type" => $attendance->type,
                        "time_attendance" => $time_attendance,
                        "time_checkout" => $time_checkout,
                        "absent_type" => $attendance->absent_type,
                    ];
            });

            $presentPercent = 0;
            $absentPercent = 0;
            if($countAll != 0)
            {
                $presentPercent = ( $countPresent / $countAll ) * 100;
                $presentPercent = sprintf("%.1f", $presentPercent);
                $absentPercent = ( $countAbsent / $countAll ) * 100;
                $absentPercent = sprintf("%.1f", $absentPercent);
            }else
            {
                $presentPercent = "0";
                $absentPercent = "0";
            }

            $result =
                [
                    'attendances' => $attendances,
                    'presentPercent' => $presentPercent,
                    'absentPercent' => $absentPercent,
                ];
            return $this->returnData(['response'], [$result],'Attendances Data');
        } catch (\Tymon\JWTAuth\Exceptions\UserNotDefinedException $e) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
    }

    public function getGymVacations($id)
    {
        try {
            $gym = auth('gym')->userOrFail();
            $gym_trainer = Gym_Trainer::find($id);
            if(!$gym_trainer || $gym->id != $gym_trainer->gym_branch->gym->id)
            {
                return $this->returnError(201, 'Invalid id');
            }
            $vacations = Vacation::where('gym_trainer_id',$gym_trainer->id)
                ->get();

            $countPaid = 0;
            $countUnpaid = 0;

            foreach ($vacations as $vacation)
            {
                if($vacation->type == 'paid')
                {
                    $countPaid++;
                }else
                {
                    $countUnpaid++;
                }
            }
            $vacations = collect($vacations)->map(function($oneRecord)
            {
                return
                    [
                        "id" => $oneRecord->id,
                        "date" => $oneRecord->date,
                        "type" => $oneRecord->type,
                    ];
            });

            $paidPercent = ( $countPaid / 21 ) * 100;
            $paidPercent = sprintf("%.1f", $paidPercent);

            $remainingPercent = ( (21 - $countPaid) / 21 ) * 100;
            $remainingPercent = sprintf("%.1f", $remainingPercent);

            $result =
                [
                    'vacations' => $vacations,
                    'paid' => $countPaid,
                    'unpaid' => $countUnpaid,
                    'paidPercent' => $paidPercent,
                    'remainingPercent' => $remainingPercent,
                ];
            return $this->returnData(['response'], [$result],'Vacations Data');
        } catch (\Tymon\JWTAuth\Exceptions\UserNotDefinedException $e) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
    }

    public function getGymFinances($id)
    {
        try {
            $gym = auth('gym')->userOrFail();
            $gym_trainer = Gym_Trainer::find($id);
            if(!$gym_trainer || $gym->id != $gym_trainer->gym_branch->gym->id)
            {
                return $this->returnError(201, 'Invalid id');
            }
            $finances = Finance::where('gym_trainer_id',$gym_trainer->id)
                ->get();


            $totalSalary = 0;
            $finalSalary = 0;
            $totalPayCut = 0;

            foreach ($finances as $finance)
            {
                $totalSalary += $finance->base_salary;
                $finalSalary += $finance->final_salary;
                $totalPayCut += $finance->pay_cut;
            }


            $finances = collect($finances)->map(function($oneRecord)
            {
                return
                    [
                        "id" => $oneRecord->id,
                        "month" => $oneRecord->month,
                        "year" => $oneRecord->year,
                        "base_salary" => $oneRecord->base_salary,
                        "pay_cut" => $oneRecord->pay_cut,
                        "final_salary" => $oneRecord->final_salary,
                    ];
            });

            if($totalSalary != 0)
            {
                $paidPercent = ( $finalSalary / $totalSalary ) * 100;
                $paidPercent = sprintf("%.1f", $paidPercent);

                $PayCutPercent = (  $totalPayCut / $totalSalary ) * 100;
                $PayCutPercent = sprintf("%.1f", $PayCutPercent);
            }else
            {
                $paidPercent = "0";
                $PayCutPercent = "0";
            }



            $result =
                [
                    'finances' => $finances,
                    'paidPercent' => $paidPercent,
                    'PayCutPercent' => $PayCutPercent,
                ];
            return $this->returnData(['response'], [$result],'Finances Data');
        } catch (\Tymon\JWTAuth\Exceptions\UserNotDefinedException $e) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
    }

    public function getGymAllAttendance($monthName)
    {
        try {
            $gym = auth('gym')->userOrFail();
            $monthsArray = ["January","February","March","April","May","June","July","August","September","October","November","December"];

            if(!in_array($monthName,$monthsArray))
            {
                return $this->returnError(201, 'invalid month name');
            }

            $attendancesAll = Attendance::all();

            $attendances = [];
            foreach ($attendancesAll as $attendance)
            {
                if ($attendance->gym_trainer->gym_branch->gym->id != $gym->id)
                {
                    continue;
                }
                $attendanceMonth = Carbon::parse($attendance->date)->monthName;
                if($attendanceMonth == $monthName)
                {
                    $object =
                        [
                            'id' => $attendance->id,
                            'date' => $attendance->date,
                        ];
                    $attendances [] = $object;
                }
            }
            $this->array_sort_by_column($attendances, 'date',SORT_DESC);

            $countAll = 0;
            $countPresent = 0;
            $countAbsent = 0;

            foreach ($attendances as $oneRecord)
            {
                $attendance = Attendance::find($oneRecord['id']);
                $countAll++;
                if($attendance->type == 'presence')
                {
                    $countPresent++;
                }else
                {
                    $countAbsent++;
                }
            }

            $attendances = collect($attendances)->map(function($oneRecord) use ($countAll,$countPresent,$countAbsent)
            {
                $attendance = Attendance::find($oneRecord['id']);

                $time_attendance = null;

                $time_checkout = null;

                if($attendance->time_attendance)
                {
                    $time_attendance = date('g:i a', strtotime($attendance->time_attendance));
                    $time_checkout = date('g:i a', strtotime($attendance->time_checkout));
                }
                return
                    [
                        "id" => $attendance->id,
                        "date" => $attendance->date,
                        "type" => $attendance->type,
                        "time_attendance" => $time_attendance,
                        "time_checkout" => $time_checkout,
                        "absent_type" => $attendance->absent_type,
                        "gym_trainer_name" => $attendance->gym_trainer->name,
                        "gym_name" => $attendance->gym_trainer->gym_branch->gym->name,
                        "gym_branch_name" => $attendance->gym_trainer->gym_branch->name,
                    ];
            });

            $presentPercent = 0;
            $absentPercent = 0;
            if($countAll != 0)
            {
                $presentPercent = ( $countPresent / $countAll ) * 100;
                $presentPercent = sprintf("%.1f", $presentPercent);
                $absentPercent = ( $countAbsent / $countAll ) * 100;
                $absentPercent = sprintf("%.1f", $absentPercent);
            }else
            {
                $presentPercent = "0";
                $absentPercent = "0";
            }

            $result =
                [
                    'attendances' => $attendances,
                    'presentPercent' => $presentPercent,
                    'absentPercent' => $absentPercent,
                ];
            return $this->returnData(['response'], [$result],'Attendances Data');
        } catch (\Tymon\JWTAuth\Exceptions\UserNotDefinedException $e) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
    }

    public function getGymAllVacations()
    {
        try {
            $gym = auth('gym')->userOrFail();

            $vacations = Vacation::all();

            $vacationsArray = [];
            foreach ($vacations as $vacation)
            {
                if ($vacation->gym_trainer->gym_branch->gym->id != $gym->id)
                {
                    continue;
                }
                $object =
                    [
                        'id' => $vacation->id,
                        'date' => $vacation->date,
                    ];
                $vacationsArray [] = $object;
            }
            $this->array_sort_by_column($vacationsArray, 'date',SORT_DESC);
            $vacations = collect($vacationsArray)->map(function($oneRecord)
            {
                $oneVacation = Vacation::find($oneRecord['id']);
                return
                    [
                        "id" => $oneVacation->id,
                        "date" => $oneVacation->date,
                        "type" => $oneVacation->type,
                        "gym_trainer_name" => $oneVacation->gym_trainer->name,
                        "gym_name" => $oneVacation->gym_trainer->gym_branch->gym->name,
                        "gym_branch_name" => $oneVacation->gym_trainer->gym_branch->name,
                    ];
            });

            $result =
                [
                    'vacations' => $vacations,
                ];
            return $this->returnData(['response'], [$result],'Vacations Data');
        } catch (\Tymon\JWTAuth\Exceptions\UserNotDefinedException $e) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
    }

    public function getGymAllFinances()
    {
        try {
            $gym = auth('gym')->userOrFail();
            $finances = Finance::all();

            $financesArray = [];
            foreach ($finances as $finance)
            {
                if ($finance->gym_trainer->gym_branch->gym->id != $gym->id)
                {
                    continue;
                }
                $dateCarbon = Carbon::parse('first day of '.$finance->month.' '.$finance->year)->format('Y-m-d');
                $object =
                    [
                        'id' => $finance->id,
                        'date' => $dateCarbon,
                    ];
                $financesArray [] = $object;
            }

            $this->array_sort_by_column($financesArray, 'date',SORT_DESC);


            $finances = collect($financesArray)->map(function($oneRecord)
            {
                $oneFinance = Finance::find($oneRecord['id']);
                return
                    [
                        "id" => $oneFinance->id,
                        "month" => $oneFinance->month,
                        "year" => $oneFinance->year,
                        "base_salary" => $oneFinance->base_salary,
                        "pay_cut" => $oneFinance->pay_cut,
                        "final_salary" => $oneFinance->final_salary,
                        "gym_trainer_name" => $oneFinance->gym_trainer->name,
                        "gym_name" => $oneFinance->gym_trainer->gym_branch->gym->name,
                        "gym_branch_name" => $oneFinance->gym_trainer->gym_branch->name,
                    ];
            });

            $result =
                [
                    'finances' => $finances,
                ];
            return $this->returnData(['response'], [$result],'Finances Data');
        } catch (\Tymon\JWTAuth\Exceptions\UserNotDefinedException $e) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
    }


    public function bookingCRM(Request $request,$monthName)
    {
        try {
            $admin = auth('admin')->userOrFail();
            $bookings = Booking_Gym::where('attendance_status','1')->get();

            $monthsArray = ["January","February","March","April","May","June","July","August","September","October","November","December"];

            if(!in_array($monthName,$monthsArray))
            {
                return $this->returnError(201, 'invalid month name');
            }

            $allBooking = [];
            foreach ($bookings as $booking)
            {
                $attendanceMonth = Carbon::parse($booking->date)->monthName;
                if($attendanceMonth == $monthName)
                {
                    $allBooking [] = $booking->id;
                }
            }


            $sumTotal = 0;
            foreach ($allBooking as $booking)
            {
                $Booking = Booking_Gym::find($booking);
                $services = explode(",", $Booking->classes);
                foreach ($services as $service)
                {
                    $Service = Gym_Class::find($service);
                    $sumTotal  += $Service->price;
                }
            }





            $allBooking = collect($allBooking)->map(function($oneRecord)
            {
                $Booking = Booking_Gym::find($oneRecord);
                $serviceNames = [];
                $sum = 0;
                $services = explode(",", $Booking->classes);
                foreach ($services as $service)
                {
                    $Service = Gym_Class::find($service);
                    $sum  += $Service->price;
                    $serviceNames [] = $Service->name;
                }


                return
                    [
                        "id" => $Booking->id,
                        "customer_name" => $Booking->user->name,
                        "price" => $sum,
                        "Class_Names" => $serviceNames,
                        "date" => $Booking->date,
                        "beauty_center_name" => $Booking->gym_branch->gym->name,

                    ];
            });
            $result =
                [
                    'allBooking' => $allBooking,
                    'total' => $sumTotal,
                ];
            return $this->returnData(['response'], [$result],'Booking Data');
        } catch (\Tymon\JWTAuth\Exceptions\UserNotDefinedException $e) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
    }




    public function array_sort_by_column(&$array, $column, $direction = SORT_DESC) {
        $reference_array = array();

        foreach($array as $key => $row) {
            $reference_array[$key] = $row[$column];
        }

        array_multisort($reference_array, $direction, $array);
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

    public function uploadImages(Request $request)
    {
        if ($request->hasFile('image')) {

            $files = $request->file('image');
            foreach ($files as $file) {

                $fileextension = $file->getClientOriginalExtension();


                $filename = $file->getClientOriginalName();
                $file_to_store = time() . '_' . explode('.', $filename)[0] . '_.' . $fileextension;

                $test = $file->move(public_path('assets/media'), $file_to_store);
                if ($test) {
                    $images [] = $file_to_store;
                }
            }
            $images = implode('|', $images);
            return $images;
        }

    }
}
