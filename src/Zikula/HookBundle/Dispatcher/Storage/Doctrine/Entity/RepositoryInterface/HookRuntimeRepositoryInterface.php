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

namespace Zikula\Bundle\HookBundle\Dispatcher\Storage\Doctrine\Entity\RepositoryInterface;

use Doctrine\Common\Collections\Selectable;
use Doctrine\Persistence\ObjectRepository;

interface HookRuntimeRepositoryInterface extends ObjectRepository, Selectable
{
    public function truncate(): void;

    public function getOneOrNullByEventName(string $eventName);

    public function deleteAllByOwner(string $owner): void;
}
