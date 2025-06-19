<?php

namespace App\Http\Controllers;

use App\Models\EmailLog;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class EmailLogController extends Controller
{

    /**
     * Get Email logs with filtering and pagination
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function getEmailLogs(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'per_page' => 'nullable|integer|min:1',
                'page' => 'nullable|integer|min:1',
                'q' => 'nullable|string',
                'type' => 'nullable|string',
                'status' => 'nullable|string',
                'sent_by' => 'nullable|string',
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
            $type = $request->input('type');
            $status = $request->input('status');
            $startDate = $request->input('start_date');
            $endDate = $request->input('end_date');

            $query = EmailLog::orderBy('created_at', 'desc');

            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('to', 'like', "%$search%")
                        ->orWhere('cc', 'like', "%$search%")
                        ->orWhere('sent_by', 'like', "%$search%")
                        ->orWhere('error_message', 'like', "%$search%");
                });
            }

            if ($type) {
                $query->where('type', $type);
            }

            if ($status) {
                $query->where('status', $status);
            }

            if ($startDate) {
                $query->whereDate('created_at', '>=', $startDate);
            }

            if ($endDate) {
                $query->whereDate('created_at', '<=', $endDate);
            }

            $emailLogs = $query->paginate($perPage, [
                'id',
                'type',
                'to',
                'cc',
                'status',
                'sent_by',
                'error_message',
                'created_at'
            ], 'page', $page);

            $emailLogs->getCollection()->transform(function ($log) {
                return [
                    'id' => $log->id,
                    'type' => $log->type,
                    'to' => $log->to,
                    'cc' => $log->cc,
                    'status' => $log->status,
                    'sent_by' => $log->sent_by,
                    'error_message' => $log->error_message,
                    'created_at' => $log->created_at
                ];
            });

            return response()->json([
                'success' => true,
                'logs' => $emailLogs->items(),
                'pagination' => [
                    'total' => $emailLogs->total(),
                    'per_page' => $emailLogs->perPage(),
                    'current_page' => $emailLogs->currentPage(),
                    'last_page' => $emailLogs->lastPage(),
                    'from' => $emailLogs->firstItem(),
                    'to' => $emailLogs->lastItem(),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve email logs.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * List of loggable actions in Audit Logs
     * 
     * @return JsonResponse
     */
    public function getEmailTypes(): JsonResponse
    {
        $types = array_values(EMAIL_TYPES);
        $formattedTypes = array_map(function ($type) {
            return [
                'value' => $type,
                'label' => ucwords(str_replace('_', ' ', $type))
            ];
        }, $types);

        return response()->json([
            'success' => true,
            'types' => $formattedTypes
        ]);
    }
}
