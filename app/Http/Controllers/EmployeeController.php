<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\Branch;
use App\Models\Designations;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;

class EmployeeController extends Controller
{
    // Add a new employee
    public function createEmployeeForAdmin(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'employee_code' => 'required|string|unique:employees,employee_code',
            'name' => 'required|string',
            'mobile' => 'required|digits:10',
            'designation_id' => 'required|exists:designations,id',
            'branch_id' => 'required|exists:branches,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $employee = Employee::create([
            'employee_code' => $request->employee_code,
            'name' => $request->name,
            'mobile' => $request->mobile,
            'designation_id' => $request->designation_id,
            'status' => DEFAULT_EMPLOYEE_STATUS,
            'branch_id' => $request->branch_id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Employee created successfully'
        ], 201);
    }

    public function createEmployeeForBranch(Request $request)
    {
        $branchId = Auth::user()->branch_id;

        $validator = Validator::make($request->all(), [
            'employee_code' => 'required|string|unique:employees,employee_code',
            'name' => 'required|string',
            'mobile' => 'required|digits:10',
            'designation_id' => 'required|exists:designations,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $employee = Employee::create([
            'employee_code' => $request->employee_code,
            'name' => $request->name,
            'mobile' => $request->mobile,
            'designation_id' => $request->designation_id,
            'status' => DEFAULT_EMPLOYEE_STATUS,
            'branch_id' => $branchId,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Employee created successfully'
        ], 201);
    }

    public function importEmployees(Request $request)
    {
        $fileValidate = Validator::make($request->all(), [
            'file' => 'required|file|mimes:xlsx,csv',
        ]);
    
        if ($fileValidate->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $fileValidate->errors(),
            ], 422);
        }
    
        $file = $request->file('file');
        $data = Excel::toArray([], $file);
        $rows = $data[0];
    
        $errors = [];
        $imported = 0;
    
        foreach ($rows as $index => $row) {
            if ($index === 0 || empty($row[0])) continue;
    
            $employeeCode = trim($row[0]);
            $name = trim($row[1]);
            $mobile = trim($row[2]);
            $designationName = trim($row[3]);
            $branchName = trim($row[4]);
    
            $designation = Designations::where('designation', $designationName)->first();
            $branch = Branch::where('branch_name', $branchName)->first();
    
            $validator = Validator::make([
                'employee_code' => $employeeCode,
                'name' => $name,
                'mobile' => $mobile,
                'designation' => $designation,
                'branch' => $branch,
            ], [
                'employee_code' => 'required|string|unique:employees,employee_code',
                'name' => 'required|string',
                'mobile' => 'required|digits:10',
                'designation' => 'required',
                'branch' => 'required',
            ]);
    
            if ($validator->fails()) {
                $errors[] = [
                    'row' => $index + 1,
                    'errors' => $validator->errors(),
                ];
                continue;
            }
    
            Employee::create([
                'employee_code' => $employeeCode,
                'name' => $name,
                'mobile' => $mobile,
                'status' => DEFAULT_STATUSES['active'],
                'designation_id' => $designation->id,
                'branch_id' => $branch->id,
            ]);
    
            $imported++;
        }
    
        return response()->json([
            'success' => true,
            'message' => "$imported employees imported successfully.",
            'errors' => $errors,
        ]);
    }
    

    public function updateEmployee(Request $request, $employee_id)
    {
        $request->validate([
            'name' => 'sometimes|string',
            'mobile' => 'sometimes|digits:10',
            'designation_id' => $request->designation_id,
            'status' => 'sometimes|integer|in:-1,0,1',
            'branch_id' => 'sometimes|exists:branches,id',
        ]);

        $employee = Employee::findOrFail($employee_id);

        $employee->fill($request->only([
            'name',
            'mobile',
            'designation',
            'status',
            'branch_id',
        ]));

        $employee->save();

        return response()->json([
            'success' => true,
            'message' => 'Employee details updated successfully',
        ], 200);
    }


    public function getEmployeesByBranch(Request $request, $branch_id)
    {
        $branch = Branch::find($branch_id);

        if (!$branch) {
            return response()->json(['error' => 'Branch not found'], 404);
        }

        $perPage = $request->input('per_page', 10); // Default to 10 items per page
        $page = $request->input('page', 1); // Default to the first page
        $search = $request->input('search', ''); // Default to an empty string for search

        $query = $branch->employees()->orderBy('employee_code', 'asc');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('employee_code', 'like', "%$search%")
                ->orWhere('name', 'like', "%$search%")
                ->orWhere('designation', 'like', "%$search%");
            });
        }

        $employees = $query->paginate($perPage, ['id', 'employee_code', 'name', 'designation', 'mobile']);

        $employees->getCollection()->transform(function ($employee) use ($branch) {
            return [
                'id' => $employee->id,
                'employee_code' => $employee->employee_code,
                'name' => $employee->name,
                'designation' => $employee->designation,
                'mobile' => $employee->mobile,
                'branch_code' => $branch->code,
            ];
        });

        return response()->json([
            'success' => true,
            'employees' => $employees->items(),
            'pagination' => [
                'total' => $employees->total(),
                'per_page' => $employees->perPage(),
                'current_page' => $employees->currentPage(),
                'last_page' => $employees->lastPage(),
                'from' => $employees->firstItem(),
                'to' => $employees->lastItem(),
            ],
        ]);
    }

    public function getMinimalEmployees()
    {
        $employees = Employee::where('status', DEFAULT_STATUSES['active'])
            ->get(['id', 'employee_code', 'name'])
            ->map(function ($employee) {
                return [
                    'id' => $employee->id,
                    'employee_code' => $employee->employee_code,
                    'name' => $employee->name
                ];
            });

        return response()->json([
            'success' => true,
            'employees' => $employees
        ], 200);
    }


    public function getEmployeesForAuthenticatedBranch(Request $request)
    {
        try {
            $branchId = Auth::user()->branch_id;

            if (!$branchId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Branch ID not found in token.'
                ], 400);
            }

            $branch = Branch::find($branchId);

            if (!$branch) {
                return response()->json([
                    'success' => false,
                    'message' => 'Branch not found.'
                ], 404);
            }

            $perPage = $request->input('per_page', 10);
            $page = $request->input('page', 1);
            $search = $request->input('search', '');

            $query = $branch->employees()->with('designation')->orderBy('employee_code', 'asc');

            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('employee_code', 'like', "%$search%")
                    ->orWhere('name', 'like', "%$search%")
                    ->orWhereHas('designation', function ($q) use ($search) {
                        $q->where('designation', 'like', "%$search%");
                    });
                });
            }

            $employees = $query->paginate($perPage, ['id', 'employee_code', 'name', 'designation_id', 'mobile']);

            $employees->getCollection()->transform(function ($employee) use ($branch) {
                return [
                    'id' => $employee->id,
                    'employee_code' => $employee->employee_code,
                    'name' => $employee->name,
                    'designation' => optional($employee->designation)->designation ?? 'N/A',
                    'mobile' => $employee->mobile,
                    'branch_code' => $branch->code,
                ];
            });

            return response()->json([
                'success' => true,
                'employees' => $employees->items(),
                'pagination' => [
                    'total' => $employees->total(),
                    'per_page' => $employees->perPage(),
                    'current_page' => $employees->currentPage(),
                    'last_page' => $employees->lastPage(),
                    'from' => $employees->firstItem(),
                    'to' => $employees->lastItem(),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve employees.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
