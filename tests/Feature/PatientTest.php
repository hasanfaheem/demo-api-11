<?php

namespace Tests\Feature;

use App\Models\Patient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PatientTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->authenticatedUser();
    }    

    public function test_can_create_a_patient(): void
    {
        $response = $this->postJson('/api/patients', [
            'name'          => 'Jane Doe',
            'date_of_birth' => '1990-05-20',
            'mrn'           => 'MRN-TEST-001',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.name', 'Jane Doe')
            ->assertJsonPath('data.mrn', 'MRN-TEST-001');

        $this->assertDatabaseHas('patients', ['mrn' => 'MRN-TEST-001']);
    }

    public function test_cannot_create_patient_with_duplicate_mrn(): void
    {
        Patient::create([
            'name'          => 'Existing Patient',
            'date_of_birth' => '1980-01-01',
            'mrn'           => 'MRN-DUPE-001',
        ]);

        $response = $this->postJson('/api/patients', [
            'name'          => 'New Patient',
            'date_of_birth' => '1990-05-20',
            'mrn'           => 'MRN-DUPE-001',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['mrn']);
    }

    public function test_cannot_create_patient_with_future_date_of_birth(): void
    {
        $response = $this->postJson('/api/patients', [
            'name'          => 'Future Patient',
            'date_of_birth' => '2099-01-01',
            'mrn'           => 'MRN-FUTURE-001',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['date_of_birth']);
    }

    public function test_cannot_create_patient_with_missing_fields(): void
    {
        $response = $this->postJson('/api/patients', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'date_of_birth', 'mrn']);
    }
}