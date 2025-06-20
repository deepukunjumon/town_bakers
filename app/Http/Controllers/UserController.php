<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\Response;
use \App\Http\Controllers\EmployeeController;
use \App\Http\Controllers\BranchController;

class UserController extends Controller
{

    /**
     * Create user
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function createUser(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'username' => 'required_if:role,admin,branch|string|unique:users,username',
            'name' => 'required|string',
            'mobile' => 'required|digits:10',
            'email' => 'nullable|email|unique:users,email',
            'role' => 'required|in:admin,branch,employee',
            'status' => 'in:-1,0,1'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $additional_info = null;

        // Handle admin
        if ($request->role == ROLES['admin']) {
            $user = User::create([
                'username' => $request->username,
                'name' => $request->name,
                'mobile' => $request->mobile,
                'email' => $request->email,
                'password' => DEFAULT_PASSWORD,
                'role' => ROLES['admin'],
                'status' => DEFAULT_STATUSES['active']
            ]);

            if ($user) {
                $additional_info = "Credentials for the user are:\n" .
                    "Username: {$request->username}\n" .
                    "Password: " . DEFAULT_PASSWORD;

                return response()->json([
                    'success' => true,
                    'message' => 'Admin User Created Successfully',
                    'additional_info' => $additional_info
                ], 201);
            }

            return response()->json([
                'success' => false,
                'message' => 'Failed to create admin user.'
            ], 500);
        }

        // Handle branch
        if ($request->role == ROLES['branch']) {
            $branchValidator = Validator::make($request->all(), [
                'code' => 'required|string|unique:branches,code',
                'name' => 'required|string',
                'address' => 'required|string',
                'mobile' => 'required|digits:10',
                'email' => 'required|email|unique:branches,email'
            ]);

            if ($branchValidator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $branchValidator->errors()
                ], 422);
            }

            $branchController = new BranchController();
            $response = $branchController->createBranch($request);

            if ($response->getStatusCode() === 201) {
                $additional_info = "Credentials for the branch user are:\n" .
                    "Username: {$request->username}\n" .
                    "Password: " . DEFAULT_PASSWORD;
            }

            return $response;
        }

        // Handle employee
        if ($request->role == ROLES['employee']) {
            $employeeValidator = Validator::make($request->all(), [
                'employee_code' => 'required|string|unique:employees,employee_code',
                'name' => 'required|string',
                'mobile' => 'required|digits:10',
                'designation_id' => 'required|exists:designations,id',
                'branch_id' => 'required|exists:branches,id'
            ]);

            if ($employeeValidator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $employeeValidator->errors()
                ], 422);
            }

            $employeeController = new EmployeeController();
            return $employeeController->createEmployeeForAdmin($request);
        }

        return response()->json([
            'success' => true,
            'message' => 'User Created Successfully',
            'additional_info' => $additional_info
        ], 201);
    }

    /**
     * Get list of users
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function getAllUsers(Request $request): JsonResponse
    {
        $query = User::query();

        $perPage = $request->input('per_page', 10);
        $page = $request->input('page', 1);

        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->has('role')) {
            $query->where('role', $request->input('role'));
        }

        if ($request->filled('q')) {
            $search = $request->input('q');
            $searchableColumns = ['username', 'name', 'mobile', 'email'];

            $query->where(function ($query) use ($search, $searchableColumns) {
                foreach ($searchableColumns as $column) {
                    $query->orWhere($column, 'like', "%{$search}%");
                }
            });
        }


        $query->orderBy('name', 'asc');

        $users = $query->paginate($perPage, ['id', 'username', 'name', 'mobile', 'email', 'role', 'status'], 'page', $page);

        $transformed = $users->getCollection()->transform(function ($user) {
            return [
                'id' => $user->id,
                'username' => $user->username,
                'name' => $user->name,
                'mobile' => $user->mobile,
                'email' => $user->email,
                'role_label' => ucwords(str_replace('_', ' ', $user->role)),
                'role' => $user->role,
                'status' => $user->status,
            ];
        });

        $users->setCollection($transformed);

        return response()->json([
            'success' => true,
            'users' => $users->items(),
            'pagination' => [
                'total' => $users->total(),
                'per_page' => $users->perPage(),
                'current_page' => $users->currentPage(),
                'last_page' => $users->lastPage(),
                'from' => $users->firstItem(),
                'to' => $users->lastItem(),
            ],
        ], 200);
    }

    /**
     * List of user roles for login
     * 
     * @return JsonResponse
     */
    public function getUserRoles(): JsonResponse
    {
        $roles = array_values(ROLES);
        $formattedRoles = array_map(function ($role) {
            return [
                'value' => $role,
                'label' => ucwords(str_replace('_', ' ', $role)),
            ];
        }, $roles);

        return response()->json([
            'success' => true,
            'roles' => $formattedRoles
        ]);
    }

