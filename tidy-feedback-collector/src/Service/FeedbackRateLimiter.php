<?php

namespace App\Service;

use Symfony\Component\RateLimiter\RateLimiterFactory;

/**
 * Rate limiter for the feedback API, keyed by API key.
 */
class FeedbackRateLimiter
{
    public function __construct(
        private readonly RateLimiterFactory $feedbackApiLimiter,
    ) {
    }

    /**
     * Check whether a request for the given API key is within the rate limit.
     *
     * Consumes one token from the configured limiter. Returns false if
     * the limit has been exceeded.
     *
     * @param string $apiKey the website API key to rate-limit against
     *
     * @return bool true if the request is allowed, false if rate-limited
     */
    public function isAllowed(string $apiKey): bool
    {
        $limiter = $this->feedbackApiLimiter->create($apiKey);

        return $limiter->consume()->isAccepted();
    }
}
