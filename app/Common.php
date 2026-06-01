<?php

/**
 * The goal of this file is to allow developers a location
 * where they can overwrite core procedural functions and
 * replace them with their own. This file is loaded during
 * the bootstrap process and is called during the framework's
 * execution.
 *
 * @see: https://codeigniter.com/user_guide/extending/common.html
 */

/**
 * DB에 HTML 엔티티로 저장된 문자열을 안전하게 HTML 출력.
 */
if (! function_exists('esc_db')) {
    function esc_db(?string $text, string $context = 'html'): string
    {
        if ($text === null || $text === '') {
            return '';
        }
        return esc(html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8'), $context);
    }
}

/**
 * tb_bbs_setting 의 PHP 직렬화된 그룹 배열을 파싱.
 * 반환값은 허용된 group_idx 정수 배열.
 * 0 = 비회원, 1 = 최고관리자, 2 = 일반회원, 3 = 개발자
 */
if (! function_exists('parse_group_setting')) {
    function parse_group_setting(?string $value): array
    {
        if (! $value) {
            return [];
        }
        $result = @unserialize($value);
        return is_array($result) ? array_map('intval', array_values($result)) : [];
    }
}

/**
 * 현재 로그인 사용자의 group_idx 반환 (비로그인 = 0).
 * JWT 요청이면 JwtService에서, 웹 세션 요청이면 session에서 읽는다.
 */
if (! function_exists('current_group_idx')) {
    function current_group_idx(): int
    {
        // API 요청 (JWT)
        if (\App\Services\JwtService::isLoggedIn()) {
            return \App\Services\JwtService::getGroupIdx();
        }
        // 웹 요청 (세션)
        return (int) (session()->get('group_idx') ?? 0);
    }
}

/**
 * 허용된 그룹 목록에 현재 사용자가 포함되는지 확인.
 * - 허용 목록에 0(비회원)이 있으면 누구나 허용.
 * - 비로그인 사용자는 0이 없으면 거부.
 */
if (! function_exists('user_can_in_groups')) {
    function user_can_in_groups(array $allowedGroups): bool
    {
        if (empty($allowedGroups)) {
            return false;
        }
        // 비회원(0) 포함이면 누구나 허용
        if (in_array(0, $allowedGroups, true)) {
            return true;
        }
        $userGroup = current_group_idx();
        // 비로그인인데 비회원 허용이 없으면 거부
        if ($userGroup === 0) {
            return false;
        }
        return in_array($userGroup, $allowedGroups, true);
    }
}

/**
 * 메인 페이지 캐시 전체 삭제 (모든 그룹 키 제거).
 * 글쓰기 / 수정 / 삭제 후 호출.
 */
if (! function_exists('clear_home_cache')) {
    function clear_home_cache(): void
    {
        $cache = \Config\Services::cache();
        // 등록된 그룹 idx: 0(비로그인), 1(최고관리자), 2(일반회원), 3(개발자)
        foreach ([0, 1, 2, 3] as $g) {
            $cache->delete('home_boards_g' . $g);
        }
    }
}
