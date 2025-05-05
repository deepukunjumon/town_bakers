<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class StockItem extends Model
{
    use HasUuids;

    protected $fillable = ['trip_id', 'item_id', 'quantity'];

    public function trip()
    {
        return $this->belongsTo(Trip::class);
    }

    public function item()
    {
        return $this->belongsTo(Items::class);
    }
}
