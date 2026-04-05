<?php

declare(strict_types=1);

$branchName = $argv[1] ?? null;
$repoUrl = $argv[2] ?? null;

if ($branchName === null || $branchName === '') {
    fwrite(STDERR, "Error: Branch name argument is required.\n");

    exit(1);
}

$composerFilePath = __DIR__.'/.workdir/composer.json';
$packageName = 'sunchayn/nimbus';
$localPackagePath = '../../';

if (! file_exists($composerFilePath)) {
    fwrite(STDERR, "Error: composer.json not found in .workdir.\n");

    exit(1);
}

$composerJson = json_decode(
    file_get_contents($composerFilePath),
    true,
    flags: JSON_THROW_ON_ERROR
);

/**
 * Ensure repositories key exists and is an array.
 */
$composerJson['repositories'] ??= [];

if (! is_array($composerJson['repositories'])) {
    fwrite(STDERR, "Error: repositories must be an array.\n");

    exit(1);
}

/**
 * Prepend the repository to ensure it takes precedence over Packagist.
 * If a repo URL is provided (fork/internal branch), use VCS.
 * Otherwise, use the local path.
 */
if (!empty($repoUrl)) {
    array_unshift(
        $composerJson['repositories'],
        [
            'type' => 'vcs',
            'url' => $repoUrl,
        ],
    );
}

/**
 * Ensure require section exists.
 */
$composerJson['require'] ??= [];

if (! array_key_exists($packageName, $composerJson['require'])) {
    fwrite(
        STDERR,
        "Error: Package '{$packageName}' is not present in require.\n"
    );

    exit(1);
}

/**
 * Force the package version to the requested dev branch.
 */
$composerJson['require'][$packageName] = "dev-{$branchName}";

/**
 * Write back composer.json with stable formatting.
 */
file_put_contents(
    $composerFilePath,
    json_encode(
        $composerJson,
        JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
    ).PHP_EOL
);

echo "composer.json updated successfully.\n";
