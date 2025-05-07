<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class BranchController extends Controller
{
    public function createBranch(Request $request)
    {
        $validator = validator::make($request->all(), [
            'code' => 'required|string|unique:branches,code',
            'name' => 'required|string',
            'address' => 'required|string',
            'mobile' => 'required|digits:10',
            'phone' => 'nullable|numeric|digits_between:1,15'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        // Create the branch
        $branch = Branch::create([
            'code' => $request->code,
            'name' => $request->name,
            'address' => $request->address,
            'mobile' => $request->mobile,
            'phone' => $request->phone,
        ]);

        $username = DEFAULT_USERNAME_PREFIX . strtoupper($request->code);
        // Create a user for the branch
        $user = User::create([
            'username' => $username,
            'password' => Hash::make(DEFAULT_PASSWORD),
            'branch_id' => $branch->id,
            'is_admin' => false
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Branch created successfully',
            'branch' => $branch,
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



    public function getMinimalBranches()
    {
        $branches = Branch::get(['id', 'code', 'name'])
            ->map(function ($branch) {
                return [
                    'id' => $branch->id,
                    'code' => $branch->code,
                    'name' => $branch->name
                ];
            });

        return response()->json([
            'success' => true,
            'branches' => $branches
        ], 200);
    }
}
