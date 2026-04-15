<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class QuestionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'            => $this->id,
            'prompt'        => $this->prompt,
            'response_type' => $this->response_type,
            'order'         => $this->order,
        ];
    }
}