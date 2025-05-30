<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Str;
use App\Traits\HasAuditLogs;

class Order extends Model
{
    use HasFactory, HasAuditLogs;

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'branch_id',
        'employee_id',
        'title',
        'description',
        'remarks',
        'delivery_date',
        'delivery_time',
        'customer_name',
        'customer_email',
        'customer_mobile',
        'total_amount',
        'advance_amount',
        'payment_status',
        'status',
        'deliverd_time',
        'created_by',
    ];

    protected $casts = [
        'delivered_at' => 'datetime',
    ];

    // Add query scopes for common queries
    public function scopeByBranch($query, $branchId)
    {
        return $query->where('branch_id', $branchId);
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeByPaymentStatus($query, $paymentStatus)
    {
        return $query->where('payment_status', $paymentStatus);
    }

    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('delivery_date', [$startDate, $endDate]);
    }

    public function scopeSearch($query, $search)
    {
        return $query->where(function ($query) use ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', '%' . $search . '%')
                    ->orWhere('description', 'like', '%' . $search . '%')
                    ->orWhere('remarks', 'like', '%' . $search . '%');
            })
                ->orWhere(function ($q) use ($search) {
                    $q->where('customer_name', 'like', '%' . $search . '%')
                        ->orWhere('customer_email', 'like', '%' . $search . '%')
                        ->orWhere('customer_mobile', 'like', '%' . $search . '%');
                });
        });
    }

    // Cache frequently accessed data
    public function getCachedOrder($id)
    {
        return cache()->remember('order.' . $id, now()->addHours(24), function () use ($id) {
            return $this->with([
                'employee:id,employee_code,name',
                'branch:id,code,name',
                'creator:id,name'
            ])->find($id);
        });
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->id = (string) Str::uuid();
        });

        // Clear cache when order is updated
        static::updated(function ($order) {
            cache()->forget('order.' . $order->id);
        });

        static::deleted(function ($order) {
            cache()->forget('order.' . $order->id);
        });
    }

    // Relationships
    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function deliveredByEmployee()
    {
        return $this->belongsTo(Employee::class, 'delivered_by');
    }
}
