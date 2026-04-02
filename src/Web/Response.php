<?php

declare(strict_types=1);

namespace Mileena\Web;

use JsonException;
use RuntimeException;

final class Response
{
    private const DEFAULT_SUCCESS_STATUS = 200;
    private const DEFAULT_ERROR_STATUS = 400;
    private const DEFAULT_FLAGS = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR;

    private static bool $sent = false;

    private function __construct() {}

    // ============ http ============

    public static function sendStatus(int $statusCode): void
    {
        if (self::$sent) {
            throw new RuntimeException('Response already sent');
        }

        if (headers_sent()) {
            throw new RuntimeException('Headers already sent');
        }

        http_response_code($statusCode);
        self::$sent = true;
    }

    // ============ jsons ============

    public static function success(array|object $data = [], string $message = '', int $statusCode = self::DEFAULT_SUCCESS_STATUS): void
    {
        self::send([
            'success' => true,
            'data' => $data,
            'message' => $message,
        ], $statusCode);
    }

    public static function error(string $message, int $statusCode = self::DEFAULT_ERROR_STATUS, array $errors = []): void
    {
        $response = [
            'success' => false,
            'error' => $message,
        ];

        if ($errors !== []) {
            $response['errors'] = $errors;
        }

        self::send($response, $statusCode, self::isAjax());
    }

    public static function badRequest(string $message = 'Bad request', array $errors = []): void
    {
        self::error($message, 400, $errors);
    }

    public static function notFound(string $message = 'Not found'): void
    {
        self::error($message, 404);
    }

    public static function forbidden(string $message = 'Forbidden'): void
    {
        self::error($message, 403);
    }

    public static function unauthorized(string $message = 'Unauthorized'): void
    {
        self::error($message, 401);
    }

    // ============ Redirects ============

    public static function redirect(string $url, int $statusCode = 302): void
    {
        if (self::isAjax()) {
            self::send([
                'success' => true,
                'redirect' => $url,
            ], self::DEFAULT_SUCCESS_STATUS);
        } else {
            if (headers_sent()) {
                throw new RuntimeException('Cannot redirect, headers already sent');
            }

            http_response_code($statusCode);
            header("Location: $url");
            self::$sent = true;
        }
    }

    public static function back(string $default = '/'): void
    {
        $referer = $_SERVER['HTTP_REFERER'] ?? $default;
        self::redirect($referer);
    }

    // ============ helpers ============

    public static function isSent(): bool
    {
        return self::$sent;
    }

    private static function isAjax(): bool
    {
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH'])
            && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
            return true;
        }

        $secFetchMode = $_SERVER['HTTP_SEC_FETCH_MODE'] ?? '';

        if (in_array($secFetchMode, ['cors', 'same-origin'], true)) {
            return true;
        }

        $accept = $_SERVER['HTTP_ACCEPT'] ?? '';

        if (strpos($accept, 'application/json') !== false) {
            return true;
        }

        return false;
    }

    private static function send(array $data, int $statusCode, bool $withJson = true): void
    {
        if (self::$sent) {
            throw new RuntimeException('Response already sent');
        }

        if (headers_sent()) {
            throw new RuntimeException('Headers already sent, cannot send response');
        }

        http_response_code($statusCode);

        if ($withJson) {
            header('Content-Type: application/json; charset=utf-8');
            header('X-Content-Type-Options: nosniff');

            try {
                echo json_encode($data, self::DEFAULT_FLAGS);
                self::$sent = true;
            } catch (JsonException $e) {
                throw new RuntimeException('Failed to encode JSON response: ' . $e->getMessage());
            }
        }
    }
}
