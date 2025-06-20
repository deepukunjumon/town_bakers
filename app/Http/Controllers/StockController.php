<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\Trip;
use App\Models\StockItem;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use App\Services\StockExportService;
use App\Mail\StockSummaryMail;
use App\Services\MailService;
use Illuminate\Support\Facades\Storage;
use App\Models\Branch;

class StockController extends Controller
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
        if ($request->has('export')) {
            $request->merge([
                'export' => filter_var($request->input('export'), FILTER_VALIDATE_BOOLEAN),
            ]);
        }

        $validator = Validator::make($request->all(), [
            'date' => 'required|date',
            'export' => 'sometimes|boolean',
            'type' => 'required_if:export,true|in:excel,pdf',
            'per_page' => 'nullable|integer|min:1',
            'page' => 'nullable|integer|min:1',
            'q' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $perPage = $request->input('per_page', 10);
        $page = $request->input('page', 1);
        $date = Carbon::parse($request->date);
        $branchId = Auth::user()->branch_id;

        $branch = DB::table('branches')->where('id', $branchId)->first();
        $branch_name = $branch ? $branch->name : 'Unknown Branch';
        $branch_code = $branch ? $branch->code : 'Unknown Branch Code';
        $branch_address = $branch ? $branch->address : 'Unknown Branch Address';

        $query = DB::table('stock_items')
            ->join('items', 'stock_items.item_id', '=', 'items.id')
            ->join('trips', 'stock_items.trip_id', '=', 'trips.id')
            ->whereDate('trips.date', $date)
            ->where('trips.branch_id', $branchId)
            ->select([
                'items.name as item_name',
                DB::raw('SUM(stock_items.quantity) as total_quantity')
            ])
            ->groupBy('items.id', 'items.name')
            ->orderBy('items.name', 'asc');

        if ($request->filled('q')) {
            $searchTerm = $request->input('q');
            $query->where('items.name', 'like', '%' . $searchTerm . '%');
        }

        // Check if export is requested
        if ($request->boolean('export')) {
            $type = $request->input('type');
            $columns = ['Sl. No', 'Item Name', 'Total Quantity'];
            $exportItems = [];
            $i = 1;

            $items = $query->get();
            foreach ($items as $item) {
                $exportItems[] = [$i++, $item->item_name, $item->total_quantity];
            }

            if ($type === 'excel') {
                StockExportService::exportExcel($exportItems, $branch_name, $branch_code, $date, $columns);
            }
            if ($type === 'pdf') {
                StockExportService::exportPdf($exportItems, $branch_name, $branch_code, $branch_address, $date, $columns);
            }
        }

        $items = $query->paginate($perPage, ['*'], 'page', $page);

        // Return JSON if export is not requested
        return response()->json([
            'success' => true,
            'message' => 'Fetched stock summary',
            'date' => $date->format('d-m-Y'),
            'data' => $items->items(),
            'pagination' => [
                'current_page' => $items->currentPage(),
                'last_page' => $items->lastPage(),
                'per_page' => $items->perPage(),
                'total' => $items->total(),
            ],
        ]);
    }

    /**
     * Get branch-wise stock summary
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function branchwiseStockSummary(Request $request): JsonResponse
    {
        if ($request->has('export')) {
            $request->merge([
                'export' => filter_var($request->input('export'), FILTER_VALIDATE_BOOLEAN),
            ]);
        }

        $validator = Validator::make($request->all(), [
            'branch_id' => 'required|exists:branches,id',
            'date' => 'required|date',
            'export' => 'sometimes|boolean',
            'type' => 'required_if:export,true|in:excel,pdf',
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1',
            'q' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $page = $request->input('page', 1);
        $perPage = $request->input('per_page', 10);

        $branch_id = $request->branch_id;
        $date = Carbon::parse($request->date);
        $branch = DB::table('branches')->where('id', $branch_id)->first();
        $branch_name = $branch->name ?? 'Unknown Branch';
        $branch_code = $branch->code ?? 'Unknown Branch';
        $branch_address = $branch->address ?? 'Unknown Branch';

        $query = DB::table('stock_items')
            ->join('items', 'stock_items.item_id', '=', 'items.id')
            ->join('trips', 'stock_items.trip_id', '=', 'trips.id')
            ->whereDate('trips.date', $date)
            ->where('trips.branch_id', $branch_id);

        if ($request->filled('q')) {
            $searchTerm = $request->input('q');
            $query->where('items.name', 'like', '%' . $searchTerm . '%');
        }

        $query->select(
            'stock_items.item_id',
            'items.name as item_name',
            DB::raw('SUM(stock_items.quantity) as total_quantity')
        )
            ->groupBy('stock_items.item_id', 'items.name')
            ->orderBy('items.name', 'asc');

        if ($request->boolean('export')) {
            $type = $request->input('type');
            $columns = ['Sl. No', 'Item Name', 'Total Quantity'];
            $exportItems = [];
            $i = 1;

            $items = $query->get();
            foreach ($items as $item) {
                $exportItems[] = [$i++, $item->item_name, $item->total_quantity];
            }

            if ($type === 'excel') {
                StockExportService::exportExcel($exportItems, $branch_name, $branch_code, $date, $columns);
            }
            if ($type === 'pdf') {
                StockExportService::exportPdf($exportItems, $branch_name, $branch_code, $branch_address, $date, $columns);
            }
        }

        $items = $query->paginate($perPage, ['*'], 'page', $page);

        $formattedData = [
            [
                'branch_id' => $branch_id,
                'items' => $items->map(function ($item) {
                    return [
                        'item_id' => $item->item_id,
                        'item_name' => $item->item_name,
                        'quantity' => $item->total_quantity,
                    ];
                }),
            ]
        ];

        return response()->json([
            'success' => true,
            'message' => 'Data fetched',
            'date' => $date->format('d-m-Y'),
            'data' => $formattedData,
            'pagination' => [
                'current_page' => $items->currentPage(),
                'last_page' => $items->lastPage(),
                'per_page' => $items->perPage(),
                'total' => $items->total(),
            ],
        ]);
    }

    /**
     * Send stock summary via email
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function sendStockSummaryEmail(Request $request): JsonResponse
    {
        $branchId = Auth::user()->branch_id;
        if (!$branchId) {
            return response()->json([
                'status' => 'error',
                'message' => 'Branch not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'date' => 'sometimes|date',
            'cc' => 'sometimes|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $branch = Branch::findOrFail($branchId);
            $toArray = DB::table('users')
                ->whereIn('role', [ROLES['admin'], ROLES['super_admin']])
                ->pluck('email')
                ->toArray();
            $ccArray = array_merge((array) $request->cc, [$branch->email]);
            $ccArray = array_unique($ccArray);
            $date = $request->date ? Carbon::parse($request->date) : Carbon::parse(today());
            $format = 'pdf';

            // Get stock items data
            $query = DB::table('stock_items')
                ->join('items', 'stock_items.item_id', '=', 'items.id')
                ->join('trips', 'stock_items.trip_id', '=', 'trips.id')
                ->whereDate('trips.date', $date)
                ->where('trips.branch_id', $branch->id)
                ->select(
                    'stock_items.item_id',
                    'items.name as item_name',
                    DB::raw('SUM(stock_items.quantity) as total_quantity')
                )
                ->groupBy('stock_items.item_id', 'items.name')
                ->orderBy('items.name', 'asc')
                ->get();

            if ($query->isEmpty()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'No Data Found'
                ], 404);
            }

            $columns = ['Sl. No', 'Item Name', 'Total Quantity'];
            $exportItems = [];
            $i = 1;
            foreach ($query as $item) {
                $exportItems[] = [$i++, $item->item_name, $item->total_quantity];
            }

            $stockExportService = new StockExportService();
            $filePath = $format === 'excel'
                ? $stockExportService->exportExcel($exportItems, $branch->name, $branch->code, $date, $columns, true)
                : $stockExportService->exportPdf($exportItems, $branch->name, $branch->code, $branch->address, $date, $columns, [], true);

            if (!$filePath || !file_exists($filePath)) {
                throw new \Exception('Failed to generate export file');
            }

            $fileName = "Stock_Summary_{$branch->code}_{$date->format('d-m-Y')}." . ($format === 'excel' ? 'xlsx' : 'pdf');
            $fileName = str_replace(' ', '_', $fileName);

            // Send email
            $mailService = new MailService();
            $mailService->send([
                'type' => EMAIL_TYPES['STOCK_SUMMARY'],
                'to' => $toArray,
                'cc' => $ccArray,
                'subject' => "Stock Summary - {$branch->name} ({$date->format('d-m-Y')})",
                'body' => view('emails.stock.summary', [
                    'branchName' => $branch->name,
                    'date' => $date->format('d-m-Y')
                ])->render(),
                'branchName' => $branch->name,
                'date' => $date->format('d-m-Y'),
                'filePath' => $filePath,
                'fileName' => $fileName
            ]);

            if (file_exists($filePath)) {
                unlink($filePath);
            }

            return response()->json([
                'success' => true,
                'status' => 'success',
                'message' => 'Email Sent Successfully'
            ]);
        } catch (\Exception $e) {
            if (isset($filePath) && file_exists($filePath)) {
                unlink($filePath);
            }

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to send stock summary: ' . $e->getMessage()
            ], 500);
        }
    }
}
