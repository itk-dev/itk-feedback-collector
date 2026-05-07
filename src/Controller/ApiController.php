<?php

namespace App\Controller;

use App\Entity\Feedback;
use App\Exception\ApiValidationException;
use App\Service\FeedbackRequestValidator;
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
        FeedbackRequestValidator $requestValidator,
        EntityManagerInterface $entityManager,
    ): JsonResponse {
        try {
            $result = $requestValidator->validate($request);
        } catch (ApiValidationException $e) {
            return $this->json(['error' => $e->getMessage()], $e->getStatusCode());
        }

        $feedback = new Feedback();
        $feedback->setWebsite($result['website']);
        $feedback->setData($result['data']);
        $entityManager->persist($feedback);
        $entityManager->flush();

        return $this->json(['status' => 'ok', 'id' => $feedback->getId()], Response::HTTP_CREATED);
    }
}
