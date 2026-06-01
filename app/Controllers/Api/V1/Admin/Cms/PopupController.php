<?php

namespace App\Controllers\Api\V1\Admin\Cms;

use App\Controllers\Api\V1\Admin\BaseAdminApiController;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * 관리자 CMS 팝업 API
 *
 * GET    /api/admin/v1/cms/popups
 * POST   /api/admin/v1/cms/popups
 * PUT    /api/admin/v1/cms/popups/:idx
 * DELETE /api/admin/v1/cms/popups/:idx
 */
class PopupController extends BaseAdminApiController
{
    public function index(): ResponseInterface
    {
        $db      = \Config\Database::connect();
        $perPage = 20;
        $page    = max(1, (int) ($this->request->getGet('page') ?? 1));

        $builder = $db->table('tb_cms_popup')
            ->select('idx, title, position, start_at, end_at, is_used, timestamp_insert')
            ->orderBy('idx', 'DESC');

        $isUsed = $this->request->getGet('is_used');
        if ($isUsed !== null && $isUsed !== '') {
            $builder->where('is_used', (int) $isUsed);
        }

        $total  = $builder->countAllResults(false);
        $popups = $builder->limit($perPage, ($page - 1) * $perPage)->get()->getResultArray();

        return $this->successList($popups, [
            'page'      => $page,
            'per_page'  => $perPage,
            'total'     => $total,
            'last_page' => (int) ceil($total / $perPage) ?: 1,
        ]);
    }

    public function create(): ResponseInterface
    {
        $db   = \Config\Database::connect();
        $body = (array) $this->request->getJSON(true);

        $title    = trim((string) ($body['title']    ?? ''));
        $contents = (string) ($body['contents'] ?? '');

        if (! $title || $contents === '') {
            return $this->failValidation([], '제목과 내용은 필수입니다.');
        }

        // TODO(#25): $contents = sanitize_html($contents); — XSS 방어 처리

        $db->table('tb_cms_popup')->insert([
            'title'            => $title,
            'contents'         => $contents,
            'position'         => trim((string) ($body['position'] ?? '')),
            'start_at'         => $this->parseTimestamp($body['start_at'] ?? null),
            'end_at'           => $this->parseTimestamp($body['end_at']   ?? null),
            'is_used'          => isset($body['is_used']) ? (int) (bool) $body['is_used'] : 1,
            'exec_user_idx'    => $this->getUserIdx(),
            'client_ip'        => $this->request->getIPAddress(),
            'timestamp_insert' => time(),
        ]);

        return $this->created(['idx' => $db->insertID()], '팝업이 생성되었습니다.');
    }

    public function show(int $idx): ResponseInterface
    {
        $db    = \Config\Database::connect();
        $popup = $db->table('tb_cms_popup')->where('idx', $idx)->get()->getRowArray();
        if (! $popup) {
            return $this->failNotFound('팝업을 찾을 수 없습니다.');
        }

        return $this->success($popup);
    }

    public function update(int $idx): ResponseInterface
    {
        $db    = \Config\Database::connect();
        $popup = $db->table('tb_cms_popup')->where('idx', $idx)->get()->getRowArray();
        if (! $popup) {
            return $this->failNotFound('팝업을 찾을 수 없습니다.');
        }

        $body = (array) $this->request->getJSON(true);

        $title    = trim((string) ($body['title']    ?? $popup['title']));
        $contents = (string) ($body['contents'] ?? $popup['contents']);

        if (! $title || $contents === '') {
            return $this->failValidation([], '제목과 내용은 필수입니다.');
        }

        // TODO(#25): $contents = sanitize_html($contents); — XSS 방어 처리

        $db->table('tb_cms_popup')->where('idx', $idx)->update([
            'title'            => $title,
            'contents'         => $contents,
            'position'         => array_key_exists('position', $body)
                                    ? trim((string) $body['position'])
                                    : $popup['position'],
            'start_at'         => array_key_exists('start_at', $body)
                                    ? $this->parseTimestamp($body['start_at'])
                                    : $popup['start_at'],
            'end_at'           => array_key_exists('end_at', $body)
                                    ? $this->parseTimestamp($body['end_at'])
                                    : $popup['end_at'],
            'is_used'          => isset($body['is_used']) ? (int) (bool) $body['is_used'] : $popup['is_used'],
            'exec_user_idx'    => $this->getUserIdx(),
            'client_ip'        => $this->request->getIPAddress(),
            'timestamp_update' => time(),
        ]);

        return $this->success(null, '팝업이 수정되었습니다.');
    }

    public function delete(int $idx): ResponseInterface
    {
        $db    = \Config\Database::connect();
        $popup = $db->table('tb_cms_popup')->where('idx', $idx)->get()->getRowArray();
        if (! $popup) {
            return $this->failNotFound('팝업을 찾을 수 없습니다.');
        }

        $db->table('tb_cms_popup')->where('idx', $idx)->delete();

        return $this->success(null, '팝업이 삭제되었습니다.');
    }

    private function parseTimestamp(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }
        if (is_numeric($value)) {
            return (int) $value;
        }
        $ts = strtotime((string) $value);
        return $ts !== false ? $ts : null;
    }
}
