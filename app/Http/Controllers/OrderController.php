<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Order;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use App\Http\Resources\OrderSummaryResource;

class OrderController extends Controller
{
    public function getOrdersForBranch(Request $request)
    {
        $user = Auth::user();
        $branchId = $user->branch_id;

        $orders = Order::with(['employee'])
            ->where('branch_id', $branchId)
            ->orderBy('delivery_date', 'desc')
            ->get();

        $orders = OrderSummaryResource::collection($orders);

        if (!$orders){
            return response()->json([
                'succes' => false,
                'error' => 'Could not fetch order details'
            ]);
        }
        
        return response()->json(['succes' => true, 'order' => $orders]);
    }

    public function getOrderDetails(Request $request, $id)
    {
        $user = Auth::user();
        $branchId = $user->branch_id;

        $order = Order::with(['employee'])
            ->where('branch_id', $branchId)
            ->where('id', $id)
            ->firstOrFail();
        
        $order_details = new OrderSummaryResource($order);

        if (!$order_details){
            return response()->json([
                'succes' => false,
                'error' => 'Could not fetch order details'
            ]);
        }
        
        return response()->json(['succes' => true, 'order' => $order_details]);
    }

    public function getOrderDetailsByAdmin(Request $request, $id)
    {
        $order = Order::with(['employee', 'branch', 'creator'])
            ->where('id', $id)
            ->firstOrFail();

        return response()->json(['order' => $order]);
    }

    public function createOrder(Request $request)
    {
        $user = Auth::user();

        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'remarks' => 'nullable|string',
            'delivery_date' => 'required|date',
            'employee_id' => 'nullable|uuid|exists:employees,id',
            'total_amount' => 'required|numeric|min:0',
            'advance_amount' => 'nullable|numeric|min:0',
            'payment_status' => 'required|in:-1,0,1,2',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $order = Order::create([
            'branch_id' => $user->branch_id,
            'employee_id' => $request->employee_id,
            'title' => $request->title,
            'description' => $request->description,
            'remarks' => $request->remarks,
            'delivery_date' => $request->delivery_date,
            'total_amount' => $request->total_amount,
            'advance_amount' => $request->advance_amount,
            'payment_status' => $request->payment_status,
            'status' => 0,
            'created_by' => $user->id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Order created', 
            'order_details' => $order
        ]);
    }

    public function updateOrderStatus(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|in:-1,0,1'
        ]);
        

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $order = Order::findOrFail($id);

        $user = Auth::user();
        if ($user->branch_id !== $order->branch_id && !$user->is_admin) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $order->status = $request->status;

        if ($request->status == 1) {
            if ($order->payment_status == 0) {
                $order->payment_status = 2;
            }
            if ($order->payment_status == 1) {
                $order->payment_status = 2;
            }
        } elseif ($request->status == -1) {
            $order->payment_status = -1;
        }

        $order->save();

        return response()->json(['success' => true, 'message' => 'Order status updated']);
    }


    public function adminIndex(Request $request)
    {
        $query = Order::with(['branch', 'employee', 'creator'])->orderBy('delivery_date', 'desc');

        if ($request->has('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }

        return response()->json(['orders' => $query->get()]);
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
}
