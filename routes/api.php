<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\BranchController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\ItemsController;
use App\Http\Controllers\StockController;
use App\Http\Controllers\MailController;
use App\Http\Controllers\DesignationController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\AuditLogController;
use App\Http\Middleware\CheckPasswordResetMiddleware;
use App\Http\Middleware\AdminMiddleware;
use App\Http\Middleware\BranchMiddleware;
use App\Http\Middleware\SuperAdminMiddleware;
use Illuminate\Support\Facades\Route;

Route::get('/ping', function () {
    return response()->json(['message' => 'pong']);
});

// Public routes (no token required)
Route::post('/login', [AuthController::class, 'login']);
Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('/reset-password', [AuthController::class, 'resetPasswordWithToken']);

// Protected routes (token required)
Route::middleware(['check.token'])->group(function () {
    Route::post('/password/reset', [AuthController::class, 'resetPassword']);

    Route::middleware(['check.password.reset'])->group(function () {
        // Authenticated routes
        Route::middleware(['jwt.auth'])->group(function () {
            Route::get('/profile', [UserController::class, 'getProfileDetails']);
            Route::post('/update/profile', [UserController::class, 'updateProfileDetails']);

            // Logout
            Route::post('/logout', [AuthController::class, 'logout']);

            // Super Admin only routes
            Route::prefix('/super-admin')->middleware(SuperAdminMiddleware::class)->group(function () {
                Route::get('/dashboard/stats', [DashboardController::class, 'getSuperAdminDashboardStats']);
                Route::post('/create/user', [UserController::class, 'createUser']);
                Route::post('/test-mail', [MailController::class, 'testMail']);

                Route::get('/logs/audit-logs', [AuditLogController::class, 'getAuditLogs']);
                Route::get('/list/tables', [AuditLogController::class, 'getLoggableTables']);
            });

            // Admin-only routes
            Route::prefix('admin')->middleware(AdminMiddleware::class)->group(function () {
                Route::get('/dashboard/stats', [DashboardController::class, 'getAdminDashboardStats']);
                Route::post('/create/branch', [BranchController::class, 'createBranch']);
                Route::post('/create/employee', [EmployeeController::class, 'createEmployeeForAdmin']);
                Route::post('/import/employees', [EmployeeController::class, 'importEmployees']);

                Route::get('/branches', [BranchController::class, 'getAllBranches']);
                Route::get('/branch/{branch_id}', [BranchController::class, 'getBranchDetails']);
                Route::put('/branch/update/{branch_id}', [BranchController::class, 'updateBranch']);

                Route::get('/all-employees', [EmployeeController::class, 'getAllEmployees']);
                Route::get('/employees/{branch_id}', [EmployeeController::class, 'getEmployeesByBranch']);

                Route::post('/branchwise/stock/summary', [StockController::class, 'branchwiseStockSummary']);

                // Order routes
                Route::get('/orders', [OrderController::class, 'getAllOrders']);
                Route::get('/orders/branch/{branch_id}', [OrderController::class, 'getOrdersByBranchID']);
            });

            // Branch-only routes (NOT affected by AdminMiddleware)
            Route::prefix('branch')->middleware(BranchMiddleware::class)->group(function () {
                Route::get('/dashboard/stats', [DashboardController::class, 'getBranchDashboardStats']);

                //Employee Routes
                Route::post('create/employee', [EmployeeController::class, 'createEmployeeForBranch']);
                Route::get('/employees', [EmployeeController::class, 'getEmployeesForAuthenticatedBranch']);

                //Order routes
                Route::post('/create/order', [OrderController::class, 'createOrder']);
                Route::get('/orders', [OrderController::class, 'getOrdersForBranch']);

                //Stock routes
                Route::post('/stock/summary', [StockController::class, 'getItemsByDate']);
            });

            // General authenticated routes (available to all roles)
            Route::post('/create/item', [ItemsController::class, 'createItem']);
            Route::post('/import/items', [ItemsController::class, 'importItems']);
            Route::get('/items', [ItemsController::class, 'getAllItems']);
            Route::get('/items/minimal', [ItemsController::class, 'getMinimalActiveItems']);
            Route::post('/item/update-status', [ItemsController::class, 'updateItemStatus']);

            Route::post('/create/designation', [DesignationController::class, 'createDesignation']);
            Route::get('/designations', [DesignationController::class, 'getAllDesignations']);
            Route::get('/designations/active', [DesignationController::class, 'getActiveDesignations']);
            Route::post('/designation/update-status', [DesignationController::class, 'updateDesignationStatus']);

            Route::get('/branches/minimal', [BranchController::class, 'getMinimalBranches']);
            Route::get('/employees/minimal', [EmployeeController::class, 'getMinimalEmployees']);
            Route::put('/employee/{employee_id}', [EmployeeController::class, 'updateEmployeeDetails']);
            Route::post('/employee/update-status', [EmployeeController::class, 'updateEmployeeStatus']);

            Route::get('/order/{id}', [OrderController::class, 'getOrderDetailsByID']);

            Route::post('/stock/add', [StockController::class, 'addStock']);
            Route::get('/stock/trip/{trip_id}', [StockController::class, 'getTripDetails']);

            Route::put('/order/{id}/status', [OrderController::class, 'updateOrderStatus']);
        });
    });
});
