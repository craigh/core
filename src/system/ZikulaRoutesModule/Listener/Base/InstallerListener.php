<?php
/**
 * Routes.
 *
 * @copyright Zikula contributors (Zikula)
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 * @author Zikula contributors <support@zikula.org>.
 * @link http://www.zikula.org
 * @link http://zikula.org
 * @version Generated by ModuleStudio 0.6.2 (http://modulestudio.de).
 */

namespace Zikula\RoutesModule\Listener\Base;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Zikula\Core\CoreEvents;
use Zikula\Core\Event\GenericEvent;
use Zikula\Core\Event\ModuleStateEvent;

/**
 * Event handler base class for module installer events.
 */
class InstallerListener implements EventSubscriberInterface
{
    /**
     * Makes our handlers known to the event system.
     */
    public static function getSubscribedEvents()
    {
        return array(
            CoreEvents::MODULE_INSTALL             => array('moduleInstalled', 5),
            CoreEvents::MODULE_UPGRADE             => array('moduleUpgraded', 5),
            CoreEvents::MODULE_ENABLE              => array('moduleEnabled', 5),
            CoreEvents::MODULE_DISABLE             => array('moduleDisabled', 5),
            CoreEvents::MODULE_REMOVE              => array('moduleRemoved', 5),
            'installer.subscriberarea.uninstalled' => array('subscriberAreaUninstalled', 5)
        );
    }
    
    /**
     * Listener for the `module.install` event.
     *
     * Called after a module has been successfully installed.
     * Receives `$modinfo` as args.
     *
     * @param ModuleStateEvent $event The event instance.
     */
    public function moduleInstalled(ModuleStateEvent $event)
    {
    }
    
    /**
     * Listener for the `module.upgrade` event.
     *
     * Called after a module has been successfully upgraded.
     * Receives `$modinfo` as args.
     *
     * @param ModuleStateEvent $event The event instance.
     */
    public function moduleUpgraded(ModuleStateEvent $event)
    {
    }
    
    /**
     * Listener for the `module.enable` event.
     *
     * Called after a module has been successfully enabled.
     * Receives `$modinfo` as args.
     */
    public function moduleEnabled(ModuleStateEvent $event)
    {
    }
    
    /**
     * Listener for the `module.disable` event.
     *
     * Called after a module has been successfully disabled.
     * Receives `$modinfo` as args.
     */
    public function moduleDisabled(ModuleStateEvent $event)
    {
    }
    
    /**
     * Listener for the `module.remove` event.
     *
     * Called after a module has been successfully removed.
     * Receives `$modinfo` as args.
     *
     * @param GenericEvent $event The event instance.
     */
    public function moduleRemoved(ModuleStateEvent $event)
    {
    }
    
    /**
     * Listener for the `installer.subscriberarea.uninstalled` event.
     *
     * Called after a hook subscriber area has been unregistered.
     * Receives args['areaid'] as the areaId. Use this to remove orphan data associated with this area.
     *
     * @param GenericEvent $event The event instance.
     */
    public function subscriberAreaUninstalled(GenericEvent $event)
    {
    }
}
