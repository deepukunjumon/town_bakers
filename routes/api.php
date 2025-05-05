<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BranchController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\ItemsController;
use App\Http\Controllers\TripController;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login']);

Route::middleware(['auth:api'])->group(function () {
    
    Route::post('/logout', [AuthController::class, 'logout']);
    
    // Admin routes (only accessible by admins)
    Route::middleware('admin')->group(function () {
        Route::post('/create/branch', [BranchController::class, 'createBranch']);
        Route::put('/branch/{branch_id}', [BranchController::class, 'updateBranch']);
        Route::get('/branches', [BranchController::class, 'getBranches']);

        Route::post('/create/employee', [EmployeeController::class, 'createEmployee']);
        Route::put('/employee/{employee_id}', [EmployeeController::class, 'updateEmployee']);
        Route::get('/employees', [EmployeeController::class, 'getEmployeesForAuthenticatedBranch']);
    });

    // Branch specific routes (for branch-level access)
    Route::middleware('branch')->group(function () {
        // Other branch-specific routes can go here, like:
        // Route::get('/employees', [EmployeeController::class, 'getEmployeesForAuthenticatedBranch']);
    });

    // Public/General Routes for all authenticated users
    Route::get('/items', [ItemsController::class, 'getItems']);

    Route::post('/stock/add', [TripController::class, 'addStock']);
    Route::get('/stock/trip/{trip_id}', [TripController::class, 'getTripDetails']);
    Route::get('/stocks/summary', [TripController::class, 'getItemsByDate']);
    Route::get('/stocks/summary/export', [TripController::class, 'exportItemsByDate']);
});
