<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShareLink extends Model
{
    protected $fillable = ['token', 'user_id', 'judul', 'permission', 'expires_at', 'revoked_at', 'views', 'last_viewed_at'];

    protected function casts(): array
    {
        return ['expires_at' => 'datetime', 'revoked_at' => 'datetime', 'last_viewed_at' => 'datetime'];
    }

    public function user() { return $this->belongsTo(User::class); }
    public function items() { return $this->hasMany(ShareLinkItem::class); }

    public function isActive(): bool
    {
        return !$this->revoked_at && (!$this->expires_at || $this->expires_at->isFuture());
    }

    public function canDownload(): bool
    {
        return $this->permission === 'download';
    }
}
