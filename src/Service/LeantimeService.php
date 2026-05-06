<?php

namespace App\Service;

use App\Entity\Feedback;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Client for the Leantime JSON-RPC 2.0 API.
 *
 * Handles authentication and provides convenience methods for
 * fetching projects and creating tickets from feedback entries.
 */
class LeantimeService
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly TranslatorInterface $translator,
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly string $leantimeApiUrl,
        private readonly string $leantimeApiKey,
    ) {
    }

    /**
     * Make a JSON-RPC 2.0 request to the Leantime API.
     *
     * @return mixed the "result" value from the JSON-RPC response
     *
     * @throws \RuntimeException if the API returns an error
     */
    public function request(string $method, array $params = []): mixed
    {
        $url = rtrim($this->leantimeApiUrl, '/').'/api/jsonrpc/';

        $response = $this->httpClient->request('POST', $url, [
            'headers' => [
                'Content-Type' => 'application/json',
                'x-api-key' => $this->leantimeApiKey,
            ],
            'json' => [
                'jsonrpc' => '2.0',
                'method' => $method,
                'params' => $params,
                'id' => uniqid(),
            ],
        ]);

        $data = $response->toArray(false);

        if (isset($data['error'])) {
            throw new \RuntimeException(sprintf('Leantime API error (%d): %s', $data['error']['code'] ?? 0, $data['error']['message'] ?? 'Unknown error'));
        }

        return $data['result'] ?? null;
    }

    /**
     * Fetch all projects from Leantime.
     *
     * @return array associative array of project id => name
     */
    public function getProjects(): array
    {
        $result = $this->request('leantime.rpc.projects.getAllProjects');

        $projects = [];
        foreach ($result ?? [] as $project) {
            $projects[$project['id']] = $project['name'];
        }

        return $projects;
    }

    /**
     * Create a Leantime issue from a feedback entry.
     *
     * @return int the created ticket ID
     *
     * @throws \RuntimeException if the website has no Leantime project configured
     */
    public function createIssue(Feedback $feedback): int
    {
        $website = $feedback->getWebsite();
        $projectId = $website->getLeantimeProjectId();

        if (!$projectId) {
            throw new \RuntimeException('No Leantime project configured for this website.');
        }

        $data = $feedback->getData();
        $description = $data['description'] ?? 'No description provided';
        $headline = mb_substr($description, 0, 100);

        $body = $this->buildIssueBody($feedback);

        $result = $this->request('leantime.rpc.tickets.addTicket', [
            'values' => [
                'headline' => $headline,
                'description' => $body,
                'projectId' => $projectId,
                'status' => '3',
            ],
        ]);

        return $result[0];
    }

    /**
     * Build the HTML body for a Leantime ticket description.
     *
     * Includes translated labels, feedback data, screenshot context,
     * timestamps, and a link back to the feedback detail page.
     */
    private function buildIssueBody(Feedback $feedback): string
    {
        $t = $this->translator;
        $data = $feedback->getData();
        $website = $feedback->getWebsite();

        $description = htmlspecialchars($data['description'] ?? 'No description', ENT_QUOTES);
        $email = htmlspecialchars($data['created_by'] ?? 'Unknown', ENT_QUOTES);
        $createdAt = $feedback->getCreatedAt()->format('d-m-Y H:i:s');

        $feedbackUrl = $this->urlGenerator->generate(
            'app_feedback_show',
            ['id' => $feedback->getId()],
            UrlGeneratorInterface::ABSOLUTE_URL,
        );

        $body = '<p><strong>'.$t->trans('Description').':</strong><br>'.$description.'</p>';
        $body .= '<p><strong>'.$t->trans('Submitted by').':</strong> '.$email.'</p>';
        $body .= '<p><strong>'.$t->trans('Website').':</strong> '.$website->getUrl().'</p>';
        $body .= '<p><strong>'.$t->trans('Created At').':</strong> '.$createdAt.'</p>';

        if ($feedback->getNote()) {
            $body .= '<p><strong>'.$t->trans('Note').':</strong><br>'.htmlspecialchars($feedback->getNote(), ENT_QUOTES).'</p>';
        }

        if (isset($data['context'])) {
            $context = $data['context'];
            if (!empty($context['url'])) {
                $sourceUrl = htmlspecialchars($context['url'], ENT_QUOTES);
                $body .= '<p><strong>'.$t->trans('Source page').':</strong> <a href="'.$sourceUrl.'">'.$sourceUrl.'</a></p>';
            }
            if (!empty($context['window'])) {
                $body .= '<p><strong>'.$t->trans('Window Size').':</strong> '.$context['window']['innerWidth'].' × '.$context['window']['innerHeight'].'</p>';
            }
        }

        $body .= '<hr>';
        $body .= '<p><strong>'.$t->trans('Feedback detail').':</strong> <a href="'.$feedbackUrl.'">'.$feedbackUrl.'</a></p>';

        return $body;
    }
}
