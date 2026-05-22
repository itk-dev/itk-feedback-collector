<?php

namespace App\Controller;

use App\Service\EntityFinder;
use App\Service\FreescoutService;
use App\Service\LeantimeService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class FeedbackController extends AbstractController
{
    public function __construct(
        private readonly EntityFinder $entityFinder,
        private readonly EntityManagerInterface $entityManager,
        private readonly LoggerInterface $logger,
    ) {
    }

    #[Route('/', name: 'app_index')]
    public function index(Request $request): Response
    {
        $websites = $this->entityFinder->findAllWebsites();

        $websiteId = $request->query->get('website');
        if ($websiteId) {
            return $this->redirectToRoute('app_website_show', ['id' => $websiteId]);
        }

        return $this->render('feedback/index.html.twig', [
            'websites' => $websites,
        ]);
    }

    #[Route('/website/{id}', name: 'app_website_show')]
    public function show(string $id): Response
    {
        $website = $this->entityFinder->findWebsiteOrFail($id);

        $apiEndpoint = $this->generateUrl('app_api_feedback', [], UrlGeneratorInterface::ABSOLUTE_URL);
        $feedbacks = $this->entityFinder->findFeedbackByWebsite($website);

        return $this->render('website/show.html.twig', [
            'website' => $website,
            'apiEndpoint' => $apiEndpoint,
            'feedbacks' => $feedbacks,
            'activeTab' => 'unhandled',
            'unhandledCount' => count($feedbacks),
            'handledCount' => $this->entityFinder->countFeedbackByWebsite($website, handled: true),
        ]);
    }

    #[Route('/website/{id}/handled', name: 'app_website_show_handled')]
    public function showHandled(string $id): Response
    {
        $website = $this->entityFinder->findWebsiteOrFail($id);

        $apiEndpoint = $this->generateUrl('app_api_feedback', [], UrlGeneratorInterface::ABSOLUTE_URL);
        $feedbacks = $this->entityFinder->findFeedbackByWebsite($website, handled: true);

        return $this->render('website/show.html.twig', [
            'website' => $website,
            'apiEndpoint' => $apiEndpoint,
            'feedbacks' => $feedbacks,
            'activeTab' => 'handled',
            'unhandledCount' => $this->entityFinder->countFeedbackByWebsite($website, handled: false),
            'handledCount' => count($feedbacks),
        ]);
    }

    #[Route('/feedback/{id}', name: 'app_feedback_show')]
    public function showFeedback(string $id): Response
    {
        $feedback = $this->entityFinder->findFeedbackOrFail($id);

        return $this->render('feedback/show.html.twig', [
            'feedback' => $feedback,
        ]);
    }

    #[Route('/feedback/{id}/note', name: 'app_feedback_note', methods: ['POST'])]
    public function saveNote(string $id, Request $request): Response
    {
        $feedback = $this->entityFinder->findFeedbackOrFail($id);

        $feedback->setNote($request->request->get('note'));
        $this->entityManager->flush();

        $this->addFlash('success', 'Note saved.');

        return $this->redirectToRoute('app_feedback_show', ['id' => $id]);
    }

    #[Route('/feedback/{id}/report', name: 'app_feedback_report', methods: ['POST'])]
    public function reportFeedback(string $id, Request $request, FreescoutService $freescoutService): Response
    {
        $feedback = $this->entityFinder->findFeedbackOrFail($id);

        $note = $request->request->get('note');
        if ($note) {
            $feedback->setNote($note);
        }

        try {
            $freescoutService->createConversation($feedback);
            $feedback->setHandled(true);
            $this->entityManager->flush();
            $this->addFlash('success', 'Support issue created in FreeScout.');
        } catch (\Exception $e) {
            $this->logger->error('FreeScout conversation creation failed for feedback {id}: {message}', [
                'id' => $id,
                'message' => $e->getMessage(),
                'exception' => $e,
            ]);
            $this->addFlash('error', 'Failed to create support issue: '.$e->getMessage());
        }

        return $this->redirectToRoute('app_feedback_show', ['id' => $id]);
    }

    #[Route('/feedback/{id}/change-request', name: 'app_feedback_change_request', methods: ['POST'])]
    public function createChangeRequest(string $id, Request $request, LeantimeService $leantimeService): Response
    {
        $feedback = $this->entityFinder->findFeedbackOrFail($id);

        $note = $request->request->get('note');
        if ($note) {
            $feedback->setNote($note);
        }

        try {
            $leantimeService->createIssue($feedback);
            $feedback->setHandled(true);
            $this->entityManager->flush();
            $this->addFlash('success', 'Change request created in Leantime.');
        } catch (\Exception $e) {
            $this->logger->error('Leantime issue creation failed for feedback {id}: {message}', [
                'id' => $id,
                'message' => $e->getMessage(),
                'exception' => $e,
            ]);
            $this->addFlash('error', 'Failed to create change request: '.$e->getMessage());
        }

        return $this->redirectToRoute('app_feedback_show', ['id' => $id]);
    }

    #[Route('/feedback/{id}/archive', name: 'app_feedback_archive', methods: ['POST'])]
    public function archiveFeedback(string $id): Response
    {
        $feedback = $this->entityFinder->findFeedbackOrFail($id);

        $feedback->setHandled(true);
        $this->entityManager->flush();

        $this->addFlash('success', 'Feedback archived.');

        return $this->redirectToRoute('app_website_show', ['id' => $feedback->getWebsite()->getId()]);
    }

    #[Route('/feedback/{id}/unarchive', name: 'app_feedback_unarchive', methods: ['POST'])]
    public function unarchiveFeedback(string $id): Response
    {
        $feedback = $this->entityFinder->findFeedbackOrFail($id);

        $feedback->setHandled(false);
        $this->entityManager->flush();

        $this->addFlash('success', 'Feedback unarchived.');

        return $this->redirectToRoute('app_website_show', ['id' => $feedback->getWebsite()->getId()]);
    }

    #[Route('/feedback/{id}/delete', name: 'app_feedback_delete', methods: ['POST'])]
    public function deleteFeedback(string $id): Response
    {
        $feedback = $this->entityFinder->findFeedbackOrFail($id);

        $websiteId = $feedback->getWebsite()->getId();
        $this->entityManager->remove($feedback);
        $this->entityManager->flush();

        $this->addFlash('success', 'Feedback deleted.');

        return $this->redirectToRoute('app_website_show', ['id' => $websiteId]);
    }
}
