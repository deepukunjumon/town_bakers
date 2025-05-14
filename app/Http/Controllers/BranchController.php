<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class BranchController extends Controller
{
    /**
     * Create a new branch
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function createBranch(Request $request): JsonResponse
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

        DB::beginTransaction();
        try {
            // Create the branch
            $branch = Branch::create([
                'code' => $request->code,
                'name' => $request->name,
                'address' => $request->address,
                'mobile' => $request->mobile,
                'phone' => $request->phone,
                'email' => $request->email,
                'status' => DEFAULT_STATUSES['active'],
            ]);

            $username = DEFAULT_USERNAME_PREFIX . strtoupper(str_replace(' ', '_', $request->code));
            $username = preg_replace('/[^A-Za-z0-9_]/', '', $username);

            // Create a user for the branch
            $user = User::create([
                'username' => $username,
                'mobile' => $branch->mobile,
                'email' => $branch->email,
                'password' => Hash::make(DEFAULT_PASSWORD),
                'branch_id' => $branch->id,
                'is_admin' => false
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Branch created successfully',
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Branch creation failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update branch details
     * 
     * @param Request $request
     * @param string $branch_id
     * @return JsonResponse
     */
    public function updateBranch(Request $request, $branch_id): JsonResponse
    {
        if (!$branch_id) {
            return response()->json([
                'success' => false,
                'message' => 'Branch ID is required',
            ], 422);
        }

        if (!$request->all()) {
            return response()->json([
                'success' => false,
                'message' => 'Request data is empty',
            ], 422);
        }

        DB::beginTransaction();

        try {
            $branch = Branch::findOrFail($branch_id);

            $validator = Validator::make($request->all(), [
                'code' => $branch->code !== $request->code ? 'string|unique:branches,code' : 'string',
                'name' => 'string',
                'mobile' => 'digits:10',
                'phone' => 'nullable|numeric|digits_between:1,15',
                'email' => 'nullable|email',
                'address' => 'string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors(),
                ], 422);
            }

            // Update the branch details
            $branch->update($request->only([
                'code',
                'name',
                'address',
                'mobile',
                'phone',
                'email',
            ]));

            // Update the associated user details
            $user = User::where('branch_id', $branch->id)->first();
            if ($user) {
                $user->update([
                    'username' => DEFAULT_USERNAME_PREFIX . strtoupper(str_replace(' ', '_', $branch->code)),
                    'mobile' => $branch->mobile,
                    'email' => $branch->email,
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Updated successfully',
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Update failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get list of all branches
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function getAllBranches(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'offset' => 'nullable|integer|min:0',
            'limit' => 'nullable|integer|min:1',
            'sortKey' => 'nullable|string|in:created_at,code,name,start_date,end_date',
            'sortDirection' => 'nullable|string|in:asc,desc',
            'q' => 'nullable|string',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $offset = $request->input('offset', 0);
        $limit = $request->input('limit', 10);
        $sortKey = $request->input('sortKey', 'created_at');
        $sortDirection = $request->input('sortDirection', 'desc');
        $searchQuery = $request->input('q', '');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        $branchesQuery = Branch::query();

        if ($searchQuery) {
            $branchesQuery->where(function ($query) use ($searchQuery) {
                $query->where('code', 'like', '%' . $searchQuery . '%')
                    ->orWhere('name', 'like', '%' . $searchQuery . '%')
                    ->orWhere('address', 'like', '%' . $searchQuery . '%');
            });
        }

        if ($startDate && $endDate) {
            $branchesQuery->whereBetween('created_at', [$startDate, $endDate]);
        }

        $branchesQuery->orderBy($sortKey, $sortDirection);
        $branches = $branchesQuery->skip($offset)->take($limit)->get();
        $total = $branchesQuery->count();

        return response()->json([
            'success' => true,
            'message' => 'Branches fetched successfully',
            'branches' => $branches,
            'pagination' => [
                'offset' => $offset,
                'limit' => $limit,
                'total' => $total,
            ],
        ]);
    }

    /**
     * Get minimal branch details
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function getMinimalBranches(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $page = $request->input('page', 1);
        $perPage = $request->input('per_page', 10);

        $branches = Branch::select(['id', 'code', 'name', 'status'])
            ->paginate($perPage, ['*'], 'page', $page);

        $branchesData = $branches->map(function ($branch) {
            return [
                'id' => $branch->id,
                'code' => $branch->code,
                'name' => $branch->name,
                'status' => $branch->status == DEFAULT_STATUSES['active'] ? 'Active' : ($branch->status == DEFAULT_STATUSES['inactive'] ? 'Inactive' : 'Deleted'),
            ];
        });

        return response()->json([
            'success' => true,
            'branches' => $branchesData,
            'pagination' => [
                'current_page' => $branches->currentPage(),
                'last_page' => $branches->lastPage(),
                'per_page' => $branches->perPage(),
                'total' => $branches->total(),
            ],
        ]);
    }
}
