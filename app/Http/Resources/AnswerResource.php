<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AnswerResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $value = $this->castValue();

        return [
            'question_id'   => $this->question_id,
            'prompt'        => $this->whenLoaded('question', fn() => $this->question->prompt),
            'response_type' => $this->whenLoaded('question', fn() => $this->question->response_type),
            'value'         => $value,
        ];
    }

    private function castValue(): mixed
    {
        if (!$this->relationLoaded('question')) {
            return $this->value;
        }

        return match ($this->question->response_type) {
            'scale_1_5' => (int) $this->value,
            'yes_no'    => $this->value === '1',
            default     => $this->value,
        };
    }
}