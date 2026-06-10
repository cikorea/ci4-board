<?php

namespace App\Models;

use App\Traits\ReadableModel;
use CodeIgniter\Model;

/**
 * @apiDefine ArticleModel
 * @apiDescription tb_bbs_article (메타) + tb_bbs_contents (본문) + tb_bbs_hit (조회수) +
 *   tb_bbs_tag + tb_bbs_url 을 통합 관리하는 모델.
 */
class ArticleModel extends Model
{
    use ReadableModel;

    protected $table         = 'tb_bbs_article';
    protected $primaryKey    = 'idx';
    protected $returnType    = 'array';
    protected $useTimestamps = false;

    protected $allowedFields = [
        'bbs_idx', 'category_idx', 'user_idx', 'exec_user_idx',
        'title', 'comment_count', 'vote_count', 'scrap_count',
        'timestamp_insert', 'timestamp_update',
        'client_ip_insert', 'client_ip_update',
        'html_used', 'is_notice', 'is_secret', 'is_deleted', 'agent_insert',
    ];

    /** @var int 마지막 getList() 전체 레코드 수 */
    public int $_pagerTotal   = 0;
    /** @var int 마지막 getList() 페이지 번호 */
    public int $_pagerPage    = 1;
    /** @var int 마지막 getList() 페이지당 수 */
    public int $_pagerPerPage = 15;

    public function __construct()
    {
        parent::__construct();
        $this->initReadDb();
    }

    /**
     * @api {model} /model/ArticleModel/getList ArticleModel::getList
     * @apiName ArticleModel_getList
     * @apiDescription 게시판 글 목록 조회
     * @apiGroup ArticleModel
     * @apiDescription 페이지네이션·검색·조회수 JOIN을 포함한 목록을 반환한다.
     *   호출 후 _pagerTotal, _pagerPage, _pagerPerPage 프로퍼티에 페이지 메타가 저장된다.
     *
     * @apiParam {Number}  bbsIdx    게시판 idx
     * @apiParam {String}  [keyword] 제목 검색어
     * @apiParam {Number}  [perPage=15] 페이지당 레코드 수
     * @apiSuccess {Array} rows      게시글 배열 (idx, title, comment_count, is_notice, timestamp_insert, nickname, hit_count)
     */
    public function getList(int $bbsIdx, ?string $keyword = null, int $perPage = 15): array
    {
        $builder = $this->readDb->table('tb_bbs_article a')
            ->select('a.idx, a.title, a.comment_count, a.vote_count, a.is_notice, a.is_secret, a.timestamp_insert, u.nickname, COALESCE(h.hit, 0) AS hit_count')
            ->join('tb_users u', 'u.idx = a.user_idx', 'left')
            ->join('tb_bbs_hit h', 'h.article_idx = a.idx AND h.bbs_idx = a.bbs_idx', 'left')
            ->where('a.bbs_idx', $bbsIdx)
            ->where('a.is_deleted', 0)
            ->orderBy('a.is_notice', 'DESC')
            ->orderBy('a.idx', 'DESC');

        if ($keyword) {
            $builder->groupStart()
                ->like('a.title', $keyword)
                ->groupEnd();
        }

        $total   = $builder->countAllResults(false);
        $page    = (int) ($_GET['page'] ?? 1);
        $offset  = ($page - 1) * $perPage;
        $rows    = $builder->limit($perPage, $offset)->get()->getResultArray();

        $this->_pagerTotal   = $total;
        $this->_pagerPage    = $page;
        $this->_pagerPerPage = $perPage;

        return $rows;
    }

    /**
     * @api {model} /model/ArticleModel/getArticleWithContents ArticleModel::getArticleWithContents
     * @apiName ArticleModel_getArticleWithContents
     * @apiDescription 단일 게시글 조회 (본문 포함)
     * @apiGroup ArticleModel
     * @apiDescription article + contents + hit + user 를 LEFT JOIN 하여 반환한다.
     *   삭제된(is_deleted=1) 게시글은 null 반환.
     *
     * @apiParam  {Number} articleIdx  게시글 idx
     * @apiSuccess {Object} row 게시글 배열 (없으면 null)
     */
    public function getArticleWithContents(int $articleIdx): ?array
    {
        $row = $this->readDb->table('tb_bbs_article a')
            ->select('a.*, c.contents, COALESCE(h.hit, 0) AS hit_count, u.nickname, u.user_id')
            ->join('tb_bbs_contents c', 'c.article_idx = a.idx', 'left')
            ->join('tb_bbs_hit h', 'h.article_idx = a.idx AND h.bbs_idx = a.bbs_idx', 'left')
            ->join('tb_users u', 'u.idx = a.user_idx', 'left')
            ->where('a.idx', $articleIdx)
            ->where('a.is_deleted', 0)
            ->get()
            ->getRowArray();
        return $row ?: null;
    }

