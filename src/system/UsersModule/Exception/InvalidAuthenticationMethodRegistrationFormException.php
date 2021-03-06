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

namespace Zikula\UsersModule\Exception;

use Exception;

class InvalidAuthenticationMethodRegistrationFormException extends Exception
{
    public function __construct()
    {
        parent::__construct();
        $this->message = 'Zikula Authentication Method registration forms are required to contain an email, uname and submit fields.';
    }
}
