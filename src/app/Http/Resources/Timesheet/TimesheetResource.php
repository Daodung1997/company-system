<?php

namespace App\Http\Resources\Timesheet;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TimesheetResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'employee_id' => $this->employee_id,
            'date' => $this->date->format('Y-m-d'),
            'check_in' => $this->check_in?->format('Y-m-d H:i:s'),
            'check_out' => $this->check_out?->format('Y-m-d H:i:s'),
            'timezone' => $this->timezone,
            'status' => $this->status,
            'note' => $this->note,
            'employee' => new \App\Http\Resources\Api\Auth\EmployeeResource($this->whenLoaded('employee')),
            'expected_start' => $this->expected_start ?? null,
            'expected_end' => $this->expected_end ?? null,
            'checkout_diff' => $this->checkout_diff ?? null,
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at->format('Y-m-d H:i:s'),
        ];
    }
}
