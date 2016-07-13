<?php
/**
 * Routes.
 *
 * @copyright Zikula contributors (Zikula)
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 * @author Zikula contributors <support@zikula.org>.
 * @link http://www.zikula.org
 * @link http://zikula.org
 * @version Generated by ModuleStudio 0.7.0 (http://modulestudio.de).
 */

namespace Zikula\RoutesModule\Container;

use Zikula\Core\LinkContainer\LinkContainerInterface;
use Zikula\RoutesModule\Container\Base\LinkContainer as BaseLinkContainer;

/**
 * This is the link container service implementation class.
 */
class LinkContainer extends BaseLinkContainer
{
    /**
     * {@inheritdoc}
     */
    public function getLinks($type = LinkContainerInterface::TYPE_ADMIN)
    {
        $links = [];

        if (LinkContainerInterface::TYPE_ADMIN != $type) {
            return $links;
        }

        if (!$this->permissionApi->hasPermission($this->getBundleName() . ':Route:', '::', ACCESS_ADMIN)) {
            return $links;
        }

        $links[] = [
            'url' => $this->router->generate('zikularoutesmodule_route_adminview'),
            'text' => $this->__('Routes'),
            'title' => $this->__('Route list')
        ];
        $links[] = [
            'url' => $this->router->generate('zikularoutesmodule_route_adminreload'),
            'text' => $this->__('Reload routes'),
            'title' => $this->__('Reload routes')
        ];
        $links[] = [
            'url' => $this->router->generate('zikularoutesmodule_route_adminrenew'),
            'text' => $this->__('Reload multilingual routing settings'),
            'title' => $this->__('Reload multilingual routing settings')
        ];
        $links[] = [
            'url' => $this->router->generate('zikularoutesmodule_route_dumpjsroutes'),
            'text' => $this->__('Dump exposed js routes to file'),
            'title' => $this->__('Dump exposed js routes to file')
        ];

        return $links;
    }
}
