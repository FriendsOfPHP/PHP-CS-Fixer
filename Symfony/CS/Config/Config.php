<?php

/*
 * This file is part of the PHP CS utility.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Symfony\CS\Config;

use Symfony\CS\ConfigInterface;
use Symfony\CS\Finder\DefaultFinder;
use Symfony\CS\FinderInterface;
use Symfony\CS\FixerInterface;

/**
 * @author Fabien Potencier <fabien@symfony.com>
 * @author Katsuhiro Ogawa <ko.fivestar@gmail.com>
 */
class Config implements ConfigInterface
{
    protected $name;
    protected $description;
    protected $finder;
    protected $fixers = array();
    protected $dir;
    protected $customFixers = array();
    protected $usingCache = true;
    protected $usingLinter = true;
    protected $hideProgress = false;
    protected $cacheFile = '.php_cs.cache';
    protected $phpExecutable;
    protected $isRiskyAllowed = false;
    protected $rules = array('@PSR2' => true);

    public function __construct($name = 'default', $description = 'A default configuration')
    {
        $this->name = $name;
        $this->description = $description;
        $this->finder = new DefaultFinder();
    }

    public static function create()
    {
        return new static();
    }

    public function setDir($dir)
    {
        $this->dir = $dir;

        return $this;
    }

    public function setUsingCache($usingCache)
    {
        $this->usingCache = $usingCache;

        return $this;
    }

    public function setUsingLinter($usingLinter)
    {
        $this->usingLinter = $usingLinter;

        return $this;
    }

    public function getDir()
    {
        return $this->dir;
    }

    public function finder(\Traversable $finder)
    {
        $this->finder = $finder;

        return $this;
    }

    public function getFinder()
    {
        if ($this->finder instanceof FinderInterface && $this->dir !== null) {
            $this->finder->setDir($this->dir);
        }

        return $this->finder;
    }

    /**
     * Set fixers.
     *
     * @param FixerInterface[] $fixers
     *
     * @return $this
     */
    public function fixers(array $fixers)
    {
        $this->fixers = $fixers;

        return $this;
    }

    public function getFixers()
    {
        return $this->fixers;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getDescription()
    {
        return $this->description;
    }

    public function getHideProgress()
    {
        return $this->hideProgress;
    }

    public function addCustomFixer(FixerInterface $fixer)
    {
        $this->customFixers[] = $fixer;

        return $this;
    }

    public function getCustomFixers()
    {
        return $this->customFixers;
    }

    public function hideProgress($hideProgress)
    {
        $this->hideProgress = $hideProgress;

        return $this;
    }

    public function usingCache()
    {
        return $this->usingCache;
    }

    public function usingLinter()
    {
        return $this->usingLinter;
    }

    /**
     * {@inheritdoc}
     */
    public function setCacheFile($cacheFile)
    {
        $this->cacheFile = $cacheFile;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getCacheFile()
    {
        return $this->cacheFile;
    }

    /**
     * Set PHP executable.
     *
     * @param string|null $phpExecutable
     *
     * @return Config
     */
    public function setPhpExecutable($phpExecutable)
    {
        $this->phpExecutable = $phpExecutable;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getPhpExecutable()
    {
        return $this->phpExecutable;
    }

    /**
     * {@inheritdoc}
     */
    public function getRiskyAllowed()
    {
        return $this->isRiskyAllowed;
    }

    /**
     * {@inheritdoc}
     */
    public function setRiskyAllowed($isRiskyAllowed)
    {
        $this->isRiskyAllowed = $isRiskyAllowed;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function setRules(array $rules)
    {
        $this->rules = $rules;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function addRules(array $rules)
    {
        $this->rules = array_merge($this->rules, $rules);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function addRule($name, $options)
    {
        $this->rules[$name] = $options;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getRules()
    {
        return $this->rules;
    }
}
