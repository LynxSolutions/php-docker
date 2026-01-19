#!/usr/bin/env php
<?php

declare(strict_types=1);

require(__DIR__ . '/common.php');

const VERSIONS = [
    '8.2',
    '8.3',
    '8.4',
    '8.5',
];

const LATEST = '8.5';

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

    if (!dockerTagExists($latestVersion)) {
        continue;
    }

    if (!isset($currentVersions[$version]) || $currentVersions[$version]['version'] !== $latestVersion) {
        $bumps[] = sprintf('%s to %s', $version, $latestVersion);
    }

    $versionData = [
        'version' => $latestVersion,
        'latest' => $version == LATEST,
        'variants' => [],
    ];

    foreach (VARIANTS as $variant) {
        foreach (DISTROS as $distro) {
            $tagSuffix = sprintf('%s-%s', $variant, $distro);

            $versionData['variants'][] = $tagSuffix;

            if (!isset(SUBVARIANTS[$variant])) {
                continue;
            }

            foreach (SUBVARIANTS[$variant] as $subvariant) {
                $versionData['variants'][] = sprintf('%s-%s', $tagSuffix, $subvariant);
            }
        }
    }

    $latestVersions[$version] = $versionData;
}

writeVersionsToFile($currentVersions, $latestVersions);

exit_cli(getGithubActionsOutputParams($bumps), status: STATUS_SUCCESS);

function dockerTagExists(string $tag): bool
{
    $resp = request(sprintf('https://hub.docker.com/v2/repositories/library/php/tags/%s', $tag));
    $result = true;

    if ($resp->getCurlErrno() > 0) {
        logError("cURL error (%d): %s", $resp->getCurlErrno(), $resp->getCurlError());
        $result = false;
    }

    if ($resp->getStatus() >= 400) {
        logError("HTTP error: %d", $resp->getStatus());
        $result = false;
    }

    if ($resp->getBody()['errinfo'] ?? false) {
        logError("Docker Hub error: %s", $resp->getBody()['message']);
        $result = false;
    }

    return $result;
}

function getLatestVersion(string $version): false|string
{
    $resp = request('https://www.php.net/releases/index.php', [
        'json' => true,
        'max' => 1,
        'version' => $version,
    ]);

    if ($resp->getCurlErrno() > 0) {
        logError("cURL error (%d): %s", $resp->getCurlErrno(), $resp->getCurlError());
        return false;
    }

    if ($resp->getStatus() >= 400) {
        logError("HTTP error: %d", $resp->getStatus());
        return false;
    }

    if (null === $resp->getBody() || isset($resp->getBody()['error'])) {
        return false;
    }

    return array_key_first($resp->getBody());
}

function writeVersionsToFile(array $currentVersions, array $latestVersions): void
{
    $currentVersions = array_merge($currentVersions, $latestVersions);

    $versions = [];
    foreach (VERSIONS as $version) {
        if (!isset($currentVersions[$version])) {
            continue;
        }

        $versions[$version] = $currentVersions[$version];
    }

    $result = file_put_contents(
        VERSIONS_FILE,
        json_encode($latestVersions, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n",
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
