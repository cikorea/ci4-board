<?php

namespace App\Controllers\Api\V1\Cms;

use App\Controllers\Api\V1\BaseApiController;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * 프론트 CMS 배너 API
 *
 * GET /api/v1/cms/banners
 */
class BannerController extends BaseApiController
{
    public function index(): ResponseInterface
    {
        $db       = \Config\Database::connect();
        $now      = time();
        $position = trim($this->request->getGet('position') ?? '');

        $builder = $db->table('tb_cms_banner')
            ->select('idx, position, image_path, link_url, sequence')
            ->where('is_used', 1)
            ->groupStart()
                ->where('start_at IS NULL')
                ->orWhere('start_at <=', $now)
            ->groupEnd()
            ->groupStart()
                ->where('end_at IS NULL')
                ->orWhere('end_at >=', $now)
            ->groupEnd()
            ->orderBy('position', 'ASC')
            ->orderBy('sequence', 'ASC');

        if ($position !== '') {
            $builder->where('position', $position);
        }

        return $this->success($builder->get()->getResultArray());
    }
}
