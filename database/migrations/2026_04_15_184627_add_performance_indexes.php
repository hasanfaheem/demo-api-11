<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Speeds up summary query — filters by patient + instrument together
        Schema::table('submissions', function (Blueprint $table) {
            $table->index(['patient_id', 'instrument_id'], 'submissions_patient_instrument_index');
        });

        // Speeds up answer aggregation — joins on submission_id + question_id
        Schema::table('answers', function (Blueprint $table) {
            $table->index(['submission_id', 'question_id'], 'answers_submission_question_index');
        });
    }

    public function down(): void
    {
        Schema::table('submissions', function (Blueprint $table) {
            $table->dropIndex('submissions_patient_instrument_index');
        });

        Schema::table('answers', function (Blueprint $table) {
            $table->dropIndex('answers_submission_question_index');
        });
    }
};