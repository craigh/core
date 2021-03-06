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

namespace Zikula\BlocksModule\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Zikula\BlocksModule\BlockHandlerInterface;

class ZikulaBlocksExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container)
    {
        $loader = new YamlFileLoader($container, new FileLocator(dirname(__DIR__) . '/Resources/config'));
        $loader->load('services.yaml');
        if ('test' === $container->getParameter('kernel.environment')) {
            $loader->load('test_services.yaml');
        }

        $container->registerForAutoconfiguration(BlockHandlerInterface::class)
            ->addTag('zikula.block_handler')
            ->setPublic(true)
            ->setShared(false)
        ;
    }
}
