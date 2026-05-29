<?php

namespace App\Controllers;

use App\Models\FileModel;
use CodeIgniter\Controller;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\HTTP\RedirectResponse;

class FileController extends Controller
{
    /** 파일 다운로드 */
    public function download(int $idx): mixed
    {
        $model = new FileModel();
        $file  = $model->find($idx);

        if (! $file) {
            throw new PageNotFoundException();
        }

        $path = FileModel::storagePath($file['conversion_filename']);

        if (! is_file($path)) {
            return redirect()->back()->with('error', '파일을 찾을 수 없습니다.');
        }

        $filename = $file['original_filename'];
        $mime     = $file['mime'] ?: 'application/octet-stream';

        return $this->response
            ->setHeader('Content-Type', $mime)
            ->setHeader('Content-Disposition', 'attachment; filename="' . rawurlencode($filename) . '"')
            ->setHeader('Content-Length', (string) filesize($path))
            ->setHeader('Cache-Control', 'no-store')
            ->setBody(file_get_contents($path));
    }

    /** 수정 중 단일 파일 삭제 (AJAX or redirect) */
    public function delete(int $idx): RedirectResponse
    {
        $model = new FileModel();
        $file  = $model->find($idx);

        if (! $file) {
            throw new PageNotFoundException();
        }

        // 파일 소유자 또는 최고관리자만 삭제 가능
        $userIdx = (int) session()->get('user_idx');
        $isAdmin = session()->get('group_name') === '최고관리자';

        if (! $isAdmin && $file['user_idx'] != $userIdx) {
            return redirect()->back()->with('error', '삭제 권한이 없습니다.');
        }

        $model->deleteFile($idx);

        return redirect()->back()->with('success', '파일이 삭제되었습니다.');
    }
}
