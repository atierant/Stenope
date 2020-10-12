<?php

/*
 * This file is part of the "Tom32i/Content" bundle.
 *
 * @author Thomas Jarrand <thomas.jarrand@gmail.com>
 */

namespace Content\EventListener;

use Content\Builder\RouteInfo;
use Content\Builder\Sitemap;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\RouterInterface;

/**
 * Map all routes into a Sitemap
 */
class SitemapListener implements EventSubscriberInterface
{
    private array $routes;
    private Sitemap $sitemap;

    public function __construct(RouterInterface $router, Sitemap $sitemap)
    {
        $this->routes = RouteInfo::createFromRouteCollection($router->getRouteCollection());
        $this->sitemap = $sitemap;
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        $request = $event->getRequest();
        $response = $event->getResponse();

        if (!$routeName = $request->attributes->get('_route')) {
            return;
        }

        $route = $this->routes[$routeName];

        if ($route && $route->isMapped() && $request->attributes->get('_canonical')) {
            $this->sitemap->add(
                $request->attributes->get('_canonical'),
                new \DateTime($response->headers->get('Last-Modified'))
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [KernelEvents::RESPONSE => 'onKernelResponse'];
    }
}