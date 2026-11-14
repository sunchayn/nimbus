<?php

namespace Sunchayn\Nimbus\Modules\Routes\Actions;

use Barryvdh\Debugbar\Facades\Debugbar;

/**
 * Disables third-party UI addons that interfere with Nimbus interface.
 */
class DisableThirdPartyUiAction
{
    public function execute(): void
    {
        // The Debugbar will interfere with the UI,
        // The page is a third party so most likely no need to have the debug toolbar here.
        if (class_exists(Debugbar::class)) {
            Debugbar::disable();
        }
    }
}
