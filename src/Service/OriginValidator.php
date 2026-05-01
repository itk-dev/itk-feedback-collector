<?php

namespace App\Service;

use App\Entity\Website;
use App\Repository\WebsiteRepository;

/**
 * Validates request origins against registered website domains.
 */
class OriginValidator
{
    public function __construct(
        private readonly WebsiteRepository $websiteRepository,
    ) {
    }

    /**
     * Check whether the given origin matches any registered website.
     *
     * @param string $origin the Origin header value
     *
     * @return bool true if the origin matches a registered website domain
     */
    public function isOriginAllowed(string $origin): bool
    {
        return null !== $this->getAllowedOrigin($origin);
    }

    /**
     * Check whether the given origin matches a specific website's registered URL.
     *
     * @param string  $origin  the Origin header value
     * @param Website $website the website entity to check against
     *
     * @return bool true if the origin host matches the website's registered host
     */
    public function isOriginAllowedForWebsite(string $origin, Website $website): bool
    {
        $originHost = parse_url($origin, PHP_URL_HOST);
        $registeredHost = parse_url($website->getUrl(), PHP_URL_HOST);

        return $originHost && $registeredHost && $originHost === $registeredHost;
    }

    /**
     * Return the origin string if it matches a registered website, or null otherwise.
     *
     * Used to set the Access-Control-Allow-Origin header to the exact
     * requesting origin rather than a wildcard.
     *
     * @param string $origin the Origin header value
     *
     * @return string|null the origin if allowed, null if not
     */
    public function getAllowedOrigin(string $origin): ?string
    {
        if ('' === $origin) {
            return null;
        }

        $originHost = parse_url($origin, PHP_URL_HOST);
        if (!$originHost) {
            return null;
        }

        $websites = $this->websiteRepository->findAll();
        foreach ($websites as $website) {
            $registeredHost = parse_url($website->getUrl(), PHP_URL_HOST);
            if ($registeredHost === $originHost) {
                return $origin;
            }
        }

        return null;
    }
}
