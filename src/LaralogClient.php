<?php

declare(strict_types=1);

namespace Laranex\LaralogClient;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Factory as HttpFactory;
use Laranex\LaralogClient\Exceptions\LaralogClientHttpException;

/**
 * Posts log records to a Laralog server.
 */
class LaralogClient
{
    public function __construct(
        private readonly HttpFactory $http,
        private readonly string $baseUrl,
        private readonly string $teamSecretKey,
        private readonly int $timeout = 5,
    ) {}

    /**
     * The URL every record is posted to.
     */
    public function endpoint(): string
    {
        return rtrim($this->baseUrl, '/').'/api/logs';
    }

    /**
     * Send one log record to the Laralog server.
     *
     * @param  array{level: string, message: string, context: array<mixed>}  $record
     *
     * @throws LaralogClientHttpException
     */
    public function send(array $record): void
    {
        try {
            $response = $this->http
                ->withHeaders([
                    'X-TEAM-SECRET-KEY' => $this->teamSecretKey,
                    'X-Requested-With' => 'XMLHttpRequest',
                ])
                ->acceptJson()
                ->asJson()
                ->timeout($this->timeout)
                ->post($this->endpoint(), $record);
        } catch (ConnectionException $exception) {
            throw LaralogClientHttpException::connectionFailed($exception);
        }

        if ($response->failed()) {
            throw LaralogClientHttpException::unexpectedStatus($response->status(), $response->body());
        }
    }
}
