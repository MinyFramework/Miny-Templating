<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Modules\Templating;

use Closure;
use Modules\Annotation\Annotation;

class ControllerHandler
{
    private $template_loader;
    private $layout_map;
    private $assigned_variables;
    private $current_layout;
    private $annotation;

    public function setAnnotation(Annotation $annotation)
    {
        $this->annotation = $annotation;
    }

    public function setTemplateLoader(TemplateLoader $loader)
    {
        $this->template_loader = $loader;
    }

    public function onControllerLoaded($controller, $action)
    {
        if ($controller instanceof iTemplatingController) {
            $this->layout_map = $controller->initLayouts();
        } else {
            $this->layout_map = array();
        }
        $this->assigned_variables = array();
        // Add templating related methods
        $controller->addMethods($this, array('assign', 'layout'));
    }

    public function onControllerFinished($controller, $action, $controller_retval)
    {
        if ($controller->getHeaders()->has('location') || $controller_retval === false) {
            return;
        }
        if (!isset($this->current_layout)) {
            if (isset($this->layout_map[$action])) {
                $this->current_layout = $this->layout_map[$action];
            } elseif (isset($this->annotation)) {
                if ($controller instanceof Closure) {
                    $comment = $this->annotation->readFunction($controller);
                } else {
                    $comment = $this->annotation->readMethod($controller, $action . 'Action');
                }
                if ($comment->has('template')) {
                    $this->current_layout = $comment->get('template');
                }
            } else {
                return;
            }
        }
        $layout = $this->template_loader->load($this->current_layout);
        $layout->set($this->assigned_variables);
        $layout->render();
    }

    public function layout($template)
    {
        $this->current_layout = $template;
    }

    public function assign($key, $value)
    {
        $this->assigned_variables[$key] = $value;
    }
}
