<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\Branch;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;

class EmployeeController extends Controller
{
    // Add a new employee
    public function createEmployee(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'employee_code' => 'required|string|unique:employees,employee_code',
            'name' => 'required|string',
            'mobile' => 'required|digits:10',
            'designation' => 'required|in:sales_manager,salesman',
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
            'designation' => $request->designation,
            'status' => DEFAULT_EMPLOYEE_STATUS,
            'branch_id' => $request->branch_id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Employee created successfully'
        ], 201);
    }

    public function updateEmployee(Request $request, $employee_id)
    {
        $request->validate([
            'name' => 'sometimes|string',
            'mobile' => 'sometimes|digits:10',
            'designation' => 'sometimes|in:sales_manager,salesman',
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


    // List all employees of a branch
    public function getEmployees($branch_id)
    {
        $branch = Branch::find($branch_id);

        if (!$branch) {
            return response()->json(['error' => 'Branch not found'], 404);
        }

        $employees = $branch->employees()->get(['employee_code', 'name', 'designation'])
            ->map(function ($employee) use ($branch) {
                return [
                    'employee_code' => $employee->employee_code,
                    'name' => $employee->name,
                    'designation' => $employee->designation,
                    'branch_code' => $branch->code,
                ];
            });

        return response()->json($employees);
    }
}
