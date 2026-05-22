<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('evolutions', function (Blueprint $table) {
            $table->id();

            // Relacionamentos
            $table->foreignId('patient_id')->constrained('patients')->cascadeOnDelete();
            $table->foreignId('professional_id')->constrained('professionals')->restrictOnDelete();
            
            // Appointment é nullable porque um enfermeiro pode apenas aferir a pressão 
            // do paciente na triagem sem que uma consulta formal seja gerada ou finalizada.
            $table->foreignId('appointment_id')->nullable()->constrained('appointments')->nullOnDelete();

            // Dados Antropométricos (Para gráficos de peso e IMC)
            $table->decimal('weight', 5, 2)->nullable(); // Ex: 75.50 (kg)
            $table->decimal('height', 3, 2)->nullable(); // Ex: 1.75 (m)
            $table->decimal('bmi', 4, 1)->nullable(); // IMC (Pode ser calculado automaticamente no backend)

            // Sinais Vitais (Excelentes para gráficos cardiológicos e acompanhamento)
            $table->integer('systolic_bp')->nullable(); // Pressão Sistólica (Ex: 120)
            $table->integer('diastolic_bp')->nullable(); // Pressão Diastólica (Ex: 80)
            $table->integer('heart_rate')->nullable(); // Frequência Cardíaca (BPM)
            $table->integer('respiratory_rate')->nullable(); // Frequência Respiratória (IRPM)
            $table->decimal('temperature', 4, 1)->nullable(); // Ex: 36.5 (°C)
            $table->integer('oxygen_saturation')->nullable(); // SpO2 (%)
            $table->integer('blood_glucose')->nullable(); // Glicemia (mg/dL)

            // Evolução Clínica
            // Opcional: Um texto livre para o médico anotar como o paciente evoluiu desde a última consulta
            $table->text('clinical_notes')->nullable(); 

            $table->timestamps(); // O created_at será o nosso Eixo X (Tempo) nos gráficos!
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('evolutions');
    }
};