<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENCE file.
 */

namespace Modules\Templating\Extensions;

use Minty\Extension;
use Miny\Log\AbstractLog;
use Modules\Templating\Extensions\Miny\NodeTreeVisualizer;

class Visualizer extends Extension
{
    /**
     * @var AbstractLog
     */
    private $log;

    public function __construct(AbstractLog $log)
    {
        $this->log = $log;
    }

    public function getExtensionName()
    {
        return 'visualizer';
    }

    public function getNodeVisitors()
    {
        return [
            new NodeTreeVisualizer($this->log)
        ];
    }

}
