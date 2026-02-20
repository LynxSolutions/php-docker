#!/usr/bin/env php
<?php

declare(strict_types=1);

require_once __DIR__ . '/common.php';

if (!file_exists(VERSIONS_FILE)) {
    exit_cli(sprintf("%s file does not exist", VERSIONS_FILE));
}
$versions = json_decode(file_get_contents(VERSIONS_FILE), true);

$repository = getenv('DOCKER_REPOSITORY');
if (false === $repository) {
    exit_cli("DOCKER_REPOSITORY environment variable is not set\n");
}

$include = [];
foreach ($versions as $majorVersion => $versionData) {
    $version = $versionData['version'];
    $isLatest = $versionData['latest'];
    $platforms = implode(',', $versionData['platforms']);

    foreach ($versionData['variants'] as $variant) {
        $tags = getVersionTags($repository, $version, $variant, $isLatest);
        $dir = sprintf('./%s/%s', $majorVersion, $variant);
        $include[] = [
            'name' => sprintf('%s-%s', $version, $variant),
            'os' => 'ubuntu-latest',
            'tags' => $tags,
            'runs' => [
                'build-and-push' => getBuildAndPushCommand($tags, $dir, platforms: $platforms, latest: $isLatest),
            ],
        ];
    }
}

$strategy = [
    'fail-fast' => true,
    'matrix' => [
        'include' => $include,
    ],
];

exit_cli(sprintf("%s\n", json_encode($strategy, JSON_PRETTY_PRINT)), STATUS_SUCCESS);

function getVersionTags(string $repository, string $version, string $variant, bool $latest = false): array
{
    $versionParts = explode('.', $version);

    $tags = [];

    $levels = $latest ? 1 : 2;
    for ($i = count($versionParts); $i > 0; $i--) {
        $tagVer = implode('.', array_slice($versionParts, 0, $i));
        $tags[] = sprintf('%s:%s-%s', $repository, $tagVer, $variant);
        if ($i <= $levels) {
            break;
        }
    }

    if ($latest) {
        $tags[] = sprintf('%s:%s', $repository, $variant);
    }

    return $tags;
}

function getBuildAndPushCommand(array $tags, string $dir, string $platforms = 'linux/amd64', bool $latest = false): string
{
    $tagArgs = implode(' ', array_map(static fn(string $tag) => '--tag ' . $tag, $tags));

    $cacheTags = $tags;
    array_shift($cacheTags);
    if ($latest) {
        array_pop($cacheTags);
    }
    $cacheFromArgs = implode(' ', array_map(static fn(string $tag) => '--cache-from ' . $tag, $cacheTags));


    return sprintf('docker buildx build --push --platform %s %s %s %s', $platforms, $cacheFromArgs, $tagArgs, $dir);
}
