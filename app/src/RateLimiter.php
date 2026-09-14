<?php
declare(strict_types=1);

namespace App;

final class RateLimiter
{
    public function __construct(private Database $db) {}

    /**
     * Returns true if the action is allowed (and records a hit).
     */
    public function attempt(string $bucket, int $max, int $perSeconds): bool
    {
        $bucket = substr($bucket, 0, 120);
        $now = time();
        $row = $this->db->first('SELECT hits, window_end FROM rate_limits WHERE bucket = ?', [$bucket]);

        if (!$row || strtotime($row['window_end']) < $now) {
            $this->db->run(
                'REPLACE INTO rate_limits (bucket, hits, window_end) VALUES (?, 1, ?)',
                [$bucket, date('Y-m-d H:i:s', $now + $perSeconds)]
            );
            return true;
        }
        if ((int) $row['hits'] >= $max) {
            return false;
        }
        $this->db->run('UPDATE rate_limits SET hits = hits + 1 WHERE bucket = ?', [$bucket]);
        return true;
    }

    public function retryAfter(string $bucket): int
    {
        $end = $this->db->column('SELECT window_end FROM rate_limits WHERE bucket = ?', [substr($bucket, 0, 120)]);
        return $end ? max(0, strtotime($end) - time()) : 0;
    }

    public function clear(string $bucket): void
    {
        $this->db->run('DELETE FROM rate_limits WHERE bucket = ?', [substr($bucket, 0, 120)]);
    }
}
