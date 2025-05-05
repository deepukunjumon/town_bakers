<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Stock extends Model
{
    use HasFactory;

    public $incrementing = false;
    protected $keyType = 'uuid';

    protected $fillable = [
        'id',
        'branch_id',
        'employee_id',
        'item_id',
        'trip_code',
        'quantity',
        'date'
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->id = (string) Str::uuid();
        });
    }

    // Relations (optional for eager loading)
    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }
    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
    public function item()
    {
        return $this->belongsTo(Items::class);
    }
}
