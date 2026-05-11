<?php

namespace App\Service;

use App\Entity\Feedback;
use App\Entity\Website;
use App\Repository\FeedbackRepository;
use App\Repository\WebsiteRepository;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class EntityFinder
{
    public function __construct(
        private readonly WebsiteRepository $websiteRepository,
        private readonly FeedbackRepository $feedbackRepository,
    ) {
    }

    public function findWebsiteOrFail(string $id): Website
    {
        return $this->websiteRepository->find($id)
            ?? throw new NotFoundHttpException('Website not found.');
    }

    public function findFeedbackOrFail(string $id): Feedback
    {
        return $this->feedbackRepository->find($id)
            ?? throw new NotFoundHttpException('Feedback not found.');
    }

    /** @return Website[] */
    public function findAllWebsites(): array
    {
        return $this->websiteRepository->findAll();
    }

    /** @return Feedback[] */
    public function findFeedbackByWebsite(Website $website): array
    {
        return $this->feedbackRepository->findBy(
            ['website' => $website, 'handled' => false],
            ['createdAt' => 'DESC'],
        );
    }
}
