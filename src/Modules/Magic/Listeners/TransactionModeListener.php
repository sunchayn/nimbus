<?php

namespace Sunchayn\Nimbus\Modules\Magic\Listeners;

use Illuminate\Database\Connection;
use Illuminate\Foundation\Http\Events\RequestHandled;
use Illuminate\Routing\Events\RouteMatched;
use Illuminate\Support\Facades\Log;
use Throwable;

class TransactionModeListener
{
    public const SKIPPED_HEADER = 'X-Nimbus-Transaction-Skipped';

    public function handle(RouteMatched $routeMatched): void
    {
        if ($routeMatched->request->header('X-Nimbus-Transaction-Mode') !== '1') {
            return;
        }

        /** @var Connection[] $opened */
        $opened = [];

        /** @var string[] $skipped */
        $skipped = [];

        foreach (array_map(strval(...), array_keys(config('database.connections', []))) as $name) {
            try {
                $connection = app('db')->connection($name);

                $connection->beginTransaction();

                $opened[] = $connection;
            } catch (Throwable $throwable) {
                $skipped[] = $name;

                Log::debug('Nimbus transaction mode skipped a connection.', [
                    'connection' => $name,
                    'reason' => $throwable->getMessage(),
                ]);
            }
        }

        app('events')->listen(RequestHandled::class, function (RequestHandled $requestHandled) use ($skipped): void {
            if ($skipped !== []) {
                $requestHandled->response->headers->set(self::SKIPPED_HEADER, implode(', ', $skipped));
            }
        });

        app()->terminating(function () use ($opened): void {
            foreach (array_reverse($opened) as $connection) {
                if ($connection->transactionLevel() > 0) {
                    $connection->rollBack(0);
                }
            }
        });
    }
}
