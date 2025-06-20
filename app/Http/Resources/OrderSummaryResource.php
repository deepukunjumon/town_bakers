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
            'description' => $this->description,
            'remarks' => $this->remarks,
            'delivery_date' => $this->delivery_date,
            'delivery_time' => $this->delivery_time,
            'delivered_on' => $this->delivered_at ? $this->delivered_at->format('d-m-Y H:i:s') : null,
            'delivered_by' => $this->delivered_by
                ? [
                    'employee_code' => optional($this->deliveredByEmployee)->employee_code,
                    'name' => optional($this->deliveredByEmployee)->name,
                ]
                : null,
            'created_date' => $this->created_at->format('d-m-Y'),
            'status' => $this->status,
            'payment_status' => $this->payment_status,
            'customer_name' => $this->customer_name,
            'customer_mobile' => $this->customer_mobile,
            'customer_email' => $this->customer_email,
            'total_amount' => $this->total_amount,
            'advance_amount' => $this->advance_amount,
            'balance_amount' => ($this->total_amount - $this->advance_amount),
            'employee' => $this->employee
                ? [
                    'id' => $this->employee->id,
                    'employee_code' => $this->employee->employee_code,
                    'name' => $this->employee->name,
                ]
                : null,
            'branch' => $this->branch
                ? [
                    'id' => $this->branch->id,
                    'code' => $this->branch->code,
                    'name' => $this->branch->name,
                ]
                : null,
            'is_deletable' => $this->status != ORDER_STATUSES['delivered'],
        ];
    }
}
