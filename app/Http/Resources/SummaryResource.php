<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SummaryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $questions = collect($this->resource['questions'])->map(function ($q) {
            $result = [
                'question_id'   => $q->id,
                'prompt'        => $q->prompt,
                'response_type' => $q->response_type,
            ];

            $result['aggregate'] = match ($q->response_type) {
                'scale_1_5' => [
                    'type'  => 'average_score',
                    'value' => $q->avg_score ? round((float) $q->avg_score, 2) : null,
                ],
                'yes_no' => [
                    'type'  => 'yes_percentage',
                    'value' => $q->yes_no_total > 0
                        ? round(($q->yes_count / $q->yes_no_total) * 100, 1)
                        : null,
                ],
                'free_text' => [
                    'type'  => 'non_empty_count',
                    'value' => (int) $q->free_text_count,
                ],
            };

            return $result;
        });

        return [
            'instrument'        => new InstrumentResource($this->resource['instrument']),
            'total_submissions' => $this->resource['total_submissions'],
            'date_range'        => $this->resource['date_range'],
            'questions'         => $questions,
        ];
    }
}