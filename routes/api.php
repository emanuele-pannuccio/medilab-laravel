<?php

use App\Http\Controllers\ReportController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\DoctorController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\PatientController;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// middleware( "auth:sanctum")->
Route::prefix("")->group(function(){
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    
    Route::prefix('department')->group(function () {
        Route::get('/{department}', [DepartmentController::class, 'show']);
        Route::delete('/{department}', [DepartmentController::class, 'destroy']);
        Route::put('/{department}', [DepartmentController::class, 'update']);
        Route::post('', [DepartmentController::class, 'store']);
        Route::get('', [DepartmentController::class, 'index']);
    });


    Route::prefix('doctor')->group(function () {
        Route::get('/{doctor}', [DoctorController::class, 'show']);
        Route::delete('/{doctor}', [DoctorController::class, 'destroy']);
        Route::put('/{doctor}', [DoctorController::class, 'update']);
        Route::post('', [DoctorController::class, 'store']);
        Route::get('', [DoctorController::class, 'index']);
    });
    
    Route::get('/document', [ReportController::class, 'get_documents']);
    Route::post('/document', [ReportController::class, 'elaborate_document']);

    Route::prefix('report')->group(function () {
        Route::get('/{report}', [ReportController::class, 'show']);
        Route::get('/{report}/document', [ReportController::class, 'get_document']);
        Route::delete('/{report}', [ReportController::class, 'destroy']);
        Route::put('/{report}', [ReportController::class, 'update']);
        Route::post('', [ReportController::class, 'store']);
        Route::get('', [ReportController::class, 'index']);
        
    });


    Route::prefix('patient')->group(function () {
        Route::get('/{patient}', [PatientController::class, 'show']);
        Route::delete('/{patient}', [PatientController::class, 'destroy']);
        Route::put('/{patient}', [PatientController::class, 'update']);
        Route::post('', [PatientController::class, 'store']);
        Route::get('', [PatientController::class, 'index']);
    });
})->middleware("auth:sanctum");

Route::middleware('auth:sanctum')->get('/notifications/unread', function (Request $request) {
    return $request->user()->unreadNotifications;
});

Route::middleware('auth:sanctum')->post('/notifications/{id}/read', function (Request $request, $id) {
    $notification = $request->user()->notifications()->findOrFail($id);
    $notification->markAsRead();
    return response()->json(['status' => 'success']);
});

Route::prefix('token')->group(function () {
    Route::post('', [AuthController::class, 'login']);
    Route::middleware('auth:sanctum')->group(function(){
        Route::delete('', [AuthController::class, 'logout']);
        Route::get('', action: [AuthController::class, 'me']);
    });
});

