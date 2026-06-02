<?php

namespace App\Http\Resources\Admin\Worker;

use App\Http\Resources\Common\ImageSimpleResource;
use App\Http\Resources\User\UserSimpleResource;
use Illuminate\Http\Resources\Json\JsonResource;

class WorkerRegistrationHistoryResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'attempt_number' => $this->attempt_number,
            'action' => $this->action,
            'action_by' => $this->actionByAdmin ? new UserSimpleResource($this->actionByAdmin) : null,
            'action_reason' => $this->action_reason,
            'submitted_data' => [
                'phone' => $this->phone,
                'dob' => $this->dob?->format('Y-m-d'),
                'id_card_number' => $this->id_card_number,
                'id_card_issue_date' => $this->id_card_issue_date?->format('Y-m-d'),
                'permanent_address' => $this->permanent_address,
                'gender' => $this->gender,
                'experience_years' => $this->experience_years,
                'skill_description' => $this->skill_description,
                'service_ids' => $this->service_ids,
                'area_ids' => $this->area_ids,
            ],
            'kyc_documents' => [
                'selfie' => $this->selfie ? new ImageSimpleResource($this->selfie) : null,
                'id_card_front' => $this->idCardFront ? new ImageSimpleResource($this->idCardFront) : null,
                'id_card_back' => $this->idCardBack ? new ImageSimpleResource($this->idCardBack) : null,
            ],
            'created_at' => $this->created_at,
        ];
    }
}
