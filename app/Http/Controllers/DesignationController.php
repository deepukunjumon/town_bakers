<?php

namespace App\Http\Controllers;

use App\Models\Designations;
use Illuminate\Http\JsonResponse;
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


    public function getActiveDesignations()
    {
        $designations = Designations::where('status', DEFAULT_STATUSES['active'])
            ->get(['id', 'designation', 'status'])
            ->map(function ($designation) {
                return [
                    'id' => $designation->id,
                    'designation' => $designation->designation,
                    'status' => $designation->status,
                ];
            });

        return response()->json([
            'success' => true,
            'designations' => $designations
        ], 200);
    }

    /**
     * Get all designations
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function getAllDesignations(Request $request): JsonResponse
    {
        $query = Designations::query();

        // Default values for pagination
        $perPage = $request->input('per_page', 10);
        $page = $request->input('page', 1);

        // Filtering by status if the parameter exists
        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }

        // Searching designations by name if the search term exists
        if ($request->filled('q')) {
            $search = $request->input('q');
            $query->where('designation', 'like', "%{$search}%");
        }

        // Paginate results
        $designations = $query->paginate($perPage, ['id', 'designation', 'status'], 'page', $page);

        // Transform the collection before returning
        $designations->getCollection()->transform(function ($item) {
            return [
                'id' => $item->id,
                'designation' => $item->designation,
                'status' => $item->status,
            ];
        });

        // Return the response with designations and pagination
        return response()->json([
            'success' => true,
            'designations' => $designations->items(),
            'pagination' => [
                'total' => $designations->total(),
                'per_page' => $designations->perPage(),
                'current_page' => $designations->currentPage(),
                'last_page' => $designations->lastPage(),
                'from' => $designations->firstItem(),
                'to' => $designations->lastItem(),
            ],
        ], 200);
    }

    /**
     * Update Designation Status
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function updateDesignationStatus(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required|exists:designations,id',
            'status' => 'required|in:-1,0,1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $designation = Designations::find($request->id);

        if ($designation->status == $request->status) {
            return response()->json([
                'success' => false,
                'error' => 'Status is already set to the given value',
            ], 422);
        }

        $designation->status = $request->status;

        if (!$designation->save()) {
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
