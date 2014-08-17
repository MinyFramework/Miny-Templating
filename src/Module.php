<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Modules\Templating;

use Minty\Environment;
use Minty\Extensions\Core;
use Minty\Extensions\Debug;
use Minty\Extensions\Optimizer;
use Miny\Application\BaseApplication;
use Miny\Factory\Container;

class Module extends \Miny\Modules\Module
{

    public function defaultConfiguration()
    {
        return [
            'options'                     => [
                'global_variables' => [],
                'cache_namespace'  => 'Application\\Templating\\Cached',
                'cache'            => 'templates/compiled',
                'autoescape'       => 1,
                'fallback_tag'     => 'print',
                'debug'            => $this->application->isDeveloperEnvironment()
            ],
            'enable_node_tree_visualizer' => false,
            'template_loader'             => 'Minty\\TemplateLoaders\\FileLoader',
            'template_loader_parameters'  => [
                '{@root}/templates',
                'tpl'
            ]
        ];
    }

    public function init(BaseApplication $app)
    {
        $container = $app->getContainer();
        $container->addAlias(
            'Minty\\Environment',
            [$this, 'setupEnvironment']
        );
        $container->addAlias(
            'Minty\\AbstractTemplateLoader',
            $this->getConfiguration('template_loader')
        );
        $container->setConstructorArguments(
            $this->getConfiguration('template_loader'),
            $this->getConfiguration('template_loader_parameters')
        );
    }

    /**
     * This method is responsible for initializing the Environment. Called by Container.
     *
     * @param Container $container
     *
     * @return Environment
     */
    public function setupEnvironment(Container $container)
    {
        $env = new Environment(
            $container->get('Minty\\AbstractTemplateLoader'),
            $this->getConfiguration('options')
        );

        /** @var $request \Miny\HTTP\Request */
        $request = $container->get('Miny\\HTTP\\Request');

        $env->addGlobalVariable('is_ajax', $request->isAjax());
        $env->addGlobalVariable('is_internal_request', $request->isSubRequest());

        $env->addExtension(new Core());
        $env->addExtension(new Optimizer());
        $env->addExtension($container->get(__NAMESPACE__ . '\\Extensions\\Miny'));

        if ($env->getOption('debug')) {
            $env->addExtension(new Debug());

            if ($this->getConfiguration('enable_node_tree_visualizer')) {
                $env->addExtension($container->get(__NAMESPACE__ . '\\Extensions\\Visualizer'));
            }
        }

        return $env;
    }

    public function eventHandlers()
    {
        $container         = $this->application->getContainer();
        $controllerHandler = $container->get(
            __NAMESPACE__ . '\\EventHandlers',
            [$this->getConfigurationTree()]
        );

        return $controllerHandler->getHandledEvents();
    }
}
