<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderSummaryResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'delivery_date' => $this->delivery_date,
            'delivery_time' => $this->delivery_time,
            'delivered_date' => $this->delivered_date,
            'created_date' => $this->created_at->format('Y-m-d'),
            'status' => $this->status,
            'payment_status' => $this->payment_status,
            'total_amount' => $this->total_amount,
            'advance_amount' => $this->advance_amount,
            'employee' => $this->employee
                ? [
                    'id' => $this->employee->id,
                    'employee_code' => $this->employee->employee_code,
                    'name' => $this->employee->name,
                  ]
                : null,
        ];
    }
}

