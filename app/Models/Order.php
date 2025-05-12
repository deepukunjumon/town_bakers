<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Str;

class Order extends Model
{
    use HasFactory;

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id', 'branch_id', 'employee_id', 'title', 'description', 'remarks',
        'delivery_date', 'delivery_time', 'customer_name', 'customer_email', 'customer_mobile', 'total_amount', 'advance_amount', 'payment_status', 'status', 'deliverd_time', 'created_by',
    ];

    protected $casts = [
        'delivered_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->id = (string) Str::uuid();
        });
    }

    // Relationships
    public function branch() {
        return $this->belongsTo(Branch::class);
    }

    public function employee() {
        return $this->belongsTo(Employee::class);
    }

    public function creator() {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function deliveredByEmployee()
    {
        return $this->belongsTo(Employee::class, 'delivered_by');
    }
}
