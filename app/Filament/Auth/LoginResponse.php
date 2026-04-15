<?php

namespace App\Filament\Auth;

use Filament\Auth\Http\Responses\Contracts\LoginResponse as Responsable;
use Filament\Facades\Filament;
use Illuminate\Http\RedirectResponse;
use Livewire\Features\SupportRedirects\Redirector;

class LoginResponse implements Responsable
{
    public function toResponse($request): RedirectResponse|Redirector
    {
        $userId = (int) ($request->user()?->getAuthIdentifier() ?? 0);

        if ($userId === 1) {
            return redirect()->intended(Filament::getUrl());
        }

        return redirect()->route('home')->with('status', 'Prijavljeni ste');
    }
}