    /**
     * @api {model} /model/ArticleModel/getLatest ArticleModel::getLatest
     * @apiName ArticleModel_getLatest
     * @apiDescription 최신 게시글 N개
     * @apiGroup ArticleModel
     * @apiDescription 메인 페이지 등에서 게시판별 최신 글을 가져올 때 사용한다.
     *
     * @apiParam  {Number} bbsIdx   게시판 idx
     * @apiParam  {Number} [limit=10] 가져올 최대 수
     * @apiSuccess {Array} rows     게시글 배열 (idx, title, timestamp_insert, is_notice, comment_count, nickname, hit_count)
     */
    public function getLatest(int $bbsIdx, int $limit = 10): array
    {
        return $this->readDb->table('tb_bbs_article a')
            ->select('a.idx, a.title, a.timestamp_insert, a.is_notice, a.comment_count, u.nickname, COALESCE(h.hit, 0) AS hit_count')
            ->join('tb_users u', 'u.idx = a.user_idx', 'left')
            ->join('tb_bbs_hit h', 'h.article_idx = a.idx AND h.bbs_idx = a.bbs_idx', 'left')
            ->where('a.bbs_idx', $bbsIdx)
            ->where('a.is_deleted', 0)
            ->orderBy('a.idx', 'DESC')
            ->limit($limit)
            ->get()
            ->getResultArray();
    }

    /**
     * @api {model} /model/ArticleModel/incrementHit ArticleModel::incrementHit
     * @apiName ArticleModel_incrementHit
     * @apiDescription 조회수 증가
     * @apiGroup ArticleModel
     * @apiDescription tb_bbs_hit 행이 있으면 hit+1 UPDATE, 없으면 hit=1 INSERT.
     *
     * @apiParam {Number} bbsIdx      게시판 idx
     * @apiParam {Number} articleIdx  게시글 idx
     */
    public function incrementHit(int $bbsIdx, int $articleIdx): void
    {
        $existing = $this->db->table('tb_bbs_hit')
            ->where('bbs_idx', $bbsIdx)
            ->where('article_idx', $articleIdx)
            ->get()->getRowArray();

        if ($existing) {
            $this->db->table('tb_bbs_hit')
                ->where('bbs_idx', $bbsIdx)
                ->where('article_idx', $articleIdx)
                ->set('hit', 'hit + 1', false)
                ->update();
        } else {
            $this->db->table('tb_bbs_hit')->insert([
                'bbs_idx'     => $bbsIdx,
                'article_idx' => $articleIdx,
                'hit'         => 1,
            ]);
        }
    }

    /**
     * @api {model} /model/ArticleModel/writeArticle ArticleModel::writeArticle
     * @apiName ArticleModel_writeArticle
     * @apiDescription 게시글 작성 (트랜잭션)
     * @apiGroup ArticleModel
     * @apiDescription tb_bbs_article, tb_bbs_contents, tb_bbs_hit 을 하나의 트랜잭션으로 삽입한다.
     *
     * @apiParam  {Object} articleData  tb_bbs_article 컬럼 맵 (bbs_idx, user_idx, title 등)
     * @apiParam  {String} contents     게시글 본문
     * @apiSuccess {Number} articleIdx 새로 생성된 article idx
     */
    public function writeArticle(array $articleData, string $contents): int
    {
        $this->db->transStart();

        $articleIdx = $this->insert($articleData, true);

        $this->db->table('tb_bbs_contents')->insert([
            'bbs_idx'       => $articleData['bbs_idx'],
            'article_idx'   => $articleIdx,
            'exec_user_idx' => $articleData['user_idx'],
            'contents'      => $contents,
            'client_ip'     => $articleData['client_ip_insert'],
        ]);

        $this->db->table('tb_bbs_hit')->insert([
            'bbs_idx'     => $articleData['bbs_idx'],
            'article_idx' => $articleIdx,
            'hit'         => 0,
        ]);

        $this->db->transComplete();

        return (int) $articleIdx;
    }

    /**
     * @api {model} /model/ArticleModel/updateArticle ArticleModel::updateArticle
     * @apiName ArticleModel_updateArticle
     * @apiDescription 게시글 수정 (트랜잭션)
     * @apiGroup ArticleModel
     * @apiDescription tb_bbs_article 과 tb_bbs_contents 를 함께 업데이트한다.
     *
     * @apiParam {Number} articleIdx   게시글 idx
     * @apiParam {Object} articleData  업데이트할 컬럼 맵 (title, timestamp_update 등)
     * @apiParam {String} contents     새 본문
     */
    public function updateArticle(int $articleIdx, array $articleData, string $contents): void
    {
        $this->db->transStart();

        $this->update($articleIdx, $articleData);

        $this->db->table('tb_bbs_contents')
            ->where('article_idx', $articleIdx)
            ->update(['contents' => $contents]);

        $this->db->transComplete();
    }

