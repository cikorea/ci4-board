<?php

namespace App\Controllers\Api\V1\Admin\Cms;

use App\Controllers\Api\V1\Admin\BaseAdminApiController;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * 관리자 CMS 페이지 API
 *
 * GET    /api/admin/v1/cms/pages
 * POST   /api/admin/v1/cms/pages
 * PUT    /api/admin/v1/cms/pages/:idx
 * DELETE /api/admin/v1/cms/pages/:idx
 */
class PageController extends BaseAdminApiController
{
    public function index(): ResponseInterface
    {
        $db      = \Config\Database::connect();
        $keyword = trim($this->request->getGet('keyword') ?? '');
        $status  = $this->request->getGet('status');
        $perPage = 20;
        $page    = max(1, (int) ($this->request->getGet('page') ?? 1));

        $builder = $db->table('tb_cms_page')
            ->select('idx, slug, title, status, timestamp_insert, timestamp_update')
            ->orderBy('idx', 'DESC');

        if ($keyword !== '') {
            $builder->groupStart()
                ->like('title', $keyword)
                ->orLike('slug', $keyword)
                ->groupEnd();
        }
        if ($status !== null && $status !== '') {
            $builder->where('status', (int) $status);
        }

        $total = $builder->countAllResults(false);
        $pages = $builder->limit($perPage, ($page - 1) * $perPage)->get()->getResultArray();

        return $this->successList($pages, [
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

        $slug     = trim((string) ($body['slug']     ?? ''));
        $title    = trim((string) ($body['title']    ?? ''));
        $contents = (string) ($body['contents'] ?? '');
        $status   = (int) (bool) ($body['status']   ?? false);

        if (! $slug || ! $title || $contents === '') {
            return $this->failValidation([], 'slug, 제목, 내용은 필수입니다.');
        }
        if (! preg_match('/^[a-z0-9\-]+$/', $slug)) {
            return $this->failValidation([], 'slug는 영문 소문자, 숫자, 하이픈(-)만 사용할 수 있습니다.');
        }

        $exists = $db->table('tb_cms_page')->where('slug', $slug)->countAllResults();
        if ($exists) {
            return $this->failValidation([], '이미 사용 중인 slug입니다.');
        }

        // TODO(#25): $contents = sanitize_html($contents); — XSS 방어 처리

        $db->table('tb_cms_page')->insert([
            'slug'             => $slug,
            'title'            => $title,
            'contents'         => $contents,
            'status'           => $status,
            'exec_user_idx'    => $this->getUserIdx(),
            'client_ip'        => $this->request->getIPAddress(),
            'timestamp_insert' => time(),
        ]);

        return $this->created(['idx' => $db->insertID()], '페이지가 생성되었습니다.');
    }

    public function update(int $idx): ResponseInterface
    {
        $db   = \Config\Database::connect();
        $page = $db->table('tb_cms_page')->where('idx', $idx)->get()->getRowArray();
        if (! $page) {
            return $this->failNotFound('페이지를 찾을 수 없습니다.');
        }

        $body = (array) $this->request->getJSON(true);

        $slug     = trim((string) ($body['slug']     ?? $page['slug']));
        $title    = trim((string) ($body['title']    ?? $page['title']));
        $contents = (string) ($body['contents'] ?? $page['contents']);
        $status   = isset($body['status']) ? (int) (bool) $body['status'] : (int) $page['status'];

        if (! $slug || ! $title || $contents === '') {
            return $this->failValidation([], 'slug, 제목, 내용은 필수입니다.');
        }
        if (! preg_match('/^[a-z0-9\-]+$/', $slug)) {
            return $this->failValidation([], 'slug는 영문 소문자, 숫자, 하이픈(-)만 사용할 수 있습니다.');
        }
        if ($slug !== $page['slug']) {
            $exists = $db->table('tb_cms_page')->where('slug', $slug)->where('idx !=', $idx)->countAllResults();
            if ($exists) {
                return $this->failValidation([], '이미 사용 중인 slug입니다.');
            }
        }

        // TODO(#25): $contents = sanitize_html($contents); — XSS 방어 처리

        $db->table('tb_cms_page')->where('idx', $idx)->update([
            'slug'             => $slug,
            'title'            => $title,
            'contents'         => $contents,
            'status'           => $status,
            'exec_user_idx'    => $this->getUserIdx(),
            'client_ip'        => $this->request->getIPAddress(),
            'timestamp_update' => time(),
        ]);

        return $this->success(null, '페이지가 수정되었습니다.');
    }

    public function delete(int $idx): ResponseInterface
    {
        $db   = \Config\Database::connect();
        $page = $db->table('tb_cms_page')->where('idx', $idx)->get()->getRowArray();
        if (! $page) {
            return $this->failNotFound('페이지를 찾을 수 없습니다.');
        }

        $db->table('tb_cms_page')->where('idx', $idx)->delete();

        return $this->success(null, '페이지가 삭제되었습니다.');
    }
}
