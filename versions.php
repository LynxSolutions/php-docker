#!/usr/bin/env php
<?php

declare(strict_types=1);

const VERSIONS_FILE = 'versions.json';

const VERSIONS = [
    "8.2",
    "8.3",
];

// Must be implemented in Dockerfile.template
const DISTROS = [
    'alpine',
];

const VARIANTS = [
    'fpm',
    'cli',
];

// Must be implemented in Dockerfile.template
const SUBVARIANTS = [
    'fpm' => [
        'xdebug',
        'pcov',
    ],
    'cli' => [
        'xdebug',
        'pcov',
    ],
];

$latestVersions = [];
foreach (VERSIONS as $version) {
    $latestVersion = getLatestVersion($version);
    if (!$latestVersion) {
        printf("Could not get latest version for %s\n", $version);
        continue;
    }
    printf("Latest version for %s is %s\n", $version, $latestVersion);

    if (!dockerTagExists($latestVersion)) {
        printf("Skipping %s as it does not exist on Docker Hub\n", $version);
        continue;
    }

    $currentVersion = [
        'version' => $latestVersion,
        'variants' => [],
    ];

    foreach (VARIANTS as $variant) {
        foreach (DISTROS as $distro) {
            $tagSuffix = sprintf('%s-%s', $variant, $distro);

            $currentVersion['variants'][] = $tagSuffix;

            if (!isset(SUBVARIANTS[$variant])) {
                continue;
            }

            foreach (SUBVARIANTS[$variant] as $subvariant) {
                $currentVersion['variants'][] = sprintf('%s-%s', $tagSuffix, $subvariant);
            }
        }
    }

    $latestVersions[$version] = $currentVersion;
}

file_put_contents(VERSIONS_FILE, json_encode($latestVersions, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

function dockerTagExists(string $tag): bool
{
    $context = stream_context_create([
        'http' => [
            'ignore_errors' => true,
        ],
    ]);

    $response = file_get_contents(
        filename: sprintf('https://hub.docker.com/v2/repositories/library/php/tags/%s', $tag),
        context: $context,
    );

    if ($response === false) {
        return false;
    }

    $response = json_decode($response, true);

    if ($response['errinfo'] ?? false) {
        return false;
    }

    return true;
}

function getLatestVersion(string $version): false|string
{
    $query = http_build_query([
        'json' => true,
        'max' => 1,
        'version' => $version,
    ]);

    $response = file_get_contents('https://www.php.net/releases/index.php?' . $query);

    if ($response === false) {
        return false;
    }

    $response = json_decode($response, true);

    if (isset($response['error'])) {
        return false;
    }

    return array_key_first($response);
}
