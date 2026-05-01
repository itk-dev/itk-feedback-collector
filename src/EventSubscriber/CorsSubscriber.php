<?php

namespace App\EventSubscriber;

use App\Service\OriginValidator;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class CorsSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly OriginValidator $originValidator,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 255],
            KernelEvents::RESPONSE => 'onKernelResponse',
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();

        if (!str_starts_with($request->getPathInfo(), '/api/')) {
            return;
        }

        if ('OPTIONS' === $request->getMethod()) {
            $origin = $request->headers->get('Origin', '');
            $allowedOrigin = $this->originValidator->getAllowedOrigin($origin);

            $response = new Response('', Response::HTTP_NO_CONTENT);
            $response->headers->set('Access-Control-Allow-Origin', $allowedOrigin ?? 'null');
            $response->headers->set('Access-Control-Allow-Methods', 'POST, OPTIONS');
            $response->headers->set('Access-Control-Allow-Headers', 'Content-Type');
            $response->headers->set('Access-Control-Max-Age', '3600');
            $response->headers->set('Vary', 'Origin');
            $event->setResponse($response);
        }
    }

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
        $response->headers->set('Vary', 'Origin');
    }
}
