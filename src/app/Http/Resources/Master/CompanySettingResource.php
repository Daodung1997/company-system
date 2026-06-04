<?php

namespace App\Http\Resources\Master;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CompanySettingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'company_id' => $this->company_id,
            'company_name' => $this->company_name,
            'company_name_kana' => $this->company_name_kana,
            'tax_code' => $this->tax_code,
            'corporate_number' => $this->corporate_number,
            'address_registered' => $this->address_registered,
            'legal_representative' => $this->legal_representative,
            'representative_title' => $this->representative_title,
            'representative_id_number' => $this->representative_id_number,
            'representative_id_date' => $this->representative_id_date?->format('Y-m-d'),
            'representative_id_place' => $this->representative_id_place,
            'charter_capital' => $this->charter_capital,
            'phone_number' => $this->phone_number,
            'email' => $this->email,
            'fax' => $this->fax,
            'postcode' => $this->postcode,
            'address' => $this->address,
            'website' => $this->website,
            'hanko_seal_path' => $this->hanko_seal_path,
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}
