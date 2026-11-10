<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('producto_plazos_entrega', function (Blueprint $table) {
            $table->decimal('ue', 10, 4)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('producto_plazos_entrega', function (Blueprint $table) {
            $table->dropColumn('ue');
        });
    }
};