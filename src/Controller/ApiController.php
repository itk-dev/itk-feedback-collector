<?php

namespace App\Controller;

use App\Entity\Feedback;
use App\Repository\WebsiteRepository;
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
    ): JsonResponse {
        $content = json_decode($request->getContent(), true);

        if (!is_array($content) || empty($content['apiKey'])) {
            return $this->json(['error' => 'Missing API key.'], Response::HTTP_BAD_REQUEST);
        }

        $website = $websiteRepository->findOneBy(['apiKey' => $content['apiKey']]);

        if (!$website) {
            return $this->json(['error' => 'Invalid API key.'], Response::HTTP_FORBIDDEN);
        }

        // Origin check as secondary validation
        $origin = $request->headers->get('Origin');
        if ($origin) {
            $registeredHost = parse_url($website->getUrl(), PHP_URL_HOST);
            $originHost = parse_url($origin, PHP_URL_HOST);

            if ($registeredHost && $originHost && $registeredHost !== $originHost) {
                return $this->json(['error' => 'Origin not allowed.'], Response::HTTP_FORBIDDEN);
            }
        }

        $data = $content['data'] ?? [];

        $feedback = new Feedback();
        $feedback->setWebsite($website);
        $feedback->setData(is_array($data) ? $data : []);
        $entityManager->persist($feedback);
        $entityManager->flush();

        return $this->json(['status' => 'ok', 'id' => $feedback->getId()], Response::HTTP_CREATED);
    }
}
