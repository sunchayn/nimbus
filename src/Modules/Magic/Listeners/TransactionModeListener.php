<?php

namespace Sunchayn\Nimbus\Modules\Magic\Listeners;

use Illuminate\Routing\Events\RouteMatched;

class TransactionModeListener
{
    public function handle(RouteMatched $routeMatched): void
    {
        if ($routeMatched->request->header('X-Nimbus-Transaction-Mode') !== '1') {
            return;
        }

        app('db')->beginTransaction();

        app()->terminating(function (): void {
            if (app('db')->transactionLevel() > 0) {
                app('db')->rollBack();
            }
        });
    }
}
