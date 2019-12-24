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

namespace Zikula\AdminModule\Twig\Extension\SimpleFunction;

use Symfony\Component\HttpKernel\Controller\ControllerReference;
use Symfony\Component\HttpKernel\Fragment\FragmentHandler;

class AdminDeveloperNoticesFunction
{
    /**
     * @var FragmentHandler
     */
    private $handler;

    public function __construct(FragmentHandler $handler)
    {
        $this->handler = $handler;
    }

    /**
     * Inserts developer notices.
     * This has NO configuration options.
     *
     * Example: {{ adminDeveloperNotices() }}
     */
    public function display(): string
    {
        $ref = new ControllerReference('Zikula\AdminModule\Controller\AdminInterfaceController::developernoticesAction');

        return $this->handler->render($ref);
    }
}
