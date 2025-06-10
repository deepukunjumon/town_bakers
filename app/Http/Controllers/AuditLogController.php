<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class AuditLogController extends Controller
{

    /**
     * Get audit logs with filtering and pagination
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function getAuditLogs(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'per_page' => 'nullable|integer|min:1',
                'page' => 'nullable|integer|min:1',
                'search' => 'nullable|string',
                'action' => 'nullable|string',
                'table' => 'nullable|string',
                'record_id' => 'nullable|string|uuid',
                'start_date' => 'nullable|date',
                'end_date' => 'nullable|date|after_or_equal:start_date',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            $perPage = $request->input('per_page', 10);
            $page = $request->input('page', 1);
            $search = $request->input('q', '');
            $action = $request->input('action');
            $table = $request->input('table');
            $recordId = $request->input('record_id');
            $startDate = $request->input('start_date');
            $endDate = $request->input('end_date');

            $query = AuditLog::with('performer:id,name,role')
                ->orderBy('created_at', 'desc');

            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('description', 'like', "%$search%")
                        ->orWhere('comments', 'like', "%$search%")
                        ->orWhereHas('performer', function ($q) use ($search) {
                            $q->where('name', 'like', "%$search%")
                                ->orWhere('role', 'like', "%$search%");
                        });
                });
            }

            if ($action) {
                $query->where('action', $action);
            }

            if ($table) {
                $query->where('table', $table);
            }

            if ($recordId) {
                $query->where('record_id', $recordId);
            }

            if ($startDate) {
                $query->whereDate('created_at', '>=', $startDate);
            }

            if ($endDate) {
                $query->whereDate('created_at', '<=', $endDate);
            }

            $logs = $query->paginate($perPage, [
                'id',
                'action',
                'table',
                'record_id',
                'description',
                'comments',
                'performed_by',
                'created_at'
            ], 'page', $page);

            $logs->getCollection()->transform(function ($log) {
                return [
                    'id' => $log->id,
                    'action' => $log->action,
                    'table' => $log->table,
                    'record_id' => $log->record_id,
                    'description' => $log->description,
                    'comments' => $log->comments ? json_decode($log->comments, true) : null,
                    'performed_by' => $log->performer ? [
                        'id' => $log->performer->id,
                        'name' => $log->performer->name,
                        'role' => $log->performer->role
                    ] : [
                        'id' => null,
                        'name' => 'Deleted User',
                        'role' => 'N/A'
                    ],
                    'created_at' => $log->created_at
                ];
            });

            return response()->json([
                'success' => true,
                'logs' => $logs->items(),
                'pagination' => [
                    'total' => $logs->total(),
                    'per_page' => $logs->perPage(),
                    'current_page' => $logs->currentPage(),
                    'last_page' => $logs->lastPage(),
                    'from' => $logs->firstItem(),
                    'to' => $logs->lastItem(),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve audit logs.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get available tables for audit logs filtering
     * 
     * @return JsonResponse
     */
    public function getLoggableTables(): JsonResponse
    {
        try {
            $tables = AuditLog::select('table')
                ->distinct()
                ->orderBy('table')
                ->get()
                ->map(function ($log) {
                    return [
                        'id' => $log->table,
                        'name' => ucwords(str_replace('_', ' ', $log->table))
                    ];
                });

            return response()->json([
                'success' => true,
                'tables' => $tables
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve audit log tables.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * List of loggable actions in Audit Logs
     * 
     * @return JsonResponse
     */
    public function getLoggableActions(): JsonResponse
    {
        $actions = array_values(AUDITLOG_ACTIONS);
        $formattedActions = array_map(function ($action) {
            return [
                'id' => $action,
                'name' => ucfirst($action)
            ];
        }, $actions);

        return response()->json([
            'success' => true,
            'actions' => $formattedActions
        ]);
    }
}
