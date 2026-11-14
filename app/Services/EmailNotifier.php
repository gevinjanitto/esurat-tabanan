<?php

namespace App\Services;

use App\Mail\NotifikasiSurat;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

class EmailNotifier
{
    public static function send(iterable $userIds, string $subject, string $judul, array $rows, string $url): void
    {
        if (!config('mail.notifikasi_aktif')) {
            return;
        }
        $users = User::whereIn('id', collect($userIds)->filter()->unique())->where('id', '!=', auth()->id())->whereNotNull('email')->where('is_active', true)->get();
        foreach ($users as $user) {
            try {
                Mail::to($user->email)->send(new NotifikasiSurat($subject, $judul, $user->name, $rows, $url));
            } catch (\Throwable $e) {
                report($e);
            }
        }
    }
}
