<?php
/**
 * Copyright Zikula Foundation 2009 - Zikula Application Framework
 *
 * This work is contributed to the Zikula Foundation under one or more
 * Contributor Agreements and licensed to You under the following license:
 *
 * @license GNU/LGPLv3 (or at your option, any later version).
 * @package Zikula_Form
 *
 * Please see the NOTICE file distributed with this source code for further
 * information regarding copyright and licensing.
 */

use Doctrine\ORM\Mapping as ORM;

/**
 * Category registry doctrine2 entity.
 *
 * @ORM\Entity
 * @ORM\Table(name="categories_registry")
 */
class Zikula_Doctrine2_Entity_CategoryRegistry extends Zikula\Core\Doctrine\Entity\CategoryRegistry
{
}
