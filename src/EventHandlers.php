<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Modules\Templating;

use Annotiny\Reader;
use Minty\Environment;
use Miny\Application\Events\FilterResponseEvent;
use Miny\Application\Events\UncaughtExceptionEvent;
use Miny\Controller\Controller;
use Miny\Controller\Events\ControllerFinishedEvent;
use Miny\Controller\Events\ControllerLoadedEvent;
use Miny\CoreEvents;
use Miny\Factory\AbstractConfigurationTree;

class EventHandlers
{
    private $environment;
    private $layoutMap = [];
    private $assignedVariables = [];
    private $currentLayout;
    private $annotation;
    private $configuration;

    public function __construct(
        AbstractConfigurationTree $configuration,
        Environment $environment,
        Reader $annotation = null
    ) {
        $this->configuration = $configuration;
        $this->annotation    = $annotation;
        $this->environment   = $environment;
    }

    public function getHandledEvents()
    {
        $events = [
            CoreEvents::CONTROLLER_LOADED   => $this,
            CoreEvents::CONTROLLER_FINISHED => $this
        ];

        if (isset($this->configuration['exceptions'])) {
            $events[CoreEvents::UNCAUGHT_EXCEPTION] = [$this, 'handleException'];
        }
        if (isset($this->configuration['codes'])) {
            $events[CoreEvents::FILTER_RESPONSE] = [$this, 'handleResponseCodes'];
        }

        return $events;
    }

    public function handleResponseCodes(FilterResponseEvent $event)
    {
        $response     = $event->getHttpResponse();
        $responseCode = $response->getCode();
        foreach ($this->configuration['codes'] as $key => $handler) {

            if (!is_array($handler)) {
                if (!$response->isCode($key)) {
                    continue;
                }
                $templateName = $handler;
            } else {
                if (isset($handler['codes'])) {
                    $codeMatches = in_array($responseCode, $handler['codes']);
                } elseif (isset($handler['code'])) {
                    $codeMatches = $response->isCode($handler['code']);
                } else {
                    throw new \UnexpectedValueException('Response code handler must contain key "code" or "codes".');
                }

                if (!$codeMatches) {
                    continue;
                }

                if (!isset($handler['template'])) {
                    throw new \UnexpectedValueException('Response code handler must specify a template.');
                }
                $templateName = $handler['template'];
            }

            $this->environment->render(
                $templateName,
                [
                    'request'  => $event->getRequest(),
                    'response' => $response
                ]
            );
        }
    }

    public function handleException(UncaughtExceptionEvent $event)
    {
        $exception = $event->getException();
        $handlers  = $this->configuration['exceptions'];
        if (!is_array($handlers)) {
            $this->environment->render($handlers, ['exception' => $exception]);
        } else {
            foreach ($handlers as $class => $handler) {
                if ($exception instanceof $class) {
                    $this->environment->render($handler, ['exception' => $exception]);

                    return;
                }
            }
        }
    }

    public function onControllerLoaded(ControllerLoadedEvent $event)
    {
        $controller = $event->getController();
        if ($controller instanceof iTemplatingController) {
            $this->layoutMap = $controller->initLayouts();
        }
        if ($controller instanceof Controller) {
            // Add templating related methods
            $controller->addMethods($this, ['assign', 'layout']);
        }
    }

    public function onControllerFinished(ControllerFinishedEvent $event)
    {
        $controller  = $event->getController();
        $returnValue = $event->getReturnValue();
        $action      = $event->getAction();

        if ($this->shouldNotRenderTemplate($controller, $returnValue)) {
            return;
        }
        if (!isset($this->currentLayout)) {
            if (!$this->determineCurrentLayout($controller, $action)) {
                return;
            }
        }
        $this->environment->render($this->currentLayout, $this->assignedVariables);
    }

    public function layout($template)
    {
        $this->currentLayout = $template;
    }

    public function assign($key, $value)
    {
        $this->assignedVariables[$key] = $value;
    }

    /**
     * @param $controller
     * @param $returnValue
     *
     * @return bool
     */
    protected function shouldNotRenderTemplate($controller, $returnValue)
    {
        if ($controller instanceof Controller) {
            if ($controller->getHeaders()->has('location')) {
                return true;
            }
        }

        return $returnValue === false;
    }

    private function determineCurrentLayout($controller, $action)
    {
        if (isset($this->layoutMap[$action])) {
            $this->currentLayout = $this->layoutMap[$action];

            return true;
        }
        if (isset($this->annotation)) {
            $comment = $this->getControllerActionAnnotations($controller, $action);
            if ($comment->has('template')) {
                $this->currentLayout = $comment->get('template');

                return true;
            }

            if ($controller instanceof Controller) {
                $comment = $this->annotation->readClass($controller);
                if ($comment->has('templateDir')) {
                    $templateDir         = $comment->get('templateDir');
                    $this->currentLayout = rtrim($templateDir, '/') . '/' . $action;

                    return true;
                }
            }
        }

        return false;
    }

    /**
     * @param $controller
     * @param $action
     *
     * @return \Annotiny\Comment
     */
    private function getControllerActionAnnotations($controller, $action)
    {
        if ($controller instanceof \Closure) {
            return $this->annotation->readFunction($controller);
        }

        return $this->annotation->readMethod($controller, $action . 'Action');
    }
}
