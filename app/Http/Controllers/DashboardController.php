<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class DashboardController extends Controller
{
    public function getAdminDashboardStats()
    {
        $activeEmployeesCount = DB::table('employees')
            ->where('status', DEFAULT_STATUSES['active'])
            ->count();
        $activeBranchesCount = DB::table('branches')
            ->where('status', DEFAULT_STATUSES['active'])
            ->count();

        return response()->json([
            'success' => true,
            'data' => [
                'active_employees_count' => $activeEmployeesCount,
                'active_branches_count' => $activeBranchesCount,
            ]
        ]);
    }

    public function getBranchDashboardStats()
    {
        $activeEmployeesCount = DB::table('employees')
            ->where('status', DEFAULT_STATUSES['active'])
            ->where('branch_id', Auth::user()->branch_id)
            ->count();

        $pendingOrdersCount = DB::table('orders')
            ->where('status', ORDER_STATUSES['pending'])
            ->where('branch_id', Auth::user()->branch_id)
            ->whereDate('delivery_date', '>=', Carbon::today())
            ->count();

        $todaysPendingOrdersCount = DB::table('orders')
            ->where('status', ORDER_STATUSES['pending'])
            ->where('branch_id', Auth::user()->branch_id)
            ->whereDate('delivery_date', Carbon::today())
            ->count();

        return response()->json([
            'success' => true,
            'data' => [
                'active_employees_count' => $activeEmployeesCount,
                'pending_orders_count' => $pendingOrdersCount,
                'todays_pending_orders_count' => $todaysPendingOrdersCount,
            ]
        ]);
    }
}
