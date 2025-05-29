<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class StockItem extends Model
{
    use HasUuids;

    protected $fillable = ['trip_id', 'item_id', 'quantity'];

    // Add query scopes for common queries
    public function scopeByTrip($query, $tripId)
    {
        return $query->where('trip_id', $tripId);
    }

    public function scopeByItem($query, $itemId)
    {
        return $query->where('item_id', $itemId);
    }

    public function scopeByDate($query, $date)
    {
        return $query->whereHas('trip', function ($q) use ($date) {
            $q->whereDate('date', $date);
        });
    }

    public function scopeByBranch($query, $branchId)
    {
        return $query->whereHas('trip', function ($q) use ($branchId) {
            $q->where('branch_id', $branchId);
        });
    }

    // Cache frequently accessed data
    public function getCachedStockSummary($date, $branchId)
    {
        $cacheKey = "stock_summary.{$date}.{$branchId}";
        return cache()->remember($cacheKey, now()->addHours(24), function () use ($date, $branchId) {
            return $this->with(['item:id,name', 'trip:id,date,branch_id'])
                ->whereHas('trip', function ($q) use ($date, $branchId) {
                    $q->whereDate('date', $date)
                        ->where('branch_id', $branchId);
                })
                ->get()
                ->groupBy('item_id')
                ->map(function ($items) {
                    return [
                        'item_id' => $items->first()->item_id,
                        'item_name' => $items->first()->item->name,
                        'total_quantity' => $items->sum('quantity')
                    ];
                });
        });
    }

    public function trip()
    {
        return $this->belongsTo(Trip::class);
    }

    public function item()
    {
        return $this->belongsTo(Items::class);
    }

    protected static function boot()
    {
        parent::boot();

        // Clear cache when stock item is updated
        static::updated(function ($stockItem) {
            $trip = $stockItem->trip;
            if ($trip) {
                cache()->forget("stock_summary.{$trip->date}.{$trip->branch_id}");
            }
        });

        static::deleted(function ($stockItem) {
            $trip = $stockItem->trip;
            if ($trip) {
                cache()->forget("stock_summary.{$trip->date}.{$trip->branch_id}");
            }
        });
    }
}
