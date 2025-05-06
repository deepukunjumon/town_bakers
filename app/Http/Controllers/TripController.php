<?php

namespace App\Http\Controllers;

use App\Models\Trip;
use App\Models\StockItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Exports\ItemQuantityExport;
use Maatwebsite\Excel\Facades\Excel;
use App\Models\Branch;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;


class TripController extends Controller
{
    public function addStock(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'branch_id' => 'required|exists:branches,id',
            'employee_id' => 'required|exists:employees,id',
            'items' => 'required|array|min:1',
            'items.*.item_id' => 'required|exists:items,id',
            'items.*.quantity' => 'required|numeric|min:0.01',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }
        // Create trip
        $trip = Trip::create([
            'branch_id' => $request->branch_id,
            'employee_id' => $request->employee_id,
        ]);

        // Create stock items
        foreach ($request->items as $stock) {
            StockItem::create([
                'trip_id' => $trip->id,
                'item_id' => $stock['item_id'],
                'quantity' => $stock['quantity'],
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Stock added successfully',
            'trip_id' => $trip->id
        ]);
    }

    public function getTripDetails($trip_id)
    {
        $trip = Trip::with('stockItems.item')->findOrFail($trip_id);

        return response()->json($trip);
    }

    public function getItemsByDate(Request $request)
    {
        $request->validate([
            'date' => 'required|date',
        ]);

        $date = $request->date;

        // Get branch_id from authenticated user (token)
        $branchId = Auth::user()->branch_id;

        // Query to get stock items for the given date, filtered by branch_id
        $items = DB::table('stock_items')
            ->join('items', 'stock_items.item_id', '=', 'items.id')
            ->join('trips', 'stock_items.trip_id', '=', 'trips.id')
            ->whereDate('trips.date', $date)
            ->where('trips.branch_id', $branchId)  // Add condition for the branch_id
            ->select(
                'items.name as item_name',
                DB::raw('SUM(stock_items.quantity) as total_quantity')
            )
            ->groupBy('items.name')
            ->get()
            ->toArray();

        // Return response with stock summary for the date
        return response()->json([
            'success' => true,
            'message' => 'Fetched stock summary of ' . $branchId . ' for ' . $date,
            'date' => $date,
            'data' => $items
        ]);
    }


    public function branchwiseStocksSummary(Request $request)
    {
        // Validate the date input
        $request->validate([
            'date' => 'required|date',
        ]);

        $date = $request->date;

        $user = Auth::user();
        $branchId = $user->branch_id;
        $isAdmin = $user->is_admin;

        $query = DB::table('stock_items')
            ->join('items', 'stock_items.item_id', '=', 'items.id')
            ->join('trips', 'stock_items.trip_id', '=', 'trips.id')
            ->whereDate('trips.date', $date)
            ->select(
                'trips.branch_id',
                'stock_items.item_id',
                DB::raw('SUM(stock_items.quantity) as total_quantity')
            )
            ->groupBy('trips.branch_id', 'stock_items.item_id');

        if (!$isAdmin) {
            $query->where('trips.branch_id', $branchId);
        }

        $items = $query->get();

        $formattedData = [];
        foreach ($items as $item) {
            if (!isset($formattedData[$item->branch_id])) {
                $formattedData[$item->branch_id] = [
                    'branch_id' => $item->branch_id,
                    'items' => []
                ];
            }

            $formattedData[$item->branch_id]['items'][] = [
                'item_id' => $item->item_id,
                'quantity' => $item->total_quantity
            ];
        }

        return response()->json([
            'success' => true,
            'message' => 'Fetched stock summary for ' . $date,
            'date' => $date,
            'data' => array_values($formattedData)
        ]);
    }
    public function exportItemsByDate(Request $request, $branch_id)
    {
        $request->validate([
            'date' => 'required|date',
        ]);

        $branch = Branch::findOrFail($branch_id);

        $items = DB::table('stock_items')
            ->join('items', 'stock_items.item_id', '=', 'items.id')
            ->join('trips', 'stock_items.trip_id', '=', 'trips.id')
            ->where('trips.branch_id', $branch_id)
            ->whereDate('trips.date', $request->date)
            ->select(
                'items.name as item_name',
                DB::raw('SUM(stock_items.quantity) as total_quantity')
            )
            ->groupBy('items.name')
            ->get();

        return Excel::download(
            new ItemQuantityExport($items, $branch->name, $branch->code, $request->date),
            'item_report_' . $branch . '_' . $request->date . '.xlsx'
        );
    }
}
