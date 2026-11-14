<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['klasifikasis', 'klasifikasi_keamanans'] as $name) {
            Schema::create($name, function (Blueprint $table) {
                $table->id();
                $table->string('kode', 30);
                $table->string('nama');
                $table->timestamps();
            });
        }

        Schema::create('sifats', function (Blueprint $table) {
            $table->id();
            $table->string('nama');
            $table->string('warna', 20)->default('ruby');
            $table->timestamps();
        });

        Schema::create('surat_masuks', function (Blueprint $table) {
            $table->id();
            $table->string('nomor_agenda', 50)->nullable();
            $table->string('nomor_surat');
            $table->date('tanggal_surat');
            $table->date('tanggal_terima');
            $table->string('pengirim');
            $table->string('perihal');
            $table->text('ringkasan')->nullable();
            $table->foreignId('sifat_id')->nullable()->constrained('sifats')->nullOnDelete();
            $table->foreignId('klasifikasi_id')->nullable()->constrained('klasifikasis')->nullOnDelete();
            $table->foreignId('klasifikasi_keamanan_id')->nullable()->constrained('klasifikasi_keamanans')->nullOnDelete();
            $table->string('file_path')->nullable();
            $table->string('file_name')->nullable();
            $table->string('status', 20)->default('baru');
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('disposisis', function (Blueprint $table) {
            $table->id();
            $table->foreignId('surat_masuk_id')->constrained('surat_masuks')->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('disposisis')->nullOnDelete();
            $table->foreignId('dari_user_id')->constrained('users');
            $table->foreignId('ke_user_id')->constrained('users');
            $table->string('instruksi')->nullable();
            $table->text('catatan')->nullable();
            $table->date('batas_waktu')->nullable();
            $table->string('status', 20)->default('terkirim');
            $table->timestamp('dibaca_at')->nullable();
            $table->text('tindak_lanjut')->nullable();
            $table->timestamps();
        });

        Schema::create('surat_keluars', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('nomor_surat')->nullable();
            $table->date('tanggal_surat')->nullable();
            $table->string('perihal');
            $table->text('tujuan');
            $table->text('ringkasan')->nullable();
            $table->foreignId('klasifikasi_id')->nullable()->constrained('klasifikasis')->nullOnDelete();
            $table->foreignId('sifat_id')->nullable()->constrained('sifats')->nullOnDelete();
            $table->foreignId('klasifikasi_keamanan_id')->nullable()->constrained('klasifikasi_keamanans')->nullOnDelete();
            $table->foreignId('satker_id')->nullable()->constrained('satkers')->nullOnDelete();
            $table->string('metode_verifikasi', 20)->default('bersamaan');
            $table->unsignedSmallInteger('status')->default(0);
            $table->unsignedInteger('urut_alur')->nullable();
            $table->unsignedInteger('revisi')->default(0);
            $table->string('file_path')->nullable();
            $table->string('file_name')->nullable();
            $table->string('signed_path')->nullable();
            $table->timestamp('signed_at')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('surat_keluar_alurs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('surat_keluar_id')->constrained('surat_keluars')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users');
            $table->string('peran', 20);
            $table->unsignedInteger('urut');
            $table->unsignedSmallInteger('status')->default(0);
            $table->text('catatan')->nullable();
            $table->timestamp('acted_at')->nullable();
            $table->timestamps();
        });

        Schema::create('surat_keluar_riwayats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('surat_keluar_id')->constrained('surat_keluars')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('aksi', 30);
            $table->text('catatan')->nullable();
            $table->unsignedInteger('revisi')->default(0);
            $table->timestamps();
        });

        Schema::create('surat_annotations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('surat_keluar_id')->constrained('surat_keluars')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users');
            $table->unsignedInteger('revisi')->default(0);
            $table->json('data');
            $table->timestamps();
            $table->unique(['surat_keluar_id', 'user_id', 'revisi']);
        });

        Schema::create('share_links', function (Blueprint $table) {
            $table->id();
            $table->string('token', 64)->unique();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('judul');
            $table->string('permission', 10)->default('view');
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->unsignedInteger('views')->default(0);
            $table->timestamp('last_viewed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('share_link_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('share_link_id')->constrained('share_links')->cascadeOnDelete();
            $table->string('doc_type', 10);
            $table->unsignedBigInteger('doc_id');
            $table->timestamps();
        });

        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('aksi', 50);
            $table->text('deskripsi')->nullable();
            $table->string('ip', 45)->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamps();
        });

        Schema::create('app_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('judul');
            $table->text('pesan')->nullable();
            $table->string('url')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        foreach (['app_notifications', 'activity_logs', 'share_link_items', 'share_links', 'surat_annotations', 'surat_keluar_riwayats', 'surat_keluar_alurs', 'surat_keluars', 'disposisis', 'surat_masuks', 'sifats', 'klasifikasi_keamanans', 'klasifikasis'] as $t) {
            Schema::dropIfExists($t);
        }
    }
};
