<?php

namespace App\Http\Controllers;

use App\Support\Activity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class ProfileController extends Controller
{
    public function edit(Request $request)
    {
        return view('profile.edit', ['user' => $request->user()->load(['satker', 'jabatan'])]);
    }

    public function update(Request $request)
    {
        $user = $request->user();
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'nip' => 'nullable|string|max:40',
            'paraf' => 'nullable|image|mimes:png,jpg,jpeg|max:2048',
            'paraf_drawn' => 'nullable|string|max:800000',
        ]);
        if ($request->hasFile('paraf')) {
            $data['paraf_path'] = $request->file('paraf')->store('paraf', 'local');
        } elseif (!empty($data['paraf_drawn']) && str_starts_with($data['paraf_drawn'], 'data:image/png;base64,')) {
            $path = 'paraf/' . $user->id . '-' . time() . '.png';
            Storage::disk('local')->put($path, base64_decode(substr($data['paraf_drawn'], 22)));
            $data['paraf_path'] = $path;
        }
        unset($data['paraf'], $data['paraf_drawn']);
        $user->update($data);
        Activity::log('profil.ubah', 'Memperbarui profil');
        return back()->with('success', 'Profil diperbarui.');
    }

    public function password(Request $request)
    {
        $request->validate(['current_password' => 'required|current_password', 'password' => 'required|string|min:8|confirmed']);
        $request->user()->update(['password' => Hash::make($request->password)]);
        Activity::log('profil.password', 'Mengubah password');
        return back()->with('success', 'Password berhasil diubah.');
    }

    public function paraf(Request $request)
    {
        $path = $request->user()->paraf_path;
        abort_unless($path && Storage::disk('local')->exists($path), 404);
        return Storage::disk('local')->response($path);
    }
}
