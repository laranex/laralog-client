<?php

namespace Laranex\LaralogClient;

use Laranex\LaralogClient\Exceptions\LaralogClientHttpException;
use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Level;
use Monolog\LogRecord;

class LaralogClient extends AbstractProcessingHandler
{
    public function __construct(int|string|Level $level = Level::Debug, bool $bubble = true)
    {
        parent::__construct($level, $bubble);
    }

    /**
     * @throws LaralogClientHttpException
     */
    public function write(LogRecord $record): void
    {
        $data = [
            'level' => $record->level->getName(),
            'message' => $record->message,
            'context' => $record->context,
        ];

        $url = config('laralog-client.base_url').'/api/logs';
        $headers = [
            'X-TEAM-SECRET-KEY: '.config('laralog-client.team_secret_key'),
            'Content-Type: application/json',
            'x-requested-with: XMLHttpRequest',
        ];

        $options = [
            'http' => [
                'header' => implode("\r\n", $headers),
                'method' => 'POST',
                'content' => json_encode($data),
                'ignore_errors' => true,
            ],
        ];

        $context = stream_context_create($options);
        $response = file_get_contents($url, false, $context);

        if ($response === false) {
            throw new LaralogClientHttpException();
        }

        $httpStatusCode = intval(explode(' ', $http_response_header[0])[1]);
        if ($httpStatusCode < 200 || $httpStatusCode > 299) {
            throw new LaralogClientHttpException("Request to laralog server failed with code $httpStatusCode & response $response");
        }
    }
}
