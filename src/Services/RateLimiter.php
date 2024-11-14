<?php
namespace XAutoPoster\Services;

class RateLimiter {
    private $cache;
    private $window = 900; // 15 dakika
    private $max_requests = 300; // Twitter API limiti

    public function __construct(CacheService $cache) {
        $this->cache = $cache;
    }

    public function check($key) {
        $requests = $this->cache->get("rate_limit_{$key}") ?: [];
        $now = time();

        // Süresi dolmuş istekleri temizle
        $requests = array_filter($requests, function($timestamp) use ($now) {
            return $timestamp > ($now - $this->window);
        });

        if (count($requests) >= $this->max_requests) {
            return false;
        }

        $requests[] = $now;
        $this->cache->set("rate_limit_{$key}", $requests, $this->window);
        return true;
    }

    public function getRemainingRequests($key) {
        $requests = $this->cache->get("rate_limit_{$key}") ?: [];
        $now = time();

        $requests = array_filter($requests, function($timestamp) use ($now) {
            return $timestamp > ($now - $this->window);
        });

        return $this->max_requests - count($requests);
    }
}