<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use App\Models\Employee;
use App\Models\Branch;
use App\Models\Order;
use App\Models\User;

class DashboardController extends Controller
{

    /**
     * Super Admin Dashboard Stats
     * @return JsonResponse
     */
    public function getSuperAdminDashboardStats(Request $request): JsonResponse
    {
        $cacheKey = 'super_admin_dashboard_stats';
        if ($request->boolean('orders')) {
            $cacheKey .= '_with_orders';
        }

        return cache()->remember($cacheKey, now()->addMinutes(15), function () use ($request) {
            $today = Carbon::today();

            $activeEmployeesCount = DB::table('employees')
                ->where('status', DEFAULT_STATUSES['active'])
                ->count();

            $activeBranchesCount = DB::table('branches')
                ->where('status', DEFAULT_STATUSES['active'])
                ->count();

            $todaysPendingOrdersCount = DB::table('orders')
                ->where('status', ORDER_STATUSES['pending'])
                ->whereDate('delivery_date', $today)
                ->count();

            $todaysCompletedOrdersCount = DB::table('orders')
                ->where('status', ORDER_STATUSES['delivered'])
                ->whereDate('delivery_date', $today)
                ->count();

            $activeUsersCount = DB::table('users')
                ->where('status', DEFAULT_STATUSES['active'])
                ->groupBy('role')
                ->select('role', DB::raw('COUNT(*) as count'))
                ->get()
                ->mapWithKeys(fn($item) => [$item->role => $item->count]);

            $responseData = [
                'active_employees_count' => $activeEmployeesCount,
                'active_branches_count' => $activeBranchesCount,
                'todays_pending_orders_count' => $todaysPendingOrdersCount,
                'todays_completed_orders_count' => $todaysCompletedOrdersCount,
                'active_users_count' => $activeUsersCount,
            ];

            if ($request->boolean('orders')) {
                $todaysOrders = DB::table('orders')
                    ->whereDate('delivery_date', $today)
                    ->select([
                        DB::raw('COUNT(*) as total'),
                        DB::raw('COUNT(CASE WHEN status = ' . ORDER_STATUSES['pending'] . ' THEN 1 END) as pending'),
                        DB::raw('COUNT(CASE WHEN status = ' . ORDER_STATUSES['delivered'] . ' THEN 1 END) as delivered'),
                        DB::raw('COUNT(CASE WHEN status = ' . ORDER_STATUSES['cancelled'] . ' THEN 1 END) as cancelled')
                    ])
                    ->first();

                $responseData['todays_orders'] = [
                    'total' => (int) $todaysOrders->total,
                    'pending' => (int) $todaysOrders->pending,
                    'delivered' => (int) $todaysOrders->delivered,
                    'cancelled' => (int) $todaysOrders->cancelled
                ];
            }

            return response()->json([
                'success' => true,
                'data' => $responseData
            ]);
        });
    }

    /**
     * Admin Dashboard Stats
     * @return JsonResponse
     */
    public function getAdminDashboardStats(): JsonResponse
    {
        $activeEmployeesCount = DB::table('employees')
            ->where('status', DEFAULT_STATUSES['active'])
            ->count();
        $activeBranchesCount = DB::table('branches')
            ->where('status', DEFAULT_STATUSES['active'])
            ->count();
        $todaysPendingOrdersCount = DB::table('orders')
            ->where('status', ORDER_STATUSES['pending'])
            ->whereDate('delivery_date', Carbon::today())
            ->count();
        $todaysDeliveredOrdersCount = DB::table('orders')
            ->where('status', ORDER_STATUSES['delivered'])
            ->whereDate('delivery_date', Carbon::today())
            ->count();

        return response()->json([
            'success' => true,
            'data' => [
                'active_employees_count' => $activeEmployeesCount,
                'active_branches_count' => $activeBranchesCount,
                'todays_pending_orders_count' => $todaysPendingOrdersCount,
                'todays_delivered_orders_count' => $todaysDeliveredOrdersCount
            ]
        ]);
    }

    /**
     * Branch Dashboard Stats
     * @return JsonResponse
     */
    public function getBranchDashboardStats(): JsonResponse
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

        $todaysDeliveredOrdersCount = DB::table('orders')
            ->where('status', ORDER_STATUSES['delivered'])
            ->where('branch_id', Auth::user()->branch_id)
            ->whereDate('delivery_date', Carbon::today())
            ->count();

        return response()->json([
            'success' => true,
            'data' => [
                'active_employees_count' => $activeEmployeesCount,
                'pending_orders_count' => $pendingOrdersCount,
                'todays_pending_orders_count' => $todaysPendingOrdersCount,
                'todays_delivered_orders_count' => $todaysDeliveredOrdersCount
            ]
        ]);
    }
}
