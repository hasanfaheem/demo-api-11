<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSubmissionRequest;
use App\Http\Resources\SubmissionResource;
use App\Http\Resources\SummaryResource;
use App\Models\Instrument;
use App\Models\Patient;
use App\Models\Submission;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class SubmissionController extends Controller
{
    public function store(StoreSubmissionRequest $request, Patient $patient): SubmissionResource
    {
        $submission = DB::transaction(function () use ($request, $patient) {
            $submission = $patient->submissions()->create([
                'instrument_id' => $request->instrument_id,
                'submitted_at'  => now(),
            ]);

            foreach ($request->answers as $answer) {
                $submission->answers()->create([
                    'question_id' => $answer['question_id'],
                    'value'       => (string) $answer['value'],
                ]);
            }

            return $submission;
        });

        // Invalidate summary cache for this patient + instrument
        Cache::forget("summary:patient:{$patient->id}:instrument:{$request->instrument_id}");

        return new SubmissionResource($submission->load(['instrument', 'answers.question']));
    }

    public function index(Patient $patient): AnonymousResourceCollection
    {
        $submissions = $patient->submissions()
            ->with(['instrument', 'answers.question'])
            ->orderByDesc('submitted_at')
            ->paginate(15);

        return SubmissionResource::collection($submissions);
    }

    public function show(Patient $patient, Submission $submission): SubmissionResource
    {
        if ($submission->patient_id !== $patient->id) {
            abort(404, 'Submission not found for this patient.');
        }

        return new SubmissionResource($submission->load(['instrument', 'answers.question']));
    }

    public function summary(Request $request, Patient $patient): SummaryResource
    {
        $request->validate([
            'instrument_id' => ['required', 'exists:instruments,id'],
        ]);

        $instrument = Instrument::with('questions')->findOrFail($request->instrument_id);

        $cacheKey = "summary:patient:{$patient->id}:instrument:{$instrument->id}";

        $data = Cache::remember($cacheKey, now()->addMinutes(10), function () use ($patient, $instrument) {
            $submissions = $patient->submissions()
                ->where('instrument_id', $instrument->id)
                ->orderBy('submitted_at')
                ->get();

            if ($submissions->isEmpty()) {
                return [
                    'instrument'        => $instrument,
                    'total_submissions' => 0,
                    'date_range'        => null,
                    'questions'         => collect(),
                ];
            }

            $aggregates = DB::table('answers')
                ->join('questions', 'answers.question_id', '=', 'questions.id')
                ->whereIn('answers.submission_id', $submissions->pluck('id'))
                ->select([
                    'questions.id',
                    'questions.prompt',
                    'questions.response_type',
                    DB::raw("AVG(CASE WHEN questions.response_type = 'scale_1_5' THEN CAST(answers.value AS DECIMAL(3,1)) END) as avg_score"),
                    DB::raw("SUM(CASE WHEN questions.response_type = 'yes_no' AND answers.value = '1' THEN 1 ELSE 0 END) as yes_count"),
                    DB::raw("COUNT(CASE WHEN questions.response_type = 'yes_no' THEN 1 END) as yes_no_total"),
                    DB::raw("COUNT(CASE WHEN questions.response_type = 'free_text' AND answers.value != '' THEN 1 END) as free_text_count"),
                ])
                ->groupBy('questions.id', 'questions.prompt', 'questions.response_type')
                ->get();

            return [
                'instrument'        => $instrument,
                'total_submissions' => $submissions->count(),
                'date_range'        => [
                    'earliest' => $submissions->first()->submitted_at,
                    'latest'   => $submissions->last()->submitted_at,
                ],
                'questions' => $aggregates,
            ];
        });

        return new SummaryResource($data);
    }
}