<?php

namespace App\Controller;

use App\Entity\Website;
use App\Form\WebsiteType;
use App\Repository\WebsiteRepository;
use App\Service\FreescoutService;
use App\Service\LeantimeService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin')]
class AdminController extends AbstractController
{
    public function __construct(
        private readonly LoggerInterface $logger,
    ) {
    }
    #[Route('/websites', name: 'app_admin_website_index')]
    public function indexWebsites(WebsiteRepository $websiteRepository): Response
    {
        $websites = $websiteRepository->findAll();

        return $this->render('admin/index_website.html.twig', [
            'websites' => $websites,
        ]);
    }

    #[Route('/website/create', name: 'app_admin_website_create')]
    public function createWebsite(Request $request, EntityManagerInterface $entityManager, FreescoutService $freescoutService, LeantimeService $leantimeService): Response
    {
        $mailboxChoices = $this->getMailboxChoices($freescoutService);
        $projectChoices = $this->getProjectChoices($leantimeService);

        $website = new Website();
        $form = $this->createForm(WebsiteType::class, $website, [
            'mailbox_choices' => $mailboxChoices,
            'project_choices' => $projectChoices,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $website->generateApiKey();
            $entityManager->persist($website);
            $entityManager->flush();

            $this->addFlash('success', 'Website "'.$website->getWebsiteId().'" created.');

            return $this->redirectToRoute('app_website_show', ['id' => $website->getId()]);
        }

        return $this->render('admin/create_website.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/website/{id}/edit', name: 'app_admin_website_edit')]
    public function editWebsite(string $id, Request $request, WebsiteRepository $websiteRepository, EntityManagerInterface $entityManager, FreescoutService $freescoutService, LeantimeService $leantimeService): Response
    {
        $website = $websiteRepository->find($id);

        if (!$website) {
            throw $this->createNotFoundException('Website not found.');
        }

        $mailboxChoices = $this->getMailboxChoices($freescoutService);
        $projectChoices = $this->getProjectChoices($leantimeService);

        $form = $this->createForm(WebsiteType::class, $website, [
            'mailbox_choices' => $mailboxChoices,
            'project_choices' => $projectChoices,
            'submit_label' => 'Save',
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', 'Website "'.$website->getWebsiteId().'" updated.');

            return $this->redirectToRoute('app_website_show', ['id' => $website->getId()]);
        }

        return $this->render('admin/edit_website.html.twig', [
            'form' => $form,
            'website' => $website,
        ]);
    }

    private function getMailboxChoices(FreescoutService $freescoutService): array
    {
        $mailboxChoices = [];
        try {
            $mailboxes = $freescoutService->getMailboxes();
            foreach ($mailboxes as $id => $name) {
                $mailboxChoices[$name] = $id;
            }
        } catch (\Exception $e) {
            $this->logger->warning('Failed to fetch FreeScout mailboxes: {message}', [
                'message' => $e->getMessage(),
                'exception' => $e,
            ]);
        }

        return $mailboxChoices;
    }

    private function getProjectChoices(LeantimeService $leantimeService): array
    {
        $projectChoices = [];
        try {
            $projects = $leantimeService->getProjects();
            foreach ($projects as $id => $name) {
                $projectChoices[$name] = $id;
            }
        } catch (\Exception $e) {
            $this->logger->warning('Failed to fetch Leantime projects: {message}', [
                'message' => $e->getMessage(),
                'exception' => $e,
            ]);
        }

        return $projectChoices;
    }
}
