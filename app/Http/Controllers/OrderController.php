<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\Order;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use App\Http\Resources\OrderSummaryResource;
use Carbon\Carbon;

class OrderController extends Controller
{
    /**
     * List orders of logged in branch
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function getOrdersForBranch(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'status' => 'nullable|in:-1,0,1',
            'payment_status' => 'nullable|in:-1,0,1,2',
            'delivery_date' => 'nullable|date',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1',
            'search' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $status = $request->input('status');
        $paymentStatus = $request->input('payment_status');
        $deliveryDate = $request->input('delivery_date');
        $startDate = $request->input('start_date') ?: now()->startOfMonth();
        $endDate = $request->input('end_date') ?: now()->endOfMonth();
        $page = $request->input('page', 1);
        $perPage = $request->input('per_page', 10);
        $search = $request->input('search', '');

        $user = Auth::user();
        $branchId = $user->branch_id;

        if (!$branchId) {
            return response()->json(['success' => false, 'message' => 'Branch ID not found'], 404);
        }

        $ordersQuery = Order::with(['employee:id,employee_code,name'])
            ->select([
                'id', 'branch_id', 'employee_id', 'title', 'description', 'remarks',
                'delivery_date', 'delivery_time', 'customer_name', 'customer_email',
                'customer_mobile', 'total_amount', 'advance_amount', 'payment_status',
                'status', 'delivered_at', 'delivered_by', 'created_by', 'created_at', 'updated_at'
            ])
            ->where('branch_id', $branchId)
            ->when($status !== null, function ($query) use ($status) {
                return $query->where('status', $status);
            })
            ->when($paymentStatus !== null, function ($query) use ($paymentStatus) {
                return $query->where('payment_status', $paymentStatus);
            })
            ->when($deliveryDate, function ($query) use ($deliveryDate) {
                return $query->where('delivery_date', $deliveryDate);
            })
            ->whereBetween('delivery_date', [$startDate, $endDate]);

        if ($search) {
            $ordersQuery->where(function ($query) use ($search) {
                $query->where('title', 'like', '%' . $search . '%')
                    ->orWhere('description', 'like', '%' . $search . '%')
                    ->orWhere('remarks', 'like', '%' . $search . '%')
                    ->orWhere('customer_name', 'like', '%' . $search . '%')
                    ->orWhere('customer_email', 'like', '%' . $search . '%')
                    ->orWhere('customer_mobile', 'like', '%' . $search . '%');
            });
        }

        $orders = $ordersQuery->orderBy('delivery_date', 'asc')
            ->paginate($perPage, ['*'], 'page', $page);

        if ($orders->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No orders found for the given date range'
            ]);
        }

        $ordersResource = OrderSummaryResource::collection($orders);

        return response()->json([
            'success' => true,
            'message' => 'Orders fetched successfully',
            'orders' => $ordersResource,
            'pagination' => [
                'current_page' => $orders->currentPage(),
                'last_page' => $orders->lastPage(),
                'per_page' => $orders->perPage(),
                'total' => $orders->total(),
            ]
        ]);
    }

    /**
     * Order details from order id
     * 
     * @param Request $request
     * @param mixed $id
     * @return JsonResponse
     */
    public function getOrderDetailsByID(Request $request, $id): JsonResponse
    {
        $validator = Validator::make(
            ['id' => $request->route('id')],
            ['id' => 'required|uuid|exists:orders,id']
        );

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = Auth::user();
        $userRole = $user->role;
        $branchId = $user->branch_id ?? null;

        // Select only required columns and eager load only needed fields
        $order = Order::with(['employee:id,employee_code,name'])
            ->select([
                'id', 'branch_id', 'employee_id', 'title', 'description', 'remarks',
                'delivery_date', 'delivery_time', 'customer_name', 'customer_email',
                'customer_mobile', 'total_amount', 'advance_amount', 'payment_status',
                'status', 'delivered_at', 'delivered_by', 'created_by', 'created_at', 'updated_at'
            ])
            ->where('id', $id)
            ->first();

        if (!$order) {
            return response()->json(['success' => false, 'message' => 'Order not found'], 404);
        }

        if (($order->branch_id !== $branchId) && (!in_array($userRole, ['admin', 'super_admin']))) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $order_details = new OrderSummaryResource($order);

        if (!$order_details){
            return response()->json([
                'success' => false,
                'error' => 'Could not fetch order details'
            ]);
        }
        
        return response()->json([
            'success' => true, 
            'message' => 'Order details fetched successfully',
            'order' => $order_details
        ]);
    }

    public function getOrderDetailsByAdmin(Request $request, $id)
    {
        // Eager load only required columns for relations
        $order = Order::with([
            'employee:id,id,name',
            'branch:id,id,name',
            'creator:id,id,name'
        ])
        ->select([
            'id', 'branch_id', 'employee_id', 'title', 'description', 'remarks',
            'delivery_date', 'delivery_time', 'customer_name', 'customer_email',
            'customer_mobile', 'total_amount', 'advance_amount', 'payment_status',
            'status', 'delivered_at', 'delivered_by', 'created_by', 'created_at', 'updated_at'
        ])
        ->where('id', $id)
        ->first();

        return response()->json(['order' => $order]);
    }

