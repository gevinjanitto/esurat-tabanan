<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('grup_opds', function (Blueprint $table) {
            $table->id();
            $table->string('nama');
            $table->timestamps();
        });
        Schema::table('satkers', function (Blueprint $table) {
            $table->foreignId('grup_opd_id')->nullable()->after('parent_id')->constrained('grup_opds')->nullOnDelete();
        });
        Schema::table('surat_masuks', function (Blueprint $table) {
            $table->string('pengirim_jenis', 10)->default('luar')->after('pengirim');
            $table->foreignId('pengirim_satker_id')->nullable()->after('pengirim_jenis')->constrained('satkers')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('surat_masuks', function (Blueprint $table) {
            $table->dropConstrainedForeignId('pengirim_satker_id');
            $table->dropColumn('pengirim_jenis');
        });
        Schema::table('satkers', function (Blueprint $table) {
            $table->dropConstrainedForeignId('grup_opd_id');
        });
        Schema::dropIfExists('grup_opds');
    }
};
