<?php

declare(strict_types=1);

const STATUS_SUCCESS = 0;
const STATUS_FAILURE = 1;

const VERSIONS_FILE = 'versions.json';

function exit_cli(string $message = "", int $status = STATUS_FAILURE): void
{
    $stream = STDOUT;
    if ($status > STATUS_SUCCESS) {
        $stream = STDERR;
    }

    fprintf($stream, $message);
    exit($status);
}
