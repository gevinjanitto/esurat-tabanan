<?php

namespace App\Http\Controllers;

use App\Models\Jabatan;
use App\Models\Satker;
use App\Models\User;
use App\Support\Activity;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $q = User::with(['satker', 'jabatan'])->orderBy('name')->when(!$request->user()->hasRole('admin'), fn ($w) => $w->where('role', '!=', 'admin'));
        if ($s = $request->string('q')->trim()->value()) {
            $q->where(fn ($w) => $w->where('name', 'like', "%$s%")->orWhere('username', 'like', "%$s%")->orWhere('nip', 'like', "%$s%"));
        }
        if ($role = $request->get('role')) {
            $q->where('role', $role);
        }
        return view('users.index', ['users' => $q->paginate(15)->withQueryString()]);
    }

    public function create()
    {
        return view('users.form', ['user' => new User(['is_active' => true, 'role' => 'user'])] + $this->options());
    }

    public function store(Request $request)
    {
        $user = User::create($this->validated($request));
        Activity::log('user.tambah', "Menambah user {$user->username}");
        return redirect()->route('users.index')->with('success', 'User ditambahkan.');
    }

    public function edit(Request $request, User $user)
    {
        abort_if($user->hasRole('admin') && !$request->user()->hasRole('admin'), 403);
        return view('users.form', ['user' => $user] + $this->options());
    }

    public function update(Request $request, User $user)
    {
        abort_if($user->hasRole('admin') && !$request->user()->hasRole('admin'), 403);
        $data = $this->validated($request, $user);
        if (blank($data['password'] ?? null)) {
            unset($data['password']);
        }
        $user->update($data);
        Activity::log('user.ubah', "Mengubah user {$user->username}");
        return redirect()->route('users.index')->with('success', 'User diperbarui.');
    }

    public function destroy(Request $request, User $user)
    {
        abort_if($user->id === $request->user()->id, 422, 'Tidak bisa menonaktifkan akun sendiri.');
        abort_if($user->hasRole('admin') && !$request->user()->hasRole('admin'), 403);
        $user->update(['is_active' => false]);
        Activity::log('user.nonaktif', "Menonaktifkan user {$user->username}");
        return back()->with('success', 'User dinonaktifkan.');
    }

    private function validated(Request $request, ?User $user = null): array
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'username' => ['required', 'string', 'max:50', 'alpha_dash', Rule::unique('users')->ignore($user?->id)],
            'email' => 'nullable|email|max:255',
            'nip' => ['nullable', 'string', 'max:40', Rule::unique('users')->ignore($user?->id)],
            'role' => ['required', Rule::in($request->user()->hasRole('admin') ? array_keys(User::ROLES) : ['operator', 'pimpinan', 'sekpri', 'user'])],
            'satker_id' => 'nullable|exists:satkers,id',
            'jabatan_id' => 'nullable|exists:jabatans,id',
            'atasan_id' => ['nullable', 'exists:users,id', Rule::notIn([$user?->id])],
            'password' => [$user ? 'nullable' : 'required', 'string', 'min:8'],
        ]);
        $data['is_active'] = $request->boolean('is_active');
        $data['disposisi_awal'] = $request->boolean('disposisi_awal');
        if ($data['role'] !== 'sekpri') {
            $data['atasan_id'] = null;
        }
        return $data;
    }

    private function options(): array
    {
        return ['satkers' => Satker::orderBy('nama')->get(), 'jabatans' => Jabatan::orderBy('nama')->get(), 'pimpinans' => User::where('role', 'pimpinan')->where('is_active', true)->orderBy('name')->get()];
    }
}
