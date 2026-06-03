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
            return $this->failValidation([], lang('Api.cms_page_required'));
        }
        if (! preg_match('/^[a-z0-9\-]+$/', $slug)) {
            return $this->failValidation([], lang('Api.cms_page_slug_invalid'));
        }

        $exists = $db->table('tb_cms_page')->where('slug', $slug)->countAllResults();
        if ($exists) {
            return $this->failValidation([], lang('Api.cms_page_slug_duplicate'));
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

        return $this->created(['idx' => $db->insertID()], lang('Api.cms_page_created'));
    }

    public function update(int $idx): ResponseInterface
    {
        $db   = \Config\Database::connect();
        $page = $db->table('tb_cms_page')->where('idx', $idx)->get()->getRowArray();
        if (! $page) {
            return $this->failNotFound(lang('Api.cms_page_not_found'));
        }

        $body = (array) $this->request->getJSON(true);

        $slug     = trim((string) ($body['slug']     ?? $page['slug']));
        $title    = trim((string) ($body['title']    ?? $page['title']));
        $contents = (string) ($body['contents'] ?? $page['contents']);
        $status   = isset($body['status']) ? (int) (bool) $body['status'] : (int) $page['status'];

        if (! $slug || ! $title || $contents === '') {
            return $this->failValidation([], lang('Api.cms_page_required'));
        }
        if (! preg_match('/^[a-z0-9\-]+$/', $slug)) {
            return $this->failValidation([], lang('Api.cms_page_slug_invalid'));
        }
        if ($slug !== $page['slug']) {
            $exists = $db->table('tb_cms_page')->where('slug', $slug)->where('idx !=', $idx)->countAllResults();
            if ($exists) {
                return $this->failValidation([], lang('Api.cms_page_slug_duplicate'));
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

        return $this->success(null, lang('Api.cms_page_updated'));
    }

    public function delete(int $idx): ResponseInterface
    {
        $db   = \Config\Database::connect();
        $page = $db->table('tb_cms_page')->where('idx', $idx)->get()->getRowArray();
        if (! $page) {
            return $this->failNotFound(lang('Api.cms_page_not_found'));
        }

        $db->table('tb_cms_page')->where('idx', $idx)->delete();

        return $this->success(null, lang('Api.cms_page_deleted'));
    }
}