    /**
     * Create order from branch
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function createOrder(Request $request): JsonResponse
    {
        $user = Auth::user();

        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'remarks' => 'nullable|string',
            'delivery_date' => 'required|date',
            'delivery_time' => 'required|date_format:H:i',
            'customer_name' => 'required|string|max:255',
            'customer_email' => 'nullable|email|max:255',
            'customer_mobile' => 'required|string|max:15',
            'employee_id' => 'nullable|uuid|exists:employees,id',
            'total_amount' => 'required|numeric|min:0',
            'advance_amount' => 'nullable|numeric|min:0',
            'payment_status' => 'required|in:-1,0,1,2',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Use mass assignment with only necessary fields
        $order = Order::create([
            'branch_id' => $user->branch_id,
            'employee_id' => $request->employee_id,
            'title' => $request->title,
            'description' => $request->description,
            'remarks' => $request->remarks,
            'delivery_date' => $request->delivery_date,
            'delivery_time' => $request->delivery_time,
            'customer_name' => $request->customer_name,
            'customer_email' => $request->customer_email,
            'customer_mobile' => $request->customer_mobile,
            'total_amount' => $request->total_amount,
            'advance_amount' => $request->advance_amount,
            'payment_status' => $request->payment_status,
            'status' => 0,
            'delivered_at' => null,
            'delivered_by' => null,
            'created_by' => $user->id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Order created', 
            'order_details' => $order
        ]);
    }

    /**
     * Updates order status
     * 
     * @param Request $request
     * @param mixed $id
     * 
     * @return JsonResponse
     */
    public function updateOrderStatus(Request $request, $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|in:-1,0,1',
            'delivered_by' => 'nullable|uuid|exists:employees,id'
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $order = Order::select(['id', 'branch_id', 'payment_status', 'status'])
            ->findOrFail($id);

        $user = Auth::user();
        if ($user->branch_id !== $order->branch_id && !in_array($user->role, [ROLES['super_admin'], ROLES['admin']])) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $order->status = $request->status;
        $order->delivered_at = $request->status == 1 ? now() : null;
        $order->delivered_by = $request->status == 1 ? $request->delivered_by : null;

        if ($request->status == 1) {
            if ($order->payment_status == 0 || $order->payment_status == 1) {
                $order->payment_status = 2;
            }
        } elseif ($request->status == -1) {
            $order->payment_status = -1;
        }

        $order->save();

        return response()->json(['success' => true, 'message' => 'Order status updated']);
    }

