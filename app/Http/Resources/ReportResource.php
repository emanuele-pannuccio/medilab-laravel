<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReportResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            "id"=> $this->id,
            "hospitalization_date"=> Carbon::parse($this->hospitalization_date)->format('d/m/Y'),
            "present_illness_history"=> $this->present_illness_history,
            "past_illness_history"=> $this->past_illness_history,
            "clinical_evolution"=> $this->clinical_evolution,
            "discharge_date"=> $this->discharge_date != null ? Carbon::parse($this->discharge_date)->format('d/m/Y') : null,
            "discharge_description"=> $this->discharge_description,
            "patient"=> PatientResource::make($this->whenLoaded("patient")),
            "doctor"=> UserResource::make($this->whenLoaded("doctor")),
            "status"=> $this->status,
            "created_at"=> $this->created_at,
            "updated_at"=> $this->updated_at,
        ];
    }
}
