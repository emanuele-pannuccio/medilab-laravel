<?php

use App\MedicalCaseStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('reports', function (Blueprint $table) {
            $table->id();
            $table->date('hospitalization_date');
            $table->longText('present_illness_history');
            $table->longText('past_illness_history');
            $table->longText('clinical_evolution')->nullable();
            $table->date('discharge_date')->nullable();
            $table->longText('discharge_description')->nullable();
            $table->foreignId('patientId')->references("id")->on("patients");
            $table->foreignId('doctorId')->references("id")->on("users");
            $table->string('document');
            $table->string('documentHash')->unique();
            $table->enum("status", array_column(MedicalCaseStatus::cases(), 'value'))->default(MedicalCaseStatus::aperto->value);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reports');
    }
};
