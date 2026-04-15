<?php

namespace App\Http\Requests;

use App\Models\Instrument;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\ValidationException;

class StoreSubmissionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'instrument_id' => ['required', 'exists:instruments,id'],
            'answers'       => ['required', 'array'],
            'answers.*.question_id' => ['required', 'exists:questions,id'],
            'answers.*.value'       => ['required'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                $instrument = Instrument::with('questions')->find($this->instrument_id);

                if (!$instrument) {
                    return;
                }

                $questionMap = $instrument->questions->keyBy('id');
                $answeredIds = collect($this->answers)->pluck('question_id')->map(fn($id) => (int) $id);
                $requiredIds = $questionMap->keys();

                // All questions must be answered
                $missing = $requiredIds->diff($answeredIds);
                if ($missing->isNotEmpty()) {
                    $validator->errors()->add('answers', 'All questions must be answered. Missing question IDs: ' . $missing->join(', '));
                    return;
                }

                // Validate each answer against its question type
                foreach ($this->answers as $index => $answer) {
                    $questionId = (int) $answer['question_id'];
                    $question = $questionMap->get($questionId);

                    if (!$question) {
                        $validator->errors()->add("answers.$index.question_id", 'Question does not belong to this instrument.');
                        continue;
                    }

                    $value = $answer['value'];

                    match ($question->response_type) {
                        'scale_1_5' => $this->validateScale($validator, $index, $value),
                        'yes_no'    => $this->validateYesNo($validator, $index, $value),
                        'free_text' => null, // any string accepted including empty
                    };
                }
            }
        ];
    }

    private function validateScale(Validator $validator, int $index, mixed $value): void
    {
        if (!is_int($value) || $value < 1 || $value > 5) {
            $validator->errors()->add("answers.$index.value", 'Scale response must be an integer between 1 and 5.');
        }
    }

    private function validateYesNo(Validator $validator, int $index, mixed $value): void
    {
        if (!is_bool($value)) {
            $validator->errors()->add("answers.$index.value", 'Yes/No response must be a boolean (true or false).');
        }
    }
}