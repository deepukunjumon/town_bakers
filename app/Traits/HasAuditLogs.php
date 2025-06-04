<?php

namespace App\Traits;

use App\Models\AuditLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

trait HasAuditLogs
{
    protected static $isBulkOperation = false;

    protected static function bootHasAuditLogs()
    {
        static::created(function ($model) {
            if (!static::$isBulkOperation) {
                $model->logAudit(AUDITLOG_ACTIONS['CREATE']);
            }
        });

        static::updated(function ($model) {
            if (!static::$isBulkOperation) {
                $model->logAudit(AUDITLOG_ACTIONS['UPDATE']);
            }
        });

        static::deleted(function ($model) {
            if (!static::$isBulkOperation) {
                $model->logAudit(AUDITLOG_ACTIONS['DELETE']);
            }
        });
    }

    public function auditLogs()
    {
        return $this->hasMany(AuditLog::class, 'record_id')
            ->where('table', $this->getTable())
            ->orderBy('created_at', 'desc');
    }

    protected function logAudit($action)
    {
        $user = Auth::user();
        $description = $this->generateAuditDescription($action);

        // If this is a status change, override the action
        if ($action === AUDITLOG_ACTIONS['UPDATE'] && $this->isDirty('status') && $this->status === DEFAULT_STATUSES['deleted']) {
            $action = AUDITLOG_ACTIONS['DELETE'];
        }
        if ($action === AUDITLOG_ACTIONS['UPDATE'] && $this->isDirty('status') && $this->status === DEFAULT_STATUSES['active']) {
            $action = AUDITLOG_ACTIONS['ENABLE'];
        }
        if ($action === AUDITLOG_ACTIONS['UPDATE'] && $this->isDirty('status') && $this->status === DEFAULT_STATUSES['inactive']) {
            $action = AUDITLOG_ACTIONS['DISABLE'];
        }

        AuditLog::create([
            'id' => (string) Str::uuid(),
            'action' => $action,
            'table' => $this->getTable(),
            'record_id' => $this->getKey(),
            'description' => $description,
            'performed_by' => $user ? $user->id : null
        ]);
    }

    protected function generateAuditDescription($action)
    {
        $modelName = class_basename($this);
        $tableName = $this->getTable();

        switch ($action) {
            case AUDITLOG_ACTIONS['CREATE']:
                return "New record created in {$tableName}";
            case AUDITLOG_ACTIONS['UPDATE']:
                $changes = $this->getDirty();
                $changedFields = array_keys($changes);

                if (isset($changes['status']) && $changes['status'] === DEFAULT_STATUSES['deleted']) {
                    return "Record deleted from {$tableName}";
                }
                if (isset($changes['status']) && $changes['status'] === DEFAULT_STATUSES['inactive']) {
                    return "Record disabled from {$tableName}";
                }
                if (isset($changes['status']) && $changes['status'] === DEFAULT_STATUSES['active']) {
                    return "Record enabled from {$tableName}";
                }
                // Special Cases
                if ($tableName === 'orders' && isset($changes['status']) && $changes['status'] === ORDER_STATUSES['delivered']) {
                    return "Order marked as delivered";
                }
                if ($tableName === 'orders' && isset($changes['status']) && $changes['status'] === ORDER_STATUSES['cancelled']) {
                    return "Order marked as cancelled";
                }

                return "Record updated in {$tableName}: " . implode(', ', $changedFields);
            case AUDITLOG_ACTIONS['DELETE']:
                return "Record deleted from {$tableName}";
            default:
                return "Record {$action} in {$tableName}";
        }
    }

    public static function startBulkOperation()
    {
        static::$isBulkOperation = true;
    }

    public static function endBulkOperation()
    {
        static::$isBulkOperation = false;
    }
}
