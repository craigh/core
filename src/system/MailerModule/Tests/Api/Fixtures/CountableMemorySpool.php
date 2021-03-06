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

namespace Zikula\MailerModule\Tests\Api\Fixtures;

use Countable;
use Swift_MemorySpool;
use Swift_Message;

final class CountableMemorySpool extends Swift_MemorySpool implements Countable
{
    public function count(): int
    {
        return count($this->messages);
    }

    /**
     * @return Swift_Message[]
     */
    public function getMessages(): iterable
    {
        return $this->messages;
    }
}
