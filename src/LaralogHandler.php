<?php

declare(strict_types=1);

namespace Laranex\LaralogClient;

use Monolog\Formatter\FormatterInterface;
use Monolog\Formatter\NormalizerFormatter;
use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Level;
use Monolog\Logger;
use Monolog\LogRecord;
use Throwable;

/**
 * Monolog handler that ships every record to a Laralog server.
 *
 * Written against Monolog 3 (LogRecord objects) but also accepts Monolog 2
 * style array records, so widening the Monolog constraint stays trivial.
 *
 * By default a failure to reach the Laralog server never escapes into the
 * code that logged: the first failure is written to PHP's error log
 * (stderr on the CLI) and later ones are dropped silently.
 */
class LaralogHandler extends AbstractProcessingHandler
{
    private bool $failureReported = false;

    public function __construct(
        private readonly LaralogClient $client,
        int|string|Level $level = Logger::DEBUG,
        bool $bubble = true,
        private readonly bool $ignoreExceptions = true,
    ) {
        parent::__construct($level, $bubble);
    }

    public function client(): LaralogClient
    {
        return $this->client;
    }

    /**
     * Whether failures to ship a record are swallowed instead of thrown.
     */
    public function ignoresExceptions(): bool
    {
        return $this->ignoreExceptions;
    }

    /**
     * @param  array<string, mixed>|LogRecord  $record
     */
    protected function write(array|LogRecord $record): void
    {
        try {
            $this->client->send($this->payload($record));
        } catch (Throwable $exception) {
            if (! $this->ignoreExceptions) {
                throw $exception;
            }

            $this->reportFailure($exception);
        }
    }

    /**
     * Report a swallowed failure once per handler so a down server cannot flood the error log.
     *
     * PHP's error_log() is used instead of a Laravel logger to avoid logging recursively.
     */
    protected function reportFailure(Throwable $exception): void
    {
        if ($this->failureReported) {
            return;
        }

        $this->failureReported = true;

        error_log(sprintf(
            '[laralog-client] Could not ship a log record to Laralog (%s: %s). Further failures from this handler are ignored.',
            $exception::class,
            $exception->getMessage(),
        ));
    }

    /**
     * Normalise context (exceptions, dates, objects) into JSON-safe values.
     */
    protected function getDefaultFormatter(): FormatterInterface
    {
        return new NormalizerFormatter;
    }

    /**
     * Build the JSON body the Laralog server expects.
     *
     * The normalised record is preferred; the raw record is used when a
     * custom formatter did not produce an array.
     *
     * @param  array<string, mixed>|LogRecord  $record
     * @return array{level: string, message: string, context: array<mixed>}
     */
    private function payload(array|LogRecord $record): array
    {
        $formatted = $this->field($record, 'formatted');
        $source = is_array($formatted) ? $formatted : $record;

        $context = $this->field($source, 'context');

        return [
            'level' => (string) $this->field($source, 'level_name'),
            'message' => (string) $this->field($source, 'message'),
            'context' => is_array($context) ? $context : [],
        ];
    }

    /**
     * Read one record field on either Monolog version.
     *
     * @param  array<string, mixed>|LogRecord  $record
     * @param  'formatted'|'level_name'|'message'|'context'  $key
     */
    private function field(array|LogRecord $record, string $key): mixed
    {
        if ($record instanceof LogRecord) {
            return match ($key) {
                'formatted' => $record->formatted,
                'level_name' => $record->level->getName(),
                'message' => $record->message,
                'context' => $record->context,
            };
        }

        return $record[$key] ?? null;
    }
}
