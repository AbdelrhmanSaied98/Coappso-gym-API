<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ClassGymController;
use App\Http\Controllers\GymTrainerController;
use App\Http\Controllers\GymController;
use App\Http\Controllers\TrainerController;
use App\Http\Controllers\BookingGymController;
use App\Http\Controllers\BookingTrainerController;
use App\Http\Controllers\FavoriteController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\crmController;
/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});


Route::group([
    'middleware' => 'api',
    'prefix' => 'auth'
], function ($router) {

    Route::post('register', [AuthController::class,'register']);
    Route::post('login', [AuthController::class,'login']);
    Route::post('logout', [AuthController::class,'logout']);
    Route::post('uploadProfileImage', [AuthController::class,'uploadProfileImage']);
    Route::get('profile/{type}/{id}', [AuthController::class,'profile']);
    Route::post('forgetPassword', [AuthController::class,'forgetPassword']);
    Route::post('verifyCode', [AuthController::class,'verifyCode']);
    Route::post('updatePassword', [AuthController::class,'updatePassword']);
    Route::get('getNewToken/{type}', [AuthController::class,'getNewToken']);
    Route::get('testAuth', [AuthController::class,'testAuth']);


    //Notification
    Route::get('getNotification/{numOfPage}/{numOfRows}', [AuthController::class,'getNotification']);

    //Map
    Route::get('getBranches', [AuthController::class,'getBranches']);

    //Chat
    Route::post('chat/{type}/{id}', [MessageController::class,'store']);
    Route::get('getChat/{type}/{id}/{numOfPage}/{numOfRows}', [MessageController::class,'index']);
    Route::get('chatRoom', [MessageController::class,'chatRoom']);

});


Route::group([
    'middleware' => 'api',
    'prefix' => 'gym'
], function ($router) {

    //Home
    Route::get('home', [GymController::class,'home']);

    //Branch
    Route::post('branches', [GymController::class,'addNewBranch']);
    Route::get('branches', [GymController::class,'getBranches']);


    //Classes
    Route::post('branch/{branch_id}/classes', [ClassGymController::class,'store']);
    Route::post('classes/makeOffer/{class_id}', [ClassGymController::class,'makeOffer']);
    Route::get('branch/{branch_id}/classes', [ClassGymController::class,'index']);

    //Gym_Trainers
    Route::post('branch/{branch_id}/gym_trainers', [GymTrainerController::class,'store']);
    Route::get('branch/{branch_id}/gym_trainers', [GymTrainerController::class,'index']);

    //Booking
    Route::get('branch/{branch_id}/booking', [BookingGymController::class,'index']);
    Route::post('verifyAttendance/{id}', [BookingGymController::class,'verify']);



    //CRM

    Route::get('getGymAttendance/{id}/{monthName}', [crmController::class,'getGymAttendance']);
    Route::get('getGymVacations/{id}', [crmController::class,'getGymVacations']);
    Route::get('getGymFinances/{id}', [crmController::class,'getGymFinances']);
    Route::get('getGymAllAttendance/{monthName}', [crmController::class,'getGymAllAttendance']);
    Route::get('getGymAllVacations', [crmController::class,'getGymAllVacations']);
    Route::get('getGymAllFinances', [crmController::class,'getGymAllFinances']);



});




