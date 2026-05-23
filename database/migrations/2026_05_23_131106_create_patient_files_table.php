<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('patient_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained('patients')->onDelete('cascade');
            $table->string('name'); // Ex: Hemograma Completo
            $table->string('file_path'); // Caminho salvo no servidor
            $table->string('file_type')->nullable(); // Ex: pdf, png, jpg
            $table->text('notes')->nullable(); // Observações adicionais
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('patient_files');
    }
};