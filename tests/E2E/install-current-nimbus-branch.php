<?php

declare(strict_types=1);

$branchName = $argv[1] ?? null;

if ($branchName === null || $branchName === '') {
    fwrite(STDERR, "Error: Branch name argument is required.\n");
    exit(1);
}

$composerFilePath = __DIR__.'/.workdir/composer.json';
$packageName = 'sunchayn/nimbus';
$localPackagePath = '../../';

if (! file_exists($composerFilePath)) {
    fwrite(STDERR, "Error: composer.json not found.\n");
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
 * Check whether the path repository already exists.
 */
$pathRepositoryAlreadyDefined = false;

foreach ($composerJson['repositories'] as $repository) {
    if (
        isset($repository['type'], $repository['url']) &&
        $repository['type'] === 'path' &&
        $repository['url'] === $localPackagePath
    ) {
        $pathRepositoryAlreadyDefined = true;
        break;
    }
}

/**
 * Append the repository only if it does not already exist.
 */
if (! $pathRepositoryAlreadyDefined) {
    $composerJson['repositories'][] = [
        'type' => 'path',
        'url' => $localPackagePath,
        'options' => [
            'symlink' => true,
        ],
    ];
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
