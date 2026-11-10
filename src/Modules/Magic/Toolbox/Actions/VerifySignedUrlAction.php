<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Modules\Magic\Toolbox\Actions;

use Illuminate\Http\Request;
use Illuminate\Routing\UrlGenerator;

/** @final */
class VerifySignedUrlAction
{
    public function __construct(private readonly UrlGenerator $urlGenerator) {}

    /**
     * @return array{valid: bool, correct_signature: bool, expired: bool}
     */
    public function execute(string $url): array
    {
        $request = Request::create($url);

        $correctSignature = $this->urlGenerator->hasCorrectSignature($request);
        $expired = ! $this->urlGenerator->signatureHasNotExpired($request);

        return [
            'valid' => $correctSignature && ! $expired,
            'correct_signature' => $correctSignature,
            'expired' => $expired,
        ];
    }
}
