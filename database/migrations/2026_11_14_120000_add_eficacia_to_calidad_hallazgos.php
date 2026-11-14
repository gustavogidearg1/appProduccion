<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::table('calidad_hallazgos', function (Blueprint $t) {
            $t->text('criterio_eficacia')->nullable();
            $t->date('fecha_verificacion_prevista')->nullable();
            $t->text('resultado_eficacia')->nullable();
            $t->timestamp('verificado_en')->nullable();
            $t->unsignedBigInteger('verificado_por')->nullable();
        });
    }
    public function down(): void {
        Schema::table('calidad_hallazgos', fn (Blueprint $t) => $t->dropColumn(['criterio_eficacia', 'fecha_verificacion_prevista', 'resultado_eficacia', 'verificado_en', 'verificado_por']));
    }
};
