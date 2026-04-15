<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SubmissionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'           => $this->id,
            'patient_id'   => $this->patient_id,
            'instrument'   => new InstrumentResource($this->whenLoaded('instrument')),
            'submitted_at' => $this->submitted_at,
            'answers'      => AnswerResource::collection($this->whenLoaded('answers')),
            'created_at'   => $this->created_at,
        ];
    }
}