    /**
     * @api {model} /model/ArticleModel/saveTagsForArticle ArticleModel::saveTagsForArticle
     * @apiName ArticleModel_saveTagsForArticle
     * @apiDescription 태그 저장 (전체 교체)
     * @apiGroup ArticleModel
     * @apiDescription 기존 태그를 모두 삭제한 뒤 새 배열로 재삽입한다.
     *
     * @apiParam {Number}   bbsIdx      게시판 idx
     * @apiParam {Number}   articleIdx  게시글 idx
     * @apiParam {String[]} tags        태그 문자열 배열
     */
    public function saveTagsForArticle(int $bbsIdx, int $articleIdx, array $tags): void
    {
        $this->db->table('tb_bbs_tag')
            ->where('bbs_idx', $bbsIdx)
            ->where('article_idx', $articleIdx)
            ->delete();

        $rows = [];
        foreach (array_values($tags) as $seq => $tag) {
            $tag = trim($tag);
            if ($tag === '') continue;
            $rows[] = [
                'bbs_idx'     => $bbsIdx,
                'article_idx' => $articleIdx,
                'tag'         => $tag,
                'sequence'    => $seq + 1,
            ];
        }
        if ($rows !== []) {
            $this->db->table('tb_bbs_tag')->insertBatch($rows);
        }
    }

    /**
     * @api {model} /model/ArticleModel/saveUrlsForArticle ArticleModel::saveUrlsForArticle
     * @apiName ArticleModel_saveUrlsForArticle
     * @apiDescription URL 저장 (전체 교체)
     * @apiGroup ArticleModel
     * @apiDescription 기존 URL을 모두 삭제한 뒤 새 배열로 재삽입한다.
     *
     * @apiParam {Number}   bbsIdx      게시판 idx
     * @apiParam {Number}   articleIdx  게시글 idx
     * @apiParam {String[]} urls        URL 문자열 배열
     */
    public function saveUrlsForArticle(int $bbsIdx, int $articleIdx, array $urls): void
    {
        $this->db->table('tb_bbs_url')
            ->where('bbs_idx', $bbsIdx)
            ->where('article_idx', $articleIdx)
            ->delete();

        $rows = [];
        foreach (array_values($urls) as $seq => $url) {
            $url = trim($url);
            if ($url === '') continue;
            $rows[] = [
                'bbs_idx'     => $bbsIdx,
                'article_idx' => $articleIdx,
                'url'         => $url,
                'sequence'    => $seq + 1,
            ];
        }
        if ($rows !== []) {
            $this->db->table('tb_bbs_url')->insertBatch($rows);
        }
    }

    /**
     * @api {model} /model/ArticleModel/getTagsByArticle ArticleModel::getTagsByArticle
     * @apiName ArticleModel_getTagsByArticle
     * @apiDescription 게시글 태그 목록
     * @apiGroup ArticleModel
     *
     * @apiParam  {Number} articleIdx  게시글 idx
     * @apiSuccess {Array} rows        태그 배열 (sequence ASC 정렬)
     */
    public function getTagsByArticle(int $articleIdx): array
    {
        return $this->readDb->table('tb_bbs_tag')
            ->where('article_idx', $articleIdx)
            ->orderBy('sequence', 'ASC')
            ->orderBy('idx', 'ASC')
            ->get()->getResultArray();
    }

    /**
     * @api {model} /model/ArticleModel/getUrlsByArticle ArticleModel::getUrlsByArticle
     * @apiName ArticleModel_getUrlsByArticle
     * @apiDescription 게시글 URL 목록
     * @apiGroup ArticleModel
     *
     * @apiParam  {Number} articleIdx  게시글 idx
     * @apiSuccess {Array} rows        URL 배열 (sequence ASC 정렬)
     */
    public function getUrlsByArticle(int $articleIdx): array
    {
        return $this->readDb->table('tb_bbs_url')
            ->where('article_idx', $articleIdx)
            ->orderBy('sequence', 'ASC')
            ->orderBy('idx', 'ASC')
            ->get()->getResultArray();
    }

    /**
     * @api {model} /model/ArticleModel/softDelete ArticleModel::softDelete
     * @apiName ArticleModel_softDelete
     * @apiDescription 게시글 소프트 삭제
     * @apiGroup ArticleModel
     * @apiDescription is_deleted=1 로 플래그를 세우며 실제 행은 보존한다.
     *
     * @apiParam {Number} articleIdx  게시글 idx
     */
    public function softDelete(int $articleIdx): void
    {
        $this->update($articleIdx, [
            'is_deleted'       => 1,
            'timestamp_update' => time(),
        ]);
    }
}
