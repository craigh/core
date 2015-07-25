<?php
/**
 * Copyright Zikula Foundation 2009 - Zikula Application Framework
 *
 * This work is contributed to the Zikula Foundation under one or more
 * Contributor Agreements and licensed to You under the following license:
 *
 * @license GNU/LGPLv3 (or at your option, any later version).
 *
 * Please see the NOTICE file distributed with this source code for further
 * information regarding copyright and licensing.
 */
namespace Zikula\Bundle\CoreBundle\EventListener;

use Symfony\Bundle\FrameworkBundle\Templating\EngineInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Zikula\Core\Response\AdminResponse;
use Zikula\Core\Response\PlainResponse;
use Zikula_View_Theme;
use Zikula\Core\Event\GenericEvent;

class ThemeListener implements EventSubscriberInterface
{
    private $templatingService;
    private $themeName = '';

    function __construct(EngineInterface $templatingService)
    {
        $this->templatingService = $templatingService;
    }

    public function onKernelResponse(FilterResponseEvent $event)
    {
        if (!$event->isMasterRequest()) {
            return;
        }
        if (\System::isInstalling()) {
            return;
        }

        $response = $event->getResponse();
        $request = $event->getRequest();
        if ($response instanceof PlainResponse
            || $response instanceof JsonResponse
            || $request->isXmlHttpRequest()
            || $response instanceof RedirectResponse) {
            return;
        }

        // if theme has already been processed the new way, stop here
        if (!isset($response->legacy) && !$request->attributes->get('_legacy', false)) {
            return;
        }

        $smartyCaching = null;

        /**
         * If Response is an AdminResponse, then change theme to the requested Admin theme (if set)
         */
        if ($response instanceof AdminResponse) {
            $adminTheme = \ModUtil::getVar('ZikulaAdminModule', 'admintheme');
            if (!empty($adminTheme)) {
                $themeInfo = \ThemeUtil::getInfo(\ThemeUtil::getIDFromName($adminTheme));
                if ($themeInfo && $themeInfo['state'] == \ThemeUtil::STATE_ACTIVE && is_dir('themes/' . \DataUtil::formatForOS($themeInfo['directory']))) {
                    $localEvent = new GenericEvent(null, array('type' => 'admin-theme'), $themeInfo['name']);
                    $this->themeName = \EventUtil::dispatch('user.gettheme', $localEvent)->getData();
                    $smartyCaching = false;
                    $_GET['type'] = 'admin'; // required for smarty and FormUtil::getPassedValue() to use the right pagetype from pageconfigurations.ini
                }
            }
        }

        if ($this->themeIsTwigBased($this->themeName)) {
            return $event->setResponse($this->wrapResponseInTheme($response));
        } else {
            // return smarty-based theme
            $smartyResponse = Zikula_View_Theme::getInstance($this->themeName, $smartyCaching)->themefooter($response);
            return $event->setResponse($smartyResponse);
        }
    }

    public static function getSubscribedEvents()
    {
        return array(
            KernelEvents::RESPONSE => array(array('onKernelResponse')),
        );
    }

    private function wrapResponseInTheme(Response $response)
    {
        // determine proper template? and location
        return $this->templatingService->renderResponse($this->themeName . '::master.html.twig', array('maincontent' => $response->getContent()));
    }

    /**
     * Is theme twig based (e.g. Core-2.0 theme)
     *
     * @param $themeName
     * @return bool
     */
    private function themeIsTwigBased($themeName = '')
    {
        $this->themeName = empty($themeName) ? \UserUtil::getTheme() : $themeName;
        $themeBundle = \ThemeUtil::getTheme($this->themeName);
        if (null !== $themeBundle) {
            $versionClass = $themeBundle->getVersionClass();
            if (!class_exists($versionClass)) {

                return true;
            }
        }

        return false;
    }
}
