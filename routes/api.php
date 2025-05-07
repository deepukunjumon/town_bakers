<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BranchController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\ItemsController;
use App\Http\Controllers\StockController;
use App\Http\Controllers\DesignationController;

use App\Http\Middleware\AdminMiddleware;
use Illuminate\Support\Facades\Route;

// Login route (public)
Route::post('/login', [AuthController::class, 'login']);

// Authenticated routes
Route::middleware('auth:api')->group(function () {
    
    // Logout
    Route::post('/logout', [AuthController::class, 'logout']);
    
    // Admin-only routes
    Route::middleware(AdminMiddleware::class)->group(function () {
        Route::post('/create/branch', [BranchController::class, 'createBranch']);
        Route::post('/create/employee   ', [EmployeeController::class, 'createEmployeeForAdmin']);
        Route::put('/branch/{branch_id}', [BranchController::class, 'updateBranch']);
        Route::get('/branches', [BranchController::class, 'getBranches']);
        
        Route::put('/employee/{employee_id}', [EmployeeController::class, 'updateEmployee']);
        Route::get('/employees/{branch_id}', [EmployeeController::class, 'getEmployeesByBranch']);
        
        Route::get('/branchwise/stocks/summary', [StockController::class, 'branchwiseStocksSummary']);
    });
    
    // Branch-only routes (NOT affected by AdminMiddleware)
    Route::middleware('branch')->group(function () {
    });
    
    Route::post('/branch/create/employee', [EmployeeController::class, 'createEmployeeForBranch']);
    Route::get('/branch/employees', [EmployeeController::class, 'getEmployeesForAuthenticatedBranch']);
    // General authenticated routes (available to all roles)
    Route::post('/create/item', [ItemsController::class, 'createItem']);
    Route::get('/items/list', [ItemsController::class, 'getItems']);
    Route::get('/branches/minimal', [BranchController::class, 'getMinimalBranches']);

    Route::post('/create/designation', [DesignationController::class, 'createDesignation']);
    Route::get('/designations', [DesignationController::class, 'getAllActiveDesignations']);

    Route::get('/employees/minimal', [EmployeeController::class, 'getMinimalEmployees']);

    Route::post('/stock/add', [StockController::class, 'addStock']);
    Route::get('/stock/trip/{trip_id}', [StockController::class, 'getTripDetails']);
    Route::post('/stocks/summary', [StockController::class, 'getItemsByDate']);
    Route::get('/stocks/summary/export', [StockController::class, 'exportItemsByDate']);
});
