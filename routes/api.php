<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BranchController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\ItemsController;
use App\Http\Controllers\StockController;
use App\Http\Controllers\DesignationController;
use App\Http\Middleware\AdminMiddleware;
use App\Http\Middleware\BranchMiddleware;
use Illuminate\Support\Facades\Route;

// Login route (public)
Route::post('/login', [AuthController::class, 'login']);

// Authenticated routes
Route::middleware('auth:api')->group(function () {

    // Logout
    Route::post('/logout', [AuthController::class, 'logout']);

    // Admin-only routes
    Route::prefix('admin')->middleware(AdminMiddleware::class)->group(function () {
        Route::get('/dashboard/stats', [DashboardController::class, 'getAdminDashboardStats']);
        Route::post('/create/branch', [BranchController::class, 'createBranch']);
        Route::post('/create/employee   ', [EmployeeController::class, 'createEmployeeForAdmin']);
        Route::post('/import/employees', [EmployeeController::class, 'importEmployees']);
        Route::put('/branch/{branch_id}', [BranchController::class, 'updateBranch']);
        Route::get('/branches', [BranchController::class, 'getBranches']);

        Route::put('/employee/{employee_id}', [EmployeeController::class, 'updateEmployee']);
        Route::get('/employees/{branch_id}', [EmployeeController::class, 'getEmployeesByBranch']);

        Route::post('/branchwise/stock/summary', [StockController::class, 'branchwiseStockSummary']);
    });

    // Branch-only routes (NOT affected by AdminMiddleware)
    Route::prefix('branch')->middleware(BranchMiddleware::class)->group(function () {
        Route::get('/dashboard/stats', [DashboardController::class, 'getBranchDashboardStats']);
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
});
