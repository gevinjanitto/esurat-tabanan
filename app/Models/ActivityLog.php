<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
    protected $fillable = ['user_id', 'aksi', 'deskripsi', 'ip', 'user_agent'];

    public function user() { return $this->belongsTo(User::class); }
}