Route::group([
    'middleware' => 'api',
    'prefix' => 'user'
], function ($router) {

    //Update
    Route::post('update/users/{id}', [AuthController::class,'updateUser']);


    //Gym
    Route::get('gyms/{numOfPage}/{numOfRows}', [GymController::class,'index']);
    Route::get('gyms/search/{name}/{numOfPage}/{numOfRows}', [GymController::class,'search']);

    //Trainers
    Route::get('trainers/search/{name}/{numOfPage}/{numOfRows}', [TrainerController::class,'search']);

    //Gym_Booking
    Route::post('booking_gym', [BookingGymController::class,'store']);
    Route::get('bookingWithTrainer/{booking_id}', [BookingGymController::class,'bookingWithTrainer']);
    Route::get('getUserBooking/{numOfPage}/{numOfRows}', [AuthController::class,'getUserBooking']);
    Route::get('getUserBookingTrainer/{numOfPage}/{numOfRows}', [AuthController::class,'getUserBookingTrainer']);
    Route::get('getOneGymBooking/{id}', [AuthController::class,'getOneGymBooking']);
    Route::get('teste', [AuthController::class,'teste']);



    //Trainer_Booking
    Route::post('booking_trainer', [BookingTrainerController::class,'store']);

    //Favorites
    Route::post('favorite/gyms/{gym_id}', [FavoriteController::class,'favoriteGym']);
    Route::post('favorite/trainer/{trainer_id}', [FavoriteController::class,'favoriteTrainer']);
    Route::delete('favorite/gyms/{gym_id}', [FavoriteController::class,'deleteGymFavorite']);
    Route::delete('favorite/trainer/{trainer_id}', [FavoriteController::class,'deleteTrainerFavorite']);
    Route::get('favorite/gyms', [FavoriteController::class,'getGymFavorites']);
    Route::get('favorite/trainers', [FavoriteController::class,'getTrainerFavorites']);


    //Feedback
    Route::post('feedback/{booking_id}', [ReviewController::class,'store']);

    //Home
    Route::get('home', [AuthController::class,'home']);

});

Route::group([
    'middleware' => 'api',
    'prefix' => 'trainer'
], function ($router) {

    //Trainers_media
    Route::post('medias', [TrainerController::class,'addToMedia']);
    Route::delete('medias/remove/{id}', [TrainerController::class,'removeMedia']);
    Route::get('medias/{id}/images/{numOfPage}/{numOfRows}', [TrainerController::class,'getImages']);
    Route::get('medias/{id}/videos/{numOfPage}/{numOfRows}', [TrainerController::class,'getVideos']);

    //Schedules
    Route::post('schedules', [TrainerController::class,'makeSchedule']);

    //Home
    Route::get('home/{status}', [TrainerController::class,'home']);

});


Route::post('/auth/broadcasting/auth/a', function (Request $request) {


    if($request->header('type') == 'user')
    {
        try {
            $user = auth('user')->userOrFail();

        } catch (\Tymon\JWTAuth\Exceptions\UserNotDefinedException $e) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
    }elseif ($request->header('type') == 'gym')
    {
        try {
            $user = auth('gym')->userOrFail();
        } catch (\Tymon\JWTAuth\Exceptions\UserNotDefinedException $e) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
    }elseif ($request->header('type') == 'trainer')
    {
        try {
            $user = auth('trainer')->userOrFail();
        } catch (\Tymon\JWTAuth\Exceptions\UserNotDefinedException $e) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
    }
    $pusher = new \Pusher\Pusher('8525a86f244e2694c04d','63dcb37f350e18687dd8','1430924',['cluster' =>'eu']);
    $data = $pusher->presenceAuth($request->channel_name,$request->socket_id,$user->id,$user);
    return $data;
});

