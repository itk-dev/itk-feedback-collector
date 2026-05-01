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
        if ($payloadValidator->isPayloadTooLarge((int) $request->headers->get('Content-Length', 0))) {
            return $this->json(['error' => 'Payload too large.'], Response::HTTP_REQUEST_ENTITY_TOO_LARGE);
        }

        $content = $payloadValidator->parseJson($request->getContent());
        if (null === $content) {
            return $this->json(['error' => 'Invalid JSON.'], Response::HTTP_BAD_REQUEST);
        }

        if (!$payloadValidator->hasValidApiKey($content)) {
            return $this->json(['error' => 'Missing API key.'], Response::HTTP_BAD_REQUEST);
        }

        $website = $websiteRepository->findOneBy(['apiKey' => $content['apiKey']]);
        if (!$website) {
            return $this->json(['error' => 'Invalid API key.'], Response::HTTP_FORBIDDEN);
        }

        if (!$rateLimiter->isAllowed($website->getApiKey())) {
            return $this->json(['error' => 'Too many requests.'], Response::HTTP_TOO_MANY_REQUESTS);
        }

        $origin = $request->headers->get('Origin');
        if ($origin && !$originValidator->isOriginAllowedForWebsite($origin, $website)) {
            return $this->json(['error' => 'Origin not allowed.'], Response::HTTP_FORBIDDEN);
        }

        // Everything except apiKey is feedback data
        $data = $content;
        unset($data['apiKey']);

        $dataError = $payloadValidator->validateData(['data' => $data]);
        if (null !== $dataError) {
            $status = str_contains($dataError, 'too large') ? Response::HTTP_REQUEST_ENTITY_TOO_LARGE : Response::HTTP_BAD_REQUEST;

            return $this->json(['error' => $dataError], $status);
        }

        $data = $payloadValidator->sanitizeData($data);

        $feedback = new Feedback();
        $feedback->setWebsite($website);
        $feedback->setData($data);
        $entityManager->persist($feedback);
        $entityManager->flush();

        return $this->json(['status' => 'ok', 'id' => $feedback->getId()], Response::HTTP_CREATED);
    }
}
