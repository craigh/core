<?php

/*
 * This file is part of the Zikula package.
 *
 * Copyright Zikula Foundation - https://ziku.la/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Zikula\RoutesModule;

use Zikula\RoutesModule\Base\AbstractRoutesModuleInstaller;

/**
 * Installer implementation class.
 */
class RoutesModuleInstaller extends AbstractRoutesModuleInstaller
{
    public function upgrade(string $oldVersion): bool
    {
        switch ($oldVersion) {
            case '1.0.0':
                // routes of system modules are not stored in database anymore
                $sql = '
                    DELETE FROM `zikula_routes_route`
                    WHERE `userRoute` = 0
                ';
                $this->entityManager->getConnection()->exec($sql);

                // update table to meet entity structure
                $this->schemaTool->update(['Zikula\RoutesModule\Entity\Historical\v110\RouteEntity']);
            case '1.0.1':
                // nothing
            case '1.1.0':
                // rename createdUserId field to createdBy_id
                $sql = '
                    ALTER TABLE `zikula_routes_route`
                    CHANGE `createdUserId` `createdBy_id` int(11) NOT NULL
                ';
                $this->entityManager->getConnection()->exec($sql);

                // rename updatedUserId field to updatedBy_id
                $sql = '
                    ALTER TABLE `zikula_routes_route`
                    CHANGE `updatedUserId` `updatedBy_id` int(11) NOT NULL
                ';
                $this->entityManager->getConnection()->exec($sql);
            case '1.1.1':
                // drop obsolete fields
                $fieldNames = ['routeType', 'replacedRouteName', 'sort_group'];
                foreach ($fieldNames as $fieldName) {
                    $sql = '
                        ALTER TABLE `zikula_routes_route`
                        DROP COLUMN `' . $fieldName . '`
                    ';
                    $this->entityManager->getConnection()->exec($sql);
                }
                // add new field
                $sql = '
                    ALTER TABLE `zikula_routes_route`
                    ADD `options` LONGTEXT NOT NULL
                    COMMENT \'(DC2Type:array)\' AFTER `requirements`
                ';
                $this->entityManager->getConnection()->exec($sql);
                $sql = '
                    UPDATE `zikula_routes_route`
                    SET `options` = \'a:0:{}\';
                ';
                $this->entityManager->getConnection()->exec($sql);
            case '1.1.2':
                // nothing
            case '1.2.0':
                // future updates
        }

        return true;
    }
}
