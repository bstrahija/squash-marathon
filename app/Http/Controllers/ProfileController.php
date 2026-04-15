<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function edit(): View
    {
        return view('profile');
    }

    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $user = $request->user();

        $user->first_name = $validated['first_name'];
        $user->last_name = $validated['last_name'];

        if (filled($validated['password'] ?? null)) {
            $user->password = $validated['password'];
        }

        $user->save();

        if ($request->hasFile('avatar')) {
            $user->addMediaFromRequest('avatar')->toMediaCollection('avatar');
        }

        return redirect()->route('profile')->with('status', 'Profil je uspješno ažuriran.');
    }

    public function destroyAvatar(Request $request): RedirectResponse
    {
        $user = $request->user();

        $user->clearMediaCollection('avatar');

        return redirect()->route('profile')->with('status', 'Avatar je uklonjen.');
    }
}
