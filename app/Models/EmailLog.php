<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EmailLog extends Model
{
    use HasUuids, SoftDeletes;

    protected $fillable = [
        'type',
        'to',
        'cc',
        'status',
        'error_message'
    ];

    /**
     * Get the parent mailable model.
     */
    public function mailable()
    {
        return $this->morphTo();
    }
}
