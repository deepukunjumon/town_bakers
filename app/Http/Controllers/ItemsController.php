<?php

namespace App\Http\Controllers;

use App\Models\Items;
use Illuminate\Http\Request;

class ItemsController extends Controller
{
    public function createItem(Request $request)
    {
        $request->validate([
            'name' => 'required|string|unique:items,name',
            'description' => 'nullable|string',
            'category' => 'required|string',
            'status' => 'in:-1,0,1'
        ]);

        $item = Items::create([
            'name' => $request->name,
            'description' => $request->description ?? null,
            'category' => $request->category,
            'status' => DEFAULT_ITEM_STATUS
        ]);

        return response()->json([
            'message' => 'Item created successfully',
            'item' => $item
        ], 201);
    }
}
