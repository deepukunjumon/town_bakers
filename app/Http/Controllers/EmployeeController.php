<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\Branch;
use App\Models\Designations;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use PhpOffice\PhpSpreadsheet\IOFactory;
use App\Services\EmployeesExportService;


class EmployeeController extends Controller
{
    /**
     * Create a new employee by admin.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function createEmployeeForAdmin(Request $request): JsonResponse
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

    /**
     * Create a new employee by the authenticated branch.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function createEmployeeForBranch(Request $request): JsonResponse
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

    /**
     * Import employees from an Excel file.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function importEmployees(Request $request): JsonResponse
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
        $spreadsheet = IOFactory::load($file->getPathname());
        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray();

        $errors = [];
        $imported = 0;

        foreach ($rows as $index => $row) {
            if ($index === 0 || empty($row[0])) continue;

            $employeeCode = trim($row[0]);
            $name = trim($row[1]);
            $mobile = trim($row[2]);
            $designationName = trim($row[3]);
            $branchCode = trim($row[4]);

            $designation = Designations::where('designation', $designationName)->first();
            $branch = Branch::where('code', $branchCode)->first();

            $validator = Validator::make([
                'employee_code' => $employeeCode,
                'name' => $name,
                'mobile' => $mobile,
                'designation_id' => $designation ? $designation->id : null,
                'branch_id' => $branch ? $branch->id : null,
            ], [
                'employee_code' => 'required|string|unique:employees,employee_code',
                'name' => 'required|string',
                'mobile' => 'required|digits:10',
                'designation_id' => 'required|exists:designations,id',
                'branch_id' => 'required|exists:branches,id',
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

    /**
     * Update employee details.
     *
     * @param Request $request
     * @param string $employee_id
     * @return JsonResponse
     */
    public function updateEmployee(Request $request, $employee_id): JsonResponse
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

    /**
     * Get employees by branch ID.
     *
     * @param Request $request
     * @param string $branch_id
     * @return JsonResponse
     */
    public function getEmployeesByBranch(Request $request, $branch_id): JsonResponse
    {
        $branch = Branch::find($branch_id);

        if (!$branch) {
            return response()->json(['error' => 'Branch not found'], 404);
        }

        $perPage = $request->input('per_page', 10);
        $page = $request->input('page', 1);
        $search = $request->input('search', '');

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

    /**
     * Get minimal employee details.
     *
     * @return JsonResponse
     */
    public function getMinimalEmployees(): JsonResponse
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

    /**
     * Get employees under the authenticated branch.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getEmployeesForAuthenticatedBranch(Request $request): JsonResponse
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

            $employees = $query->paginate($perPage, ['id', 'employee_code', 'name', 'designation_id', 'mobile', 'status'], 'page', $page);

            $employees->getCollection()->transform(function ($employee) use ($branch) {
                return [
                    'id' => $employee->id,
                    'employee_code' => $employee->employee_code,
                    'name' => $employee->name,
                    'designation' => optional($employee->designation)->designation ?? 'N/A',
                    'mobile' => $employee->mobile,
                    'branch_code' => $branch->code,
                    'status' => $employee->status,
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

    /**
     * Get all employee details
     *
     * @param request $request
     * @return JsonResponse
     */
    public function getAllEmployees(Request $request): JsonResponse
    {
        if ($request->has('export')) {
            $request->merge(['export' => filter_var($request->input('export'), FILTER_VALIDATE_BOOLEAN)]);
        }
    
        if ($request->has('type')) {
            $request->merge(['type' => filter_var($request->input('type'), FILTER_SANITIZE_STRING)]);
        }
    
        $validator = Validator::make($request->all(), [
            'per_page' => 'nullable|integer|min:1',
            'page' => 'nullable|integer|min:1',
            'q' => 'nullable|string',
            'status' => 'nullable|integer|in:-1,0,1',
            'branch_code' => 'nullable|string',
        ]);
        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }
    
        $perPage = $request->input('per_page', 10);
        $page = $request->input('page', 1);
        $search = $request->input('q', '');
        $status = $request->input('status');
        $branchCode = $request->input('branch_code');
        $type = $request->input('type');
        $isExport = $request->boolean('export');
    
        $query = Employee::with(['branch', 'designation'])->orderBy('employee_code', 'asc');
    
        if (!is_null($status)) {
            $query->where('status', $status);
        }
    
        if ($branchCode) {
            $query->whereHas('branch', function ($q) use ($branchCode) {
                $q->where('code', $branchCode);
            });
        }
    
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('employee_code', 'like', "%$search%")
                  ->orWhere('name', 'like', "%$search%")
                  ->orWhereHas('branch', function ($q) use ($search) {
                      $q->where('code', 'like', "%$search%");
                  })
                  ->orWhereHas('designation', function ($q) use ($search) {
                      $q->where('designation', 'like', "%$search%");
                  });
            });
        }
    
        if ($isExport) {
            $allEmployees = $query->get();
    
            if ($allEmployees->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Nothing to export',
                ], 400);
            }
    
            $columns = ['Sl. No', 'Employee Code', 'Branch Code', 'Name', 'Mobile'];
            $exportEmployees = [];
            $i = 1;
    
            foreach ($allEmployees as $employee) {
                $exportEmployees[] = [
                    $i++,
                    $employee->employee_code,
                    optional($employee->branch)->code ?? 'N/A',
                    $employee->name,
                    $employee->mobile,
                ];
            }
    
            $branch_name = $branchCode ? optional($allEmployees->first()->branch)->name ?? 'All Branches' : 'All Branches';
            $branch_code = $branchCode ?? 'ALL';
            $branch_address = $branchCode ? optional($allEmployees->first()->branch)->address ?? 'N/A' : 'N/A';
            $date = now();
    
            if ($type === 'excel') {
                return EmployeesExportService::exportExcel($exportEmployees, $branch_name, $branch_code, $date, $columns);
            }
    
            if ($type === 'pdf') {
                return EmployeesExportService::exportPdf($exportEmployees, $branch_name, $branch_code, $branch_address, $date, $columns);
            }
        } else {
            $employees = $query->paginate($perPage, ['id', 'employee_code', 'name', 'mobile', 'status', 'branch_id', 'designation_id'], 'page', $page);
    
            $employees->getCollection()->transform(function ($employee) {
                return [
                    'id' => $employee->id,
                    'employee_code' => $employee->employee_code,
                    'name' => $employee->name,
                    'mobile' => $employee->mobile,
                    'status' => $employee->status,
                    'branch_code' => optional($employee->branch)->code ?? 'N/A',
                    'branch_name' => optional($employee->branch)->name ?? 'N/A',
                    'designation' => optional($employee->designation)->designation ?? 'N/A',
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
    }

    /**
     * Update Employee Status
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function updateEmployeeStatus(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required|exists:employees,id',
            'status' => 'required|in:-1,0,1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $employee = Employee::find($request->id);

        if ($employee->status == $request->status) {
            return response()->json([
                'success' => false,
                'error' => 'Status is already set to the given value',
            ], 422);
        }

        $employee->status = $request->status;

        if (!$employee->save()) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to update employee status',
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'Updated',
        ], 200);
    }
}
