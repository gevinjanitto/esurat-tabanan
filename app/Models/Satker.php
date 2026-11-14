<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Satker extends Model
{
    public const KATEGORI = ['direksi' => 'Direksi', 'divisi' => 'Divisi', 'cabang' => 'Cabang'];

    protected $fillable = ['kode', 'nama', 'kategori', 'parent_id'];

    public function parent() { return $this->belongsTo(Satker::class, 'parent_id'); }
    public function jabatans() { return $this->hasMany(Jabatan::class)->orderBy('nama'); }
    public function users() { return $this->hasMany(User::class); }
}
