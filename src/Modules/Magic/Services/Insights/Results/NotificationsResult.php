<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Modules\Magic\Services\Insights\Results;

/**
 * @phpstan-type NotificationEntry array{notification: string, channel: string}
 *
 * @extends InsightListResult<NotificationEntry>
 */
class NotificationsResult extends InsightListResult {}
