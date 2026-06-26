<?php

declare(strict_types=1);

namespace App\Support;

final class DineInJoinUrl
{
    public static function build(string $sessionCode): string
    {
        $base = rtrim((string) config('dine_in.qr_sessions.frontend_base_url'), '/');
        $path = trim((string) config('dine_in.qr_sessions.frontend_join_path', '/dine-in/join'), '/');

        return sprintf('%s/%s/%s', $base, $path, $sessionCode);
    }

    public static function buildShortPublic(string $sessionCode): string
    {
        $base = rtrim((string) config('dine_in.qr_sessions.frontend_base_url'), '/');
        $path = trim((string) config('dine_in.qr_sessions.frontend_public_qr_path', '/qr'), '/');

        return sprintf('%s/%s/%s', $base, $path, $sessionCode);
    }
}
