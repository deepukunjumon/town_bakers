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
            'username' => 'required|string|unique:users,username',
            'mobile' => 'required|digits:10',
            'email' => 'required|email|unique:users,email',
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
            'mobile' => $user->mobile,
            'email' => $user->email,
            'role' => $user->role,
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
        $user = Auth::user();

        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $rules = [
            'username' => 'sometimes|string|max:255',
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

        $input = $request->only(['username', 'mobile', 'email']);
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
