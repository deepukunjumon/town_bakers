<?php

use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\BranchController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\ItemsController;
use App\Http\Controllers\StockController;
use App\Http\Controllers\TripController;

Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout']);

Route::post('/create/branch', [BranchController::class, 'createBranch']);
Route::post('/branch/update/{branch_id}', [BranchController::class, 'updateBranch']);
Route::get('/branches', [BranchController::class, 'getBranches']);

Route::post('/create/employee', [EmployeeController::class, 'createEmployee']);
Route::post('/employee/update/{employee_id}', [EmployeeController::class, 'updateEmployee']);
Route::get('/employees/{branch_id}', [EmployeeController::class, 'getEmployees']);

Route::post('/create/item', [ItemsController::class, 'createItem']);

Route::post('/stock/add', [TripController::class, 'addStock']);
Route::get('/stock/trip/{trip_id}', [TripController::class, 'getTripDetails']);
Route::get('/stocks/summary', [TripController::class, 'getItemsByDate']);
Route::get('/stocks/summary/{branch_id}/export', [TripController::class, 'exportItemsByDate']);
