<?php

namespace App\Http\Controllers;

use App\Models\Designations;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;


class DesignationController extends Controller
{

    public function createDesignation(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'designation' => 'required|string|unique:designations,designation',
            'status' => 'nullable|integer|in:0,1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $designation = Designations::create([
            'designation' => $request->designation,
            'status' => $request->status ?? 1,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Designation created successfully',
        ]);
    }


    public function getAllActiveDesignations()
    {
        $designations = Designations::where('status', DEFAULT_STATUSES['active'])
            ->get(['id', 'designation'])
            ->map(function ($designation) {
                return [
                    'id' => $designation->id,
                    'designation' => $designation->designation,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $designations
        ], 200);
    }
}
