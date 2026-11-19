<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('template_surats', function (Blueprint $table) {
            $table->id();
            $table->string('nama', 200);
            $table->text('deskripsi')->nullable();
            $table->string('counter_penomoran', 20)->default('satker_uker');
            $table->longText('kop')->nullable();
            $table->longText('isi')->nullable();
            $table->boolean('status')->default(true);
            $table->timestamps();
        });

        Schema::create('template_surat_isians', function (Blueprint $table) {
            $table->id();
            $table->foreignId('template_surat_id')->constrained('template_surats')->cascadeOnDelete();
            $table->string('nama', 150);
            $table->string('kode', 60);
            $table->string('jenis', 20)->default('teks');
            $table->unsignedInteger('urut')->default(0);
            $table->timestamps();
        });

        Schema::create('unit_pemrakarsas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('satker_id')->nullable()->constrained('satkers')->nullOnDelete();
            $table->string('kode', 30);
            $table->string('nama');
            $table->boolean('status')->default(true);
            $table->timestamps();
        });

        Schema::create('template_nomors', function (Blueprint $table) {
            $table->id();
            $table->string('nama', 150);
            $table->foreignId('template_surat_id')->nullable()->constrained('template_surats')->nullOnDelete();
            $table->foreignId('satker_id')->nullable()->constrained('satkers')->nullOnDelete();
            $table->json('segments');
            $table->string('nomor_tambahan', 10)->default('angka');
            $table->unsignedTinyInteger('padding')->default(4);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('nomor_inisiasis', function (Blueprint $table) {
            $table->id();
            $table->foreignId('satker_id')->constrained('satkers')->cascadeOnDelete();
            $table->unsignedSmallInteger('tahun');
            $table->unsignedInteger('nomor_awal')->default(1);
            $table->timestamps();
            $table->unique(['satker_id', 'tahun']);
        });

        Schema::create('alur_surats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('judul', 150);
            $table->string('metode_verifikasi', 20)->default('bersamaan');
            $table->json('verifikator');
            $table->json('penandatangan');
            $table->timestamps();
        });

        Schema::create('template_tujuan_surats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('nama', 150);
            $table->json('tujuan');
            $table->json('tembusan');
            $table->timestamps();
        });

        Schema::create('template_tujuan_disposisis', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('nama', 150);
            $table->json('penerima');
            $table->timestamps();
        });

        Schema::table('surat_keluars', function (Blueprint $table) {
            $table->foreignId('template_surat_id')->nullable()->after('satker_id')->constrained('template_surats')->nullOnDelete();
            $table->json('isian')->nullable()->after('template_surat_id');
            $table->string('lampiran_keterangan', 100)->nullable()->after('isian');
            $table->foreignId('template_nomor_id')->nullable()->after('lampiran_keterangan')->constrained('template_nomors')->nullOnDelete();
            $table->foreignId('unit_pemrakarsa_id')->nullable()->after('template_nomor_id')->constrained('unit_pemrakarsas')->nullOnDelete();
            $table->foreignId('satker_nomor_id')->nullable()->after('unit_pemrakarsa_id')->constrained('satkers')->nullOnDelete();
            $table->unsignedInteger('no_urut')->nullable()->after('satker_nomor_id');
            $table->boolean('is_arsip')->default(false)->after('no_urut');
        });

        Schema::create('esign_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('surat_keluar_id')->nullable()->constrained('surat_keluars')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status', 20);
            $table->string('dokumen')->nullable();
            $table->text('keterangan')->nullable();
            $table->string('ip', 45)->nullable();
            $table->timestamps();
        });

        Schema::create('agendas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('surat_masuk_id')->nullable()->constrained('surat_masuks')->nullOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('judul');
            $table->text('deskripsi')->nullable();
            $table->string('metode', 20)->default('offline');
            $table->string('tempat')->nullable();
            $table->string('link_meeting')->nullable();
            $table->dateTime('mulai');
            $table->dateTime('selesai')->nullable();
            $table->timestamps();
        });

        Schema::create('disposisi_comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('disposisi_id')->constrained('disposisis')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->text('komentar');
            $table->timestamps();
        });

        Schema::create('user_tags', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('taggable_type', 20);
            $table->unsignedBigInteger('taggable_id');
            $table->json('tags');
            $table->timestamps();
            $table->unique(['user_id', 'taggable_type', 'taggable_id']);
            $table->index(['taggable_type', 'taggable_id']);
        });
    }

    public function down(): void
    {
        foreach (['user_tags', 'disposisi_comments', 'agendas', 'esign_logs'] as $t) {
            Schema::dropIfExists($t);
        }
        Schema::table('surat_keluars', function (Blueprint $table) {
            foreach (['satker_nomor_id', 'unit_pemrakarsa_id', 'template_nomor_id', 'template_surat_id'] as $fk) {
                $table->dropConstrainedForeignId($fk);
            }
            $table->dropColumn(['isian', 'lampiran_keterangan', 'no_urut', 'is_arsip']);
        });
        foreach (['template_tujuan_disposisis', 'template_tujuan_surats', 'alur_surats', 'nomor_inisiasis', 'template_nomors', 'unit_pemrakarsas', 'template_surat_isians', 'template_surats'] as $t) {
            Schema::dropIfExists($t);
        }
    }
};
