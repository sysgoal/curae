<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Utilizamos uma instrução SQL direta porque é o método mais seguro e infalível 
        // para converter colunas do tipo ENUM para VARCHAR no MySQL sem causar conflitos.
        DB::statement("ALTER TABLE appointments MODIFY status VARCHAR(30) DEFAULT 'agendado'");
        
        // Vamos também garantir que os registos antigos em inglês não quebram a interface
        DB::statement("UPDATE appointments SET status = 'agendado' WHERE status = 'scheduled'");
        DB::statement("UPDATE appointments SET status = 'concluido' WHERE status = 'completed'");
        DB::statement("UPDATE appointments SET status = 'cancelado' WHERE status = 'canceled'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE appointments MODIFY status VARCHAR(30) DEFAULT 'scheduled'");
    }
};