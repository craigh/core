<?php

declare(strict_types=1);

/*
 * This file is part of the Zikula package.
 *
 * Copyright Zikula Foundation - https://ziku.la/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zikula\BlocksModule\Twig\Extension;

use RuntimeException;
use Twig\Extension\AbstractExtension;
use Twig\Loader\FilesystemLoader;
use Twig\Loader\LoaderInterface;
use Twig\TwigFunction;
use Zikula\BlocksModule\Api\ApiInterface\BlockApiInterface;
use Zikula\BlocksModule\Api\ApiInterface\BlockFilterApiInterface;
use Zikula\BlocksModule\Entity\BlockEntity;
use Zikula\Bundle\CoreBundle\HttpKernel\ZikulaHttpKernelInterface;
use Zikula\ThemeModule\Engine\Engine;

class BlocksExtension extends AbstractExtension
{
    /**
     * @var BlockApiInterface
     */
    private $blockApi;

    /**
     * @var BlockFilterApiInterface
     */
    private $blockFilter;

    /**
     * @var Engine
     */
    private $themeEngine;

    /**
     * @var ZikulaHttpKernelInterface
     */
    private $kernel;

    /**
     * @var LoaderInterface
     */
    private $loader;

    public function __construct(
        BlockApiInterface $blockApi,
        BlockFilterApiInterface $blockFilterApi,
        Engine $themeEngine,
        ZikulaHttpKernelInterface $kernel,
        FilesystemLoader $loader
    ) {
        $this->blockApi = $blockApi;
        $this->blockFilter = $blockFilterApi;
        $this->themeEngine = $themeEngine;
        $this->kernel = $kernel;
        $this->loader = $loader;
    }

    public function getFunctions()
    {
        return [
            new TwigFunction('showblockposition', [$this, 'showBlockPosition'], ['is_safe' => ['html']]),
            new TwigFunction('showblock', [$this, 'showBlock'], ['is_safe' => ['html']]),
            new TwigFunction('positionavailable', [$this, 'isPositionAvailable'])
        ];
    }

    /**
     * Show all the blocks in a position by name.
     *
     * @return array|string
     */
    public function showBlockPosition(string $positionName, bool $implode = true)
    {
        $instance = $this->kernel->getModule('ZikulaBlocksModule');
        if (!isset($instance)) {
            return 'Blocks not currently available.';
        }
        $blocks = $this->blockApi->getBlocksByPosition($positionName);
        foreach ($blocks as $key => $block) {
            $blocks[$key] = $this->showBlock($block, $positionName);
        }

        return $implode ? implode("\n", $blocks) : $blocks;
    }

    /**
     * Display one block.
     */
    public function showBlock(BlockEntity $block, string $positionName = ''): string
    {
        $blocksModuleInstance = $this->kernel->getModule('ZikulaBlocksModule');
        if (!isset($blocksModuleInstance)) {
            return 'Blocks not currently available.';
        }
        // Check if providing module not available, if block is inactive, if block filter prevents display.
        $bundleName = $block->getModule()->getName();
        $moduleInstance = $this->kernel->getModule($bundleName);
        if (!isset($moduleInstance) || !$block->getActive() || !$this->blockFilter->isDisplayable($block)) {
            return '';
        }

        // add theme path to twig loader for theme overrides using namespace notation (e.g. @BundleName/foo)
        // this duplicates functionality from \Zikula\ThemeModule\EventListener\TemplatePathOverrideListener::setUpThemePathOverrides
        // but because blockHandlers don't call (and are not considered) a controller, that listener doesn't get called.
        $theme = $this->themeEngine->getTheme();
        if ($theme) {
            $overridePath = $theme->getPath() . '/Resources/' . $bundleName . '/views';
            if (is_readable($overridePath)) {
                $paths = $this->loader->getPaths($bundleName);
                // inject themeOverridePath before the original path in the array
                array_splice($paths, count($paths) - 1, 0, [$overridePath]);
                $this->loader->setPaths($paths, $bundleName);
            }
        }

        try {
            $blockInstance = $this->blockApi->createInstanceFromBKey($block->getBkey());
        } catch (RuntimeException $exception) {
            //return 'Error during block creation: ' . $exception->getMessage();
            return '';
        }
        $blockProperties = $block->getProperties();
        $blockProperties['bid'] = $block->getBid();
        $blockProperties['title'] = $block->getTitle();
        $blockProperties['position'] = $positionName;
        $content = $blockInstance->display($blockProperties);
        if (isset($moduleInstance)) {
            // add module stylesheet to page
            $moduleInstance->addStylesheet();
        }

        return $this->themeEngine->wrapBlockContentInTheme($content, $block->getTitle(), $block->getBlocktype(), $block->getBid(), $positionName);
    }

    public function isPositionAvailable(string $name): bool
    {
        return $this->themeEngine->positionIsAvailableInTheme($name);
    }
}
