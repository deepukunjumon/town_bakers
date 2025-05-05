<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Trip extends Model
{
    use HasUuids;

    protected $fillable = ['branch_id', 'employee_id', 'date'];

    public function stockItems()
    {
        return $this->hasMany(StockItem::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
}
