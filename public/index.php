<?php

use App\Kernel;

if (!is_file(dirname(__DIR__) . '/vendor/autoload_runtime.php')) {
    http_response_code(503);
    header('Content-Type: application/json');

    echo json_encode([
        'error' => [
            'code' => 'dependencies_not_installed',
            'message' => 'Composer dependencies are not installed yet.'
        ]
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

    return;
}

require_once dirname(__DIR__) . '/vendor/autoload_runtime.php';

return function (array $context) {
    return new Kernel($context['APP_ENV'], (bool) $context['APP_DEBUG']);
};
