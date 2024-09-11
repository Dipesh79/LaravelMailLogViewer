<?php

namespace Dipesh79\LaravelMailLogViewer\Http\Controllers;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\File;
use Illuminate\View\View;

class MailLogViewerController extends Controller
{
    /**
     * Display the mail log viewer.
     *
     * @return View
     */
    public function index(): View
    {
        $rawEmails = $this->extractEmailsFromLogs();
        $emails = array_map([$this, 'parseEmail'], $rawEmails);

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
     * Extract emails from multiple log files.
     *
     * @return array
     */
    private function extractEmailsFromLogs(): array
    {
        $logDirectory = storage_path('logs');
        $logFiles = File::files($logDirectory);
        $emails = [];

        foreach ($logFiles as $logFile) {
            $logContent = File::get($logFile);
            $pattern = '/From:.*?--\w+--/s';
            preg_match_all($pattern, $logContent, $matches);
            $emails = array_merge($emails, array_unique($matches[0]));
        }
        return $emails;
    }

    /**
     * Parse a raw email string into headers and body.
     *
     * @param string $rawEmail
     * @return array
     */
    private function parseEmail(string $rawEmail): array
    {
        list($headerPart, $bodyPart) = explode("\r\n\r\n", $rawEmail, 2);
        $headers = [];
        foreach (explode("\r\n", $headerPart) as $line) {
            if (strpos($line, ':') !== false) {
                list($key, $value) = explode(':', $line, 2);
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
