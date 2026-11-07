<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Modules\Magic\Services\Insights\Results;

/**
 * @phpstan-type MailEntry array{subject: string, to: list<string>, mailable: string|null}
 *
 * @extends InsightListResult<MailEntry>
 */
class MailResult extends InsightListResult {}
