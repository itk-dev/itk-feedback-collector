<?php

namespace App\Controller;

use App\Repository\FeedbackRepository;
use App\Repository\WebsiteRepository;
use App\Service\FreescoutService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class FeedbackController extends AbstractController
{
    #[Route('/', name: 'app_index')]
    public function index(Request $request, WebsiteRepository $websiteRepository): Response
    {
        $websites = $websiteRepository->findAll();

        $websiteId = $request->query->get('website');
        if ($websiteId) {
            return $this->redirectToRoute('app_website_show', ['id' => $websiteId]);
        }

        return $this->render('feedback/index.html.twig', [
            'websites' => $websites,
        ]);
    }

    #[Route('/website/{id}', name: 'app_website_show')]
    public function show(string $id, WebsiteRepository $websiteRepository, FeedbackRepository $feedbackRepository): Response
    {
        $website = $websiteRepository->find($id);

        if (!$website) {
            throw $this->createAccessDeniedException('Access denied.');
        }

        $apiEndpoint = $this->generateUrl('app_api_feedback', [], UrlGeneratorInterface::ABSOLUTE_URL);
        $feedbacks = $feedbackRepository->findBy(['website' => $website, 'handled' => false], ['createdAt' => 'DESC']);

        return $this->render('website/show.html.twig', [
            'website' => $website,
            'apiEndpoint' => $apiEndpoint,
            'feedbacks' => $feedbacks,
        ]);
    }

    #[Route('/feedback/{id}', name: 'app_feedback_show')]
    public function showFeedback(string $id, FeedbackRepository $feedbackRepository): Response
    {
        $feedback = $feedbackRepository->find($id);

        if (!$feedback) {
            throw $this->createAccessDeniedException('Access denied.');
        }

        return $this->render('feedback/show.html.twig', [
            'feedback' => $feedback,
        ]);
    }

    #[Route('/feedback/{id}/note', name: 'app_feedback_note', methods: ['POST'])]
    public function saveNote(string $id, Request $request, FeedbackRepository $feedbackRepository, EntityManagerInterface $entityManager): Response
    {
        $feedback = $feedbackRepository->find($id);

        if (!$feedback) {
            throw $this->createAccessDeniedException('Access denied.');
        }

        $feedback->setNote($request->request->get('note'));
        $entityManager->flush();

        $this->addFlash('success', 'Note saved.');

        return $this->redirectToRoute('app_feedback_show', ['id' => $id]);
    }

    #[Route('/feedback/{id}/report', name: 'app_feedback_report', methods: ['POST'])]
    public function reportFeedback(string $id, FeedbackRepository $feedbackRepository, FreescoutService $freescoutService, EntityManagerInterface $entityManager): Response
    {
        $feedback = $feedbackRepository->find($id);

        if (!$feedback) {
            throw $this->createAccessDeniedException('Access denied.');
        }

        try {
            $freescoutService->createConversation($feedback);
            $feedback->setHandled(true);
            $entityManager->flush();
            $this->addFlash('success', 'Support issue created in FreeScout.');
        } catch (\Exception $e) {
            $this->addFlash('error', 'Failed to create support issue: '.$e->getMessage());
        }

        return $this->redirectToRoute('app_feedback_show', ['id' => $id]);
    }

    #[Route('/feedback/{id}/delete', name: 'app_feedback_delete', methods: ['POST'])]
    public function deleteFeedback(string $id, FeedbackRepository $feedbackRepository, EntityManagerInterface $entityManager): Response
    {
        $feedback = $feedbackRepository->find($id);

        if (!$feedback) {
            throw $this->createAccessDeniedException('Access denied.');
        }

        $websiteId = $feedback->getWebsite()->getId();
        $entityManager->remove($feedback);
        $entityManager->flush();

        $this->addFlash('success', 'Feedback deleted.');

        return $this->redirectToRoute('app_website_show', ['id' => $websiteId]);
    }
}
