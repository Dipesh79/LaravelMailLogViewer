<?php

use Dipesh79\LaravelMailLogViewer\Http\Controllers\MailLogViewerController;
use Illuminate\Support\Facades\Route;

/**
 * Register a route for the email logs.
 *
 * This route maps the URL '/email-logs' to the 'index' method
 * of the MailLogViewerController. It is named 'email.logs.index'.
 */
Route::get('/email-logs', [MailLogViewerController::class, 'index'])->name('email.logs.index');
