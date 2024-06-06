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

function logError(string $format, mixed ...$values): void
{
    fprintf(STDERR, "Error: %s\n", sprintf($format, ...$values));
}

function request(string $url, array $query = []): Response
{
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, sprintf("%s?%s", $url, http_build_query($query)));
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

    $data = curl_exec($curl);
    $status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    $error = curl_error($curl);
    $errno = curl_errno($curl);

    curl_close($curl);

    $body = null;
    if (!empty($data) && json_validate($data)) {
        $body = json_decode($data, true);
    }

    return new Response($errno, $error, $status, $body);
}

class Response
{
    public function __construct(
        private readonly int    $curlErrno,
        private readonly string $curlError,
        private readonly int    $status,
        private readonly ?array  $body = null,
    ) {}

    public function getCurlErrno(): int
    {
        return $this->curlErrno;
    }

    public function getCurlError(): string
    {
        return $this->curlError;
    }

    public function getStatus(): int
    {
        return $this->status;
    }

    public function getBody(): ?array
    {
        return $this->body;
    }
}

if (!function_exists('json_validate')) {
    /**
     * Polyfill for json_validate function.
     */
    function json_validate(string $json, int $depth = 512, int $flags = 0): bool
    {
        json_decode($json, null, $depth, $flags);
        return \JSON_ERROR_NONE === json_last_error();
    }
}
