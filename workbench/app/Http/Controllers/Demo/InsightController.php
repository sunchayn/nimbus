<?php

namespace Workbench\App\Http\Controllers\Demo;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use RuntimeException;
use Workbench\App\Events\InventorySynced;
use Workbench\App\Jobs\SyncInventoryJob;
use Workbench\App\Mail\OrderReceiptMail;
use Workbench\App\Notifications\OrderReceiptNotification;

/**
 * Does a little of everything Insight reports, so the panel has data to show.
 */
class InsightController
{
    public function activity(): JsonResponse
    {
        foreach (range(1, 6) as $id) {
            DB::select('select * from sqlite_master where name = ?', ['table_'.$id]);
        }

        DB::select('select * from sqlite_master where name = ? and name != ?', ['users', 'password_token']);

        Log::warning('Insight demo warning.', ['order' => 42]);

        Cache::get('insight-demo-missing');
        Cache::put('insight-demo', 'value', 60);
        Cache::get('insight-demo');
        Cache::forget('insight-demo');

        Gate::define('insight-demo', fn ($user = null): bool => false);
        Gate::allows('insight-demo');

        $this->callAnotherServer();

        // A recursive query that takes long enough to be flagged as slow.
        DB::select('with recursive counter(n) as (select 1 union all select n + 1 from counter where n < 2000000) select count(*) from counter');

        event(new InventorySynced(items: 3));

        // The sync queue runs a job at once and raises no queued event, so the database queue is used.
        SyncInventoryJob::dispatch()->onConnection('database');

        config(['mail.default' => 'array']);

        Mail::to('demo@example.com')->send(new OrderReceiptMail);
        Notification::route('mail', 'demo@example.com')->notify(new OrderReceiptNotification);

        return response()->json(['ok' => true]);
    }

    public function failure(): JsonResponse
    {
        throw new RuntimeException('Insight demo failure.');
    }

    /**
     * Calls the other demo server, so the panel has a call with a real duration.
     * A server that is not running shows up as a failed call.
     */
    private function callAnotherServer(): void
    {
        $port = request()->getPort() === 8000 ? 8001 : 8000;

        try {
            Http::timeout(2)->get("http://127.0.0.1:{$port}/demo/time?token=demo");
        } catch (ConnectionException) {
            // The failed call is recorded by Insight, so the demo needs no handling.
        }
    }
}