    /**
     * List orders for admin (all branches)
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getAllOrders(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'branch_id' => 'nullable|exists:branches,id',
            'status' => 'nullable|in:-1,0,1',
            'payment_status' => 'nullable|in:-1,0,1,2',
            'delivery_date' => 'nullable|date',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1',
            'search' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $status = $request->input('status');
        $paymentStatus = $request->input('payment_status');
        $deliveryDate = $request->input('delivery_date');
        $startDate = $request->input('start_date') ?: now()->startOfMonth();
        $endDate = $request->input('end_date') ?: now()->endOfMonth();
        $branchId = $request->input('branch_id');
        $page = $request->input('page', 1);
        $perPage = $request->input('per_page', 10);
        $search = $request->input('search', '');

        $ordersQuery = Order::with(['employee:id,employee_code,name', 'branch:id,code,name'])
            ->select([
                'id', 'branch_id', 'employee_id', 'title', 'description', 'remarks',
                'delivery_date', 'delivery_time', 'customer_name', 'customer_email',
                'customer_mobile', 'total_amount', 'advance_amount', 'payment_status',
                'status', 'delivered_at', 'delivered_by', 'created_by', 'created_at', 'updated_at'
            ])
            ->when($branchId, function ($query) use ($branchId) {
                return $query->where('branch_id', $branchId);
            })
            ->when($status !== null, function ($query) use ($status) {
                return $query->where('status', $status);
            })
            ->when($paymentStatus !== null, function ($query) use ($paymentStatus) {
                return $query->where('payment_status', $paymentStatus);
            })
            ->when($deliveryDate, function ($query) use ($deliveryDate) {
                return $query->where('delivery_date', $deliveryDate);
            })
            ->whereBetween('delivery_date', [$startDate, $endDate]);

        if ($search) {
            $ordersQuery->where(function ($query) use ($search) {
                $query->where('title', 'like', '%' . $search . '%')
                    ->orWhere('description', 'like', '%' . $search . '%')
                    ->orWhere('remarks', 'like', '%' . $search . '%')
                    ->orWhere('customer_name', 'like', '%' . $search . '%')
                    ->orWhere('customer_email', 'like', '%' . $search . '%')
                    ->orWhere('customer_mobile', 'like', '%' . $search . '%');
            });
        }

        $orders = $ordersQuery->orderBy('delivery_date', 'asc')
            ->paginate($perPage, ['*'], 'page', $page);

        $ordersResource = OrderSummaryResource::collection($orders);

        return response()->json([
            'success' => true,
            'message' => 'Orders fetched successfully',
            'orders' => $ordersResource,
            'pagination' => [
                'current_page' => $orders->currentPage(),
                'last_page' => $orders->lastPage(),
                'per_page' => $orders->perPage(),
                'total' => $orders->total(),
            ]
        ]);
    }

    public function adminStore(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'branch_id' => 'required|uuid|exists:branches,id',
            'employee_id' => 'nullable|uuid|exists:employees,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'remarks' => 'nullable|string',
            'delivery_date' => 'required|date',
            'total_amount' => 'required|numeric|min:0',
            'advance_amount' => 'nullable|numeric|min:0',
            'payment_status' => 'required|in:-1,0,1,2',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $order = Order::create([
            'branch_id' => $request->branch_id,
            'employee_id' => $request->employee_id,
            'title' => $request->title,
            'description' => $request->description,
            'remarks' => $request->remarks,
            'delivery_date' => $request->delivery_date,
            'total_amount' => $request->total_amount,
            'advance_amount' => $request->advance_amount,
            'payment_status' => $request->payment_status,
            'status' => 0,
            'created_by' => Auth::id(),
        ]);

        return response()->json([
            'success' => true, 
            'message' => 'Order created by admin', 
            'order_details' => $order
        ]);
    }

    /**
     * List orders for a specific branch (Admin only)
     * 
     * @param Request $request
     * @param string $branchId
     * @return JsonResponse
     */
    public function getOrdersByBranchId(Request $request, $branchId): JsonResponse
    {
        $validator = Validator::make(
            array_merge($request->all(), ['branch_id' => $branchId]),
            [
                'branch_id' => 'required|uuid|exists:branches,id',
                'status' => 'nullable|in:-1,0,1',
                'payment_status' => 'nullable|in:-1,0,1,2',
                'delivery_date' => 'nullable|date',
                'start_date' => 'nullable|date',
                'end_date' => 'nullable|date',
                'page' => 'nullable|integer|min:1',
                'per_page' => 'nullable|integer|min:1',
                'search' => 'nullable|string',
            ]
        );

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $status = $request->input('status');
        $paymentStatus = $request->input('payment_status');
        $deliveryDate = $request->input('delivery_date');
        $startDate = $request->input('start_date') ?: now()->startOfMonth();
        $endDate = $request->input('end_date') ?: now()->endOfMonth();
        $page = $request->input('page', 1);
        $perPage = $request->input('per_page', 10);
        $search = $request->input('search', '');

        $ordersQuery = Order::with(['employee:id,employee_code,name'])
            ->select([
                'id', 'branch_id', 'employee_id', 'title', 'description', 'remarks',
                'delivery_date', 'delivery_time', 'customer_name', 'customer_email',
                'customer_mobile', 'total_amount', 'advance_amount', 'payment_status',
                'status', 'delivered_at', 'delivered_by', 'created_by', 'created_at', 'updated_at'
            ])
            ->where('branch_id', $branchId)
            ->when($status !== null, function ($query) use ($status) {
                return $query->where('status', $status);
            })
            ->when($paymentStatus !== null, function ($query) use ($paymentStatus) {
                return $query->where('payment_status', $paymentStatus);
            })
            ->when($deliveryDate, function ($query) use ($deliveryDate) {
                return $query->where('delivery_date', $deliveryDate);
            })
            ->whereBetween('delivery_date', [$startDate, $endDate]);

        if ($search) {
            $ordersQuery->where(function ($query) use ($search) {
                $query->where('title', 'like', '%' . $search . '%')
                    ->orWhere('description', 'like', '%' . $search . '%')
                    ->orWhere('remarks', 'like', '%' . $search . '%')
                    ->orWhere('customer_name', 'like', '%' . $search . '%')
                    ->orWhere('customer_email', 'like', '%' . $search . '%')
                    ->orWhere('customer_mobile', 'like', '%' . $search . '%');
            });
        }

        $orders = $ordersQuery->orderBy('delivery_date', 'asc')
            ->paginate($perPage, ['*'], 'page', $page);

        if ($orders->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No orders found for the given date range'
            ]);
        }

        $ordersResource = OrderSummaryResource::collection($orders);

        return response()->json([
            'success' => true,
            'message' => 'Orders fetched successfully',
            'orders' => $ordersResource,
            'pagination' => [
                'current_page' => $orders->currentPage(),
                'last_page' => $orders->lastPage(),
                'per_page' => $orders->perPage(),
                'total' => $orders->total(),
            ]
        ]);
    }
}
