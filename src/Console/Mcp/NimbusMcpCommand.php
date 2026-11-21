<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Console\Mcp;

use Illuminate\Console\Command;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Support\Facades\File;
use Mcp\Server\Transport\StdioTransport;
use Sunchayn\Nimbus\Modules\Mcp\Enums\McpLevelEnum;
use Sunchayn\Nimbus\Modules\Mcp\Server\NimbusMcpServer;
use Sunchayn\Nimbus\Modules\Mcp\Services\McpLogger;

class NimbusMcpCommand extends Command
{
    protected $signature = 'nimbus:mcp
        {--install : Print the MCP client config for this application instead of starting the server}
        {--write : With --install, merge the config into .mcp.json in the project root}';

    protected $description = 'Start the Nimbus MCP server, or print the config that connects an agent to it.';

    public function handle(Repository $config): int
    {
        if (McpLevelEnum::tryFrom((string) $config->get('nimbus.mcp.level', 'send')) === null) {
            $this->output->getErrorStyle()->error('The `nimbus.mcp.level` config must be `read`, `send`, or `write`.');

            return self::FAILURE;
        }

        if ($this->option('install')) {
            return $this->install();
        }

        return $this->startServer();
    }

    /**
     * Stdout carries the protocol only, so anything else that prints is sent to stderr.
     */
    private function startServer(): int
    {
        $displayErrors = ini_set('display_errors', 'stderr');

        ob_start(
            callback: static function (string $buffer): string {
                fwrite(STDERR, $buffer);

                return '';
            },
            chunk_size: 1,
        );

        try {
            $stdioTransport = $this->createStdioTransport();

            return $this->laravel->make(NimbusMcpServer::class)->build()->run($stdioTransport);
        } finally {
            ob_end_clean();

            ini_set('display_errors', (string) $displayErrors);
        }
    }

    /**
     * The transport closes its streams at the end, so it gets its own handles and not the STDIN and STDOUT constants.
     */
    private function createStdioTransport(): StdioTransport
    {
        return $this->laravel->makeWith(
            abstract: StdioTransport::class,
            parameters: [
                'input' => fopen('php://stdin', 'r'),
                'output' => fopen('php://stdout', 'w'),
                'logger' => $this->laravel->make(McpLogger::class),
            ],
        );
    }

    private function install(): int
    {
        $serverEntry = [
            'command' => PHP_BINARY,
            'args' => [base_path('artisan'), 'nimbus:mcp'],
        ];

        if (! $this->option('write')) {
            $snippet = ['mcpServers' => ['nimbus' => $serverEntry]];

            $encodedSnippet = $this->encodeConfig(config: $snippet);

            $this->line($encodedSnippet);

            return self::SUCCESS;
        }

        return $this->writeConfig(serverEntry: $serverEntry);
    }

    /**
     * @param  array<string, mixed>  $serverEntry
     */
    private function writeConfig(array $serverEntry): int
    {
        $filepath = base_path('.mcp.json');

        $existingConfig = $this->readConfig(filepath: $filepath);

        if (! is_array($existingConfig)) {
            $this->components->error('The file .mcp.json exists but is not valid JSON. Fix it or remove it first.');

            return self::FAILURE;
        }

        $existingConfig['mcpServers']['nimbus'] = $serverEntry;

        $encodedConfig = $this->encodeConfig(config: $existingConfig);

        File::put(
            path: $filepath,
            contents: $encodedConfig.PHP_EOL,
        );

        $this->components->info('Wrote the nimbus server to .mcp.json.');

        return self::SUCCESS;
    }

    private function readConfig(string $filepath): mixed
    {
        if (File::missing($filepath)) {
            return [];
        }

        return json_decode(File::get($filepath), true);
    }

    /**
     * @param  array<string, mixed>  $config
     */
    private function encodeConfig(array $config): string
    {
        return (string) json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }
}
