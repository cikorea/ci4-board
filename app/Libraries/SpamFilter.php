<?php

namespace App\Libraries;

/**
 * 게시글 스팸 필터
 *
 * 검사 항목:
 *  1. 쿨다운     — 동일 사용자 COOLDOWN_SECONDS 이내 재작성 차단
 *  2. 중복 감지  — DUPLICATE_TTL 이내 동일 제목+내용 재작성 차단
 *  3. 반복 문자  — 동일 문자 MAX_REPEAT_CHARS 개 이상 연속 차단
 *  4. URL 과다   — 본문 URL MAX_URLS 개 이상 차단
 *  5. 최소 길이  — 제목 MIN_TITLE_LEN, 내용 MIN_CONTENT_LEN 자 미만 차단
 */
class SpamFilter
{
    private const COOLDOWN_SECONDS = 30;
    private const DUPLICATE_TTL    = 300;
    private const MAX_REPEAT_CHARS = 10;
    private const MAX_URLS         = 5;
    private const MIN_TITLE_LEN    = 2;
    private const MIN_CONTENT_LEN  = 10;

    /**
     * 모든 스팸 검사 실행. 위반 시 lang 메시지 반환, 통과 시 null.
     */
    public function check(int $userIdx, string $title, string $contents): ?string
    {
        if ($msg = $this->checkCooldown($userIdx))                  return $msg;
        if ($msg = $this->checkDuplicate($userIdx, $title, $contents)) return $msg;
        if ($msg = $this->checkContent($title, $contents))          return $msg;

        return null;
    }

    /**
     * 글쓰기 성공 후 호출 — 쿨다운 및 중복 해시 캐시에 기록.
     */
    public function recordPost(int $userIdx, string $title, string $contents): void
    {
        $cache = service('cache');
        $cache->save('spam_cd_' . $userIdx, 1, self::COOLDOWN_SECONDS);

        $hash = md5($title . $contents);
        $cache->save('spam_dup_' . $userIdx . '_' . $hash, 1, self::DUPLICATE_TTL);
    }

    private function checkCooldown(int $userIdx): ?string
    {
        if (service('cache')->get('spam_cd_' . $userIdx)) {
            return lang('Api.spam_cooldown', [self::COOLDOWN_SECONDS]);
        }

        return null;
    }

    private function checkDuplicate(int $userIdx, string $title, string $contents): ?string
    {
        $hash = md5($title . $contents);
        if (service('cache')->get('spam_dup_' . $userIdx . '_' . $hash)) {
            return lang('Api.spam_duplicate');
        }

        return null;
    }

    private function checkContent(string $title, string $contents): ?string
    {
        if (mb_strlen($title) < self::MIN_TITLE_LEN) {
            return lang('Api.spam_title_too_short', [self::MIN_TITLE_LEN]);
        }

        $plain = strip_tags($contents);

        if (mb_strlen($plain) < self::MIN_CONTENT_LEN) {
            return lang('Api.spam_content_too_short', [self::MIN_CONTENT_LEN]);
        }

        if (preg_match('/(.)\1{' . (self::MAX_REPEAT_CHARS - 1) . ',}/u', $plain)) {
            return lang('Api.spam_repeated_chars');
        }

        $urlCount = preg_match_all('/https?:\/\//i', $contents);
        if ($urlCount >= self::MAX_URLS) {
            return lang('Api.spam_too_many_urls', [self::MAX_URLS]);
        }

        return null;
    }
}