Route::group([
    'middleware' => 'api',
    'prefix' => 'admin'
], function ($router) {

    Route::get('getGyms/{numOfPage}/{numOfRows}', [AdminController::class,'getGyms']);
    Route::get('search/getGyms/{name}/{numOfPage}/{numOfRows}', [AdminController::class,'getGymsSearch']);
    Route::get('getGymDetails/{id}', [AdminController::class,'getGymDetails']);
    Route::get('getBranchDetails/{id}', [AdminController::class,'getBranchDetails']);
    Route::get('getUsers/{numOfPage}/{numOfRows}', [AdminController::class,'getUsers']);
    Route::get('search/getUsers/{name}/{numOfPage}/{numOfRows}', [AdminController::class,'getUsersSearch']);
    Route::get('getUserDetails/{id}', [AdminController::class,'getUserDetails']);
    Route::get('getTrainers/{numOfPage}/{numOfRows}', [AdminController::class,'getTrainers']);
    Route::get('search/getTrainers/{name}/{numOfPage}/{numOfRows}', [AdminController::class,'getTrainersSearch']);
    Route::get('getTrainerDetails/{id}', [AdminController::class,'getTrainerDetails']);
    Route::get('getTrainerImages/{id}/{numOfPage}/{numOfRows}', [AdminController::class,'getTrainerImages']);
    Route::get('getTrainerVideos/{id}/{numOfPage}/{numOfRows}', [AdminController::class,'getTrainerVideos']);
    Route::get('getGymBooking/{numOfPage}/{numOfRows}', [AdminController::class,'getGymBooking']);
    Route::get('search/getGymBooking/{name}/{numOfPage}/{numOfRows}', [AdminController::class,'getGymBookingSearch']);
    Route::get('getGymBookingDetails/{id}', [AdminController::class,'getGymBookingDetails']);
    Route::get('getTrainerBooking/{numOfPage}/{numOfRows}', [AdminController::class,'getTrainerBooking']);
    Route::get('search/getTrainerBooking/{name}/{numOfPage}/{numOfRows}', [AdminController::class,'getTrainerBookingSearch']);
    Route::get('getTrainerBookingDetails/{id}', [AdminController::class,'getTrainerBookingDetails']);


    Route::delete('delete/users/{id}', [AdminController::class,'deleteUser']);
    Route::delete('delete/gyms/{id}', [AdminController::class,'deleteGym']);
    Route::delete('delete/trainers/{id}', [AdminController::class,'deleteTrainer']);
    Route::delete('delete/trainer_medias/{id}', [AdminController::class,'deleteTrainerMedia']);
    Route::delete('delete/gym_trainers/{id}', [AdminController::class,'deleteGymTrainer']);
    Route::delete('delete/gymBooking/{id}', [AdminController::class,'deleteGymBooking']);
    Route::delete('delete/trainerBooking/{id}', [AdminController::class,'deleteTrainerBooking']);

    Route::post('changePassword', [AdminController::class,'changePassword']);
    Route::post('update/users/{id}', [AdminController::class,'updateUser']);
    Route::post('update/gyms/{id}', [AdminController::class,'updateGym']);
    Route::post('update/trainers/{id}', [AdminController::class,'updateTrainer']);
    Route::post('update/gymBooking/{id}', [AdminController::class,'updateGymBooking']);
    Route::post('update/trainerBooking/{id}', [AdminController::class,'updateTrainerBooking']);

    //Features
    Route::post('block/users/{id}', [AdminController::class,'blockUser']);
    Route::post('block/gyms/{id}', [AdminController::class,'blockGym']);
    Route::post('block/trainers/{id}', [AdminController::class,'blockTrainer']);
    Route::post('unblock/users/{id}', [AdminController::class,'unblockUser']);
    Route::post('unblock/gyms/{id}', [AdminController::class,'unblockGym']);
    Route::post('unblock/trainers/{id}', [AdminController::class,'unblockTrainer']);
    Route::post('ban/users/{id}', [AdminController::class,'banUser']);
    Route::post('ban/gyms/{id}', [AdminController::class,'banGym']);
    Route::post('ban/trainers/{id}', [AdminController::class,'banTrainer']);



    //CRM

    Route::post('addAttendance/{id}', [crmController::class,'addAttendance']);
    Route::post('addFinance/{id}', [crmController::class,'addFinance']);

    Route::get('getAttendance/{id}/{monthName}', [crmController::class,'getAttendance']);
    Route::get('getVacations/{id}', [crmController::class,'getVacations']);
    Route::get('getFinances/{id}', [crmController::class,'getFinances']);
    Route::get('getAllAttendance/{monthName}', [crmController::class,'getAllAttendance']);
    Route::get('getAllVacations', [crmController::class,'getAllVacations']);
    Route::get('getAllFinances', [crmController::class,'getAllFinances']);
    Route::get('bookingCRM/{monthName}', [crmController::class,'bookingCRM']);

});
