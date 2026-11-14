<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use Illuminate\Http\Request;

class LogController extends Controller
{
    public function index(Request $request)
    {
        $isAdmin = $request->user()->hasRole('admin');
        $q = ActivityLog::with('user')->latest('id')->when(!$isAdmin, fn ($w) => $w->where('user_id', $request->user()->id));
        if ($s = $request->string('q')->trim()->value()) {
            $q->where(fn ($w) => $w->where('deskripsi', 'like', "%$s%")->orWhere('aksi', 'like', "%$s%"));
        }
        return view('logs.index', ['logs' => $q->paginate(25)->withQueryString(), 'isAdmin' => $isAdmin]);
    }
}
