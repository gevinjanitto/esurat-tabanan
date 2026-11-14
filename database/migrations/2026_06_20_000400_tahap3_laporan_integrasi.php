<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('key', 60)->unique();
            $table->text('value')->nullable();
            $table->timestamps();
        });
        Schema::create('hari_liburs', function (Blueprint $table) {
            $table->id();
            $table->date('tanggal')->unique();
            $table->string('keterangan');
            $table->timestamps();
        });
        Schema::create('nomor_cadangans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('satker_id')->constrained('satkers')->cascadeOnDelete();
            $table->foreignId('template_surat_id')->nullable()->constrained('template_surats')->cascadeOnDelete();
            $table->unsignedTinyInteger('bulan');
            $table->unsignedSmallInteger('jumlah')->default(5);
            $table->timestamps();
            $table->unique(['satker_id', 'template_surat_id', 'bulan']);
        });
        Schema::create('riwayat_nomors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('satker_id')->constrained('satkers')->cascadeOnDelete();
            $table->date('tanggal');
            $table->unsignedInteger('no_akhir');
            $table->unsignedInteger('no_max');
            $table->timestamps();
            $table->unique(['satker_id', 'tanggal']);
        });
        Schema::create('sdms', function (Blueprint $table) {
            $table->id();
            $table->string('nrk', 40)->unique();
            $table->string('nama');
            $table->string('unit')->nullable();
            $table->string('satuan_kerja')->nullable();
            $table->string('sub_unit')->nullable();
            $table->string('jabatan')->nullable();
            $table->string('email')->nullable();
            $table->boolean('status_cuti')->default(false);
            $table->timestamps();
        });
        Schema::create('captcha_stats', function (Blueprint $table) {
            $table->id();
            $table->date('tanggal')->unique();
            $table->unsignedInteger('success')->default(0);
            $table->unsignedInteger('failed')->default(0);
            $table->timestamps();
        });
        Schema::create('mobile_tokens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('access_hash', 64)->unique();
            $table->string('refresh_hash', 64)->unique();
            $table->timestamp('access_expires_at');
            $table->timestamp('refresh_expires_at');
            $table->string('fcm_token', 300)->nullable();
            $table->string('device')->nullable();
            $table->timestamp('last_active_at')->nullable();
            $table->timestamps();
        });
        Schema::table('users', function (Blueprint $table) {
            $table->string('nrk', 40)->nullable()->index()->after('nip');
        });
        Schema::table('jabatans', function (Blueprint $table) {
            $table->boolean('sync_sdm')->default(false)->after('is_pimpinan');
        });
    }

    public function down(): void
    {
        Schema::table('jabatans', fn (Blueprint $table) => $table->dropColumn('sync_sdm'));
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['nrk']);
            $table->dropColumn('nrk');
        });
        foreach (['mobile_tokens', 'captcha_stats', 'sdms', 'riwayat_nomors', 'nomor_cadangans', 'hari_liburs', 'settings'] as $t) {
            Schema::dropIfExists($t);
        }
    }
};
