<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DisposisiComment extends Model
{
    protected $fillable = ['disposisi_id', 'user_id', 'komentar'];

    public function user() { return $this->belongsTo(User::class); }
    public function disposisi() { return $this->belongsTo(Disposisi::class); }
}
