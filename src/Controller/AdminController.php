<?php

namespace App\Controller;

use App\Entity\Website;
use App\Form\WebsiteType;
use App\Repository\WebsiteRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

#[Route('/admin')]
class AdminController extends AbstractController
{
    #[Route('/website/create', name: 'app_admin_website_create')]
    public function createWebsite(Request $request, EntityManagerInterface $entityManager): Response
    {
        $website = new Website();
        $form = $this->createForm(WebsiteType::class, $website);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $website->generateApiKey();
            $entityManager->persist($website);
            $entityManager->flush();

            $this->addFlash('success', 'Website "'.$website->getWebsiteId().'" created.');

            return $this->redirectToRoute('app_admin_website_show', ['id' => $website->getId()]);
        }

        return $this->render('admin/create_website.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/website/{id}', name: 'app_admin_website_show')]
    public function showWebsite(string $id, WebsiteRepository $websiteRepository): Response
    {
        $website = $websiteRepository->find($id);

        if (!$website) {
            throw $this->createNotFoundException('Website not found.');
        }

        $apiEndpoint = $this->generateUrl('app_api_feedback', [], UrlGeneratorInterface::ABSOLUTE_URL);

        return $this->render('admin/show_website.html.twig', [
            'website' => $website,
            'apiEndpoint' => $apiEndpoint,
        ]);
    }
}
