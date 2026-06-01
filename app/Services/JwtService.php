<?php

namespace App\Services;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class JwtService
{
    private const ALGO        = 'HS256';
    private const ACCESS_TTL  = 3600;      // 1시간
    private const REFRESH_TTL = 2592000;   // 30일

    private static ?object $currentUser = null;

    // ------------------------------------------------------------------ //
    // 설정
    // ------------------------------------------------------------------ //

    private static function secret(): string
    {
        $secret = env('jwt.secret', '');
        if (empty($secret)) {
            throw new \RuntimeException('jwt.secret 이 .env에 설정되지 않았습니다.');
        }
        return $secret;
    }

    // ------------------------------------------------------------------ //
    // 토큰 발급
    // ------------------------------------------------------------------ //

    public static function issueAccess(array $user): string
    {
        $now = time();
        return JWT::encode([
            'iss'        => 'ci4-board',
            'sub'        => (int) $user['idx'],
            'type'       => 'user',
            'user_id'    => $user['user_id'],
            'nickname'   => $user['nickname'],
            'email'      => $user['email'],
            'group_idx'  => (int) $user['group_idx'],
            'group_name' => $user['group_name'] ?? '',
            'iat'        => $now,
            'exp'        => $now + self::ACCESS_TTL,
        ], self::secret(), self::ALGO);
    }

    public static function issueAdminAccess(array $user): string
    {
        $now = time();
        return JWT::encode([
            'iss'        => 'ci4-board',
            'sub'        => (int) $user['idx'],
            'type'       => 'admin',
            'user_id'    => $user['user_id'],
            'nickname'   => $user['nickname'],
            'email'      => $user['email'],
            'group_idx'  => (int) $user['group_idx'],
            'group_name' => $user['group_name'] ?? '',
            'iat'        => $now,
            'exp'        => $now + self::ACCESS_TTL,
        ], self::secret(), self::ALGO);
    }

    public static function issueRefresh(int $userIdx): string
    {
        $now = time();
        return JWT::encode([
            'iss'  => 'ci4-board',
            'sub'  => $userIdx,
            'type' => 'refresh',
            'iat'  => $now,
            'exp'  => $now + self::REFRESH_TTL,
        ], self::secret(), self::ALGO);
    }

    // ------------------------------------------------------------------ //
    // 토큰 검증
    // ------------------------------------------------------------------ //

    /**
     * @throws \Exception 토큰이 유효하지 않으면 예외 발생
     */
    public static function decode(string $token): object
    {
        return JWT::decode($token, new Key(self::secret(), self::ALGO));
    }

    // ------------------------------------------------------------------ //
    // 현재 사용자 (필터에서 주입)
    // ------------------------------------------------------------------ //

    public static function setCurrentUser(object $payload): void
    {
        self::$currentUser = $payload;
    }

    public static function getCurrentUser(): ?object
    {
        return self::$currentUser;
    }

    public static function isLoggedIn(): bool
    {
        return self::$currentUser !== null;
    }

    public static function getUserIdx(): int
    {
        return (int) (self::$currentUser?->sub ?? 0);
    }

    public static function getGroupIdx(): int
    {
        return (int) (self::$currentUser?->group_idx ?? 0);
    }

    public static function isAdmin(): bool
    {
        return (int) (self::$currentUser?->group_idx ?? 0) === 1;
    }

    // ------------------------------------------------------------------ //
    // 유틸
    // ------------------------------------------------------------------ //

    public static function extractFromRequest(\CodeIgniter\HTTP\RequestInterface $request): ?string
    {
        $header = $request->getHeaderLine('Authorization');
        if (str_starts_with($header, 'Bearer ')) {
            return substr($header, 7);
        }
        return null;
    }

    public static function accessTtl(): int
    {
        return self::ACCESS_TTL;
    }

    public static function refreshTtl(): int
    {
        return self::REFRESH_TTL;
    }
}
