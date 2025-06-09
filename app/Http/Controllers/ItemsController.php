<?php

namespace App\Http\Controllers;

use App\Models\Items;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ItemsController extends Controller
{
    /**
     * Create item
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function createItem(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|unique:items,name',
            'description' => 'nullable|string',
            'category' => 'required|string',
            'status' => 'in:-1,0,1'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $item = Items::create([
            'name' => $request->name,
            'description' => $request->description ?? null,
            'category' => $request->category,
            'status' => DEFAULT_ITEM_STATUS
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Item created successfully'
        ], 201);
    }

    /**
     * Update item details
     * 
     * @param Request $request
     * @param $item_id
     * @return JsonResponse
     */
    public function updateItemDetails(Request $request, $item_id): JsonResponse
    {
        if (!$item_id) {
            return response()->json([
                'success' => false,
                'message' => 'Missing mandatory parameter',
            ], 422);
        }

        if (!$request->all()) {
            return response()->json([
                'success' => false,
                'message' => 'Request data is empty',
            ], 422);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string',
            'category' => 'sometimes|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $item = Items::findOrFail($item_id);

        if ($item['status'] != DEFAULT_STATUSES['active']) {
            return response()->json([
                'success' => false,
                'message' => 'item is not active',
            ], 400);
        }

        $item->fill($request->only([
            'name',
            'category'
        ]));

        $item->save();

        return response()->json([
            'success' => true,
            'message' => 'Updated Successfully',
        ], 200);
    }

    /**
     * Import items from an Excel file.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function importItems(Request $request): JsonResponse
    {
        $fileValidate = Validator::make($request->all(), [
            'file' => 'required|file|mimes:xlsx,csv',
        ]);

        if ($fileValidate->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $fileValidate->errors(),
            ], 422);
        }

        $file = $request->file('file');
        $spreadsheet = IOFactory::load($file->getPathname());
        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray();

        $errors = [];
        $imported = 0;
        $importedItems = [];

        // Start bulk operation to prevent individual audit logs
        Items::startBulkOperation();

        foreach ($rows as $index => $row) {
            if ($index === 0 || empty($row[0])) continue;

            $name = trim($row[0]);
            $category = trim($row[1]);
            $description = trim($row[2]);

            $validator = Validator::make([
                'name' => $name,
                'category' => $category,
                'description' => $description ? $description : null,
            ], [
                'name' => 'required|string',
                'category' => 'required|string',
                'description' => 'nullable|string',
            ]);

            if ($validator->fails()) {
                $errors[] = [
                    'row' => $index + 1,
                    'errors' => $validator->errors(),
                ];
                continue;
            }

            try {
                $item = Items::create([
                    'name' => $name,
                    'description' => $description ?: null,
                    'category' => $category,
                    'status' => DEFAULT_ITEM_STATUS
                ]);
                $imported++;
                $importedItems[] = $item->id;
            } catch (\Exception $e) {
                $errors[] = [
                    'row' => $index + 1,
                    'errors' => ['database' => 'Failed to create item: ' . $e->getMessage()],
                ];
            }
        }

        // End bulk operation
        Items::endBulkOperation();

        // Create a single audit log entry for the import
        if ($imported > 0) {
            AuditLog::create([
                'id' => (string) Str::uuid(),
                'action' => AUDITLOG_ACTIONS['IMPORT'],
                'table' => 'items',
                'record_id' => $importedItems[0],
                'description' => "Imported {$imported} items from file: {$file->getClientOriginalName()}",
                'comments' => json_encode([
                    'total_imported' => $imported,
                    'imported_ids' => $importedItems,
                    'file_name' => $file->getClientOriginalName(),
                    'errors' => $errors
                ]),
                'performed_by' => Auth::id()
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => "$imported items imported successfully.",
            'errors' => $errors,
        ]);
    }

    /**
     * Get list of items
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function getAllItems(Request $request): JsonResponse
    {
        $query = Items::query();

        $perPage = $request->input('per_page', 10);
        $page = $request->input('page', 1);

        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('q')) {
            $search = $request->input('q');
            $query->where('name', 'like', "%{$search}%");
        }

        $query->orderBy('name', 'asc');

        $items = $query->paginate($perPage, ['id', 'name', 'category', 'status'], 'page', $page);

        $transformed = $items->getCollection()->transform(function ($item) {
            return [
                'id' => $item->id,
                'name' => $item->name,
                'category' => $item->category,
                'status' => $item->status,
            ];
        });

        $items->setCollection($transformed);

        return response()->json([
            'success' => true,
            'items' => $items->items(),
            'pagination' => [
                'total' => $items->total(),
                'per_page' => $items->perPage(),
                'current_page' => $items->currentPage(),
                'last_page' => $items->lastPage(),
                'from' => $items->firstItem(),
                'to' => $items->lastItem(),
            ],
        ], 200);
    }

    /**
     * Minimal list of active items
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function getMinimalActiveItems(Request $request): JsonResponse
    {
        $query = Items::where('status', DEFAULT_STATUSES['active'])
            ->orderby('name', 'asc');

        if ($request->has('q') && !empty($request->q)) {
            $searchTerm = $request->q;
            $query->where(function ($q) use ($searchTerm) {
                $q->where('name', 'like', "%{$searchTerm}%");
            });
        }

        $items = $query->get(['id', 'name']);

        return response()->json([
            'success' => true,
            'items' => $items,
        ], 200);
    }


    /**
     * Update Item Status
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function updateItemStatus(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required|exists:items,id',
            'status' => 'required|in:-1,0,1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $item = Items::find($request->id);

        if ($item->status == $request->status) {
            return response()->json([
                'success' => false,
                'error' => 'Status is already set to the given value',
            ], 422);
        }

        $item->status = $request->status;

        if (!$item->save()) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to update status',
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'Updated',
        ], 200);
    }
}
