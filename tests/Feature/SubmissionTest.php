<?php

namespace Tests\Feature;

use App\Models\Instrument;
use App\Models\Patient;
use App\Models\Question;
use App\Models\Submission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubmissionTest extends TestCase
{
    use RefreshDatabase;

    private Patient $patient;
    private Instrument $instrument;
    private Question $scaleQuestion;
    private Question $yesNoQuestion;
    private Question $freeTextQuestion;

    protected function setUp(): void
    {
        parent::setUp();
        $this->authenticatedUser();

        $this->patient = Patient::create([
            'name'          => 'Test Patient',
            'date_of_birth' => '1985-06-15',
            'mrn'           => 'MRN-TEST-001',
        ]);

        $this->instrument = Instrument::create([
            'title'       => 'Test Instrument',
            'description' => 'For testing purposes.',
        ]);

        $this->scaleQuestion = $this->instrument->questions()->create([
            'prompt'        => 'Rate your pain.',
            'response_type' => 'scale_1_5',
            'order'         => 0,
        ]);

        $this->yesNoQuestion = $this->instrument->questions()->create([
            'prompt'        => 'Did you feel nauseous?',
            'response_type' => 'yes_no',
            'order'         => 1,
        ]);

        $this->freeTextQuestion = $this->instrument->questions()->create([
            'prompt'        => 'Any other symptoms?',
            'response_type' => 'free_text',
            'order'         => 2,
        ]);
    }

    private function validAnswers(): array
    {
        return [
            ['question_id' => $this->scaleQuestion->id,   'value' => 3],
            ['question_id' => $this->yesNoQuestion->id,   'value' => true],
            ['question_id' => $this->freeTextQuestion->id, 'value' => 'Mild headache.'],
        ];
    }

    public function test_can_submit_a_valid_instrument(): void
    {
        $response = $this->postJson("/api/patients/{$this->patient->id}/submissions", [
            'instrument_id' => $this->instrument->id,
            'answers'       => $this->validAnswers(),
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.patient_id', $this->patient->id);

        $this->assertDatabaseCount('submissions', 1);
        $this->assertDatabaseCount('answers', 3);
    }

    public function test_cannot_submit_with_missing_answer(): void
    {
        $response = $this->postJson("/api/patients/{$this->patient->id}/submissions", [
            'instrument_id' => $this->instrument->id,
            'answers'       => [
                ['question_id' => $this->scaleQuestion->id, 'value' => 3],
                // missing yes_no and free_text
            ],
        ]);

        $response->assertStatus(422);
    }

    public function test_cannot_submit_with_invalid_scale_value(): void
    {
        $answers = $this->validAnswers();
        $answers[0]['value'] = 7; // out of range

        $response = $this->postJson("/api/patients/{$this->patient->id}/submissions", [
            'instrument_id' => $this->instrument->id,
            'answers'       => $answers,
        ]);

        $response->assertStatus(422);
    }

    public function test_cannot_submit_with_invalid_yes_no_value(): void
    {
        $answers = $this->validAnswers();
        $answers[1]['value'] = 'maybe'; // not a boolean

        $response = $this->postJson("/api/patients/{$this->patient->id}/submissions", [
            'instrument_id' => $this->instrument->id,
            'answers'       => $answers,
        ]);

        $response->assertStatus(422);
    }

    public function test_can_list_submissions_paginated(): void
    {
        foreach (range(1, 5) as $i) {
            $submission = Submission::create([
                'patient_id'    => $this->patient->id,
                'instrument_id' => $this->instrument->id,
                'submitted_at'  => now()->subDays($i),
            ]);

            $submission->answers()->createMany([
                ['question_id' => $this->scaleQuestion->id,    'value' => '3'],
                ['question_id' => $this->yesNoQuestion->id,    'value' => '1'],
                ['question_id' => $this->freeTextQuestion->id, 'value' => 'Notes.'],
            ]);
        }

        $response = $this->getJson("/api/patients/{$this->patient->id}/submissions");

        $response->assertStatus(200)
            ->assertJsonCount(5, 'data')
            ->assertJsonStructure(['data', 'links', 'meta']);
    }

    public function test_summary_returns_correct_aggregates(): void
    {
        // 2 submissions: pain 2 and 4 (avg 3.0), nausea true/false (50%), free text one filled
        $data = [
            ['pain' => 2, 'nausea' => '1', 'notes' => 'Some notes.'],
            ['pain' => 4, 'nausea' => '0', 'notes' => ''],
        ];

        foreach ($data as $d) {
            $submission = Submission::create([
                'patient_id'    => $this->patient->id,
                'instrument_id' => $this->instrument->id,
                'submitted_at'  => now(),
            ]);

            $submission->answers()->createMany([
                ['question_id' => $this->scaleQuestion->id,    'value' => (string) $d['pain']],
                ['question_id' => $this->yesNoQuestion->id,    'value' => $d['nausea']],
                ['question_id' => $this->freeTextQuestion->id, 'value' => $d['notes']],
            ]);
        }

        $response = $this->getJson("/api/patients/{$this->patient->id}/summary?instrument_id={$this->instrument->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.total_submissions', 2);

        $questions = $response->json('data.questions');

        $scale   = collect($questions)->firstWhere('response_type', 'scale_1_5');
        $yesNo   = collect($questions)->firstWhere('response_type', 'yes_no');
        $free    = collect($questions)->firstWhere('response_type', 'free_text');

        $this->assertEquals(3.0,  $scale['aggregate']['value']);
        $this->assertEquals(50.0, $yesNo['aggregate']['value']);
        $this->assertEquals(1,    $free['aggregate']['value']);
    }
}