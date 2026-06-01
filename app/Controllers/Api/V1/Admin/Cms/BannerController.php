<?php

namespace App\Controllers\Api\V1\Admin\Cms;

use App\Controllers\Api\V1\Admin\BaseAdminApiController;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * 관리자 CMS 배너 API
 *
 * GET    /api/admin/v1/cms/banners
 * POST   /api/admin/v1/cms/banners
 * PUT    /api/admin/v1/cms/banners/:idx
 * DELETE /api/admin/v1/cms/banners/:idx
 */
class BannerController extends BaseAdminApiController
{
    public function index(): ResponseInterface
    {
        $db       = \Config\Database::connect();
        $position = trim($this->request->getGet('position') ?? '');

        $builder = $db->table('tb_cms_banner')
            ->select('idx, position, image_path, link_url, start_at, end_at, sequence, is_used, timestamp_insert')
            ->orderBy('position', 'ASC')
            ->orderBy('sequence', 'ASC');

        if ($position !== '') {
            $builder->where('position', $position);
        }

        return $this->success($builder->get()->getResultArray());
    }

    public function create(): ResponseInterface
    {
        $db   = \Config\Database::connect();
        $body = (array) $this->request->getJSON(true);

        $position   = trim((string) ($body['position']   ?? ''));
        $imagePath  = trim((string) ($body['image_path'] ?? ''));

        if (! $position || ! $imagePath) {
            return $this->failValidation([], '위치(position)와 이미지 경로(image_path)는 필수입니다.');
        }

        $db->table('tb_cms_banner')->insert([
            'position'         => $position,
            'image_path'       => $imagePath,
            'link_url'         => trim((string) ($body['link_url'] ?? '')) ?: null,
            'start_at'         => $this->parseTimestamp($body['start_at'] ?? null),
            'end_at'           => $this->parseTimestamp($body['end_at']   ?? null),
            'sequence'         => max(0, (int) ($body['sequence'] ?? 0)),
            'is_used'          => isset($body['is_used']) ? (int) (bool) $body['is_used'] : 1,
            'exec_user_idx'    => $this->getUserIdx(),
            'client_ip'        => $this->request->getIPAddress(),
            'timestamp_insert' => time(),
        ]);

        return $this->created(['idx' => $db->insertID()], '배너가 생성되었습니다.');
    }

    public function update(int $idx): ResponseInterface
    {
        $db     = \Config\Database::connect();
        $banner = $db->table('tb_cms_banner')->where('idx', $idx)->get()->getRowArray();
        if (! $banner) {
            return $this->failNotFound('배너를 찾을 수 없습니다.');
        }

        $body = (array) $this->request->getJSON(true);

        $position  = trim((string) ($body['position']   ?? $banner['position']));
        $imagePath = trim((string) ($body['image_path'] ?? $banner['image_path']));

        if (! $position || ! $imagePath) {
            return $this->failValidation([], '위치(position)와 이미지 경로(image_path)는 필수입니다.');
        }

        $db->table('tb_cms_banner')->where('idx', $idx)->update([
            'position'         => $position,
            'image_path'       => $imagePath,
            'link_url'         => array_key_exists('link_url', $body)
                                    ? (trim((string) $body['link_url']) ?: null)
                                    : $banner['link_url'],
            'start_at'         => array_key_exists('start_at', $body)
                                    ? $this->parseTimestamp($body['start_at'])
                                    : $banner['start_at'],
            'end_at'           => array_key_exists('end_at', $body)
                                    ? $this->parseTimestamp($body['end_at'])
                                    : $banner['end_at'],
            'sequence'         => isset($body['sequence']) ? max(0, (int) $body['sequence']) : $banner['sequence'],
            'is_used'          => isset($body['is_used']) ? (int) (bool) $body['is_used'] : $banner['is_used'],
            'exec_user_idx'    => $this->getUserIdx(),
            'client_ip'        => $this->request->getIPAddress(),
            'timestamp_update' => time(),
        ]);

        return $this->success(null, '배너가 수정되었습니다.');
    }

    public function delete(int $idx): ResponseInterface
    {
        $db     = \Config\Database::connect();
        $banner = $db->table('tb_cms_banner')->where('idx', $idx)->get()->getRowArray();
        if (! $banner) {
            return $this->failNotFound('배너를 찾을 수 없습니다.');
        }

        $db->table('tb_cms_banner')->where('idx', $idx)->delete();

        return $this->success(null, '배너가 삭제되었습니다.');
    }

    private function parseTimestamp(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }
        // Unix timestamp 정수로 전달되는 경우
        if (is_numeric($value)) {
            return (int) $value;
        }
        // ISO 8601 문자열 (e.g. "2026-07-01T00:00:00") 처리
        $ts = strtotime((string) $value);
        return $ts !== false ? $ts : null;
    }
}
