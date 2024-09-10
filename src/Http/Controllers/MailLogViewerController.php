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
        $rawEmails = $this->extractEmailsFromLog();
        $emails = array_map([$this, 'parseEmail'], $rawEmails);
        $emails = array_reverse($emails);

        $emailsCollection = collect($emails);

        $perPage = config('laravel-mail-log-viewer.pagination', 6);
        $currentPage = LengthAwarePaginator::resolveCurrentPage();
        $currentPageItems = $emailsCollection->slice(($currentPage - 1) * $perPage, $perPage)->all();
        $paginatedEmails = new LengthAwarePaginator($currentPageItems, $emailsCollection->count(), $perPage);
        $paginatedEmails->setPath(request()->url());

        return view('emaillogviewer::index', ['emails' => $paginatedEmails]);
    }

    /**
     * Extract emails from the log file.
     *
     * @return array
     */
    private function extractEmailsFromLog(): array
    {
        $logPath = storage_path('logs/laravel.log');
        if (!File::exists($logPath)) {
            return [];
        }

        $logContent = File::get($logPath);
        $pattern = '/From:.*?--\w+--/s';
        preg_match_all($pattern, $logContent, $matches);

        return array_unique($matches[0]);
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

        return ['headers' => $headers, 'body' => $htmlBody];
    }
}
