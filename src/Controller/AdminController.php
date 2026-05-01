<?php

namespace App\Controller;

use App\Entity\Website;
use App\Form\WebsiteType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

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
            $entityManager->persist($website);
            $entityManager->flush();

            $this->addFlash('success', 'Website "'.$website->getWebsiteId().'" created.');

            return $this->redirectToRoute('app_admin_website_create');
        }

        return $this->render('admin/create_website.html.twig', [
            'form' => $form,
        ]);
    }
}
