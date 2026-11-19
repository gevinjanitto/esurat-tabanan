<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('surat_keluars', function (Blueprint $table) {
            $table->string('metode_ttd', 20)->default('elektronik')->after('metode_verifikasi');
        });
        Schema::table('agendas', function (Blueprint $table) {
            $table->foreignId('disposisi_id')->nullable()->after('surat_masuk_id')->constrained('disposisis')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('agendas', function (Blueprint $table) {
            $table->dropConstrainedForeignId('disposisi_id');
        });
        Schema::table('surat_keluars', function (Blueprint $table) {
            $table->dropColumn('metode_ttd');
        });
    }
};
