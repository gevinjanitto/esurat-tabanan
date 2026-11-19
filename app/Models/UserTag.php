<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserTag extends Model
{
    public const TYPES = ['surat_masuk' => SuratMasuk::class, 'disposisi' => Disposisi::class, 'surat_keluar' => SuratKeluar::class];

    protected $fillable = ['user_id', 'taggable_type', 'taggable_id', 'tags'];

    protected function casts(): array
    {
        return ['tags' => 'array'];
    }

    public static function for(User $user, string $type, int $id): array
    {
        return static::where(['user_id' => $user->id, 'taggable_type' => $type, 'taggable_id' => $id])->value('tags') ?? [];
    }

    public static function idsWithTag(User $user, string $type, string $tag): array
    {
        $tag = mb_strtolower(trim($tag));
        return static::where('user_id', $user->id)->where('taggable_type', $type)->get()
            ->filter(fn ($t) => collect($t->tags)->contains(fn ($x) => str_contains(mb_strtolower($x), $tag)))->pluck('taggable_id')->all();
    }
}
