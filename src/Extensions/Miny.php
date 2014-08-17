<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENCE file.
 */

namespace Modules\Templating\Extensions;

use Minty\Compiler\TemplateFunction;
use Minty\Extension;
use Miny\Application\Dispatcher;
use Miny\Factory\Container;
use Miny\Router\RouteGenerator;

class Miny extends Extension
{
    /**
     * @var Container
     */
    private $container;

    /**
     * @var Dispatcher
     */
    private $dispatcher;

    /**
     * @var RouteGenerator
     */
    private $routeGenerator;

    /**
     * @param Container      $container
     * @param Dispatcher     $dispatcher
     * @param RouteGenerator $routeGenerator
     */
    public function __construct(
        Container $container,
        Dispatcher $dispatcher,
        RouteGenerator $routeGenerator
    ) {
        $this->container      = $container;
        $this->dispatcher     = $dispatcher;
        $this->routeGenerator = $routeGenerator;
    }

    public function getExtensionName()
    {
        return 'miny';
    }

    public function getFunctions()
    {
        $functions = [
            new TemplateFunction('route', [$this->routeGenerator, 'generate']),
            new TemplateFunction('request', [$this, 'requestFunction']),
        ];

        return $functions;
    }

    public function requestFunction($url, $method = 'GET', array $post = [])
    {
        $main = $this->container->get('\\Miny\\HTTP\\Response');
        $main->addContent(ob_get_clean());

        $response = $this->dispatcher->dispatch(
            $this->container->get('\\Miny\\HTTP\\Request')
                ->getSubRequest($method, $url, $post)
        );

        $main->addResponse($response);
        ob_start();
    }
}
