<?php

namespace App\Controller;

use App\Entity\Feedback;
use App\Repository\WebsiteRepository;
use App\Service\FeedbackRateLimiter;
use App\Service\OriginValidator;
use App\Service\PayloadValidator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ApiController extends AbstractController
{
    #[Route('/api/feedback', name: 'app_api_feedback', methods: ['POST'])]
    public function createFeedback(
        Request $request,
        WebsiteRepository $websiteRepository,
        EntityManagerInterface $entityManager,
        PayloadValidator $payloadValidator,
        OriginValidator $originValidator,
        FeedbackRateLimiter $rateLimiter,
    ): JsonResponse {
        // Reject oversized payloads to prevent memory exhaustion
        if ($payloadValidator->isPayloadTooLarge((int) $request->headers->get('Content-Length', 0))) {
            return $this->json(['error' => 'Payload too large.'], Response::HTTP_REQUEST_ENTITY_TOO_LARGE);
        }

        // Reject malformed JSON bodies
        $content = $payloadValidator->parseJson($request->getContent());
        if (null === $content) {
            return $this->json(['error' => 'Invalid JSON.'], Response::HTTP_BAD_REQUEST);
        }

        // Require a valid API key to identify the target website
        if (!$payloadValidator->hasValidApiKey($content)) {
            return $this->json(['error' => 'Missing API key.'], Response::HTTP_BAD_REQUEST);
        }

        // Verify the API key belongs to a registered website
        $website = $websiteRepository->findOneBy(['apiKey' => $content['apiKey']]);
        if (!$website) {
            return $this->json(['error' => 'Invalid API key.'], Response::HTTP_FORBIDDEN);
        }

        // Enforce rate limit per API key to prevent spam floods
        if (!$rateLimiter->isAllowed($website->getApiKey())) {
            return $this->json(['error' => 'Too many requests.'], Response::HTTP_TOO_MANY_REQUESTS);
        }

        // Require Origin header — browsers enforce this and cannot spoof it via JS
        $origin = $request->headers->get('Origin');
        if (!$origin) {
            return $this->json(['error' => 'Missing Origin header.'], Response::HTTP_FORBIDDEN);
        }

        // Reject requests from origins not matching the website's registered domain
        if (!$originValidator->isOriginAllowedForWebsite($origin, $website)) {
            return $this->json(['error' => 'Origin not allowed.'], Response::HTTP_FORBIDDEN);
        }

        // Everything except apiKey is feedback data
        $data = $content;
        unset($data['apiKey']);

        // Validate data structure and enforce size limits
        $dataError = $payloadValidator->validateData(['data' => $data]);
        if (null !== $dataError) {
            $status = str_contains($dataError, 'too large') ? Response::HTTP_REQUEST_ENTITY_TOO_LARGE : Response::HTTP_BAD_REQUEST;

            return $this->json(['error' => $dataError], $status);
        }

        // Strip HTML tags from all string values to prevent stored XSS
        $data = $payloadValidator->sanitizeData($data);

        $feedback = new Feedback();
        $feedback->setWebsite($website);
        $feedback->setData($data);
        $entityManager->persist($feedback);
        $entityManager->flush();

        return $this->json(['status' => 'ok', 'id' => $feedback->getId()], Response::HTTP_CREATED);
    }
}
