<?php

namespace App\Http\Controllers;

use App\Models\Trip;
use App\Models\StockItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Exports\ItemQuantityExport;
use Maatwebsite\Excel\Facades\Excel;
use App\Models\Branch;

class TripController extends Controller
{
    public function addStock(Request $request)
    {
        $request->validate([
            'branch_id' => 'required|exists:branches,id',
            'employee_id' => 'required|exists:employees,id',
            'items' => 'required|array|min:1',
            'items.*.item_id' => 'required|exists:items,id',
            'items.*.quantity' => 'required|numeric|min:0.01',
        ]);

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
        $branchId = auth()->payload()->get('branch_id');
    
        $items = DB::table('stock_items')
            ->join('items', 'stock_items.item_id', '=', 'items.id')
            ->join('trips', 'stock_items.trip_id', '=', 'trips.id')
            ->where('trips.branch_id', $branchId)
            ->whereDate('trips.date', $date)
            ->select(
                'items.name as item_name',
                DB::raw('SUM(stock_items.quantity) as total_quantity')
            )
            ->groupBy('items.name')
            ->get()
            ->toArray();
    
        return response()->json([
            'success' => true,
            'message' => 'Fetched stock summary for ' . $date,
            'date' => $date,
            'branch_id' => $branchId,
            'data' => $items
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
