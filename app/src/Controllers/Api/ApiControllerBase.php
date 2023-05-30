<?php
namespace SWCPR\Controllers\Api;

abstract class ApiControllerBase {
    protected function respondJson(mixed $data, int $httpStatusCode = 200): void {
        http_response_code($httpStatusCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data);
        die();
    }

    protected function respondStatusCode(int $httpStatusCode): void {
        http_response_code($httpStatusCode);
        die();
    }

    protected function respondWithError(int $httpStatusCode, string $message) {
        http_response_code($httpStatusCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'error' => $message
        ]);
        die();
    }
}