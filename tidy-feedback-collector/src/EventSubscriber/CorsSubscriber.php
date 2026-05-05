<?php

namespace App\EventSubscriber;

use App\Service\OriginValidator;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Handles CORS headers for the /api/ endpoints.
 *
 * Only allows origins that match a registered website domain,
 * rather than using a wildcard. This prevents unknown sites from
 * making cross-origin requests with a stolen API key.
 */
class CorsSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly OriginValidator $originValidator,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            // High priority so the preflight is handled before routing
            KernelEvents::REQUEST => ['onKernelRequest', 255],
            KernelEvents::RESPONSE => 'onKernelResponse',
        ];
    }

    /**
     * Intercept CORS preflight (OPTIONS) requests for /api/ routes.
     *
     * Browsers send a preflight before the actual POST to check if
     * the origin is allowed. We respond immediately with the appropriate
     * CORS headers and skip further processing.
     */
    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();

        // Only handle CORS for API routes
        if (!str_starts_with($request->getPathInfo(), '/api/')) {
            return;
        }

        if ('OPTIONS' === $request->getMethod()) {
            $origin = $request->headers->get('Origin', '');
            $allowedOrigin = $this->originValidator->getAllowedOrigin($origin);

            // Return early with CORS headers — no controller is invoked
            $response = new Response('', Response::HTTP_NO_CONTENT);
            $response->headers->set('Access-Control-Allow-Origin', $allowedOrigin ?? 'null');
            $response->headers->set('Access-Control-Allow-Methods', 'POST, OPTIONS');
            $response->headers->set('Access-Control-Allow-Headers', 'Content-Type');
            $response->headers->set('Access-Control-Max-Age', '3600');
            $response->headers->set('Vary', 'Origin');
            $event->setResponse($response);
        }
    }

    /**
     * Add CORS headers to all /api/ responses.
     *
     * After the controller has handled the request, we attach the
     * Access-Control-Allow-Origin header so the browser permits the
     * response to be read by the calling script.
     */
    public function onKernelResponse(ResponseEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();

        if (!str_starts_with($request->getPathInfo(), '/api/')) {
            return;
        }

        $origin = $request->headers->get('Origin', '');
        $allowedOrigin = $this->originValidator->getAllowedOrigin($origin);

        $response = $event->getResponse();
        $response->headers->set('Access-Control-Allow-Origin', $allowedOrigin ?? 'null');
        // Vary by Origin so caches don't serve a response for the wrong origin
        $response->headers->set('Vary', 'Origin');
    }
}
