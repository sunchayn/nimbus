<?php

declare(strict_types=1);

namespace Workbench\App\Http\Controllers\Dev;

use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Workbench\App\Models\User;
use Workbench\Database\Factories\UserFactory;

/**
 * Session login helpers so Nimbus can be tested as a logged-in user.
 */
class DevAuthController
{
    public function loginAs(int $id): RedirectResponse
    {
        Auth::login(User::findOrFail($id), remember: true);

        return redirect('/demo');
    }

    public function loginAsNewUser(): RedirectResponse
    {
        Auth::login(UserFactory::new()->create(), remember: true);

        return redirect('/demo');
    }
}
