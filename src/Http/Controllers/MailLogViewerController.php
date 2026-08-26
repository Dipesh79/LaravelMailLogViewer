<?php

namespace Dipesh79\LaravelMailLogViewer\Http\Controllers;

use Generator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\File;
use Illuminate\View\View;

class MailLogViewerController extends Controller
{
    /**
     * Display the mail log viewer.
     */
    public function index(): View
    {
        $emails = $this->buildEmailIndex();

        // Sort emails by timestamp
        usort($emails, function ($a, $b) {
            return $b['timestamp'] <=> $a['timestamp'];
        });

        $emailsCollection = collect($emails);

        $perPage = config('laravel-mail-log-viewer.pagination', 6);
        $currentPage = LengthAwarePaginator::resolveCurrentPage();
        $currentPageItems = $emailsCollection->slice(($currentPage - 1) * $perPage, $perPage)->all();
        $paginatedEmails = new LengthAwarePaginator($currentPageItems, $emailsCollection->count(), $perPage);
        $paginatedEmails->setPath(request()->url());

        return view('emaillogviewer::index', ['emails' => $paginatedEmails]);
    }

    /**
     * Stream the body of a single email identified by file and byte offset.
     */
    public function show(Request $request): JsonResponse
    {
        $file = basename((string) $request->query('file'));
        $offset = (int) $request->query('offset');

        if ($file === '' || $file === '.' || $file === '..') {
            abort(404);
        }

        $path = storage_path('logs'.DIRECTORY_SEPARATOR.$file);

        if (! File::exists($path)) {
            abort(404);
        }

        $rawEmail = $this->readRawEmailAtOffset($path, $offset);

        if ($rawEmail === '') {
            abort(404);
        }

        return response()->json($this->parseEmail($rawEmail));
    }

    /**
     * Build a lightweight metadata index of all emails across log files.
     *
     * Only headers are kept in memory; bodies are streamed on demand.
     */
    private function buildEmailIndex(): array
    {
        $logDirectory = storage_path('logs');
        $logFiles = File::files($logDirectory);
        $emails = [];
        $seen = [];

        foreach ($logFiles as $logFile) {
            $fileName = $logFile->getBasename();
            $current = null;

            foreach ($this->streamLogLines($logFile->getPathname()) as $offset => $line) {
                $content = $this->stripLogPrefix($line);

                if (str_starts_with($content, 'From:')) {
                    $this->finalizeCurrentEmail($emails, $seen, $fileName, $current);

                    $fromOffset = $offset;
                    if (preg_match('/^\[[^\]]+\]\s+\w+\.\w+:\s/', $line, $prefixMatch)) {
                        $fromOffset += strlen($prefixMatch[0]);
                    }

                    $current = [
                        'headers' => [],
                        'timestamp' => null,
                        'file' => $fileName,
                        'offset' => $fromOffset,
                    ];
                } elseif (isset($current)) {
                    if (trim($content) === '') {
                        // Headers are done. Do not collect the body into memory.
                        $this->finalizeCurrentEmail($emails, $seen, $fileName, $current);
                        $current = null;
                    } elseif (str_contains($content, ':')) {
                        [$key, $value] = explode(':', $content, 2);
                        $current['headers'][trim($key)] = trim($value);
                    }
                }
            }

            $this->finalizeCurrentEmail($emails, $seen, $fileName, $current);
            $current = null;
        }

        return $emails;
    }

    /**
     * Stream the lines of a file, yielding each line with its byte offset.
     */
    private function streamLogLines(string $path): Generator
    {
        $handle = fopen($path, 'r');

        if ($handle === false) {
            return;
        }

        try {
            while (! feof($handle)) {
                $offset = ftell($handle);
                $line = fgets($handle);

                if ($line === false) {
                    break;
                }

                yield $offset => $line;
            }
        } finally {
            fclose($handle);
        }
    }

    /**
     * Strip the Laravel log prefix ("[timestamp] channel.level: ") if present.
     */
    private function stripLogPrefix(string $line): string
    {
        $line = rtrim($line, "\r\n");

        if (preg_match('/^\[[^\]]+\]\s+\w+\.\w+:\s(.*)$/', $line, $matches)) {
            return $matches[1];
        }

        return $line;
    }

    /**
     * Resolve the timestamp from the parsed headers.
     */
    private function resolveTimestamp(array $current): int
    {
        $date = $current['headers']['Date'] ?? null;

        if ($date !== null) {
            $timestamp = strtotime($date);

            if ($timestamp !== false) {
                return $timestamp;
            }
        }

        return time();
    }

    /**
     * Push the current email into the index unless it is a duplicate.
     */
    private function finalizeCurrentEmail(array &$emails, array &$seen, string $fileName, ?array $current): void
    {
        if ($current === null || empty($current['headers'])) {
            return;
        }

        $messageId = $current['headers']['Message-ID'] ?? null;
        $key = $messageId !== null ? $messageId : $fileName.':'.$current['offset'];

        if (isset($seen[$key])) {
            return;
        }

        $seen[$key] = true;

        $emails[] = [
            'headers' => $current['headers'],
            'timestamp' => $this->resolveTimestamp($current),
            'file' => $current['file'],
            'offset' => $current['offset'],
        ];
    }

    /**
     * Read the raw email starting at the given byte offset until the closing boundary.
     */
    private function readRawEmailAtOffset(string $path, int $offset): string
    {
        $handle = fopen($path, 'r');

        if ($handle === false) {
            return '';
        }

        try {
            fseek($handle, $offset);

            $raw = '';
            $isFirstLine = true;

            while (! feof($handle)) {
                $line = fgets($handle);

                if ($line === false) {
                    break;
                }

                $content = $this->stripLogPrefix($line);

                if (! $isFirstLine && str_starts_with($content, 'From:')) {
                    break;
                }

                $isFirstLine = false;
                $raw .= $line;

                if (preg_match('/^--[\w-]+--/', $line)) {
                    break;
                }
            }

            return $raw;
        } finally {
            fclose($handle);
        }
    }

    /**
     * Parse a raw email string into headers and body.
     */
    private function parseEmail(string $rawEmail): array
    {
        [$headerPart, $bodyPart] = array_pad(explode("\r\n\r\n", $rawEmail, 2), 2, '');
        $headers = [];
        foreach (explode("\r\n", $headerPart) as $line) {
            if (strpos($line, ':') !== false) {
                [$key, $value] = explode(':', $line, 2);
                $headers[trim($key)] = trim($value);
            }
        }
        preg_match('/Content-Type: text\/html;.*?\r\n\r\n(.*?)\r\n--/s', $bodyPart, $matches);
        $htmlBody = $matches[1] ?? '';

        // Extract timestamp from the Date header
        $timestamp = isset($headers['Date']) ? strtotime($headers['Date']) : time();

        return ['headers' => $headers, 'body' => $htmlBody, 'timestamp' => $timestamp];
    }
}
