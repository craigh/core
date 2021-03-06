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

namespace Zikula\UsersModule\Entity\RepositoryInterface;

use DateTime;
use Doctrine\Common\Collections\Selectable;
use Doctrine\ORM\Internal\Hydration\IterableResult;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ObjectRepository;
use Zikula\UsersModule\Entity\UserEntity;

interface UserRepositoryInterface extends ObjectRepository, Selectable
{
    public function findByUids(array $userIds = []): array;

    public function persistAndFlush(UserEntity $user): void;

    public function removeAndFlush(UserEntity $user): void;

    /**
     * If $approvedBy is null, user will be considered as 'self-approved'.
     */
    public function setApproved(UserEntity $user, DateTime $approvedOn, int $approvedBy = null): void;

    /**
     * @return Paginator|UserEntity[]
     */
    public function queryBySearchForm(array $formData = []);

    /**
     * Find users for a search result
     *
     * @return UserEntity[]
     */
    public function getSearchResults(array $words = []);

    /**
     * Fetch a collection of users. Optionally filter, sort, limit, offset results.
     *   filter = [field => value, field => value, field => ['operator' => '!=', 'operand' => value], ...]
     *   when value is not an array, operator is assumed to be '='
     *
     * @return Paginator|UserEntity[]
     */
    public function query(
        array $filter = [],
        array $sort = [],
        int $limit = 0,
        int $offset = 0,
        string $exprType = 'and'
    );

    public function count(array $filter = [], string $exprType = 'and'): int;

    /**
     * Return all users as memory-saving iterable result.
     */
    public function findAllAsIterable(): IterableResult;

    /**
     * @return Paginator|UserEntity[]
     */
    public function searchActiveUser(array $unameFilter = [], int $limit = 50);
}
