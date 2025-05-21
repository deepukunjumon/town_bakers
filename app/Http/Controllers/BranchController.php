<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Employee;
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
                'role' => ROLES['branch']
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
     * Get branch details with ID
     * 
     * @param string $request
     * @return JsonResponse
     */
    public function getBranchDetails(string $branch_id): JsonResponse
    {
        $validator = Validator::make(
            ['branch_id' => $branch_id],
            ['branch_id' => 'required']
        );

        if ($validator->fails()) {
            return response()->json(['success' => false, 'error' => 'Branch ID not provided or invalid'], 422);
        }

        $branch = Branch::select('id', 'code', 'name', 'address', 'mobile', 'email', 'status')
            ->find($branch_id);

        if (!$branch) {
            return response()->json(['success' => false, 'error' => 'Branch not found'], 404);
        }

        $branch['active_employees_count'] = Employee::where('branch_id', $branch_id)
            ->where('status', DEFAULT_STATUSES['active'])
            ->count();

        return response()->json([
            'success' => true,
            'branch_details' => $branch,
        ], 200);
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
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1',
            'sortKey' => 'nullable|string|in:created_at,code,name',
            'sortDirection' => 'nullable|string|in:asc,desc',
            'q' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $page = $request->input('page', 1);
        $perPage = $request->input('per_page', 10);
        $sortKey = $request->input('sortKey', 'created_at');
        $sortDirection = $request->input('sortDirection', 'desc');
        $searchQuery = $request->input('q', '');

        $query = Branch::query();

        if ($searchQuery) {
            $query->where(function ($query) use ($searchQuery) {
                $query->where('code', 'like', '%' . $searchQuery . '%')
                    ->orWhere('name', 'like', '%' . $searchQuery . '%')
                    ->orWhere('address', 'like', '%' . $searchQuery . '%');
            });
        }

        $query->orderBy($sortKey, $sortDirection);

        $branches = $query->paginate($perPage, ['*'], 'page', $page);

        $branchesData = $branches->map(function ($branch) {
            return [
                'id' => $branch->id,
                'code' => $branch->code,
                'name' => $branch->name,
                'address' => $branch->address,
                'mobile' => $branch->mobile,
                'email' => $branch->email,
                'phone' => $branch->phone,
                'status' => $branch->status
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
                'status' => $branch->status
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
