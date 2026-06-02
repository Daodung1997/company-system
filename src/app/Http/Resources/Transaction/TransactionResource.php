<?php

namespace App\Http\Resources\Transaction;

use App\Http\Resources\Document\DocumentResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TransactionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'type' => $this->type,
            'amount' => $this->amount,
            'net_amount' => $this->net_amount,
            'tax_amount' => $this->tax_amount,
            'tax_rate_type' => $this->tax_rate_type,
            'invoice_registration_number' => $this->invoice_registration_number,
            'withholding_tax' => $this->withholding_tax,
            'payment_method' => $this->payment_method,
            'category' => $this->category,
            'transaction_date' => $this->transaction_date ? $this->transaction_date->format('Y-m-d') : null,
            'description' => $this->description,
            'status' => $this->status,
            'created_by' => $this->created_by,
            'updated_by' => $this->updated_by,
            'documents' => DocumentResource::collection($this->whenLoaded('documents')),
            'created_at' => $this->created_at ? $this->created_at->toISOString() : null,
            'updated_at' => $this->updated_at ? $this->updated_at->toISOString() : null,
        ];
    }
}
