<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Http\Api\Doctor;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Sunchayn\Nimbus\Modules\Config\ActiveApplicationResolver;
use Sunchayn\Nimbus\Modules\Doctor\Actions\RunDiagnosticsAction;
use Sunchayn\Nimbus\Modules\Doctor\DataTransferObjects\DiagnoseData;
use Sunchayn\Nimbus\Modules\Doctor\DataTransferObjects\DiagnosticCheckData;
use Throwable;

class DoctorController
{
    public function __invoke(
        Request $request,
        RunDiagnosticsAction $runDiagnosticsAction,
    ): JsonResponse {
        $pageUrl = $request->getSchemeAndHttpHost();

        // A broken config makes the resolver fail, and the config check reports it.
        try {
            $baseUrl = resolve(ActiveApplicationResolver::class)->getApiBaseUrl();
        } catch (Throwable) {
            $baseUrl = $pageUrl;
        }

        $checks = $runDiagnosticsAction->execute(
            data: new DiagnoseData(pageUrl: $pageUrl, baseUrl: $baseUrl),
        );

        return response()->json(
            array_map(
                callback: fn (DiagnosticCheckData $check): array => $check->toArray(),
                array: $checks,
            ),
        );
    }
}
