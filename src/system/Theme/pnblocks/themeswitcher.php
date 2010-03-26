<?php
/**
 * Zikula Application Framework
 *
 * @copyright (c) 2004, Zikula Development Team
 * @link http://www.zikula.org
 * @version $Id$
 * @license GNU/GPL - http://www.gnu.org/copyleft/gpl.html
 * @package Zikula_System_Modules
 * @subpackage Theme
 */

// Based on
// Originally a script for MamboOS http://www.mamboserver.com
// ThemeChanger    - Version: 1.1
// Author : Arthur Konze - webmaster@mamboportal.com
// ThemeChanger for Zikula 0.760 - Version 0.8
// Adapted to Zikula by N!cklas - nicklas@johansson.tk
// Last changes by Lindbergh , http://lindbergh.ohost.de

/**
 * initialise block
 *
 */
function theme_themeswitcherblock_init()
{
    SecurityUtil::registerPermissionSchema('ThemeSwitcherblock::', 'Block title::');
}

/**
 * get information on block
 *
 * @return       array       The block information
 */
function theme_themeswitcherblock_info()
{
    $requirement_message = '';
    $switchThemeEnable = pnConfigGetVar('theme_change');
    if (!$switchThemeEnable) {
        $requirement_message .= __("Notice: This theme switcher block will not be display until you allow users to change themes, you can enable/disable this into the settings of the 'Theme' module.");
    }

    return array('module'           => 'Theme',
                 'text_type'        => __('Theme switcher'),
                 'text_type_long'   => __('Theme switcher'),
                 'allow_multiple'   => true,
                 'form_content'     => false,
                 'form_refresh'     => false,
                 'show_preview'     => true,
                 'admin_tableless'  => true,
                 'requirement'      => $requirement_message);
}

function theme_themeswitcherblock_display($blockinfo)
{
    // check if the module is available
    if (!pnModAvailable('Theme')) {
        return;
    }

    // check if theme switching is allowed
    if (!pnConfigGetVar('theme_change')) {
        return;
    }

    // security check
    if (!SecurityUtil::checkPermission( "ThemeSwitcherblock::", "$blockinfo[title]::", ACCESS_READ)) {
        return;
    }

    // Get variables from content block
    $vars = pnBlockVarsFromContent($blockinfo['content']);

    // Defaults
    if (empty($vars['format'])) {
        $vars['format'] = 1;
    }

    // get some use information about our environment
    $currenttheme = pnUserGetTheme();

    // get all themes in our environment
    $themes = ThemeUtil::getAllThemes();

    // get some use information about our environment
    $currenttheme = ThemeUtil::getInfo(ThemeUtil::getIDFromName(pnUserGetTheme()));

    // get all themes in our environment
    $themes = ThemeUtil::getAllThemes(PNTHEME_FILTER_USER);

    $previewthemes = array();
    $currentthemepic = null;
    foreach ($themes as $themeinfo) {
        $themename = $themeinfo['name'];
        if (file_exists($themepic = 'themes/'.DataUtil::formatForOS($themeinfo['directory']).'/images/preview_small.png')) {
            $themeinfo['previewImage'] = $themepic;
        }
        else {
            $themeinfo['previewImage'] = 'system/Theme/pnimages/preview_small.png';
        }
        $previewthemes[$themename] = $themeinfo;
        if ($themename == $currenttheme['name']) {
            $currentthemepic = $themeinfo['previewImage'];
        }
    }


    $pnRender = Renderer::getInstance('Theme');
    $pnRender->assign($vars);
    $pnRender->assign('currentthemepic', $currentthemepic);
    $pnRender->assign('currenttheme', $currenttheme);
    $pnRender->assign('themes', $previewthemes);
    $blockinfo['content'] = $pnRender->fetch('theme_block_themeswitcher.htm');

    return pnBlockThemeBlock($blockinfo);
}

function theme_themeswitcherblock_modify($blockinfo)
{
    // Get current content
    $vars = pnBlockVarsFromContent($blockinfo['content']);

    // Defaults
    // format: 1 = drop down with preview, 2 = simple list
    if (empty($vars['format'])) {
        $vars['format'] = 1;
    }

    // Create output object
    $pnRender = Renderer::getInstance('Theme', false);

    // assign the approriate values
    $pnRender->assign($vars);

    // Return the output that has been generated by this function
    return $pnRender->fetch('theme_block_themeswitcher_modify.htm');
}

function theme_themeswitcherblock_update($blockinfo)
{
    // Get current content
    $vars = pnBlockVarsFromContent($blockinfo['content']);

    // alter the corresponding variable
    $vars['format'] = FormUtil::getPassedValue('format', 1, 'POST');

    // write back the new contents
    $blockinfo['content'] = pnBlockVarsToContent($vars);

    // clear the block cache
    $pnRender = Renderer::getInstance('Theme');
    $pnRender->clear_cache('theme_block_themeswitcher.htm');

    return $blockinfo;
}
