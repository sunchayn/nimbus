<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Modules\Mcp\Services;

use Psr\Log\AbstractLogger;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Stringable;

class McpLogger extends AbstractLogger
{
    private const LOGGED_LEVELS = [
        LogLevel::WARNING,
        LogLevel::ERROR,
        LogLevel::CRITICAL,
        LogLevel::ALERT,
        LogLevel::EMERGENCY,
    ];

    public function __construct(
        private readonly LoggerInterface $logger,
    ) {}

    /**
     * @param  array<mixed>  $context
     */
    public function log($level, string|Stringable $message, array $context = []): void
    {
        if (! in_array($level, self::LOGGED_LEVELS, true)) {
            return;
        }

        $this->logger->log($level, $message, $context);
    }
}
