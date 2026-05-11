<?php

namespace App\Service;

use App\Entity\Website;
use App\Exception\ApiValidationException;
use App\Repository\WebsiteRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class FeedbackRequestValidator
{
    public function __construct(
        private readonly PayloadValidator $payloadValidator,
        private readonly OriginValidator $originValidator,
        private readonly FeedbackRateLimiter $rateLimiter,
        private readonly WebsiteRepository $websiteRepository,
    ) {
    }

    /**
     * Validate an incoming feedback API request.
     *
     * Runs all checks (payload size, JSON parsing, API key, rate limit,
     * origin, data structure) and returns the validated website and
     * sanitized data. Throws ApiValidationException on any failure.
     *
     * @return array{website: Website, data: array}
     *
     * @throws ApiValidationException
     */
    public function validate(Request $request): array
    {
        if ($this->payloadValidator->isPayloadTooLarge((int) $request->headers->get('Content-Length', 0))) {
            throw new ApiValidationException('Payload too large.', Response::HTTP_REQUEST_ENTITY_TOO_LARGE);
        }

        $content = $this->payloadValidator->parseJson($request->getContent());
        if (null === $content) {
            throw new ApiValidationException('Invalid JSON.', Response::HTTP_BAD_REQUEST);
        }

        if (!$this->payloadValidator->hasValidApiKey($content)) {
            throw new ApiValidationException('Missing API key.', Response::HTTP_BAD_REQUEST);
        }

        $website = $this->websiteRepository->findOneBy(['apiKey' => $content['apiKey']]);
        if (!$website) {
            throw new ApiValidationException('Invalid API key.', Response::HTTP_FORBIDDEN);
        }

        if (!$this->rateLimiter->isAllowed($website->getApiKey())) {
            throw new ApiValidationException('Too many requests.', Response::HTTP_TOO_MANY_REQUESTS);
        }

        $origin = $request->headers->get('Origin');
        if (!$origin) {
            throw new ApiValidationException('Missing Origin header.', Response::HTTP_FORBIDDEN);
        }

        if (!$this->originValidator->isOriginAllowedForWebsite($origin, $website)) {
            throw new ApiValidationException('Origin not allowed.', Response::HTTP_FORBIDDEN);
        }

        $data = $content;
        unset($data['apiKey']);

        $dataError = $this->payloadValidator->validateData(['data' => $data]);
        if (null !== $dataError) {
            $status = str_contains($dataError, 'too large') ? Response::HTTP_REQUEST_ENTITY_TOO_LARGE : Response::HTTP_BAD_REQUEST;
            throw new ApiValidationException($dataError, $status);
        }

        $data = $this->payloadValidator->sanitizeData($data);

        return ['website' => $website, 'data' => $data];
    }
}
