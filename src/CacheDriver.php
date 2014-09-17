<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <bugadani@gmail.com>
 *
 * For licensing information see the LICENSE file.
 */

namespace Modules\Templating;

use Minty\TemplateCacheInterface;
use Miny\TemporaryFiles\TemporaryFileManager;

class CacheDriver implements TemplateCacheInterface
{
    /**
     * @var TemporaryFileManager
     */
    private $temporaryFileManager;

    public function __construct(TemporaryFileManager $temporaryFileManager)
    {
        $this->temporaryFileManager = $temporaryFileManager;
    }

    /**
     * Loads $file.
     *
     * @param $file
     */
    public function load($file)
    {
        $this->temporaryFileManager->enterModule('Templating');
        $this->temporaryFileManager->load($file . '.php');
        $this->temporaryFileManager->exitModule();
    }

    /**
     * Saves $compiled to $file.
     *
     * @param $file     string The file name in cache.
     * @param $compiled string The cache content.
     *
     * @return mixed
     */
    public function save($file, $compiled)
    {
        $this->temporaryFileManager->enterModule('Templating');

        $this->temporaryFileManager->save($file . '.php', $compiled);
        $this->temporaryFileManager->exitModule();
    }

    /**
     * Returns whether $file exists in cache.
     *
     * @param $file
     *
     * @return bool
     */
    public function exists($file)
    {
        $this->temporaryFileManager->enterModule('Templating');

        $exists = $this->temporaryFileManager->exists($file . '.php');
        $this->temporaryFileManager->exitModule();

        return $exists;
    }

    /**
     * Returns the time $file was last saved to.
     *
     * @param $file
     *
     * @return int
     */
    public function getCreatedTime($file)
    {
        $this->temporaryFileManager->enterModule('Templating');

        $fileInfo = $this->temporaryFileManager->getFileInfo($file . '.php');
        if (!$fileInfo->isFile()) {
            return 0;
        }

        $this->temporaryFileManager->exitModule();

        return $fileInfo->getMTime();
    }
}
