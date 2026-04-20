<?php
if (!defined('API_BOOTSTRAP_LOADED')) {
    define('API_BOOTSTRAP_LOADED', true);

    if (!headers_sent()) {
        header('Content-Type: application/json; charset=utf-8');
    }

    ini_set('display_errors', '0');
    ini_set('html_errors', '0');
    error_reporting(E_ALL);

    set_exception_handler(function ($e) {
        if (!headers_sent()) {
            http_response_code(500);
            header('Content-Type: application/json; charset=utf-8');
        }
        echo json_encode([
            'success' => false,
            'message' => 'Excepcion en servidor',
            'error' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ]);
        exit;
    });

    set_error_handler(function ($severity, $message, $file, $line) {
        throw new ErrorException($message, 0, $severity, $file, $line);
    });

    register_shutdown_function(function () {
        $error = error_get_last();
        if ($error === null) {
            return;
        }

        $fatalTypes = [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR];
        if (!in_array($error['type'], $fatalTypes, true)) {
            return;
        }

        if (!headers_sent()) {
            http_response_code(500);
            header('Content-Type: application/json; charset=utf-8');
        }

        echo json_encode([
            'success' => false,
            'message' => 'Fatal en servidor',
            'error' => $error['message'] ?? 'Error fatal',
            'file' => $error['file'] ?? '',
            'line' => $error['line'] ?? 0
        ]);
    });
}
?>
