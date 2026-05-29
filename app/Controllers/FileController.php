<?php

namespace App\Controllers;

use App\Models\FileModel;
use CodeIgniter\Controller;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\HTTP\RedirectResponse;

/**
 * @api {group} File 파일
 * @apiGroup File
 * @apiDescription 게시글 첨부파일 다운로드 및 삭제를 처리한다.
 */
class FileController extends Controller
{
    /**
     * @api {get} /file/:idx 파일 다운로드
     * @apiGroup File
     * @apiName FileDownload
     * @apiDescription WRITEPATH/uploads/{conversion_filename} 경로의 파일을 스트리밍 다운로드한다.
     *   파일이 DB에 존재하지 않거나 실제 파일이 없으면 각각 404 또는 오류 리다이렉트한다.
     *
     * @apiParam  {Number} idx  파일 idx
     * @apiSuccess {String} file 파일 바이너리 (Content-Disposition: attachment)
     * @apiError {String} notFound           파일 레코드 없음
     * @apiError {String} redirect         실제 파일 없음 (error 플래시 포함)
     */
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

    /**
     * @api {get} /file/:idx/delete 파일 삭제
     * @apiGroup File
     * @apiName FileDelete
     * @apiPermission 파일 소유자 또는 최고관리자
     * @apiDescription DB 레코드와 실제 파일을 모두 삭제한다.
     *
     * @apiParam  {Number} idx  파일 idx
     * @apiSuccess {String} redirect  이전 페이지로 이동
     * @apiError {String} notFound              파일 레코드 없음
     * @apiError {String} redirect            권한 없음 (error 플래시 포함)
     */
    public function delete(int $idx): RedirectResponse
    {
        $model = new FileModel();
        $file  = $model->find($idx);

        if (! $file) {
            throw new PageNotFoundException();
        }

        $userIdx = (int) session()->get('user_idx');
        $isAdmin = session()->get('group_name') === '최고관리자';

        if (! $isAdmin && $file['user_idx'] != $userIdx) {
            return redirect()->back()->with('error', '삭제 권한이 없습니다.');
        }

        $model->deleteFile($idx);

        return redirect()->back()->with('success', '파일이 삭제되었습니다.');
    }
}
