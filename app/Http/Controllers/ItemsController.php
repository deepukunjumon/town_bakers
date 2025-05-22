<?php

namespace App\Http\Controllers;

use App\Models\Items;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

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

        $items = $query->paginate($perPage, ['id', 'name', 'status'], 'page', $page);

        $transformed = $items->getCollection()->transform(function ($item) {
            return [
                'id' => $item->id,
                'name' => $item->name,
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

    public function getMinimalActiveItems(): JsonResponse
    {
        $items = Items::where('status', DEFAULT_STATUSES['active'])
            ->get(['id', 'name']);

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
