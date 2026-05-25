<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
   public function up() {
    Schema::table('anamneses', function (Blueprint $table) {
        $table->json('adult_data')->nullable()->after('child_data');
    });
}
public function down() {
    Schema::table('anamneses', function (Blueprint $table) {
        $table->dropColumn('adult_data');
    });
}
};
