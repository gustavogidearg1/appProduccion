<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up(): void {Schema::table('ventas',function(Blueprint $t){$t->date('fecha_entrega_pactada')->nullable();});}
 public function down(): void {Schema::table('ventas',function(Blueprint $t){$t->dropColumn('fecha_entrega_pactada');});}
};
