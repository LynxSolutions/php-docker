#!/usr/bin/env php
<?php

declare(strict_types=1);

require(__DIR__ . '/common.php');

const VERSIONS = [
    '8.2',
    '8.3',
];

const LATEST = '8.3';

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

$currentVersions = [];
if (file_exists(VERSIONS_FILE)) {
    $currentVersions = json_decode(file_get_contents(VERSIONS_FILE), true);
}

$bumps = [];
$latestVersions = [];
foreach (VERSIONS as $version) {
    $latestVersion = getLatestVersion($version);
    if (!$latestVersion) {
        exit_cli(sprintf("Could not get latest version for %s\n", $version));
    }

    if (!isset($currentVersions[$version]) || $currentVersions[$version]['version'] !== $latestVersion) {
        $bumps[] = sprintf('%s to %s', $version, $latestVersion);
    }

    if (!dockerTagExists($latestVersion)) {
        continue;
    }

    $currentVersion = [
        'version' => $latestVersion,
        'latest' => $version == LATEST,
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

writeVersionsToFile($latestVersions);

exit_cli(getGithubActionsOutputParams($bumps), status: STATUS_SUCCESS);

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

function writeVersionsToFile(array $versions): void
{
    $result = file_put_contents(
        VERSIONS_FILE,
        json_encode($versions, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n",
    );
    if ($result === false) {
        exit_cli(sprintf("Could not write to %s\n", VERSIONS_FILE));
    }
}

function getGithubActionsOutputParams(array $bumps): string
{
    $bumped = 0;
    $message = 'n/a';
    if (count($bumps) > 0) {
        $bumped = 1;
        $message = getCommitMessage($bumps);
    }

    // Must match the expected
    return sprintf("bumped=%d\ncommit_message=%s", $bumped, $message);
}

function getCommitMessage(array $bumps): string
{
    $last = array_pop($bumps);
    $message = implode(', ', $bumps);

    if ($message) {
        $message .= ' and ';
    }

    return sprintf("Bump %s%s", $message, $last);
}
