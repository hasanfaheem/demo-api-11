<?php

namespace Database\Seeders;

use App\Models\Answer;
use App\Models\Instrument;
use App\Models\Patient;
use App\Models\Submission;
use Illuminate\Database\Seeder;

class ProSeeder extends Seeder
{
    public function run(): void
    {
        // Patients
        $patient1 = Patient::create([
            'name'          => 'Sarah Johnson',
            'date_of_birth' => '1985-03-15',
            'mrn'           => 'MRN-001-SJ',
        ]);

        $patient2 = Patient::create([
            'name'          => 'Michael Torres',
            'date_of_birth' => '1972-11-08',
            'mrn'           => 'MRN-002-MT',
        ]);

        // Instrument 1 — All 3 question types
        $instrument1 = Instrument::create([
            'title'       => 'Weekly Symptom Tracker',
            'description' => 'A weekly questionnaire to monitor treatment side effects and quality of life.',
        ]);

        $q1 = $instrument1->questions()->create([
            'prompt'        => 'How would you rate your overall pain level this week?',
            'response_type' => 'scale_1_5',
            'order'         => 0,
        ]);

        $q2 = $instrument1->questions()->create([
            'prompt'        => 'Did you experience nausea in the past 7 days?',
            'response_type' => 'yes_no',
            'order'         => 1,
        ]);

        $q3 = $instrument1->questions()->create([
            'prompt'        => 'Please describe any other symptoms you experienced this week.',
            'response_type' => 'free_text',
            'order'         => 2,
        ]);

        // Instrument 2 — Quality of life
        $instrument2 = Instrument::create([
            'title'       => 'Quality of Life Assessment',
            'description' => 'Monthly assessment of patient quality of life and functional status.',
        ]);

        $q4 = $instrument2->questions()->create([
            'prompt'        => 'How would you rate your energy levels this month?',
            'response_type' => 'scale_1_5',
            'order'         => 0,
        ]);

        $q5 = $instrument2->questions()->create([
            'prompt'        => 'Were you able to perform your daily activities without assistance?',
            'response_type' => 'yes_no',
            'order'         => 1,
        ]);

        $q6 = $instrument2->questions()->create([
            'prompt'        => 'What has been the biggest challenge to your quality of life this month?',
            'response_type' => 'free_text',
            'order'         => 2,
        ]);

        // Submissions for Patient 1 — Instrument 1 (6 submissions)
        $submissions1 = [
            ['pain' => 3, 'nausea' => true,  'notes' => 'Mild headaches in the morning.'],
            ['pain' => 4, 'nausea' => true,  'notes' => 'Fatigue and joint stiffness.'],
            ['pain' => 2, 'nausea' => false, 'notes' => ''],
            ['pain' => 3, 'nausea' => true,  'notes' => 'Occasional dizziness after medication.'],
            ['pain' => 2, 'nausea' => false, 'notes' => 'Feeling better overall this week.'],
            ['pain' => 1, 'nausea' => false, 'notes' => 'Significant improvement noted.'],
        ];

        foreach ($submissions1 as $index => $data) {
            $submission = Submission::create([
                'patient_id'    => $patient1->id,
                'instrument_id' => $instrument1->id,
                'submitted_at'  => now()->subWeeks(6 - $index),
            ]);

            Answer::insert([
                [
                    'submission_id' => $submission->id,
                    'question_id'   => $q1->id,
                    'value'         => (string) $data['pain'],
                    'created_at'    => now(),
                    'updated_at'    => now(),
                ],
                [
                    'submission_id' => $submission->id,
                    'question_id'   => $q2->id,
                    'value'         => $data['nausea'] ? '1' : '0',
                    'created_at'    => now(),
                    'updated_at'    => now(),
                ],
                [
                    'submission_id' => $submission->id,
                    'question_id'   => $q3->id,
                    'value'         => $data['notes'],
                    'created_at'    => now(),
                    'updated_at'    => now(),
                ],
            ]);
        }

        // Submissions for Patient 2 — Instrument 1 (5 submissions)
        $submissions2 = [
            ['pain' => 5, 'nausea' => true,  'notes' => 'Severe pain, difficult to sleep.'],
            ['pain' => 4, 'nausea' => true,  'notes' => 'Pain slightly reduced but still high.'],
            ['pain' => 4, 'nausea' => false, 'notes' => 'Nausea gone but pain persists.'],
            ['pain' => 3, 'nausea' => false, 'notes' => ''],
            ['pain' => 3, 'nausea' => true,  'notes' => 'Nausea returned after new medication.'],
        ];

        foreach ($submissions2 as $index => $data) {
            $submission = Submission::create([
                'patient_id'    => $patient2->id,
                'instrument_id' => $instrument1->id,
                'submitted_at'  => now()->subWeeks(5 - $index),
            ]);

            Answer::insert([
                [
                    'submission_id' => $submission->id,
                    'question_id'   => $q1->id,
                    'value'         => (string) $data['pain'],
                    'created_at'    => now(),
                    'updated_at'    => now(),
                ],
                [
                    'submission_id' => $submission->id,
                    'question_id'   => $q2->id,
                    'value'         => $data['nausea'] ? '1' : '0',
                    'created_at'    => now(),
                    'updated_at'    => now(),
                ],
                [
                    'submission_id' => $submission->id,
                    'question_id'   => $q3->id,
                    'value'         => $data['notes'],
                    'created_at'    => now(),
                    'updated_at'    => now(),
                ],
            ]);
        }

        // Submissions for Patient 1 — Instrument 2 (3 submissions)
        $submissions3 = [
            ['energy' => 2, 'daily' => false, 'challenge' => 'Chronic fatigue making it hard to work full days.'],
            ['energy' => 3, 'daily' => true,  'challenge' => 'Some difficulty concentrating but managing.'],
            ['energy' => 4, 'daily' => true,  'challenge' => 'Energy improving, back to most normal activities.'],
        ];

        foreach ($submissions3 as $index => $data) {
            $submission = Submission::create([
                'patient_id'    => $patient1->id,
                'instrument_id' => $instrument2->id,
                'submitted_at'  => now()->subMonths(3 - $index),
            ]);

            Answer::insert([
                [
                    'submission_id' => $submission->id,
                    'question_id'   => $q4->id,
                    'value'         => (string) $data['energy'],
                    'created_at'    => now(),
                    'updated_at'    => now(),
                ],
                [
                    'submission_id' => $submission->id,
                    'question_id'   => $q5->id,
                    'value'         => $data['daily'] ? '1' : '0',
                    'created_at'    => now(),
                    'updated_at'    => now(),
                ],
                [
                    'submission_id' => $submission->id,
                    'question_id'   => $q6->id,
                    'value'         => $data['challenge'],
                    'created_at'    => now(),
                    'updated_at'    => now(),
                ],
            ]);
        }

        $this->command->info('✅ PRO seeder completed: 2 patients, 2 instruments, 14 submissions.');
    }
}