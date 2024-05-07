<?php

declare(strict_types=1);

require(__DIR__ . '/common.php');

if (!file_exists(VERSIONS_FILE)) {
    exit_cli(sprintf("%s file does not exist", VERSIONS_FILE));
}
$versions = json_decode(file_get_contents(VERSIONS_FILE), true);

if (false === getenv('DOCKER_REPOSITORY')) {
    exit_cli("DOCKER_REPOSITORY environment variable is not set\n");
}

$include = [];
foreach ($versions as $majorVersion => $versionData) {
    $version = $versionData['version'];
    $isLatest = $versionData['latest'];

    foreach ($versionData['variants'] as $variant) {
        $tags = getVersionTags($version, $variant, $isLatest);
        $dir = sprintf('./%s/%s', $majorVersion, $variant);
        $include[] = [
            'name' => sprintf('%s-%s', $version, $variant),
            'os' => 'ubuntu-latest',
            'tags' => $tags,
            'runs' => [
                'build' => getBuildCommand($tags, $dir),
                'push' => getPushCommand($tags),
            ],
        ];
    }
}

$strategy = [
    'fail-fast' => false,
    'matrix' => [
        'include' => $include,
    ],
];

exit_cli(json_encode($strategy, JSON_PRETTY_PRINT));

function getVersionTags(string $version, string $variant, bool $latest = false): array
{
    $repository = getenv('DOCKER_REPOSITORY');
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

function getBuildCommand(array $tags, string $dir, string $platform = 'linux/amd64'): string
{
    $tagArgs = implode(' ', array_map(fn($tag) => '--tag ' . $tag, $tags));
    return sprintf('docker build --platform %s %s %s', $platform, $tagArgs, $dir);
}

function getPushCommand(array $tags): string
{
    return implode(' && ', array_map(fn($tag) => sprintf('docker push %s', $tag), $tags));
}
