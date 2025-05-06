<?php

namespace App\Http\Controllers;

use App\Models\Stock;
use App\Models\Branch;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class StockController extends Controller
{
    public function addStock(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'branch_id' => 'required|exists:branches,id',
            'employee_id' => 'required|exists:employees,id',
            'item_id' => 'required|exists:items,id',
            'quantity' => 'required|numeric|min:0.01',
            'trip_code' => 'nullable|string',
            'date' => 'nullable|date'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $stock = Stock::create([
            'branch_id' => $request->branch_id,
            'employee_id' => $request->employee_id,
            'item_id' => $request->item_id,
            'quantity' => $request->quantity,
            'trip_code' => $request->trip_code,
            'date' => $request->date ?? date('Y-m-d'),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Stock added successfully',
            'stock' => $stock
        ], 201);
    }


    // Get daily stock report for a branch
    public function dailyReport($branch_id)
    {
        $stocks = Stock::where('branch_id', $branch_id)
            ->whereDate('created_at', today())
            ->get();

        return response()->json($stocks);
    }
}
