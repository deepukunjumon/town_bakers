<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class BranchController extends Controller
{
    public function createBranch(Request $request)
    {
        $request->validate([
            'code' => 'required|string|unique:branches,code',
            'name' => 'required|string',
        ]);

        $branch = Branch::create([
            'code' => $request->code,
            'name' => $request->name,
            'password' => Hash::make(DEFAULT_PASSWORD)
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Branch created successfully',
        ], 201);
    }

    public function updateBranch(Request $request, $branch_id)
    {
        $request->validate([
            'name' => 'string',
        ]);

        $branch = Branch::findOrFail($branch_id);

        $branch->update([
            'name' => $request->name,
        ]);

        return response()->json(['success' => true, 'message' => 'Branch details updated successfully'], 200);
    }

    public function getBranches()
    {
        return response()->json(Branch::all());
    }
}
