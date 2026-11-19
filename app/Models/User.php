<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use Notifiable;

    public const ROLES = [
        'admin' => 'Administrator',
        'operator' => 'Operator',
        'pimpinan' => 'Pimpinan',
        'sekpri' => 'Sekretaris Pimpinan',
        'user' => 'Pegawai',
    ];

    protected $fillable = ['name', 'username', 'email', 'nip', 'nrk', 'role', 'disposisi_awal', 'satker_id', 'jabatan_id', 'atasan_id', 'paraf_path', 'is_active', 'password', 'last_login_at'];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return ['password' => 'hashed', 'is_active' => 'boolean', 'disposisi_awal' => 'boolean', 'last_login_at' => 'datetime'];
    }

    public function satker() { return $this->belongsTo(Satker::class); }
    public function jabatan() { return $this->belongsTo(Jabatan::class); }
    public function atasan() { return $this->belongsTo(User::class, 'atasan_id'); }
    public function sekpris() { return $this->hasMany(User::class, 'atasan_id')->where('role', 'sekpri')->where('is_active', true); }

    public function hasRole(string ...$roles): bool
    {
        return in_array($this->role, $roles, true);
    }

    public function isSekpri(): bool
    {
        return $this->role === 'sekpri' && $this->atasan_id;
    }

    public function isSekpriDireksi(): bool
    {
        return $this->isSekpri() && $this->atasan?->satker?->kategori === 'direksi';
    }

    public function getRoleLabelAttribute(): string
    {
        return self::ROLES[$this->role] ?? $this->role;
    }

    public function getInitialsAttribute(): string
    {
        $parts = preg_split('/\s+/', trim($this->name));
        return strtoupper(substr($parts[0] ?? '', 0, 1) . substr($parts[1] ?? '', 0, 1));
    }
}
