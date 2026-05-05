<?php

namespace App\Service;

use App\Entity\Feedback;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Client for the FreeScout API.
 *
 * Handles authentication and provides convenience methods for
 * common API operations (mailboxes, conversations).
 */
class FreescoutService
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly TranslatorInterface $translator,
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly string $freescoutApiUrl,
        private readonly string $freescoutApiKey,
    ) {
    }

    /**
     * Make a request to the FreeScout API.
     *
     * @param string $method   HTTP method (GET, POST, PUT, DELETE)
     * @param string $endpoint API endpoint path (e.g. "mailboxes", "conversations")
     * @param array  $data     request body data for POST/PUT requests
     *
     * @return array decoded JSON response
     *
     * @throws \RuntimeException if the API returns an error
     */
    public function request(string $method, string $endpoint, array $data = []): array
    {
        $url = rtrim($this->freescoutApiUrl, '/').'/api/'.$endpoint;

        $options = [
            'headers' => [
                'X-FreeScout-API-Key' => $this->freescoutApiKey,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ],
        ];

        if (!empty($data) && in_array($method, ['POST', 'PUT'], true)) {
            $options['json'] = $data;
        }

        $response = $this->httpClient->request($method, $url, $options);
        $statusCode = $response->getStatusCode();

        if ($statusCode >= 400) {
            throw new \RuntimeException(sprintf(
                'FreeScout API error (%d): %s',
                $statusCode,
                $response->getContent(false),
            ));
        }

        return $response->toArray();
    }

    /**
     * Fetch all mailboxes from FreeScout.
     *
     * @return array associative array of mailbox id => name
     */
    public function getMailboxes(): array
    {
        $response = $this->request('GET', 'mailboxes');

        $mailboxes = [];
        foreach ($response['_embedded']['mailboxes'] ?? [] as $mailbox) {
            $mailboxes[$mailbox['id']] = $mailbox['name'];
        }

        return $mailboxes;
    }

    /**
     * Create a FreeScout conversation from a feedback entry.
     *
     * Uses the website's configured mailbox ID and the feedback
     * data (email, description, context) to create a support ticket.
     *
     * @param Feedback $feedback the feedback entity to report
     *
     * @return array the API response
     *
     * @throws \RuntimeException if the website has no mailbox configured
     */
    public function createConversation(Feedback $feedback): array
    {
        $website = $feedback->getWebsite();
        $mailboxId = $website->getFreescoutMailboxId();

        if (!$mailboxId) {
            throw new \RuntimeException('No FreeScout mailbox configured for this website.');
        }

        $data = $feedback->getData();
        $email = $data['created_by'] ?? 'unknown@example.com';
        $description = $data['description'] ?? 'No description provided';
        $subject = mb_substr($description, 0, 100);

        $body = $this->buildConversationBody($feedback);

        return $this->request('POST', 'conversations', [
            'type' => 'email',
            'mailboxId' => $mailboxId,
            'subject' => $subject,
            'customer' => [
                'email' => $email,
            ],
            'threads' => [
                [
                    'type' => 'customer',
                    'text' => $body,
                    'customer' => [
                        'email' => $email,
                    ],
                ],
            ],
        ]);
    }

    /**
     * Build the HTML body for a FreeScout conversation thread.
     *
     * Includes translated labels, feedback data, screenshot, timestamps,
     * and a link back to the feedback detail page.
     *
     * @param Feedback $feedback the feedback entity
     *
     * @return string HTML-formatted conversation body
     */
    private function buildConversationBody(Feedback $feedback): string
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
