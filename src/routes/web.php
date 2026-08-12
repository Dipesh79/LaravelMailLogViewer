<?php

use Dipesh79\LaravelMailLogViewer\Http\Controllers\MailLogViewerController;
use Illuminate\Support\Facades\Route;

/**
 * Register a route for the email logs.
 *
 * This route maps the URL '/email-logs' to the 'index' method
 * of the MailLogViewerController. It is named 'email.logs.index'.
 */
if (config('app.env') !== 'production') {
    Route::get('/email-logs', [MailLogViewerController::class, 'index'])->name('email.logs.index')->middleware(config('laravel-mail-log-viewer.middleware', ['web']));
    Route::get('/email-logs/show', [MailLogViewerController::class, 'show'])->name('email.logs.show')->middleware(config('laravel-mail-log-viewer.middleware', ['web']));
}
