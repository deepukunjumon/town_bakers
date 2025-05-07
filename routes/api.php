<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BranchController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\ItemsController;
use App\Http\Controllers\StockController;

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

        Route::get('/branchwise/stocks/summary', [StockController::class, 'branchwiseStocksSummary']);
    });

    Route::middleware('branch')->group(function () {
        // Route::get('/branch/employees', [EmployeeController::class, 'getEmployeesForAuthenticatedBranch']);
    });

    // Public/General Routes for all authenticated users
    Route::post('/create/item', [ItemsController::class, 'createItem']);
    Route::get('/items/list', [ItemsController::class, 'getItems']);
    Route::get('/branches/minimal', [BranchController::class, 'getMinimalBranches']);

    Route::post('/create/employee', [EmployeeController::class, 'createEmployee']);
    Route::get('/employees/minimal', [EmployeeController::class, 'getMinimalEmployees']);
    Route::get('/employees/{branch_id}', [EmployeeController::class, 'getEmployeesByBranch']);

    Route::post('/stock/add', [StockController::class, 'addStock']);
    Route::get('/stock/trip/{trip_id}', [StockController::class, 'getTripDetails']);
    Route::get('/stocks/summary', [StockController::class, 'getItemsByDate']);
    Route::get('/stocks/summary/export', [StockController::class, 'exportItemsByDate']);
});
