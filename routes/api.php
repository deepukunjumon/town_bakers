<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BranchController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\ItemsController;
use App\Http\Controllers\TripController;
use App\Http\Middleware\AdminMiddleware;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login']);

Route::middleware(['auth:api'])->group(function () {

    Route::post('/logout', [AuthController::class, 'logout']);

    // Admin routes (only accessible by admins)
    Route::middleware(AdminMiddleware::class)->group(function () {
        Route::post('/create/branch', [BranchController::class, 'createBranch']);
        Route::put('/branch/{branch_id}', [BranchController::class, 'updateBranch']);
        Route::get('/branches', [BranchController::class, 'getBranches']);

        Route::put('/employee/{employee_id}', [EmployeeController::class, 'updateEmployee']);
        Route::get('/employees', [EmployeeController::class, 'getEmployeesForAuthenticatedBranch']);
        
        Route::get('/branchwise/stocks/summary', [TripController::class, 'branchwiseStocksSummary']);
    });
    
    // Branch specific routes (for branch-level access)
    Route::middleware('branch')->group(function () {});
    
    // Public/General Routes for all authenticated users
    Route::post('/create/item', [ItemsController::class, 'createItem']);
    Route::get('/items/list', [ItemsController::class, 'getItems']);
    Route::get('/employees/minimal', [EmployeeController::class, 'getMinimalEmployees']);
    Route::get('/branches/minimal', [BranchController::class, 'getMinimalBranches']);

    Route::post('/create/employee', [EmployeeController::class, 'createEmployee']);

    Route::post('/stock/add', [TripController::class, 'addStock']);
    Route::get('/stock/trip/{trip_id}', [TripController::class, 'getTripDetails']);
    Route::get('/stocks/summary', [TripController::class, 'getItemsByDate']);
});
