<?php

return [
    /**
     * Route configuration
     *
     * The configuration for the route of the package.
     * This specifies the URL for the dashboard.
     *
     */
    'dashboard_url' => '/home',

    /**
     * Pagination configuration
     *
     * The configuration for the pagination for the mail log viewer.
     * This specifies the number of items per page.
     *
     */
    'pagination' => 5,

    /**
     * Primary color configuration
     *
     * The configuration for the primary color for the mail log viewer.
     * This specifies the primary color used in the UI.
     *
     */
    'primary-color' => '#ff2d20',

    /**
     * Middleware configuration
     *
     * The configuration for the middleware for the mail log viewer
     */
    'middleware' => ['web'],
];