    /**
     * Update User Status
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function updateUserStatus(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required|exists:users,id',
            'status' => 'required|in:-1,0,1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = User::find($request->id);

        if ($user->status == $request->status) {
            return response()->json([
                'success' => false,
                'error' => 'Status is already set to the given value',
            ], 422);
        }

        $user->status = $request->status;

        if (!$user->save()) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to update user status',
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'Updated',
        ], 200);
    }

    /**
     * Update user details.
     *
     * @param Request $request
     * @param string $user_id
     * @return JsonResponse
     */
    public function updateUserDetails(Request $request, $user_id): JsonResponse
    {
        if (!$user_id) {
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
            'mobile' => 'sometimes|unique:users,mobile|digits:10',
            'email' => 'sometimes|unique:users,email|email',
            'role' => 'sometimes|in:' . implode(',', array_values(ROLES))
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = User::findOrFail($user_id);

        if ($user['status'] != DEFAULT_STATUSES['active']) {
            return response()->json([
                'success' => false,
                'message' => 'User is not active',
            ], 400);
        }

        $user->fill($request->only([
            'name',
            'mobile',
            'email',
            'role'
        ]));

        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Updated Successfully',
        ], 200);
    }

    /**
     * Get the profile of the authenticated user.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getProfileDetails(Request $request): JsonResponse
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        }

        $userDetails = [
            'id' => $user->id,
            'username' => $user->username,
            'name' => $user->name,
            'mobile' => $user->mobile,
            'email' => $user->email,
            'role' => ucwords(str_replace('_', ' ', $user->role)),
            'user_since' => $user->created_at ? $user->created_at->format('Y-m-d') : ""
        ];

        if ($user->role == ROLES['branch']) {

            $branch = DB::table('branches')->where('id', $user->branch_id)->first();

            if ($branch) {
                $userDetails['branch'] = [
                    'id' => $branch->id,
                    'name' => $branch->name,
                    'code' => $branch->code ?? null,
                    'address' => $branch->address ?? null,
                ];
            } else {
                $userDetails['branch'] = null;
            }
        }

        return response()->json([
            'success' => true,
            'message' => "Details fetched",
            'user_details' => $userDetails
        ]);
    }

    /**
     * Update profile details
     * 
     * @param Request $request
     * @return JsonResponse|mixed
     */
    public function updateProfileDetails(Request $request): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $rules = [
            'username' => 'sometimes|string|max:255',
            'name' => 'sometimes|string|max:255',
            'mobile' => 'sometimes|string|max:20',
            'email' => 'sometimes|email|max:255',
        ];

        if ($user->role == ROLES['branch']) {
            $rules['branch.name'] = 'sometimes|string|max:255';
            $rules['branch.code'] = 'sometimes|nullable|string|max:50';
            $rules['branch.address'] = 'sometimes|nullable|string|max:500';
        }

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $input = $request->only(['username', 'name', 'mobile', 'email']);
        $branchData = $request->input('branch', []);

        $userUpdates = array_filter($input, fn($val) => $val !== null);

        $branchUpdates = [];
        if ($user->role == ROLES['branch']) {
            foreach (['name', 'code', 'address'] as $field) {
                if (isset($branchData[$field]) && $branchData[$field] !== null) {
                    $branchUpdates[$field] = $branchData[$field];
                }
            }
        }

        if (empty($userUpdates) && empty($branchUpdates)) {
            return response()->json([
                'success' => true,
                'message' => 'No changes detected, profile is up to date.'
            ]);
        }

        DB::beginTransaction();

        try {
            if (!empty($userUpdates)) {
                foreach ($userUpdates as $key => $value) {
                    $user->$key = $value;
                }
                $user->save();
            }

            if (!empty($branchUpdates)) {
                $branchUpdates['updated_at'] = now();
                DB::table('branches')
                    ->where('id', $user->branch_id)
                    ->update($branchUpdates);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Profile updated successfully'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to update profile',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
