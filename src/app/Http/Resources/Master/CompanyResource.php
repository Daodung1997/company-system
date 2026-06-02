<?php

namespace App\Http\Resources\Master;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CompanyResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'name' => $this->name,
            'name_kana' => $this->name_kana,
            'tax_code' => $this->tax_code,
            'corporate_number' => $this->corporate_number,
            'address_registered' => $this->address_registered,
            'legal_representative' => $this->legal_representative,
            'hanko_seal_path' => $this->hanko_seal_path,
            'fax' => $this->fax,
            'phone_number' => $this->phone_number,
            'postcode' => $this->postcode,
            'address' => $this->address,
            'email' => $this->email,
            'note' => $this->note,
            'status' => $this->status,
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}
