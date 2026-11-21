<?php

declare(strict_types=1);

namespace Sunchayn\Nimbus\Tests\Concerns;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

/**
 * Moves the base and storage paths of the application to a throwaway folder.
 * A test that writes files then never touches the shared Testbench skeleton.
 */
trait UsesTemporaryBasePath
{
    protected function useTemporaryBasePath(): string
    {
        $basePath = sys_get_temp_dir().'/nimbus-test-'.Str::uuid();

        File::ensureDirectoryExists($basePath);

        $this->app->setBasePath($basePath);

        $this->app->useStoragePath("{$basePath}/storage");

        $this->beforeApplicationDestroyed(
            static fn () => File::deleteDirectory($basePath),
        );

        return $basePath;
    }
}
