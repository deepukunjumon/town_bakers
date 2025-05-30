<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class AuditLog extends Model
{
    use HasUuids;

    protected $fillable = [
        'action',
        'table',
        'record_id',
        'description',
        'comments',
        'performed_by'
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    public function performer()
    {
        return $this->belongsTo(User::class, 'performed_by');
    }
} 