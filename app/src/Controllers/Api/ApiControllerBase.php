<?php
namespace SWCPR\Controllers\Api;

use JetBrains\PhpStorm\NoReturn;

abstract class ApiControllerBase {
    #[NoReturn] protected function respondJson(mixed $data, int $httpStatusCode = 200): void {
        http_response_code($httpStatusCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data);
        die();
    }

    #[NoReturn] protected function respondStatusCode(int $httpStatusCode): void {
        http_response_code($httpStatusCode);
        die();
    }

    #[NoReturn] protected function respondWithError(int $httpStatusCode, string $message): void
    {
        http_response_code($httpStatusCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'error' => $message
        ]);
        die();
    }
}