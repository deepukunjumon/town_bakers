<?php

namespace App\Http\Controllers;

use App\Models\Items;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ItemsController extends Controller
{
    public function createItem(Request $request)
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
     * Get a list of items
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getItems()
    {
        $items = Items::where('status', DEFAULT_STATUSES['active'])
            ->get(['id', 'name'])
            ->map(function ($items) {
                return [
                    'id' => $items->id,
                    'name' => $items->name
                ];
            });
    
        return response()->json([
            'success' => true,
            'items' => $items
        ], 200);
    }
}