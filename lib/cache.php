<?php
declare(strict_types=1);

function cache_dir(): string {
    $dir = __DIR__ . '/../.cache/api';
    if (!is_dir($dir)) {
        @mkdir($dir, 0775, true);
    }
    return $dir;
}

function cache_path(string $namespace, string $key): string {
    $safeNamespace = preg_replace('/[^a-z0-9_-]/i', '_', $namespace);
    return cache_dir() . '/' . $safeNamespace . '_' . sha1($key) . '.json';
}

function cache_get(string $namespace, string $key, int $ttl): ?string {
    if ($ttl <= 0) return null;

    $path = cache_path($namespace, $key);
    if (!is_file($path)) return null;
    if (time() - (int)filemtime($path) > $ttl) return null;

    $data = @file_get_contents($path);
    return $data === false ? null : $data;
}

function cache_set(string $namespace, string $key, string $payload): void {
    $path = cache_path($namespace, $key);
    $tmp = $path . '.' . getmypid() . '.tmp';
    if (@file_put_contents($tmp, $payload, LOCK_EX) !== false) {
        @rename($tmp, $path);
    }
}

function cache_send_json(string $payload, int $ttl, string $hit = 'file'): void {
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: public, max-age=' . max(0, $ttl));
    header('X-Estimatiz-Cache: ' . $hit);
    echo $payload;
    exit;
}
