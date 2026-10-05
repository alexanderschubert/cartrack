<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ApiTokenController extends Controller
{
    public function index(Request $request): Response
    {
        return Inertia::render('settings/Tokens', [
            'tokens' => $request->user()->tokens()->latest()->get(['id', 'name', 'abilities', 'last_used_at', 'created_at']),
            'createdToken' => session('createdToken'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'abilities' => ['required', 'array', 'min:1'],
            'abilities.*' => ['required', 'string', 'in:odometer:read,odometer:write,fuel:read,fuel:write'],
        ]);
        $token = $request->user()->createToken($data['name'], array_values(array_unique($data['abilities'])));

        return back()->with('createdToken', $token->plainTextToken)->with('success', 'API-Token erstellt. Kopiere ihn jetzt an einen sicheren Ort.');
    }

    public function destroy(Request $request, int $token): RedirectResponse
    {
        $request->user()->tokens()->whereKey($token)->delete();

        return back()->with('success', 'API-Token wurde widerrufen.');
    }
}
