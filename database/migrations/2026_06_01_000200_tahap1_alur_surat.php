<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('disposisi_awal')->default(false)->after('role');
            $table->foreignId('atasan_id')->nullable()->after('jabatan_id')->constrained('users')->nullOnDelete();
        });

        Schema::table('satkers', function (Blueprint $table) {
            $table->string('kategori', 20)->default('divisi')->after('nama');
        });

        Schema::table('surat_masuks', function (Blueprint $table) {
            $table->string('jenis', 20)->default('eksternal')->after('status');
            $table->foreignId('satker_id')->nullable()->after('jenis')->constrained('satkers')->nullOnDelete();
            $table->foreignId('surat_keluar_id')->nullable()->after('satker_id')->constrained('surat_keluars')->nullOnDelete();
        });

        Schema::create('surat_masuk_teruskans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('surat_masuk_id')->constrained('surat_masuks')->cascadeOnDelete();
            $table->string('tipe', 20);
            $table->foreignId('satker_id')->nullable()->constrained('satkers')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('dari_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('surat_masuk_teruskans')->nullOnDelete();
            $table->string('status', 20)->default('aktif');
            $table->timestamp('dibaca_at')->nullable();
            $table->timestamps();
        });

        Schema::create('subjek_disposisis', function (Blueprint $table) {
            $table->id();
            $table->string('nama');
            $table->timestamps();
        });

        Schema::table('disposisis', function (Blueprint $table) {
            $table->boolean('is_draft')->default(false)->after('status');
            $table->foreignId('drafted_by')->nullable()->after('is_draft')->constrained('users')->nullOnDelete();
        });

        Schema::table('surat_keluars', function (Blueprint $table) {
            $table->timestamp('terkirim_at')->nullable()->after('signed_at');
        });

        Schema::table('surat_keluar_alurs', function (Blueprint $table) {
            $table->unsignedSmallInteger('status_sekpri')->nullable()->after('status');
            $table->foreignId('diteruskan_dari_id')->nullable()->after('status_sekpri')->constrained('surat_keluar_alurs')->nullOnDelete();
        });

        Schema::create('surat_keluar_tujuans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('surat_keluar_id')->constrained('surat_keluars')->cascadeOnDelete();
            $table->foreignId('satker_id')->constrained('satkers')->cascadeOnDelete();
            $table->string('jenis', 20)->default('tujuan');
            $table->timestamps();
        });

        Schema::create('surat_keluar_lampirans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('surat_keluar_id')->constrained('surat_keluars')->cascadeOnDelete();
            $table->string('file_path');
            $table->string('file_name');
            $table->unsignedBigInteger('size')->default(0);
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('form_drafts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('key', 60);
            $table->json('data');
            $table->timestamps();
            $table->unique(['user_id', 'key']);
        });

        DB::table('sifats')->where('warna', 'emerald')->update(['warna' => 'ruby']);
    }

    public function down(): void
    {
        foreach (['form_drafts', 'surat_keluar_lampirans', 'surat_keluar_tujuans', 'subjek_disposisis', 'surat_masuk_teruskans'] as $t) {
            Schema::dropIfExists($t);
        }
        Schema::table('surat_keluar_alurs', function (Blueprint $table) {
            $table->dropConstrainedForeignId('diteruskan_dari_id');
            $table->dropColumn('status_sekpri');
        });
        Schema::table('surat_keluars', fn (Blueprint $table) => $table->dropColumn('terkirim_at'));
        Schema::table('disposisis', function (Blueprint $table) {
            $table->dropConstrainedForeignId('drafted_by');
            $table->dropColumn('is_draft');
        });
        Schema::table('surat_masuks', function (Blueprint $table) {
            $table->dropConstrainedForeignId('surat_keluar_id');
            $table->dropConstrainedForeignId('satker_id');
            $table->dropColumn('jenis');
        });
        Schema::table('satkers', fn (Blueprint $table) => $table->dropColumn('kategori'));
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('atasan_id');
            $table->dropColumn('disposisi_awal');
        });
    }
};
