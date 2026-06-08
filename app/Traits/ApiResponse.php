<?php

namespace App\Traits;

use CodeIgniter\HTTP\ResponseInterface;

/**
 * 표준 JSON 응답 트레이트.
 * API 컨트롤러에서 use 하여 일관된 응답 형식을 제공한다.
 *
 * 성공: { success: true,  data: mixed, message: string|null, meta?: object }
 * 실패: { success: false, data: null,  message: string, errors?: array }
 */
trait ApiResponse
{
    protected function success(mixed $data = null, ?string $message = null, int $status = 200): ResponseInterface
    {
        return $this->response->setStatusCode($status)->setJSON([
            'success' => true,
            'data'    => $data,
            'message' => $message,
        ]);
    }

    protected function created(mixed $data = null, ?string $message = null): ResponseInterface
    {
        return $this->success($data, $message, 201);
    }

    protected function successList(array $data, array $meta): ResponseInterface
    {
        return $this->response->setStatusCode(200)->setJSON([
            'success' => true,
            'data'    => $data,
            'meta'    => $meta,
        ]);
    }

    protected function fail(string $message, int $status = 400, array $errors = []): ResponseInterface
    {
        $body = [
            'success' => false,
            'data'    => null,
            'message' => $message,
        ];
        if ($errors) {
            $body['errors'] = $errors;
        }
        return $this->response->setStatusCode($status)->setJSON($body);
    }

    protected function failUnauthorized(string $message = '인증이 필요합니다.'): ResponseInterface
    {
        return $this->fail($message, 401);
    }

    protected function failForbidden(string $message = '권한이 없습니다.'): ResponseInterface
    {
        return $this->fail($message, 403);
    }

    protected function failNotFound(string $message = '리소스를 찾을 수 없습니다.'): ResponseInterface
    {
        return $this->fail($message, 404);
    }

    protected function failValidation(array $errors, string $message = '입력값을 확인해주세요.'): ResponseInterface
    {
        return $this->fail($message, 422, $errors);
    }

    protected function failConflict(string $message, array $errors = []): ResponseInterface
    {
        return $this->fail($message, 409, $errors);
    }

    protected function failServerError(string $message = '서버 오류가 발생했습니다.'): ResponseInterface
    {
        return $this->fail($message, 500);
    }
}
