<?php

namespace App\Controller;

use App\Repository\FeedbackRepository;
use App\Repository\WebsiteRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class FeedbackController extends AbstractController
{
    #[Route('/', name: 'app_index')]
    public function index(Request $request, WebsiteRepository $websiteRepository): Response
    {
        $websites = $websiteRepository->findAll();

        $websiteId = $request->query->get('website');
        if ($websiteId) {
            return $this->redirectToRoute('app_feedback_list', ['id' => $websiteId]);
        }

        return $this->render('feedback/index.html.twig', [
            'websites' => $websites,
        ]);
    }

    #[Route('/website/{id}/feedback', name: 'app_feedback_list')]
    public function list(string $id, WebsiteRepository $websiteRepository, FeedbackRepository $feedbackRepository): Response
    {
        $website = $websiteRepository->find($id);

        if (!$website) {
            throw $this->createAccessDeniedException('Access denied.');
        }

        $feedbacks = $feedbackRepository->findBy(['website' => $website]);

        return $this->render('feedback/list.html.twig', [
            'website' => $website,
            'feedbacks' => $feedbacks,
        ]);
    }
}
